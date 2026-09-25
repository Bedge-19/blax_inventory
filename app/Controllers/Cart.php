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
use App\Models\PaymentModel;
use App\Models\ProductVariantModel;
use App\Traits\CheckoutProcessorTrait;

class Cart extends BaseController
{
    use CheckoutProcessorTrait;

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

        $itemsByShop = [];
        $total = 0;
        $selectedCount = 0;
        foreach ($cartItems as $item) {
            $shopId = (int) ($item['shop_id'] ?? 0);
            if (!isset($itemsByShop[$shopId])) {
                $itemsByShop[$shopId] = [
                    'shop_id'         => $shopId,
                    'shop_name'       => $item['shop_name'] ?? 'Merchant Store',
                    'shop_slug'       => $item['shop_slug'] ?? '',
                    'offers_delivery' => (int) ($item['offers_delivery'] ?? 1),
                    'offers_pickup'   => (int) ($item['offers_pickup'] ?? 1),
                    'items'           => [],
                ];
            }
            $itemsByShop[$shopId]['items'][] = $item;

            if (!empty($item['is_selected'])) {
                $total += $item['price'] * $item['quantity'];
                $selectedCount += $item['quantity'];
            }
        }

        return view('customer/cart', [
            'cart'          => $cart,
            'cartItems'     => $cartItems,
            'itemsByShop'   => $itemsByShop,
            'addresses'     => $addresses,
            'total'         => $total,
            'selectedCount' => $selectedCount,
            'shippingFee'   => 50.00,
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
        $variantId = (int)($this->request->getPost('variant_id') ?? 0);

        $productModel = new ProductModel();
        $product      = $productModel->find($productId);

        if (!$product) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Product not found.']);
            }
            return redirect()->back();
        }

        $variantModel = new ProductVariantModel();
        $variants = $variantModel->where('product_id', $productId)->findAll();

        $selectedVariant = null;
        $variantLabel = null;
        $unitPrice = (float) $product['price'];
        $maxStock = (int) $product['stock_quantity'];

        if (!empty($variants)) {
            if (!$variantId) {
                $msg = 'Please select a product option to continue.';
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON(['status' => 'error', 'message' => $msg]);
                }
                return redirect()->back()->with('error', $msg);
            }

            foreach ($variants as $v) {
                if ((int)$v['id'] === $variantId) {
                    $selectedVariant = $v;
                    break;
                }
            }

