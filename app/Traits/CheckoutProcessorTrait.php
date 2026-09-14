<?php

namespace App\Traits;

use App\Models\CartItemModel;
use App\Models\CartModel;
use App\Models\NotificationModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\ShippingAddressModel;
use App\Models\ShopModel;
use App\Models\ShopNotificationPreferenceModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

trait CheckoutProcessorTrait
{
    /**
     * Process checkout items grouped by shop.
     * Supports both Cart checkout and Direct Buy Now checkout without logic duplication.
     *
     * @param array    $itemsByShop          Grouped items: [shop_id => [ ['product_id'=>..., 'variant_id'=>..., 'variant_label'=>..., 'product_name'=>..., 'unit_price'=>..., 'quantity'=>...], ... ]]
     * @param int      $userId               Customer ID
     * @param string   $paymentMethod        'gcash', 'cod', or 'pickup'
     * @param string   $fulfillmentMethod    'delivery' or 'pickup'
     * @param int|null $addressId            Shipping address ID
     * @param array    $selectedCartItemIds  IDs of cart items to delete (empty for Direct Buy)
     * @param string   $cancelRedirectUrl    Redirect URL if GCash checkout is cancelled
     * @param string   $failureRedirectUrl   Redirect URL if validation/PayMongo fails
     */
    protected function processItemsByShop(
        array $itemsByShop,
        int $userId,
        string $paymentMethod,
        string $fulfillmentMethod,
        ?int $addressId,
        array $selectedCartItemIds = [],
        string $cancelRedirectUrl = '/cart',
        string $failureRedirectUrl = '/cart'
    ): ResponseInterface {
        // Polomolok boundary check for deliveries
        if ($fulfillmentMethod === 'delivery' && $addressId) {
            $addr = (new ShippingAddressModel())->find((int) $addressId);
            if ($addr) {
                $cityOk = stripos($addr['city'] ?? '', 'Polomolok') !== false;
                $provOk = stripos($addr['province'] ?? '', 'South Cotabato') !== false;
                if (!$cityOk && !$provOk) {
                    return redirect()->to($failureRedirectUrl)->with('error', 'Delivery is available only within Polomolok, South Cotabato.');
                }
            }
        }

        // Branch 1: PayMongo GCash Session Creation
        if ($paymentMethod === 'gcash') {
            $grandTotal = 0;
            $shopOrders = [];

            foreach ($itemsByShop as $shopId => $items) {
                $subtotal = 0;
                foreach ($items as $item) {
                    $subtotal += $item['unit_price'] * $item['quantity'];
                }
                $shippingFee = $fulfillmentMethod === 'delivery' ? 50.00 : 0.00;
                $totalAmount = $subtotal + $shippingFee;
                $grandTotal += $totalAmount;

                $shopOrders[] = [
                    'shop_id'      => $shopId,
                    'subtotal'     => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'total_amount' => $totalAmount,
                    'items'        => array_map(function ($it) {
                        return [
                            'product_id'    => $it['product_id'],
                            'variant_id'    => $it['variant_id'] ?? null,
                            'variant_label' => $it['variant_label'] ?? null,
                            'product_name'  => $it['product_name'],
                            'quantity'      => $it['quantity'],
                            'unit_price'    => $it['unit_price'],
                            'line_total'    => $it['unit_price'] * $it['quantity'],
                        ];
                    }, $items),
                ];
            }

            $token      = bin2hex(random_bytes(16));
            $paymongo   = service('paymongoService');
            $successUrl = base_url('cart/payment/callback?token=' . $token);
            $cancelUrl  = base_url('cart/payment/callback?cancel=1&token=' . $token);

            $sessionRes = $paymongo->createGcashCheckoutSession(
                $grandTotal,
                'Order Payment (Blax MarketPlace)',
                $successUrl,
                $cancelUrl,
                [
                    'type'     => 'product_order',
                    'user_id'  => (string) $userId,
                    'token'    => $token,
                ]
            );

            if (!empty($sessionRes['success']) && !empty($sessionRes['checkout_url'])) {
                $pendingData = [
                    'user_id'             => $userId,
                    'fulfillment_method'  => $fulfillmentMethod,
                    'payment_method'      => 'gcash',
                    'shipping_address_id' => $addressId,
                    'shop_orders'         => $shopOrders,
                    'selected_ids'        => $selectedCartItemIds,
                    'is_direct_buy'       => empty($selectedCartItemIds),
                    'cancel_redirect'     => $cancelRedirectUrl,
                    'session_id'          => $sessionRes['session_id'],
                    'grand_total'         => $grandTotal,
                    'token'               => $token,
                ];

                session()->set('pending_cart_checkout_' . $userId, $pendingData);
                session()->set('pending_cart_token_' . $token, $pendingData);
                cache()->save('pending_cart_' . $sessionRes['session_id'], $pendingData, 86400);
                cache()->save('pending_cart_token_' . $token, $pendingData, 86400);

                return redirect()->to($sessionRes['checkout_url']);
            }

            return redirect()->to($failureRedirectUrl)->with('error', $sessionRes['error'] ?? 'Unable to connect to PayMongo checkout. Please try again.');
        }

        // Branch 2: Direct COD / Store Pick-up Order Creation with row-locking stock safety
        $db = \Config\Database::connect();
        $db->transStart();

        // Final stock check and row-locking before creating orders
        foreach ($itemsByShop as $shopId => $items) {
            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity  = (int) $item['quantity'];

                $prod = $db->table('products')
                    ->where('id', $productId)
                    ->where('deleted_at IS NULL')
                    ->get()->getRowArray();

                if (!$prod || (int) $prod['stock_quantity'] < $quantity) {
                    $db->transRollback();
                    return redirect()->to($failureRedirectUrl)->with('error', "Item '{$item['product_name']}' is out of stock or does not have enough inventory.");
                }

                if (!empty($item['variant_id'])) {
                    $var = $db->table('product_variants')
                        ->where('id', (int) $item['variant_id'])
                        ->where('product_id', $productId)
                        ->get()->getRowArray();

                    if (!$var || (int) $var['stock_quantity'] < $quantity) {
                        $db->transRollback();
                        return redirect()->to($failureRedirectUrl)->with('error', "Selected option for '{$item['product_name']}' is out of stock.");
                    }

                    // Decrement variant stock
                    $db->table('product_variants')
                        ->where('id', (int) $item['variant_id'])
                        ->update(['stock_quantity' => max(0, (int) $var['stock_quantity'] - $quantity)]);
                }

                // Decrement product total stock
                $db->table('products')
                    ->where('id', $productId)
                    ->update(['stock_quantity' => max(0, (int) $prod['stock_quantity'] - $quantity)]);
            }
        }

