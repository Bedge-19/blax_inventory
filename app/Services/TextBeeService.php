<?php

namespace App\Services;

use Config\TextBee as TextBeeConfig;
use App\Models\UserModel;
use App\Models\ShopModel;
use App\Models\ShippingAddressModel;
use App\Models\DeliveryModel;

class TextBeeService
{
    protected TextBeeConfig $config;

    public function __construct(?TextBeeConfig $config = null)
    {
        $this->config = $config ?? config('TextBee');
    }

    /**
     * Normalize a phone number to standard E.164 format (+639xxxxxxxxx).
     * Supports Philippine formats (09..., 9..., 639..., +639...).
     */
    public function normalizePhoneNumber(?string $rawPhone): ?string
    {
        if ($rawPhone === null || trim($rawPhone) === '') {
            return null;
        }

        // Remove spaces, hyphens, parentheses, and periods
        $clean = preg_replace('/[\s\-\(\)\.]/', '', trim($rawPhone));

        if ($clean === '' || $clean === 'N/A' || $clean === 'null') {
            return null;
        }

        // Philippine numbers:
        // Case 1: +639XXXXXXXXX (13 chars)
        if (preg_match('/^\+639\d{9}$/', $clean)) {
            return $clean;
        }

        // Case 2: 639XXXXXXXXX (12 chars) -> +639XXXXXXXXX
        if (preg_match('/^639\d{9}$/', $clean)) {
            return '+' . $clean;
        }

        // Case 3: 09XXXXXXXXX (11 chars) -> +639XXXXXXXXX
        if (preg_match('/^09\d{9}$/', $clean)) {
            return '+63' . substr($clean, 1);
        }

        // Case 4: 9XXXXXXXXX (10 chars) -> +639XXXXXXXXX
        if (preg_match('/^9\d{9}$/', $clean)) {
            return '+63' . $clean;
        }

        // International E.164 fallback (+ followed by 10 to 15 digits)
        if (preg_match('/^\+\d{10,15}$/', $clean)) {
            return $clean;
        }

        return null;
    }

