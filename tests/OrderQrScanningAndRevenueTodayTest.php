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
        // Ensure test order ORD-TEST-QR1 is ready_for_pickup for shop_id 1
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
                'status'             => 'ready_for_pickup',
                'payment_status'     => 'unpaid',
                'placed_at'          => date('Y-m-d H:i:s'),
            ]);
        } else {
            $db->table('orders')->where('order_number', 'ORD-TEST-QR1')->update([
                'status'             => 'ready_for_pickup',
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

    public function testPosVerifyQrRejectsProcessingAndPendingOrdersWith400()
    {
        $db = \Config\Database::connect();
        // 1. Test processing order
        $db->table('orders')->where('order_number', 'ORD-TEST-QR1')->update(['status' => 'processing']);
        $res = $this->asTenant(1)->post('tenant/pos/verify-qr', [
            'qr_code' => 'ORD-TEST-QR1',
        ]);
        $res->assertStatus(400);
        $json = json_decode($res->response()->getBody(), true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('PROCESSING', $json['error']);

        // 2. Test pending order
        $db->table('orders')->where('order_number', 'ORD-TEST-QR1')->update(['status' => 'pending']);
        $res2 = $this->asTenant(1)->post('tenant/pos/verify-qr', [
            'qr_code' => 'ORD-TEST-QR1',
        ]);
        $res2->assertStatus(400);
        $json2 = json_decode($res2->response()->getBody(), true);
        $this->assertFalse($json2['success']);
        $this->assertStringContainsString('PENDING', $json2['error']);

        // Restore to ready_for_pickup
        $db->table('orders')->where('order_number', 'ORD-TEST-QR1')->update(['status' => 'ready_for_pickup']);
    }

    public function testDeliveryLookupRejectsProcessingAndPendingOrdersWith400()
    {
        $db = \Config\Database::connect();
        // Create an active delivery order for shop 1 in processing status
        $order = $db->table('orders')->where('order_number', 'ORD-TEST-PROC1')->get()->getRowArray();
        if (!$order) {
            $db->table('orders')->insert([
                'order_number'       => 'ORD-TEST-PROC1',
                'customer_id'        => 3,
                'shop_id'            => 1,
                'fulfillment_method' => 'delivery',
                'payment_method'     => 'cod',
                'subtotal'           => 500.00,
                'shipping_fee'       => 50.00,
                'tax_amount'         => 0.00,
                'total_amount'       => 550.00,
                'status'             => 'processing',
                'payment_status'     => 'unpaid',
                'placed_at'          => date('Y-m-d H:i:s'),
            ]);
            $ordId = $db->insertID();
        } else {
            $ordId = (int) $order['id'];
            $db->table('orders')->where('id', $ordId)->update(['status' => 'processing']);
        }
        $db->table('deliveries')->where('deliverable_type', 'order')->where('deliverable_id', $ordId)->delete();

        $res = $this->asTenant(1)->post('tenant/deliveries/lookup', [
            'tracking_id' => 'ORD-TEST-PROC1',
        ]);
        $res->assertStatus(400);
        $json = json_decode($res->response()->getBody(), true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('PROCESSING', $json['error']);
    }

    public function testPosAndDeliveryLookupRejectInProductionPrintingRequestWith400()
    {
        $db = \Config\Database::connect();
        $pr = $db->table('printing_requests')->where('request_number', 'PR-TEST-PROD1')->get()->getRowArray();
        if (!$pr) {
            $db->table('printing_requests')->insert([
                'request_number'       => 'PR-TEST-PROD1',
                'customer_id'          => 3,
                'shop_id'              => 1,
                'file_name'            => 'thesis.pdf',
                'file_url'             => 'uploads/thesis.pdf',
                'page_count'           => 10,
                'copies'               => 1,
                'fulfillment_method'   => 'pickup',
                'total_price'          => 50.00,
                'down_payment'         => 25.00,
                'status'               => 'in_production',
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
            $prId = $db->insertID();
        } else {
            $prId = (int) $pr['id'];
            $db->table('printing_requests')->where('id', $prId)->update(['status' => 'in_production']);
        }

        // 1. POS scan rejection for in_production printing request
        $resPos = $this->asTenant(1)->post('tenant/pos/verify-qr', [
            'qr_code' => 'PR-TEST-PROD1',
        ]);
        $resPos->assertStatus(400);
        $jsonPos = json_decode($resPos->response()->getBody(), true);
        $this->assertFalse($jsonPos['success']);
        $this->assertStringContainsString('IN PRODUCTION', $jsonPos['error']);

        // 2. Deliveries scan rejection for in_production printing request
        $resDel = $this->asTenant(1)->post('tenant/deliveries/lookup', [
            'tracking_id' => 'PR-TEST-PROD1',
        ]);
        $resDel->assertStatus(400);
        $jsonDel = json_decode($resDel->response()->getBody(), true);
        $this->assertFalse($jsonDel['success']);
        $this->assertStringContainsString('IN PRODUCTION', $jsonDel['error']);
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

    public function testDeliveryLookupProcessesPickupOrderSuccessfully()
    {
        $db = \Config\Database::connect();
        // ORD-TEST-QR1 is a ready_for_pickup order
        $db->table('orders')->where('order_number', 'ORD-TEST-QR1')->update(['status' => 'ready_for_pickup']);
        $db->table('deliveries')->where('tracking_id', 'ORD-TEST-QR1')->delete();

        $res = $this->asTenant(1)->post('tenant/deliveries/lookup', [
            'tracking_id' => 'ORD-TEST-QR1',
        ]);
        $res->assertOK();
        $json = json_decode($res->response()->getBody(), true);
        $this->assertTrue($json['success']);
        $this->assertNotEmpty($json['delivery']);
    }

    public function testDeliveryLookupFindsByNumericIdAndRequestNumber()
    {
        $db = \Config\Database::connect();
        // Find or create ready_for_pickup order for shop 1
        $ord = $db->table('orders')->where('shop_id', 1)->whereIn('status', ['ready_for_pickup', 'shipped'])->get()->getRowArray();
        if (!$ord) {
            $db->table('orders')->where('order_number', 'ORD-TEST-QR1')->update(['status' => 'ready_for_pickup']);
            $ord = $db->table('orders')->where('order_number', 'ORD-TEST-QR1')->get()->getRowArray();
        }
        if ($ord) {
            $db->table('deliveries')->where('tracking_id', $ord['order_number'])->delete();
            // Test lookup by numeric ID
            $res = $this->asTenant(1)->post('tenant/deliveries/lookup', [
                'tracking_id' => (string) $ord['id'],
            ]);
            $res->assertOK();
            $json = json_decode($res->response()->getBody(), true);
            $this->assertTrue($json['success']);
        }
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

    public function testTenantDeliveryDetailRendersWithoutQueryErrors()
    {
        $db = \Config\Database::connect();
        // Get or create a delivery for shop 1
        $delivery = $db->table('deliveries d')
            ->select('d.*')
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'inner')
            ->where('o.shop_id', 1)
            ->get()->getRowArray();

        if ($delivery) {
            $res = $this->asTenant(1)->get('tenant/deliveries/' . $delivery['id']);
            $res->assertOK();
            $this->assertStringContainsString('Live Route', $res->response()->getBody());
        }
    }

    public function testPosVerifyQrHandlesCompletedOrderGracefully()
    {
        $db = \Config\Database::connect();
        $num = 'ORD-DONE-' . rand(1000, 9999);
        $db->table('orders')->insert([
            'order_number'       => $num,
            'customer_id'        => 3,
            'shop_id'            => 1,
            'fulfillment_method' => 'pickup',
            'payment_method'     => 'counter_cash',
            'subtotal'           => 150.00,
            'shipping_fee'       => 0.00,
            'tax_amount'         => 0.00,
            'total_amount'       => 150.00,
            'status'             => 'completed',
            'payment_status'     => 'paid',
            'placed_at'          => date('Y-m-d H:i:s'),
            'completed_at'       => date('Y-m-d H:i:s'),
        ]);

        $res = $this->asTenant(1)->post('tenant/pos/verify-qr', [
            'qr_code' => $num,
        ]);
        $res->assertOK();
        $json = json_decode($res->response()->getBody(), true);
        $this->assertTrue($json['success']);
        $this->assertTrue($json['already_done']);
        $this->assertStringContainsString('already been completed', $json['message']);
    }

    public function testPosVerifyQrSupportsPrintingRequestAndTokens()
    {
        $db = \Config\Database::connect();
        $reqNum = 'PR-TEST-' . time() . '-' . rand(1000, 9999);
        $db->table('printing_requests')->insert([
            'request_number'     => $reqNum,
            'customer_id'        => 3,
            'shop_id'            => 1,
            'file_name'          => 'test.pdf',
            'file_url'           => 'uploads/printing/test.pdf',
            'paper_size'         => 'A4',
            'color_mode'         => 'bw',
            'page_count'         => 10,
            'copies'             => 1,
            'total_price'        => 50.00,
            'fulfillment_method' => 'pickup',
            'status'             => 'ready',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $res = $this->asTenant(1)->post('tenant/pos/verify-qr', [
            'qr_code' => $reqNum,
        ]);
        $res->assertOK();
        $json = json_decode($res->response()->getBody(), true);
        $this->assertTrue($json['success']);
        $this->assertTrue($json['is_printing']);
        $this->assertStringContainsString('Printing Request', $json['message']);
    }
}

