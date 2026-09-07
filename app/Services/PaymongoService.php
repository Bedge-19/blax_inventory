<?php

namespace App\Services;

class PaymongoService
{
    private string $secretKey;
    private string $publicKey;
    private string $apiBase = 'https://api.paymongo.com/v1';

    public function __construct(?string $secretKey = null, ?string $publicKey = null)
    {
        $this->secretKey = $secretKey
            ?? (string) (getenv('PAYMONGO_SECRET_KEY') ?: ($_ENV['PAYMONGO_SECRET_KEY'] ?? 'sk_live_F8TiQ3yT1NTgnKuFWZ2VsMBf'));

        $this->publicKey = $publicKey
            ?? (string) (getenv('PAYMONGO_PUBLIC_KEY') ?: ($_ENV['PAYMONGO_PUBLIC_KEY'] ?? 'pk_live_FS3baWVJknkiWb5uMubQicWD'));
    }

    /**
     * Create a GCash Checkout Session for an amount (in PHP, e.g. 150.00).
     *
     * @param float  $amountInPhp  Amount in PHP
     * @param string $itemName     Item/service name
     * @param string $successUrl   Redirect URL upon success
     * @param string $cancelUrl    Redirect URL upon cancel
     * @param array  $metadata     Custom metadata tracking
     * @return array{success: bool, checkout_url?: string, session_id?: string, error?: string}
     */
    public function createGcashCheckoutSession(
        float $amountInPhp,
        string $itemName,
        string $successUrl,
        string $cancelUrl,
        array $metadata = []
    ): array {
        // Amount must be in centavos (PHP * 100), minimum 2000 centavos (₱20.00) for PayMongo
        $amountInCentavos = max(2000, (int) round($amountInPhp * 100));

        $payload = [
            'data' => [
                'attributes' => [
                    'send_email_receipt'   => false,
                    'show_description'     => true,
                    'show_line_items'      => true,
                    'payment_method_types' => ['qrph'],
                    'line_items'           => [
                        [
                            'currency' => 'PHP',
                            'amount'   => $amountInCentavos,
                            'name'     => $itemName,
                            'quantity' => 1,
                        ]
                    ],
                    'success_url' => $successUrl,
                    'cancel_url'  => $cancelUrl,
                    'metadata'    => !empty($metadata) ? $metadata : null,
                ]
            ]
        ];

        $res = $this->request('POST', '/checkout_sessions', $payload);

        if (!empty($res['data']['attributes']['checkout_url'])) {
            return [
                'success'      => true,
                'checkout_url' => $res['data']['attributes']['checkout_url'],
                'session_id'   => $res['data']['id'] ?? '',
                'data'         => $res['data'],
            ];
        }

        $errorMsg = 'Failed to generate PayMongo checkout session.';
        if (!empty($res['errors'][0]['detail'])) {
            $errorMsg = $res['errors'][0]['detail'];
        }

        return [
            'success' => false,
            'error'   => $errorMsg,
        ];
    }

    /**
     * Retrieve a Checkout Session by ID and check payment state.
     *
     * @param string $sessionId
     * @return array{success: bool, paid: bool, session?: array, error?: string}
     */
    public function getCheckoutSession(string $sessionId): array
    {
        $res = $this->request('GET', '/checkout_sessions/' . urlencode($sessionId));

        if (empty($res['data'])) {
            return [
                'success' => false,
                'paid'    => false,
                'error'   => $res['errors'][0]['detail'] ?? 'Checkout session not found.',
            ];
        }

        $attributes = $res['data']['attributes'] ?? [];
        $payments   = $attributes['payments'] ?? [];
        $paymentIntent = $attributes['payment_intent'] ?? [];
        $piStatus = $paymentIntent['attributes']['status'] ?? '';

        // Strictly verify that either status is paid, payment_intent succeeded, or at least one payment record is paid/succeeded
        $hasPaidPayment = false;
        if (!empty($payments) && is_array($payments)) {
            foreach ($payments as $p) {
                $pStatus = $p['attributes']['status'] ?? '';
                if ($pStatus === 'paid' || $pStatus === 'succeeded') {
                    $hasPaidPayment = true;
                    break;
                }
            }
        }

        $sessionStatus = $attributes['status'] ?? '';
        $isPaid = $hasPaidPayment || $piStatus === 'succeeded' || $sessionStatus === 'paid';

        return [
            'success'  => true,
            'paid'     => $isPaid,
            'session'  => $res['data'],
            'payments' => $payments,
        ];
    }

    /**
     * Execute an HTTP request with Basic Auth to PayMongo API.
     */
    private function request(string $method, string $endpoint, ?array $body = null): array
    {
        $url = $this->apiBase . $endpoint;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->secretKey . ':'),
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        } elseif (strtoupper($method) !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        }

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', 'PayMongo cURL Error: ' . $err);
            return ['errors' => [['detail' => 'Network error connecting to PayMongo.']]];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