    /**
     * Dispatch an SMS message via TextBee Gateway REST API.
     *
     * @param string $recipient Raw or normalized recipient phone number.
     * @param string $message   Text message body.
     * @return array ['success' => bool, 'data' => mixed, 'error' => ?string]
     */
    public function sendSms(string $recipient, string $message): array
    {
        $normalizedPhone = $this->normalizePhoneNumber($recipient);
        if (!$normalizedPhone) {
            log_message('warning', '[TextBeeService] Cannot send SMS: Invalid phone number: ' . $recipient);
            return ['success' => false, 'error' => 'Invalid phone number format'];
        }

        if (empty($this->config->apiKey)) {
            log_message('warning', '[TextBeeService] Cannot send SMS: TextBee API key is not configured');
            return ['success' => false, 'error' => 'TextBee API key is not configured'];
        }

        $endpoint = rtrim($this->config->baseUrl, '/') . '/gateway/send-sms';
        $payload = [
            'recipients' => [$normalizedPhone],
            'message'    => $message,
        ];

        if (!empty($this->config->deviceId)) {
            $payload['deviceId'] = $this->config->deviceId;
        }

        try {
            $client = \Config\Services::curlrequest([
                'timeout'     => 8,
                'http_errors' => false,
            ]);

            $response = $client->post($endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key'    => $this->config->apiKey,
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            $decoded = json_decode($body, true);

            if ($statusCode >= 200 && $statusCode < 300) {
                log_message('info', "[TextBeeService] SMS sent successfully to {$normalizedPhone}. Status: {$statusCode}");
                return [
                    'success' => true,
                    'status'  => $statusCode,
                    'data'    => $decoded ?? $body,
                ];
            }

            $errMsg = $decoded['message'] ?? $decoded['error'] ?? "HTTP {$statusCode}: {$body}";
            log_message('error', "[TextBeeService] Failed to send SMS to {$normalizedPhone}. {$errMsg}");
            return [
                'success' => false,
                'status'  => $statusCode,
                'error'   => $errMsg,
            ];
        } catch (\Throwable $e) {
            log_message('error', "[TextBeeService] Exception sending SMS to {$normalizedPhone}: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Dispatch notification SMS for a product order.
     * Fires on 'shipped' (Doorstep Delivery) and 'ready_for_pickup' (Store Pick-up).
     */
    public function sendOrderNotification(array $order, string $status, ?array $shop = null, ?array $delivery = null): array
    {
        if (!in_array($status, ['shipped', 'ready_for_pickup'], true)) {
            return ['success' => false, 'error' => 'Status does not trigger SMS notification'];
        }

        // Resolve phone number
        $phone = $order['customer_phone'] ?? null;
        if (!$phone && !empty($order['shipping_address_id'])) {
            $addr = (new ShippingAddressModel())->find($order['shipping_address_id']);
            $phone = $addr['phone'] ?? null;
        }
        if (!$phone && !empty($order['customer_id'])) {
            $customer = (new UserModel())->find($order['customer_id']);
            $phone = $customer['phone'] ?? null;
        }

        if (!$phone) {
            log_message('notice', "[TextBeeService] Skipping order SMS: No customer phone for order #{$order['id']}");
            return ['success' => false, 'error' => 'Customer phone number not available'];
        }

        // Resolve customer and shop details
        $customerName = 'Customer';
        if (!empty($order['first_name'])) {
            $customerName = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
        } elseif (!empty($order['customer_id'])) {
            $user = (new UserModel())->find($order['customer_id']);
            if ($user) {
                $customerName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            }
        }

        $shopName = $shop['shop_name'] ?? 'Blax Marketplace';
        if (!$shop && !empty($order['shop_id'])) {
            $s = (new ShopModel())->find($order['shop_id']);
            if ($s) {
                $shopName = $s['shop_name'];
            }
        }

        $orderNum = $order['order_number'] ?? ('ORD-' . ($order['id'] ?? ''));

        // Build message text
        if ($status === 'shipped') {
            $courier = $delivery['courier_name'] ?? 'Courier';
            $tracking = $delivery['tracking_id'] ?? '';
            $trackingPart = $tracking !== '' ? " (Tracking: {$tracking})" : '';
            $message = "Hello {$customerName}, your order #{$orderNum} from {$shopName} has been SHIPPED via {$courier}{$trackingPart}. Thank you for shopping at Blax Marketplace!";
        } else {
            // ready_for_pickup
            $message = "Hello {$customerName}, your order #{$orderNum} from {$shopName} is now READY FOR PICK-UP! Please bring your Pick-up QR code or order number to claim your items. Thank you!";
        }

        return $this->sendSms($phone, $message);
    }

    /**
     * Dispatch notification SMS for a printing request.
     * Fires on 'ready_for_pickup' (Store Pick-up) and 'ready_for_delivery' / 'completed' (Doorstep Delivery).
     */
    public function sendPrintingNotification(array $request, string $status, ?array $shop = null, ?array $delivery = null): array
    {
        $isDelivery = ($request['fulfillment_method'] ?? 'pickup') === 'delivery';

        // Check if this status transition should notify via SMS
        $shouldNotify = false;
        if (!$isDelivery && $status === 'ready_for_pickup') {
            $shouldNotify = true;
        } elseif ($isDelivery && in_array($status, ['ready_for_delivery', 'completed'], true)) {
            $shouldNotify = true;
        }

        if (!$shouldNotify) {
            return ['success' => false, 'error' => 'Printing status does not trigger SMS notification'];
        }

        // Resolve phone number from customer user record
        $phone = null;
        $customerName = 'Customer';
        if (!empty($request['customer_id'])) {
            $user = (new UserModel())->find($request['customer_id']);
            if ($user) {
                $phone = $user['phone'] ?? null;
                $customerName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            }
        }

        if (!$phone) {
            log_message('notice', "[TextBeeService] Skipping printing SMS: No customer phone for request #{$request['id']}");
            return ['success' => false, 'error' => 'Customer phone number not available'];
        }

        $shopName = $shop['shop_name'] ?? 'Blax Printing Services';
        if (!$shop && !empty($request['shop_id'])) {
            $s = (new ShopModel())->find($request['shop_id']);
            if ($s) {
                $shopName = $s['shop_name'];
            }
        }

        $reqNum = $request['request_number'] ?? ('PR-' . ($request['id'] ?? ''));

        if ($isDelivery) {
            $message = "Hello {$customerName}, your printing request #{$reqNum} from {$shopName} has been printed and SHIPPED for delivery! Thank you for choosing Blax Printing Services.";
        } else {
            $message = "Hello {$customerName}, your printing request #{$reqNum} from {$shopName} is now READY FOR PICK-UP! Please bring your Pick-up QR code to the shop to claim your prints.";
        }

        return $this->sendSms($phone, $message);
    }
}
