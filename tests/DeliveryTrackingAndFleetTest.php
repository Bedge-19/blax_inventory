<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\ShopModel;
use App\Models\UserModel;
use App\Models\DeliveryModel;
use App\Models\PrintingRequestModel;

class DeliveryTrackingAndFleetTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        $db = \Config\Database::connect();
        $db->table('deliveries')->like('tracking_id', 'TRK-FLEET-')->delete();
        $db->table('order_items')->whereIn('order_id', static function ($builder) {
            $builder->select('id')->from('orders')->like('order_number', 'ORD-FLEET-');
        })->delete();
        $db->table('orders')->like('order_number', 'ORD-FLEET-')->delete();
        $db->table('printing_requests')->like('request_number', 'PR-FLEET-')->delete();
        parent::tearDown();
    }

    private function asCustomer(int $userId, string $email = 'customer@test.com'): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => $userId,
            'user_name'  => 'Fleet Customer',
            'user_email' => $email,
            'user_role'  => 'customer',
            'role'       => 'customer',
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN'     => $hash,
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    private function asShopOwner(int $userId = 2, int $shopId = 1): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => $userId,
            'user_name'  => 'Fleet Tenant',
            'user_email' => 'tenant@test.com',
            'user_role'  => 'shop_owner',
            'role'       => 'shop_owner',
            'shop_id'    => $shopId,
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN'     => $hash,
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    private function asAdmin(): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => 1,
            'user_name'  => 'Admin User',
            'user_email' => 'admin@test.com',
            'user_role'  => 'admin',
            'role'       => 'admin',
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN'     => $hash,
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    public function testCustomerCanPollDeliveryPosition()
    {
        $userModel = new UserModel();
        $customer = $userModel->where('role', 'customer')->first();
        $this->assertNotNull($customer);

        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);

        $orderModel = new OrderModel();
        $deliveryModel = new DeliveryModel();

        $orderNumber = 'ORD-FLEET-' . uniqid('', false);
        $orderId = $orderModel->insert([
            'order_number'       => $orderNumber,
            'customer_id'        => (int) $customer['id'],
            'shop_id'            => (int) $shop['id'],
            'status'             => 'shipped',
            'fulfillment_method' => 'delivery',
            'payment_status'     => 'paid',
            'payment_method'     => 'cash',
            'subtotal'           => 200.00,
            'total_amount'       => 250.00,
            'shipping_fee'       => 50.00,
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        $deliveryId = $deliveryModel->insert([
            'deliverable_type'    => 'order',
            'deliverable_id'      => $orderId,
            'tracking_id'         => 'TRK-FLEET-' . uniqid('', false),
            'courier_name'        => 'Store Courier',
            'destination_address' => 'Poblacion, Polomolok',
            'status'              => 'shipped',
            'current_lat'         => 6.2205,
            'current_lng'         => 125.0645,
            'location_updated_at' => date('Y-m-d H:i:s'),
        ]);

        $result = $this->asCustomer((int) $customer['id'], $customer['email'])
            ->get('customer/orders/track/' . $orderNumber . '/position');

        $result->assertOK();
        $body = $result->response()->getBody();
        $json = json_decode($body, true);
        if ($json === null) {
            $this->fail('JSON decode failed: ' . $body);
        }
        $this->assertTrue($json['success']);
        $this->assertEquals(6.2205, (float) $json['lat']);
        $this->assertEquals(125.0645, (float) $json['lng']);
        $this->assertEquals('shipped', $json['status']);
    }

    public function testTenantCanUpdateDeliveryLocationWithinPolomolok()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);

        $ownerId = (int) ($shop['owner_id'] ?? $shop['user_id'] ?? 2);
        $shopId  = (int) $shop['id'];
        $orderModel = new OrderModel();
        $deliveryModel = new DeliveryModel();

        $orderNumber = 'ORD-FLEET-' . uniqid('', false);
        $orderId = $orderModel->insert([
            'order_number'       => $orderNumber,
            'customer_id'        => 14,
            'shop_id'            => $shopId,
            'status'             => 'shipped',
            'fulfillment_method' => 'delivery',
            'payment_status'     => 'paid',
            'payment_method'     => 'cash',
            'subtotal'           => 150.00,
            'total_amount'       => 150.00,
            'shipping_fee'       => 0.00,
        ]);

        $deliveryId = $deliveryModel->insert([
            'deliverable_type'    => 'order',
            'deliverable_id'      => $orderId,
            'tracking_id'         => 'TRK-FLEET-' . uniqid('', false),
            'courier_name'        => 'Shop Courier',
            'destination_address' => 'Cannery Site, Polomolok',
            'status'              => 'shipped',
            'current_lat'         => 6.2200,
            'current_lng'         => 125.0600,
        ]);

        // 1. Valid update within Polomolok bounds (lat: 6.2250, lng: 125.0680)
        $validPost = $this->asShopOwner($ownerId, $shopId)
            ->post('tenant/deliveries/update-location', [
                'delivery_id' => $deliveryId,
                'lat'         => 6.2250,
                'lng'         => 125.0680,
            ]);

        $validPost->assertOK();
        $validJson = json_decode($validPost->response()->getBody(), true);
        $this->assertTrue($validJson['success']);

        $updatedDelivery = $deliveryModel->find($deliveryId);
        $this->assertEquals(6.2250, (float) $updatedDelivery['current_lat']);
        $this->assertEquals(125.0680, (float) $updatedDelivery['current_lng']);
        $this->assertNotNull($updatedDelivery['location_updated_at']);

        // 2. Out-of-bounds update (Manila coords: 14.5995, 120.9842) should be rejected
        $invalidPost = $this->asShopOwner($ownerId, $shopId)
            ->post('tenant/deliveries/update-location', [
                'delivery_id' => $deliveryId,
                'lat'         => 14.5995,
                'lng'         => 120.9842,
            ]);

        $invalidPost->assertStatus(422);
        $invalidJson = json_decode($invalidPost->response()->getBody(), true);
        $this->assertFalse($invalidJson['success']);
        $this->assertStringContainsString('Polomolok', $invalidJson['error']);
    }

    public function testAdminTrackingPinsEndpointReturnsJsonWithGroupedShops()
    {
        $result = $this->asAdmin()->get('admin/tracking/pins');
        $result->assertOK();

        $json = json_decode($result->response()->getBody(), true);
        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('pins', $json);
        $this->assertArrayHasKey('groupedShops', $json);
        $this->assertArrayHasKey('kpis', $json);
        $this->assertArrayHasKey('count', $json);
        $this->assertArrayHasKey('total_active', $json['kpis']);
        $this->assertArrayHasKey('in_transit', $json['kpis']);
        $this->assertArrayHasKey('shipped', $json['kpis']);
        $this->assertArrayHasKey('active_shops', $json['kpis']);
    }

    public function testTenantPrintingFileResolutionRedirectsForNonexistent()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $ownerId = (int) ($shop['owner_id'] ?? $shop['user_id'] ?? 2);
        $shopId  = (int) $shop['id'];

        $result = $this->asShopOwner($ownerId, $shopId)
            ->get('tenant/printing/download/9999999');

        $result->assertRedirect();
    }

    public function testAdminAnalyticsRendersRedesignedIntelligenceDashboard()
    {
        $result = $this->asAdmin()->get('admin/analytics');
        $result->assertOK();

        $body = $result->response()->getBody();
        $this->assertStringContainsString('Platform Analytics &amp; Intelligence', $body);
        $this->assertStringContainsString('Admin Revenue Growth &amp; Fee Velocity', $body);
        $this->assertStringContainsString('Marketplace GMV', $body);
        $this->assertStringContainsString('Channel &amp; Logistics Share', $body);
        $this->assertStringContainsString('Merchant Performance Leaderboard', $body);
        $this->assertStringContainsString('Polomolok Logistics Hub', $body);
        $this->assertStringNotContainsString('prototype placeholder', $body);
        $this->assertStringNotContainsString('No revenue — volume only', $body);
    }

    public function testAdminAnalyticsDataReturnsRangeStats()
    {
        foreach (['7', '30', 'year'] as $range) {
            $result = $this->asAdmin()->get('admin/analytics/data?range=' . $range);
            $result->assertOK();

            $json = json_decode($result->response()->getBody(), true);
            $this->assertTrue($json['success']);
            $this->assertEquals($range, $json['range']);
            $this->assertArrayHasKey('labels', $json);
            $this->assertArrayHasKey('values', $json);
            $this->assertArrayHasKey('total', $json);
            $this->assertArrayHasKey('avg', $json);
            $this->assertArrayHasKey('peak', $json);
        }
    }

    public function testAiAssistantHandlesPlatformFaqPromptsAccurately()
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        // 1. Test Delivery Tracking & Pickup QR prompt
        $trackingResult = $this->withHeaders([
            'X-CSRF-TOKEN'     => $hash,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('ai-assistant/chat', [
            'message' => 'How do I track my delivery or use store pickup QR code?',
        ]);

        $trackingResult->assertOK();
        $trackingJson = json_decode($trackingResult->response()->getBody(), true);
        $this->assertEquals('success', $trackingJson['status']);
        $this->assertEquals('order_tracking', $trackingJson['intent']);
        $this->assertStringContainsString('Live Delivery Tracking', $trackingJson['reply']);
        $this->assertStringContainsString('Store Pick-up QR Code', $trackingJson['reply']);
        $this->assertStringNotContainsString('Pasayloa', $trackingJson['reply']);
        $this->assertNotEmpty($trackingJson['actions']);
        $this->assertStringContainsString('customer/orders', $trackingJson['actions'][0]['url']);

        // 2. Test Printing prompt
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;
        $printResult = $this->withHeaders([
            'X-CSRF-TOKEN'     => $hash,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('ai-assistant/chat', [
            'message' => 'How do custom PDF printing requests and downpayments work?',
        ]);

        $printResult->assertOK();
        $printJson = json_decode($printResult->response()->getBody(), true);
        $this->assertEquals('success', $printJson['status']);
        $this->assertEquals('printing_service', $printJson['intent']);
        $this->assertStringContainsString('Printing Services', $printJson['reply']);
        $this->assertNotEmpty($printJson['actions']);
        $this->assertStringContainsString('printing-services', $printJson['actions'][0]['url']);

        // 3. Test Verified Shops prompt
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;
        $shopResult = $this->withHeaders([
            'X-CSRF-TOKEN'     => $hash,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('ai-assistant/chat', [
            'message' => 'Show verified shops and print partners in Polomolok',
        ]);

        $shopResult->assertOK();
        $shopJson = json_decode($shopResult->response()->getBody(), true);
        $this->assertEquals('success', $shopJson['status']);
        $this->assertEquals('shop_directory', $shopJson['intent']);
        $this->assertStringContainsString('Shops Directory', $shopJson['reply']);
        $this->assertNotEmpty($shopJson['actions']);
        $this->assertStringContainsString('shops', $shopJson['actions'][0]['url']);

        // 4. Test Merchandise/Supplies prompt
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;
        $productResult = $this->withHeaders([
            'X-CSRF-TOKEN'     => $hash,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('ai-assistant/chat', [
            'message' => 'What school, office, and merchandise products are available?',
        ]);

        $productResult->assertOK();
        $productJson = json_decode($productResult->response()->getBody(), true);
        $this->assertEquals('success', $productJson['status']);
        $this->assertEquals('product_search', $productJson['intent']);
        $this->assertStringContainsString('school supplies, office stationery, and merchandise', $productJson['reply']);
    }
}
