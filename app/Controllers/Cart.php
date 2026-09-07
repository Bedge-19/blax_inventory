<?php

namespace App\Controllers;

use App\Models\CartModel;
use App\Models\CartItemModel;
use App\Models\ProductModel;
use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\ShippingAddressModel;
use App\Models\ShopModel;
use App\Models\NotificationModel;
use App\Models\ShopNotificationPreferenceModel;

class Cart extends BaseController
{
    public function index()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $cartModel     = new CartModel();
        $cartItemModel = new CartItemModel();
        $addressModel  = new ShippingAddressModel();

        $cart      = $cartModel->getOrCreateCart($userId);

        // Defensive purge of any expired items (> 72 hours)
        $cartItemModel->where('cart_id', $cart['id'])
            ->where('expires_at IS NOT NULL')
            ->where('expires_at <=', date('Y-m-d H:i:s'))
            ->delete();

        $cartItems = $cartItemModel->getCartItemsWithProducts($cart['id']);
        $addresses = $addressModel->where('user_id', $userId)->findAll();

        $total = 0;
        foreach ($cartItems as $item) {
            if (!empty($item['is_selected'])) {
                $total += $item['price'] * $item['quantity'];
            }
        }

        return view('customer/cart', [
            'cart'        => $cart,
            'cartItems'   => $cartItems,
            'addresses'   => $addresses,
            'total'       => $total,
            'shippingFee' => 50.00,
        ]);
    }

    public function remove($itemId)
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $cartModel     = new CartModel();
        $cartItemModel = new CartItemModel();

        $cart = $cartModel->getOrCreateCart($userId);
        $cartItemModel->where('id', (int) $itemId)->where('cart_id', $cart['id'])->delete();

        return redirect()->to('/cart');
    }

    public function updateQuantity()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $itemId    = (int) $this->request->getPost('item_id');
        $quantity  = max(1, (int) ($this->request->getPost('quantity') ?? 1));

        $cartModel     = new CartModel();
        $cartItemModel = new CartItemModel();

        $cart = $cartModel->getOrCreateCart($userId);

        $item = $cartItemModel->where('id', $itemId)->where('cart_id', $cart['id'])->first();
        if (!$item) {
            return redirect()->to('/cart');
        }

        $productModel = new ProductModel();
        $product      = $productModel->find($item['product_id']);
        $maxQty       = $product ? max(1, (int) $product['stock_quantity']) : $quantity;
        $quantity     = min($quantity, $maxQty);

        $cartItemModel->update($itemId, ['quantity' => $quantity]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'success', 'quantity' => $quantity]);
        }

        return redirect()->to('/cart');
    }

    public function removeSelected()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $ids = $this->request->getPost('ids');
        $ids = is_array($ids) ? array_map('intval', $ids) : [];
        $ids = array_values(array_filter($ids));

        if ($ids) {
            $cartModel     = new CartModel();
            $cartItemModel = new CartItemModel();

            $cart = $cartModel->getOrCreateCart($userId);
            $cartItemModel->builder()->where('cart_id', $cart['id'])->whereIn('id', $ids)->delete();
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'success']);
        }

        return redirect()->to('/cart');
    }

    public function add()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Please log in to add items to cart.']);
            }
            return redirect()->to('/login');
        }

        $productId = (int)$this->request->getPost('product_id');
        $quantity  = max(1, (int)($this->request->getPost('quantity') ?? 1));

        $productModel = new ProductModel();
        $product      = $productModel->find($productId);

        if (!$product) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Product not found.']);
            }
            return redirect()->back();
        }

        $quantity = min($quantity, max(1, (int) $product['stock_quantity']));

        $cartModel     = new CartModel();
        $cartItemModel = new CartItemModel();

        $cart = $cartModel->getOrCreateCart($userId);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+72 hours'));

        $existingItem = $cartItemModel->where('cart_id', $cart['id'])
            ->where('product_id', $productId)
            ->first();

        if ($existingItem) {
            $cartItemModel->update($existingItem['id'], [
                'quantity'         => $existingItem['quantity'] + $quantity,
                'expires_at'       => $expiresAt,
                'last_reminder_at' => null,
                'reminder_count'   => 0,
            ]);
        } else {
            $cartItemModel->insert([
                'cart_id'          => $cart['id'],
                'product_id'       => $productId,
                'quantity'         => $quantity,
                'unit_price'       => $product['price'],
                'is_selected'      => 1,
                'expires_at'       => $expiresAt,
                'last_reminder_at' => null,
                'reminder_count'   => 0,
            ]);
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Product added to cart successfully.']);
        }

        return redirect()->to('/cart');
    }

    public function checkout()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $cartModel     = new CartModel();
        $cartItemModel = new CartItemModel();

        $cart = $cartModel->getOrCreateCart($userId);

        $selectedIds = $this->request->getPost('selected_items');
        $selectedIds = is_array($selectedIds) ? array_map('intval', $selectedIds) : [];
        $selectedIds = array_values(array_filter($selectedIds));

        if ($selectedIds) {
            $cartItemModel->builder()->where('cart_id', $cart['id'])->update(['is_selected' => 0]);
            $cartItemModel->builder()->where('cart_id', $cart['id'])->whereIn('id', $selectedIds)->update(['is_selected' => 1]);
        } else {
            $existing = $cartItemModel->where('cart_id', $cart['id'])->where('is_selected', 1)->findAll();
            $selectedIds = array_column($existing, 'id');
            if (empty($selectedIds)) {
                $all = $cartItemModel->where('cart_id', $cart['id'])->findAll();
                $selectedIds = array_column($all, 'id');
                if ($selectedIds) {
                    $cartItemModel->builder()->where('cart_id', $cart['id'])->update(['is_selected' => 1]);
                }
            }
        }

        $cartItems = $cartItemModel->getCartItemsWithProducts($cart['id']);

        if (empty($cartItems) || empty($selectedIds)) {
            return redirect()->to('/cart');
        }

        $paymentMethod     = $this->request->getPost('payment_method') ?? 'gcash';
        $fulfillmentMethod = $this->request->getPost('fulfillment_method') ?? ($paymentMethod === 'pickup' ? 'pickup' : 'delivery');
        $addressId         = $this->request->getPost('shipping_address_id') ?? null;

        // Polomolok-only restriction: delivery & printing limited to Polomolok
        if ($fulfillmentMethod === 'delivery' && $addressId) {
            $addr = (new ShippingAddressModel())->find((int)$addressId);
            if ($addr) {
                $cityOk = stripos($addr['city'] ?? '', 'Polomolok') !== false;
                $provOk = stripos($addr['province'] ?? '', 'South Cotabato') !== false;
                if (!$cityOk && !$provOk) {
                    return redirect()->back()->with('error', 'Delivery is available only within Polomolok, South Cotabato.');
                }
            }
        }

        // Group items by shop_id to create separate order numbers per shop
        $itemsByShop = [];
        foreach ($cartItems as $item) {
            if (!empty($item['is_selected'])) {
                $itemsByShop[$item['shop_id']][] = $item;
            }
        }

        // If GCash payment method, do NOT create order yet.
        // Instead, build the checkout intent payload, create PayMongo session, and redirect.
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
                            'product_id'   => $it['product_id'],
                            'product_name' => $it['product_name'],
                            'quantity'     => $it['quantity'],
                            'unit_price'   => $it['unit_price'],
                            'line_total'   => $it['unit_price'] * $it['quantity'],
                        ];
                    }, $items),
                ];
            }

            $token = bin2hex(random_bytes(16));
            $paymongo = service('paymongoService');
            $successUrl = base_url('cart/payment/callback?token=' . $token);
            $cancelUrl  = base_url('cart/payment/callback?cancel=1&token=' . $token);

            $sessionRes = $paymongo->createGcashCheckoutSession(
                $grandTotal,
                'Order Payment (Blax MarketPlace)',
                $successUrl,
                $cancelUrl,
                [
                    'type'     => 'product_order',
                    'user_id'  => (string)$userId,
                    'token'    => $token,
                ]
            );

            if (!empty($sessionRes['success']) && !empty($sessionRes['checkout_url'])) {
                $pendingData = [
                    'user_id'            => $userId,
                    'fulfillment_method' => $fulfillmentMethod,
                    'payment_method'     => 'gcash',
                    'shipping_address_id'=> $addressId,
                    'shop_orders'        => $shopOrders,
                    'selected_ids'       => $selectedIds,
                    'session_id'         => $sessionRes['session_id'],
                    'grand_total'        => $grandTotal,
                    'token'              => $token,
                ];

                session()->set('pending_cart_checkout_' . $userId, $pendingData);
                session()->set('pending_cart_token_' . $token, $pendingData);
                cache()->save('pending_cart_' . $sessionRes['session_id'], $pendingData, 86400);
                cache()->save('pending_cart_token_' . $token, $pendingData, 86400);

                return redirect()->to($sessionRes['checkout_url']);
            }

            return redirect()->to('/cart')->with('error', $sessionRes['error'] ?? 'Unable to connect to PayMongo checkout. Please try again.');
        }

        // Non-GCash (COD, Store Pickup) - create orders directly
        $orderModel     = new OrderModel();
        $orderItemModel = new OrderItemModel();

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
                    'order_id'    => $orderId,
                    'product_id'  => $item['product_id'],
                    'product_name'=> $item['product_name'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'line_total'  => $item['unit_price'] * $item['quantity'],
                ]);
            }

            $this->notifyNewOrder($shopId, $orderId);
        }

        // Clear selected cart items directly for non-GCash
        foreach ($cartItems as $item) {
            if (!empty($item['is_selected'])) {
                $cartItemModel->delete($item['id']);
            }
        }

        return redirect()->to('/customer/orders')->with('success', 'Your order has been placed successfully.');
    }

    /**
     * PayMongo Callback verification for GCash cart checkout.
     * Order is ONLY created when payment is server-verified.
     */
    public function paymentCallback()
    {
        $session = session();
        $userId  = $session->get('user_id');
        if (!$userId) {
            return redirect()->to('/login');
        }

        $isCancel = (bool) $this->request->getGet('cancel');
        if ($isCancel) {
            return redirect()->to('/cart')->with('warning', 'GCash checkout was cancelled. Your cart items have been kept.');
        }

        $sessionId = (string) ($this->request->getGet('session_id') ?? $this->request->getGet('checkout_session_id') ?? '');
        $token     = (string) $this->request->getGet('token');

        $pendingData = null;
        if ($token !== '') {
            $pendingData = session()->get('pending_cart_token_' . $token) ?? cache()->get('pending_cart_token_' . $token);
        }
        if (!$pendingData && $sessionId !== '') {
            $pendingData = cache()->get('pending_cart_' . $sessionId);
        }
        if (!$pendingData) {
            $pendingData = session()->get('pending_cart_checkout_' . $userId);
        }

        if (empty($pendingData)) {
            return redirect()->to('/cart')->with('error', 'Checkout session expired or not found. Your cart items are preserved.');
        }

        if ($sessionId === '') {
            $sessionId = (string) ($pendingData['session_id'] ?? '');
        }

        if ($sessionId === '') {
            return redirect()->to('/cart')->with('error', 'Missing PayMongo session ID.');
        }

        $paymongo = service('paymongoService');
        $paid = false; // Never default to true!
        $check = $paymongo->getCheckoutSession($sessionId);
        if (!empty($check['success']) && !empty($check['paid'])) {
            $paid = true;
        }

        if (!$paid) {
            return redirect()->to('/cart')->with('error', 'Payment was not completed or could not be verified. Your cart items have been kept.');
        }

        // Server-verified payment -> create orders and payment records in a DB transaction
        $db = \Config\Database::connect();
        $db->transStart();

        $paymentModel = new PaymentModel();
        // Idempotency check: prevent duplicate orders if callback is triggered multiple times
        $alreadyProcessed = $paymentModel->where('reference_number', $sessionId)->first();
        if ($alreadyProcessed) {
            $db->transComplete();
            return redirect()->to('/customer/orders')->with('info', 'Your order has already been verified and placed.');
        }

        $orderModel     = new OrderModel();
        $orderItemModel = new OrderItemModel();
        $cartModel      = new CartModel();
        $cartItemModel  = new CartItemModel();

        $createdOrderIds = [];
        foreach ($pendingData['shop_orders'] as $so) {
            $orderId = $orderModel->insert([
                'order_number'        => 'ORD-' . rand(10000, 99999),
                'customer_id'         => $userId,
                'shop_id'             => $so['shop_id'],
                'shipping_address_id' => $pendingData['shipping_address_id'],
                'fulfillment_method'  => $pendingData['fulfillment_method'],
                'payment_method'      => 'gcash',
                'subtotal'            => $so['subtotal'],
                'shipping_fee'        => $so['shipping_fee'],
                'tax_amount'          => 0,
                'total_amount'        => $so['total_amount'],
                'status'              => 'pending',
                'payment_status'      => 'paid',
            ]);

            $createdOrderIds[] = $orderId;

            foreach ($so['items'] as $it) {
                $orderItemModel->insert([
                    'order_id'    => $orderId,
                    'product_id'  => $it['product_id'],
                    'product_name'=> $it['product_name'],
                    'quantity'    => $it['quantity'],
                    'unit_price'  => $it['unit_price'],
                    'line_total'  => $it['line_total'],
                ]);
            }

            $paymentModel->insert([
                'payable_type'     => 'order',
                'payable_id'       => $orderId,
                'method'           => 'gcash',
                'amount'           => $so['total_amount'],
                'reference_number' => $sessionId,
                'proof_image_url'  => null,
                'status'           => 'verified',
                'processed_at'     => date('Y-m-d H:i:s'),
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        // Clear selected cart items
        $cart = $cartModel->getOrCreateCart($userId);
        if (!empty($pendingData['selected_ids'])) {
            $cartItemModel->where('cart_id', $cart['id'])->whereIn('id', $pendingData['selected_ids'])->delete();
        } else {
            $cartItemModel->where('cart_id', $cart['id'])->where('is_selected', 1)->delete();
        }

        // Clean up pending session data and cache
        session()->remove('pending_cart_checkout_' . $userId);
        if ($token !== '') {
            session()->remove('pending_cart_token_' . $token);
            cache()->delete('pending_cart_token_' . $token);
        }
        cache()->delete('pending_cart_' . $sessionId);

        $db->transComplete();

        // Notify shops after successful commit
        if ($db->transStatus() !== false) {
            foreach ($createdOrderIds as $oId) {
                $ord = $orderModel->find($oId);
                if ($ord) {
                    $this->notifyNewOrder($ord['shop_id'], $oId);
                }
            }
            return redirect()->to('/customer/orders')->with('success', 'Payment verified successfully via PayMongo GCash! Your order has been placed.');
        }

        return redirect()->to('/cart')->with('error', 'A database error occurred while finalizing your order. Please contact support.');
    }

    /**
     * PayMongo Webhook Endpoint
     * Handles checkout_session.payment.paid events asynchronously and idempotently.
     */
    public function paymongoWebhook()
    {
        $payload = (string) $this->request->getBody();
        $data = json_decode($payload, true);
        if (!$data || empty($data['data']['attributes']['data']['id'])) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid webhook payload']);
        }

        $sessionData = $data['data']['attributes']['data'];
        $sessionId   = $sessionData['id'];
        $attributes  = $sessionData['attributes'] ?? [];
        $status      = $attributes['status'] ?? '';
        $metadata    = $attributes['metadata'] ?? [];
        $type        = $metadata['type'] ?? '';

        if ($status !== 'paid') {
            return $this->response->setJSON(['status' => 'ignored', 'reason' => 'not_paid']);
        }

        $paymentModel = new PaymentModel();
        // Idempotency: skip if already processed
        $already = $paymentModel->where('reference_number', $sessionId)->first();
        if ($already) {
            return $this->response->setJSON(['status' => 'already_processed']);
        }

        if ($type === 'product_order') {
            $pendingData = cache()->get('pending_cart_' . $sessionId);
            if (!$pendingData) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Pending cart data expired or not found']);
            }

            $userId = (int) $pendingData['user_id'];
            $db = \Config\Database::connect();
            $db->transStart();

            $orderModel     = new OrderModel();
            $orderItemModel = new OrderItemModel();
            $cartModel      = new CartModel();
            $cartItemModel  = new CartItemModel();

            $createdOrderIds = [];
            foreach ($pendingData['shop_orders'] as $so) {
                $orderId = $orderModel->insert([
                    'order_number'        => 'ORD-' . rand(10000, 99999),
                    'customer_id'         => $userId,
                    'shop_id'             => $so['shop_id'],
                    'shipping_address_id' => $pendingData['shipping_address_id'],
                    'fulfillment_method'  => $pendingData['fulfillment_method'],
                    'payment_method'      => 'gcash',
                    'subtotal'            => $so['subtotal'],
                    'shipping_fee'        => $so['shipping_fee'],
                    'tax_amount'          => 0,
                    'total_amount'        => $so['total_amount'],
                    'status'              => 'pending',
                    'payment_status'      => 'paid',
                ]);

                $createdOrderIds[] = $orderId;

                foreach ($so['items'] as $it) {
                    $orderItemModel->insert([
                        'order_id'    => $orderId,
                        'product_id'  => $it['product_id'],
                        'product_name'=> $it['product_name'],
                        'quantity'    => $it['quantity'],
                        'unit_price'  => $it['unit_price'],
                        'line_total'  => $it['line_total'],
                    ]);
                }

                $paymentModel->insert([
                    'payable_type'     => 'order',
                    'payable_id'       => $orderId,
                    'method'           => 'gcash',
                    'amount'           => $so['total_amount'],
                    'reference_number' => $sessionId,
                    'proof_image_url'  => null,
                    'status'           => 'verified',
                    'processed_at'     => date('Y-m-d H:i:s'),
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
            }

            $cart = $cartModel->getOrCreateCart($userId);
            if (!empty($pendingData['selected_ids'])) {
                $cartItemModel->where('cart_id', $cart['id'])->whereIn('id', $pendingData['selected_ids'])->delete();
            } else {
                $cartItemModel->where('cart_id', $cart['id'])->where('is_selected', 1)->delete();
            }

            cache()->delete('pending_cart_' . $sessionId);
            $db->transComplete();

            if ($db->transStatus() !== false) {
                foreach ($createdOrderIds as $oId) {
                    $ord = $orderModel->find($oId);
                    if ($ord) {
                        $this->notifyNewOrder($ord['shop_id'], $oId);
                    }
                }
                return $this->response->setJSON(['status' => 'success', 'orders' => $createdOrderIds]);
            }
        } elseif ($type === 'printing_down_payment') {
            $pending = cache()->get('pending_printing_' . $sessionId);
            if (!$pending) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Pending printing data not found']);
            }

            $db = \Config\Database::connect();
            $db->transStart();

            $prModel = new \App\Models\PrintingRequestModel();
            $insertData = [
                'request_number'     => $pending['request_number'] ?? ('PR-' . date('Ymd') . '-' . rand(1000, 9999)),
                'customer_id'        => $pending['customer_id'],
                'shop_id'            => $pending['shop_id'],
                'file_name'          => $pending['file_name'],
                'file_url'           => $pending['file_url'],
                'paper_size'         => $pending['paper_size'],
                'color_mode'         => $pending['color_mode'],
                'copies'             => $pending['copies'],
                'page_count'         => $pending['page_count'],
                'binding_option'     => $pending['binding_option'],
                'paper_stock'        => $pending['paper_stock'],
                'fulfillment_method' => $pending['fulfillment_method'],
                'total_price'        => $pending['total_price'],
                'down_payment'       => $pending['down_payment'],
                'status'             => 'Paid (50% Down Payment)',
                'progress_percent'   => 0,
            ];

            $prId = $prModel->insert($insertData);

            $paymentModel->insert([
                'payable_type'     => 'printing_request',
                'payable_id'       => $prId,
                'method'           => 'gcash',
                'amount'           => $pending['down_payment'],
                'reference_number' => $sessionId,
                'proof_image_url'  => null,
                'status'           => 'verified',
                'processed_at'     => date('Y-m-d H:i:s'),
                'created_at'       => date('Y-m-d H:i:s'),
            ]);

            cache()->delete('pending_printing_' . $sessionId);
            $db->transComplete();

            if ($db->transStatus() !== false) {
                $shop = (new \App\Models\ShopModel())->find($pending['shop_id']);
                if ($shop && !empty($shop['owner_id'])) {
                    $customerUser = (new \App\Models\UserModel())->find($pending['customer_id']);
                    $cName = $customerUser ? trim(($customerUser['first_name'] ?? '') . ' ' . ($customerUser['last_name'] ?? '')) : 'A customer';
                    if ($cName === '') $cName = 'A customer';
                    $prTime = date('M d, Y h:i A');
                    (new \App\Models\NotificationModel())->create(
                        (int) $shop['owner_id'],
                        'new_printing_request',
                        'New Printing Request #' . ($pending['request_number'] ?? ('PR-' . $prId)),
                        "Received printing request #{$pending['request_number']} from {$cName} at {$prTime}.",
                        '/tenant/printing'
                    );
                }
                return $this->response->setJSON(['status' => 'success', 'printing_request_id' => $prId]);
            }
        }

        return $this->response->setJSON(['status' => 'ignored']);
    }

    /**
     * Emit a gated "new order" notification to the shop owner when the shop's
     * "New Orders" preference is enabled.
     */
    private function notifyNewOrder(int $shopId, int $orderId): void
    {
        $shop = (new ShopModel())->find($shopId);
        if (!$shop || empty($shop['owner_id'])) {
            return;
        }

        $prefs = (new ShopNotificationPreferenceModel())->getForShop($shopId);
        if (empty($prefs['new_orders'])) {
            return;
        }

        $order = (new OrderModel())->find($orderId);
        $customerName = 'A customer';
        $orderNumber  = 'ORD-' . $orderId;
        $itemsSummary = 'products';
        $placedAt     = date('M d, Y h:i A');

        if ($order) {
            $orderNumber = $order['order_number'] ?? $orderNumber;
            $placedAt    = date('M d, Y h:i A', strtotime($order['placed_at'] ?? 'now'));
            $customer    = (new \App\Models\UserModel())->find($order['customer_id']);
            if ($customer) {
                $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
                if ($customerName === '') {
                    $customerName = $customer['email'] ?? 'A customer';
                }
            }
            $items = (new OrderItemModel())->where('order_id', $orderId)->findAll();
            if (!empty($items)) {
                $firstItem   = $items[0]['product_name'] ?? 'Product';
                $extraCount  = count($items) - 1;
                $itemsSummary = $firstItem . ($extraCount > 0 ? " + {$extraCount} more" : "");
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