        $orderModel      = new OrderModel();
        $orderItemModel  = new OrderItemModel();
        $createdOrderIds = [];

        foreach ($itemsByShop as $shopId => $items) {
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['unit_price'] * $item['quantity'];
            }
            $shippingFee = $fulfillmentMethod === 'delivery' ? 50.00 : 0.00;
            $totalAmount = $subtotal + $shippingFee;

            $orderId = $orderModel->insert([
                'order_number'        => 'ORD-' . rand(10000, 99999),
                'customer_id'         => $userId,
                'shop_id'             => $shopId,
                'shipping_address_id' => $addressId,
                'fulfillment_method'  => $fulfillmentMethod,
                'payment_method'      => $paymentMethod,
                'subtotal'            => $subtotal,
                'shipping_fee'        => $shippingFee,
                'tax_amount'          => 0,
                'total_amount'        => $totalAmount,
                'status'              => 'pending',
                'payment_status'      => 'unpaid',
            ]);

            $createdOrderIds[] = $orderId;

            foreach ($items as $item) {
                $orderItemModel->insert([
                    'order_id'      => $orderId,
                    'product_id'    => $item['product_id'],
                    'variant_id'    => $item['variant_id'] ?? null,
                    'variant_label' => $item['variant_label'] ?? null,
                    'product_name'  => $item['product_name'],
                    'quantity'      => $item['quantity'],
                    'unit_price'    => $item['unit_price'],
                    'line_total'    => $item['unit_price'] * $item['quantity'],
                ]);
            }
        }

        // Only delete cart items if cart IDs were provided (Direct Buy leaves cart untouched)
        if (!empty($selectedCartItemIds)) {
            $cartItemModel = new CartItemModel();
            $cartItemModel->whereIn('id', $selectedCartItemIds)->delete();
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to($failureRedirectUrl)->with('error', 'A database error occurred while finalizing your order.');
        }

        // Dispatch notifications after commit
        foreach ($createdOrderIds as $oId) {
            $ord = $orderModel->find($oId);
            if ($ord) {
                $this->notifyNewOrder($ord['shop_id'], $oId);
            }
        }

        return redirect()->to('/customer/orders')->with('success', 'Your order has been placed successfully.');
    }

    /**
     * Emit a gated "new order" notification to the shop owner when the shop's
     * "New Orders" preference is enabled.
     */
    protected function notifyNewOrder(int $shopId, int $orderId): void
    {
        $shop = (new ShopModel())->find($shopId);
        if (!$shop || empty($shop['owner_id'])) {
            return;
        }

        $prefs = (new ShopNotificationPreferenceModel())->getForShop($shopId);
        if (empty($prefs['new_orders'])) {
            return;
        }

        $order        = (new OrderModel())->find($orderId);
        $customerName = 'A customer';
        $orderNumber  = 'ORD-' . $orderId;
        $itemsSummary = 'products';
        $placedAt     = date('M d, Y h:i A');

        if ($order) {
            $orderNumber = $order['order_number'] ?? $orderNumber;
            $placedAt    = date('M d, Y h:i A', strtotime($order['placed_at'] ?? 'now'));
            $customer    = (new UserModel())->find($order['customer_id']);
            if ($customer) {
                $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
                if ($customerName === '') {
                    $customerName = $customer['email'] ?? 'A customer';
                }
            }
            $items = (new OrderItemModel())->where('order_id', $orderId)->findAll();
            if (!empty($items)) {
                $firstItem    = $items[0]['product_name'] ?? 'Product';
                $extraCount   = count($items) - 1;
                $itemsSummary = $firstItem . ($extraCount > 0 ? " + {$extraCount} more" : '');
            }
        }

        (new NotificationModel())->create(
            (int) $shop['owner_id'],
            'new_order',
            'New Order #' . $orderNumber,
            "{$customerName} purchased {$itemsSummary} at {$placedAt}.",
            '/tenant/orders'
        );
    }
}
