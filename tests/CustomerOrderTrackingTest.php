<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\ProductModel;
use App\Models\ShopModel;
use App\Models\UserModel;
use App\Models\DeliveryModel;

class CustomerOrderTrackingTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        $db = \Config\Database::connect();
        $db->table('deliveries')->like('tracking_id', 'TRK-ORD-TRACK-')->orLike('tracking_id', 'ORD-TRACK-')->delete();
        $db->table('order_items')->whereIn('order_id', static function ($builder) {
            $builder->select('id')->from('orders')->like('order_number', 'ORD-TEST-ISO-')->orLike('order_number', 'ORD-PICKUP-')->orLike('order_number', 'ORD-TRACK-')->orLike('order_number', 'ORD-BTN-');
        })->delete();
        $db->table('orders')->like('order_number', 'ORD-TEST-ISO-')->orLike('order_number', 'ORD-PICKUP-')->orLike('order_number', 'ORD-TRACK-')->orLike('order_number', 'ORD-BTN-')->delete();
        parent::tearDown();
    }

    private function asCustomer(int $userId, string $email = 'customer@test.com'): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => $userId,
            'user_name'  => 'Test Customer',
            'user_email' => $email,
            'user_role'  => 'customer',
            'role'       => 'customer',
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN' => $hash,
        ]);
    }

    public function testTrackingRequiresAuthentication()
    {
        $result = $this->get('customer/orders/track/ORD-99999');
        $result->assertRedirectTo('/login');
    }

    public function testCustomerCannotTrackOtherCustomersOrder()
    {
        $userModel = new UserModel();
        $customers = $userModel->where('role', 'customer')->findAll(2);
        if (count($customers) < 2) {
            $this->markTestSkipped('Need at least two customer accounts to test isolation.');
        }

        $customerA = $customers[0];
        $customerB = $customers[1];

        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);

        $orderModel = new OrderModel();
        $orderNumber = 'ORD-TEST-ISO-' . uniqid('', false) . rand(100, 999);
        $orderId = $orderModel->insert([
            'order_number'       => $orderNumber,
            'customer_id'        => (int) $customerA['id'],
            'shop_id'            => (int) $shop['id'],
            'status'             => 'shipped',
            'fulfillment_method' => 'delivery',
            'payment_status'     => 'paid',
            'payment_method'     => 'gcash',
            'subtotal'           => 300.00,
            'total_amount'       => 350.00,
            'shipping_fee'       => 50.00,
        ]);

        // Customer B tries to view Customer A's order tracking
        $result = $this->asCustomer((int) $customerB['id'], $customerB['email'])
            ->get('customer/orders/track/' . $orderNumber);

        $result->assertRedirectTo('customer/orders');
    }

    public function testPickupOrderRedirectsWithNotice()
    {
        $userModel = new UserModel();
        $customer = $userModel->where('role', 'customer')->first();
        $this->assertNotNull($customer);

        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);

        $orderModel = new OrderModel();
        $orderNumber = 'ORD-PICKUP-' . uniqid('', false) . rand(100, 999);
        $orderId = $orderModel->insert([
            'order_number'       => $orderNumber,
            'customer_id'        => (int) $customer['id'],
            'shop_id'            => (int) $shop['id'],
            'status'             => 'ready_for_pickup',
            'fulfillment_method' => 'pickup',
            'payment_status'     => 'paid',
            'payment_method'     => 'cash',
            'subtotal'           => 150.00,
            'total_amount'       => 150.00,
            'shipping_fee'       => 0.00,
        ]);

        // Accessing tracking for a pickup order should redirect with message
        $result = $this->asCustomer((int) $customer['id'], $customer['email'])
            ->get('customer/orders/track/' . $orderNumber);

        $result->assertRedirectTo('customer/orders');
    }

    public function testDoorstepDeliveryRendersDedicatedTrackingPage()
    {
        $userModel = new UserModel();
        $customer = $userModel->where('role', 'customer')->first();
        $this->assertNotNull($customer);

        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);

        $orderModel = new OrderModel();
        $orderItemModel = new OrderItemModel();
        $deliveryModel = new DeliveryModel();

        $orderNumber = 'ORD-TRACK-' . uniqid('', false) . rand(100, 999);
        $orderId = $orderModel->insert([
            'order_number'       => $orderNumber,
            'customer_id'        => (int) $customer['id'],
            'shop_id'            => (int) $shop['id'],
            'status'             => 'shipped',
            'fulfillment_method' => 'delivery',
            'payment_status'     => 'paid',
            'payment_method'     => 'online',
            'subtotal'           => 450.00,
            'total_amount'       => 500.00,
            'shipping_fee'       => 50.00,
            'placed_at'          => date('Y-m-d H:i:s', time() - 3600),
        ]);

        $orderItemModel->insert([
            'order_id'     => $orderId,
            'product_id'   => 1,
            'product_name' => 'Premium Thermal Paper Roll',
            'quantity'     => 2,
            'unit_price'   => 225.00,
            'total_price'  => 450.00,
        ]);

        $deliveryModel->insert([
            'deliverable_type'    => 'order',
            'deliverable_id'      => $orderId,
            'tracking_id'         => 'TRK-' . $orderNumber,
            'courier_name'        => 'Ronel Macaraeg',
            'destination_address' => 'Purok 3, Cannery Site, Polomolok',
            'status'              => 'shipped',
            'current_lat'         => 6.2290,
            'current_lng'         => 125.0710,
        ]);

        $result = $this->asCustomer((int) $customer['id'], $customer['email'])
            ->get('customer/orders/track/' . $orderNumber);

        $result->assertOK();
        $body = $result->response()->getBody();

        // 1. Navigation & Order info
        $this->assertStringContainsString('Back to My Orders', $body);
        $this->assertStringContainsString($orderNumber, $body);
        $this->assertStringContainsString('Out for Delivery', $body);

        // 2. Estimated Delivery & Progress
        $this->assertStringContainsString('Arrival Estimate', $body);
        $this->assertStringContainsString('Fulfillment Progress', $body);

        // 3. Milestone stepper
        $this->assertStringContainsString('Order Placed', $body);
        $this->assertStringContainsString('Order Confirmed &amp; Packed by Store', $body);
        $this->assertStringContainsString('Handed to Delivery Courier', $body);
        $this->assertStringContainsString('Out for Delivery', $body);
        $this->assertStringContainsString('Delivered', $body);

        // 5. Items Summary
        $this->assertStringContainsString('Premium Thermal Paper Roll', $body);

        // 6. Interactive Google Map
        $this->assertStringContainsString('id="trackMap"', $body);
        $this->assertStringContainsString('Center on Courier', $body);
        $this->assertStringContainsString('Live Route Tracking', $body);
        $this->assertStringContainsString('maps.googleapis.com', $body);
        $this->assertStringContainsString('STORE_COORDS', $body);
        $this->assertStringContainsString('COURIER_COORDS', $body);
        $this->assertStringContainsString('DEST_COORDS', $body);
    }

    public function testOrdersIndexViewContainsTrackOrderLink()
    {
        $userModel = new UserModel();
        $customer = $userModel->where('role', 'customer')->first();
        $this->assertNotNull($customer);

        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);

        $orderModel = new OrderModel();
        $orderNumber = 'ORD-BTN-' . uniqid('', false) . rand(100, 999);
        $orderModel->insert([
            'order_number'       => $orderNumber,
            'customer_id'        => (int) $customer['id'],
            'shop_id'            => (int) $shop['id'],
            'status'             => 'shipped',
            'fulfillment_method' => 'delivery',
            'payment_status'     => 'paid',
            'payment_method'     => 'gcash',
            'subtotal'           => 100.00,
            'total_amount'       => 150.00,
            'shipping_fee'       => 50.00,
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        $result = $this->asCustomer((int) $customer['id'], $customer['email'])
            ->get('customer/orders');

        $result->assertOK();
        $body = $result->response()->getBody();

        $this->assertStringContainsString('/customer/orders/track/' . $orderNumber, $body);
        $this->assertStringContainsString('Track Order', $body);
    }

    public function testPendingOrderShowsShippedInStepperAndHidesTrackButtonUntilShipped()
    {
        $userModel = new UserModel();
        $customer = $userModel->where('role', 'customer')->first();
        $this->assertNotNull($customer);

        \Config\Database::connect()->table('orders')->where('customer_id', (int) $customer['id'])->where('status', 'in_transit')->delete();

        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);

        $orderModel = new OrderModel();
        $orderNumber = 'ORD-BTN-' . uniqid('', false) . rand(100, 999);
        $orderId = $orderModel->insert([
            'order_number'       => $orderNumber,
            'customer_id'        => (int) $customer['id'],
            'shop_id'            => (int) $shop['id'],
            'status'             => 'pending',
            'fulfillment_method' => 'delivery',
            'payment_status'     => 'paid',
            'payment_method'     => 'gcash',
            'subtotal'           => 200.00,
            'total_amount'       => 250.00,
            'shipping_fee'       => 50.00,
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        // 1. Check orders index view: Stepper has "Shipped" and button shows "Tracking available once shipped"
        $result = $this->asCustomer((int) $customer['id'], $customer['email'])
            ->get('customer/orders');
        $result->assertOK();
        $body = $result->response()->getBody();

        // Must show 'Shipped' in timeline, NOT 'In Transit'
        $this->assertStringContainsString('Shipped', $body);
        $this->assertStringNotContainsString('In Transit', $body);

        // For this pending order, 'Track Order' link must NOT be present
        $this->assertStringNotContainsString('/customer/orders/track/' . $orderNumber, $body);
        $this->assertStringContainsString('Tracking available once shipped', $body);

        // 2. Direct GET to track endpoint on pending order must redirect back with notice
        $trackResult = $this->asCustomer((int) $customer['id'], $customer['email'])
            ->get('customer/orders/track/' . $orderNumber);
        $trackResult->assertRedirectTo('customer/orders');

        // 3. Mark as shipped -> Now Track Order link is visible and tracking page works
        $orderModel->update($orderId, ['status' => 'shipped']);

        $resultShipped = $this->asCustomer((int) $customer['id'], $customer['email'])
            ->get('customer/orders');
        $resultShipped->assertOK();
        $bodyShipped = $resultShipped->response()->getBody();

        $this->assertStringContainsString('/customer/orders/track/' . $orderNumber, $bodyShipped);
    }
}