            if (!$selectedVariant) {
                $msg = 'The selected product option does not exist.';
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON(['status' => 'error', 'message' => $msg]);
                }
                return redirect()->back()->with('error', $msg);
            }

            if ((int)$selectedVariant['stock_quantity'] <= 0) {
                $msg = 'The selected option is out of stock.';
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON(['status' => 'error', 'message' => $msg]);
                }
                return redirect()->back()->with('error', $msg);
            }

            $maxStock = (int) $selectedVariant['stock_quantity'];
            $variantLabel = trim($selectedVariant['name'] . ': ' . $selectedVariant['value']);
            if ($selectedVariant['price_override'] !== null && (float)$selectedVariant['price_override'] > 0) {
                $unitPrice = (float) $selectedVariant['price_override'];
            }
        } else {
            $variantId = null;
        }

        $quantity = min($quantity, max(1, $maxStock));

        $cartModel     = new CartModel();
        $cartItemModel = new CartItemModel();

        $cart = $cartModel->getOrCreateCart($userId);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+3 days'));

        $existingItemQuery = $cartItemModel->where('cart_id', $cart['id'])
            ->where('product_id', $productId);
        if ($variantId) {
            $existingItemQuery->where('variant_id', $variantId);
        } else {
            $existingItemQuery->where('variant_id IS NULL');
        }
        $existingItem = $existingItemQuery->first();

        if ($existingItem) {
            $newQty = min($existingItem['quantity'] + $quantity, max(1, $maxStock));
            $cartItemModel->update($existingItem['id'], [
                'quantity'         => $newQty,
                'unit_price'       => $unitPrice,
                'variant_label'    => $variantLabel,
                'expires_at'       => $expiresAt,
                'last_reminder_at' => null,
                'reminder_count'   => 0,
            ]);
        } else {
            $cartItemModel->insert([
                'cart_id'          => $cart['id'],
                'product_id'       => $productId,
                'variant_id'       => $variantId,
                'variant_label'    => $variantLabel,
                'quantity'         => $quantity,
                'unit_price'       => $unitPrice,
                'is_selected'      => 0,
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
        }

        if (empty($selectedIds)) {
            return redirect()->to('/cart')->with('error', 'Please select at least one item from your cart to proceed with checkout.');
        }

        $cartItems = $cartItemModel->getCartItemsWithProducts($cart['id']);

        if (empty($cartItems)) {
            return redirect()->to('/cart')->with('error', 'Your cart is empty.');
        }

        // Task 10: Require at least one address before checkout
        $addressCount = (new ShippingAddressModel())->where('user_id', $userId)->countAllResults();
        if ($addressCount === 0) {
            return redirect()->to(base_url('customer/addresses?open_add=1'))->with('error', 'Please add and set a shipping address before checking out.');
        }

        $paymentMethod     = $this->request->getPost('payment_method') ?? 'gcash';
        $fulfillmentMethod = $this->request->getPost('fulfillment_method') ?? ($paymentMethod === 'pickup' ? 'pickup' : 'delivery');
        $addressId         = $this->request->getPost('shipping_address_id') ?? null;

        // Polomolok-only restriction & address ownership: delivery & printing limited to Polomolok
        if ($fulfillmentMethod === 'delivery' && $addressId) {
            $addr = (new ShippingAddressModel())->find((int)$addressId);
            if (!$addr || (int) ($addr['user_id'] ?? 0) !== (int) $userId) {
                return redirect()->back()->with('error', 'Invalid delivery address selected.');
            }
            $cityOk = (stripos($addr['city'] ?? '', 'Polomolok') !== false) || (stripos($addr['city'] ?? '', 'Tupi') !== false);
            $provOk = stripos($addr['province'] ?? '', 'South Cotabato') !== false;
            if (!$cityOk && !$provOk) {
                return redirect()->back()->with('error', 'Delivery is available only within Polomolok and Tupi, South Cotabato.');
            }
        }

        // Group items by shop_id to create separate order numbers per shop
        $itemsByShop = [];
        foreach ($cartItems as $item) {
            if (!empty($item['is_selected'])) {
                $itemsByShop[$item['shop_id']][] = [
                    'product_id'    => $item['product_id'],
                    'variant_id'    => $item['variant_id'] ?? null,
                    'variant_label' => $item['variant_label'] ?? null,
                    'product_name'  => $item['name'] ?? $item['product_name'] ?? 'Product',
                    'quantity'      => (int) $item['quantity'],
                    'unit_price'    => (float) $item['unit_price'],
                ];
            }
        }

        return $this->processItemsByShop(
            $itemsByShop,
            $userId,
            $paymentMethod,
            $fulfillmentMethod,
            $addressId ? (int) $addressId : null,
            $selectedIds,
            '/cart',
            '/cart'
        );
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
            $token = (string) $this->request->getGet('token');
            $pendingData = $token !== '' ? (session()->get('pending_cart_token_' . $token) ?? cache()->get('pending_cart_token_' . $token)) : null;
            $cancelRedirect = $pendingData['cancel_redirect'] ?? '/cart';
            $cancelMsg = !empty($pendingData['is_direct_buy'])
                ? 'GCash checkout was cancelled.'
                : 'GCash checkout was cancelled. Your cart items have been kept.';
            return redirect()->to($cancelRedirect)->with('warning', $cancelMsg);
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
                'order_number'        => 'ORD-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
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
                'placed_at'           => date('Y-m-d H:i:s'),
            ]);

            $createdOrderIds[] = $orderId;

            foreach ($so['items'] as $it) {
                $orderItemModel->insert([
                    'order_id'      => $orderId,
                    'product_id'    => $it['product_id'],
                    'variant_id'    => $it['variant_id'] ?? null,
                    'variant_label' => $it['variant_label'] ?? null,
                    'product_name'  => $it['product_name'],
                    'quantity'      => $it['quantity'],
                    'unit_price'    => $it['unit_price'],
                    'line_total'    => $it['line_total'],
                ]);

                // Lock row FOR UPDATE to prevent race condition / negative stock
                $pId  = (int) $it['product_id'];
                $pQty = (int) $it['quantity'];
                if (!empty($it['variant_id'])) {
                    $vId = (int) $it['variant_id'];
                    $vRow = $db->query('SELECT * FROM product_variants WHERE id = ? FOR UPDATE', [$vId])->getRowArray();
                    if ($vRow) {
                        $db->table('product_variants')->where('id', $vId)->update(['stock_quantity' => max(0, (int) $vRow['stock_quantity'] - $pQty)]);
                    }
                }
                $pRow = $db->query('SELECT * FROM products WHERE id = ? FOR UPDATE', [$pId])->getRowArray();
                if ($pRow) {
                    $db->table('products')->where('id', $pId)->update(['stock_quantity' => max(0, (int) $pRow['stock_quantity'] - $pQty)]);
                }
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

        // Clear selected cart items only if not Direct Buy
        if (empty($pendingData['is_direct_buy'])) {
            $cart = $cartModel->getOrCreateCart($userId);
            if (!empty($pendingData['selected_ids'])) {
                $cartItemModel->where('cart_id', $cart['id'])->whereIn('id', $pendingData['selected_ids'])->delete();
            } else {
                $cartItemModel->where('cart_id', $cart['id'])->where('is_selected', 1)->delete();
            }
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

        // Verify PayMongo webhook signature
        $signatureHeader = $this->request->getHeaderLine('Paymongo-Signature');
        if (empty($signatureHeader)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing signature header']);
        }

        $webhookSecret = (string) (env('PAYMONGO_WEBHOOK_SECRET') ?? '');
        if (empty($webhookSecret)) {
            log_message('error', 'PAYMONGO_WEBHOOK_SECRET is not configured.');
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Webhook secret not configured']);
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $pair) {
            $pairParts = explode('=', trim($pair), 2);
            if (count($pairParts) === 2) {
                $parts[$pairParts[0]] = $pairParts[1];
            }
        }

        if (empty($parts['t'])) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid signature header']);
        }

        $timestamp = $parts['t'];
        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $webhookSecret);

        $signatures = [];
        if (!empty($parts['te'])) {
            $signatures[] = $parts['te'];
        }
        if (!empty($parts['li'])) {
            $signatures[] = $parts['li'];
        }

        $isValid = false;
        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid signature']);
        }

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
                    'order_number'        => 'ORD-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
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
                    'placed_at'           => date('Y-m-d H:i:s'),
                ]);

                $createdOrderIds[] = $orderId;

                foreach ($so['items'] as $it) {
                    $orderItemModel->insert([
                        'order_id'      => $orderId,
                        'product_id'    => $it['product_id'],
                        'variant_id'    => $it['variant_id'] ?? null,
                        'variant_label' => $it['variant_label'] ?? null,
                        'product_name'  => $it['product_name'],
                        'quantity'      => $it['quantity'],
                        'unit_price'    => $it['unit_price'],
                        'line_total'    => $it['line_total'],
                    ]);

                    // Lock row FOR UPDATE to prevent race condition / negative stock
                    $pId  = (int) $it['product_id'];
                    $pQty = (int) $it['quantity'];
                    if (!empty($it['variant_id'])) {
                        $vId = (int) $it['variant_id'];
                        $vRow = $db->query('SELECT * FROM product_variants WHERE id = ? FOR UPDATE', [$vId])->getRowArray();
                        if ($vRow) {
                            $db->table('product_variants')->where('id', $vId)->update(['stock_quantity' => max(0, (int) $vRow['stock_quantity'] - $pQty)]);
                        }
                    }
                    $pRow = $db->query('SELECT * FROM products WHERE id = ? FOR UPDATE', [$pId])->getRowArray();
                    if ($pRow) {
                        $db->table('products')->where('id', $pId)->update(['stock_quantity' => max(0, (int) $pRow['stock_quantity'] - $pQty)]);
                    }
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

            // Clear selected cart items only if not Direct Buy
            if (empty($pendingData['is_direct_buy'])) {
                $cart = $cartModel->getOrCreateCart($userId);
                if (!empty($pendingData['selected_ids'])) {
                    $cartItemModel->where('cart_id', $cart['id'])->whereIn('id', $pendingData['selected_ids'])->delete();
                } else {
                    $cartItemModel->where('cart_id', $cart['id'])->where('is_selected', 1)->delete();
                }
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

            $printingRequestModel = new \App\Models\PrintingRequestModel();
            $paymentModel         = new PaymentModel();
            $db                   = \Config\Database::connect();
            $db->transStart();

            $alreadyProcessed = $paymentModel->where('reference_number', $sessionId)->first();
            if ($alreadyProcessed) {
                $db->transComplete();
                return $this->response->setJSON(['status' => 'already_processed']);
            }

            $prId = $printingRequestModel->insert([
                'request_number'       => $pending['request_number'] ?? ('PR-' . strtoupper(substr(md5(uniqid()), 0, 8))),
                'customer_id'          => $pending['customer_id'],
                'shop_id'              => $pending['shop_id'],
                'file_name'            => $pending['file_name'] ?? ($pending['original_filename'] ?? 'document.pdf'),
                'file_url'             => $pending['file_url'],
                'page_count'           => $pending['page_count'] ?? 1,
                'paper_size'           => $pending['paper_size'] ?? 'Letter',
                'color_mode'           => $pending['color_mode'] ?? 'bw',
                'copies'               => $pending['copies'] ?? ($pending['quantity'] ?? 1),
                'binding_option'       => $pending['binding_option'] ?? ($pending['binding_type'] ?? 'none'),
                'paper_stock'          => $pending['paper_stock'] ?? 'standard',
                'fulfillment_method'   => $pending['fulfillment_method'] ?? 'pickup',
                'total_price'          => $pending['total_price'],
                'down_payment'         => $pending['down_payment'] ?? ($pending['down_payment_amount'] ?? 0.00),
                'status'               => 'new',
                'progress_percent'     => 0,
                'document_type'        => $pending['document_type'] ?? 'pdf',
                'doc_change_type'      => $pending['doc_change_type'] ?? 'as_is',
                'special_instructions' => $pending['special_instructions'] ?? null,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);

            // Save reference photo attachments if any
            if (!empty($pending['attachments']) && is_array($pending['attachments'])) {
                $attachModel = new \App\Models\PrintingRequestAttachmentModel();
                foreach ($pending['attachments'] as $attPath) {
                    $attachModel->insert([
                        'printing_request_id' => $prId,
                        'image_url'           => $attPath,
                        'created_at'          => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            $paymentModel->insert([
                'payable_type'     => 'printing_request',
                'payable_id'       => $prId,
                'method'           => 'gcash',
                'amount'           => $pending['down_payment_amount'],
                'reference_number' => $sessionId,
                'proof_image_url'  => null,
                'status'           => 'verified',
                'processed_at'     => date('Y-m-d H:i:s'),
                'created_at'       => date('Y-m-d H:i:s'),
            ]);

            cache()->delete('pending_printing_' . $sessionId);
            $db->transComplete();

            if ($db->transStatus() !== false) {
                $shop = (new ShopModel())->find($pending['shop_id']);
                if ($shop && !empty($shop['owner_id'])) {
                    (new NotificationModel())->create(
                        (int) $shop['owner_id'],
                        'printing_request',
                        'New Printing Request',
                        'A customer submitted a printing request with down payment.',
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
