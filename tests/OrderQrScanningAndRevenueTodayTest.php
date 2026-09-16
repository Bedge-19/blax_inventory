<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\OrderModel;
use App\Models\DeliveryModel;

class OrderQrScanningAndRevenueTodayTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        $db = \Config\Database::connect();
        $db->table('deliveries')->like('tracking_id', 'ORD-REV-')->orLike('tracking_id', 'ORD-TEST-')->delete();
        $db->table('orders')->like('order_number', 'ORD-REV-')->delete();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $db = \Config\Database::connect();
        
        // Ensure ORD-88102 (belongs to shop 12)
        if (!$db->table('orders')->where('order_number', 'ORD-88102')->get()->getRowArray()) {
            $db->table('orders')->insert([
                'order_number'       => 'ORD-88102',
                'customer_id'        => 3,
                'shop_id'            => 12,
                'fulfillment_method' => 'pickup',
                'payment_method'     => 'counter_cash',
                'subtotal'           => 200.00,
                'shipping_fee'       => 0.00,
                'tax_amount'         => 0.00,
                'total_amount'       => 200.00,
                'status'             => 'processing',
                'payment_status'     => 'unpaid',
                'placed_at'          => date('Y-m-d H:i:s'),
            ]);
        }

        // Ensure ORD-0922 (delivery order for shop 1)
        if (!$db->table('orders')->where('order_number', 'ORD-0922')->get()->getRowArray()) {
            $db->table('orders')->insert([
                'order_number'       => 'ORD-0922',
                'customer_id'        => 3,
                'shop_id'            => 1,
                'fulfillment_method' => 'delivery',
                'payment_method'     => 'cod',
                'subtotal'           => 150.00,
                'shipping_fee'       => 50.00,
                'tax_amount'         => 0.00,
                'total_amount'       => 200.00,
                'status'             => 'processing',
                'payment_status'     => 'unpaid',
                'placed_at'          => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function asTenant(int $shopId = 1): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => 2,
            'user_role'  => 'shop_owner',
            'shop_id'    => $shopId,
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN'     => $hash,
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    public function testPosVerifyQrSupportsPrefixedAndJsonPayloads()
    {
        $db = \Config\Database::connect();
        // Ensure test order ORD-TEST-QR1 is processing pickup for shop_id 1
        $existing = $db->table('orders')->where('order_number', 'ORD-TEST-QR1')->get()->getRowArray();
        if (!$existing) {
            $db->table('orders')->insert([
                'order_number'       => 'ORD-TEST-QR1',
                'customer_id'        => 3,
                'shop_id'            => 1,
                'fulfillment_method' => 'pickup',
                'payment_method'     => 'counter_cash',
                'subtotal'           => 350.00,
                'shipping_fee'       => 0.00,
                'tax_amount'         => 0.00,
                'total_amount'       => 350.00,
                'status'             => 'processing',
                'payment_status'     => 'unpaid',
                'placed_at'          => date('Y-m-d H:i:s'),
            ]);
        } else {
            $db->table('orders')->where('order_number', 'ORD-TEST-QR1')->update([
                'status' => 'processing',
                'fulfillment_method' => 'pickup',
            ]);
        }

        // 1. Test scanning with leading '#'
        $resHash = $this->asTenant(1)->post('tenant/pos/verify-qr', [
            'qr_code' => '#ORD-TEST-QR1',
        ]);
        $resHash->assertOK();
        $jsonHash = json_decode($resHash->response()->getBody(), true);
        $this->assertTrue($jsonHash['success']);
        $this->assertSame('ORD-TEST-QR1', $jsonHash['order_number']);

        // 2. Test scanning JSON payload
        $resJson = $this->asTenant(1)->post('tenant/pos/verify-qr', [
            'qr_code' => json_encode(['order_no' => 'ORD-TEST-QR1', 'tenant_id' => 1]),
        ]);
        $resJson->assertOK();
        $jsonPayload = json_decode($resJson->response()->getBody(), true);
        $this->assertTrue($jsonPayload['success']);
        $this->assertSame('ORD-TEST-QR1', $jsonPayload['order_number']);
    }

    public function testPosVerifyQrRejectsDoorstepDeliveryWithClearMessage()
    {
        $res = $this->asTenant(1)->post('tenant/pos/verify-qr', [
            'qr_code' => 'ORD-0922',
        ]);
        $res->assertStatus(400);
        $json = json_decode($res->response()->getBody(), true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('Wrong fulfillment type', $json['error']);
        $this->assertStringContainsString('Doorstep Delivery', $json['error']);
    }

    public function testDeliveryLookupSupportsHashAndJsonAndUpdatesToDelivered()
    {
        $db = \Config\Database::connect();
        // Create an active delivery order for shop 1
        $order = $db->table('orders')->where('order_number', 'ORD-TEST-DEL1')->get()->getRowArray();
        if (!$order) {
            $db->table('orders')->insert([
                'order_number'       => 'ORD-TEST-DEL1',
                'customer_id'        => 3,
                'shop_id'            => 1,
                'fulfillment_method' => 'delivery',
                'payment_method'     => 'cod',
                'subtotal'           => 500.00,
                'shipping_fee'       => 50.00,
                'tax_amount'         => 0.00,
                'total_amount'       => 550.00,
                'status'             => 'shipped',
                'payment_status'     => 'unpaid',
                'placed_at'          => date('Y-m-d H:i:s'),
            ]);
            $ordId = $db->insertID();
        } else {
            $ordId = (int) $order['id'];
            $db->table('orders')->where('id', $ordId)->update(['status' => 'shipped']);
        }
        $db->table('deliveries')->where('deliverable_type', 'order')->where('deliverable_id', $ordId)->delete();

        // Test delivery lookup with '#' prefix
        $res = $this->asTenant(1)->post('tenant/deliveries/lookup', [
            'tracking_id' => '#ORD-TEST-DEL1',
        ]);
        $res->assertOK();
        $json = json_decode($res->response()->getBody(), true);
        $this->assertTrue($json['success']);
        $this->assertTrue($json['status_updated']);
        $this->assertSame('delivered', $json['new_status']);

        // Check database status
        $updatedOrder = $db->table('orders')->where('id', $ordId)->get()->getRowArray();
        $this->assertSame('delivered', $updatedOrder['status']);

        // Subsequent scan returns already delivered
        $res2 = $this->asTenant(1)->post('tenant/deliveries/lookup', [
            'tracking_id' => 'ORD-TEST-DEL1',
        ]);
        $res2->assertOK();
        $json2 = json_decode($res2->response()->getBody(), true);
        $this->assertTrue($json2['success']);
        $this->assertFalse($json2['status_updated']);
        $this->assertSame('already_delivered', $json2['action_type']);
    }

    public function testDeliveryLookupRejectsPickupOrderWithClearMessage()
    {
        $db = \Config\Database::connect();
        // ORD-TEST-QR1 is a pickup order
        $res = $this->asTenant(1)->post('tenant/deliveries/lookup', [
            'tracking_id' => 'ORD-TEST-QR1',
        ]);
        $res->assertStatus(400);
        $json = json_decode($res->response()->getBody(), true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('Wrong fulfillment type', $json['error']);
        $this->assertStringContainsString('Store Pick-up', $json['error']);
    }

    public function testDeliveryLookupRejectsCrossShopOrderWith403()
    {
        // ORD-88102 belongs to shop_id 12, whereas tenant is shop_id 1
        $res = $this->asTenant(1)->post('tenant/deliveries/lookup', [
            'tracking_id' => 'ORD-88102',
        ]);
        $res->assertStatus(403);
        $json = json_decode($res->response()->getBody(), true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('belongs to another shop', $json['error']);
    }

    public function testRevenueTodayIncludesCompletedAndPaidSales()
    {
        $orderModel = new OrderModel();
        $initialRev = $orderModel->getRevenueTodayForShop(1);
        $this->assertIsFloat($initialRev);

        $db = \Config\Database::connect();
        // Insert order completed today
        $num = 'ORD-REV-' . uniqid('', false) . rand(100, 999);
        $db->table('orders')->insert([
            'order_number'       => $num,
            'customer_id'        => 3,
            'shop_id'            => 1,
            'fulfillment_method' => 'pickup',
            'payment_method'     => 'counter_cash',
            'subtotal'           => 120.00,
            'shipping_fee'       => 0.00,
            'tax_amount'         => 0.00,
            'total_amount'       => 120.00,
            'status'             => 'completed',
            'payment_status'     => 'paid',
            'placed_at'          => date('Y-m-d H:i:s'),
            'completed_at'       => date('Y-m-d H:i:s'),
        ]);

        $newRev = $orderModel->getRevenueTodayForShop(1);
        $this->assertEquals(round($initialRev + 120.00, 2), $newRev);

        // Orders summary includes this in 'revenue_today'
        $summary = $orderModel->getOrdersSummary(1);
        $this->assertEquals($newRev, $summary['revenue_today']);
    }
}
