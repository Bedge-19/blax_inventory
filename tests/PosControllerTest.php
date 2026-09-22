<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class PosControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $db = \Config\Database::connect();
        
        // Ensure ORD-88102 (belongs to shop 12)
        if ($existing = $db->table('orders')->where('order_number', 'ORD-88102')->get()->getRowArray()) {
            $db->table('orders')->where('id', $existing['id'])->update(['shop_id' => 12, 'fulfillment_method' => 'pickup', 'status' => 'processing']);
        } else {
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
        if ($existing = $db->table('orders')->where('order_number', 'ORD-0922')->get()->getRowArray()) {
            $db->table('orders')->where('id', $existing['id'])->update(['shop_id' => 1, 'fulfillment_method' => 'delivery', 'status' => 'processing']);
        } else {
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

        // Ensure ORD-90097 (pending order for shop 1)
        if ($existing = $db->table('orders')->where('order_number', 'ORD-90097')->get()->getRowArray()) {
            $db->table('orders')->where('id', $existing['id'])->update(['shop_id' => 1, 'fulfillment_method' => 'pickup', 'status' => 'pending']);
        } else {
            $db->table('orders')->insert([
                'order_number'       => 'ORD-90097',
                'customer_id'        => 3,
                'shop_id'            => 1,
                'fulfillment_method' => 'pickup',
                'payment_method'     => 'counter_cash',
                'subtotal'           => 100.00,
                'shipping_fee'       => 0.00,
                'tax_amount'         => 0.00,
                'total_amount'       => 100.00,
                'status'             => 'pending',
                'payment_status'     => 'unpaid',
                'placed_at'          => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function asTenant(): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => 2,
            'user_role'  => 'shop_owner',
            'shop_id'    => 1,
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN' => $hash,
        ]);
    }

    public function testPosPageRendersWalkinMode()
    {
        $result = $this->asTenant()->get('tenant/pos');
        $result->assertOK();
        $this->assertStringContainsString('Walk-in Counter POS', $result->getBody());
        $this->assertStringContainsString('Find Products', $result->getBody());
        $this->assertStringContainsString('Counter Cart', $result->getBody());
    }

    public function testPosProductSearchReturnsResults()
    {
        $result = $this->asTenant()->get('tenant/pos/search-products?q=');
        $result->assertOK();
        $json = json_decode($result->response()->getBody(), true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success']);
        $this->assertIsArray($json['products']);
    }

    public function testPosCompleteWalkinValidationRejectsEmptyItems()
    {
        $result = $this->asTenant()->post('tenant/pos/complete-walkin', [
            'items' => json_encode([]),
            'counter_payment_method' => 'cash',
        ]);
        $result->assertStatus(400);
        $json = json_decode($result->response()->getBody(), true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success']);
    }

    public function testPosPageShowsScanCustomerQrButton()
    {
        $result = $this->asTenant()->get('tenant/pos');
        $result->assertOK();
        $this->assertStringContainsString('Scan Customer QR', $result->getBody());
        $this->assertStringContainsString('posQrScannerModal', $result->getBody());
    }

    public function testPosVerifyQrRejectsEmptyCode()
    {
        $result = $this->asTenant()->post('tenant/pos/verify-qr', [
            'qr_code' => '',
        ]);
        $result->assertStatus(400);
        $json = json_decode($result->response()->getBody(), true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('No QR code', $json['error']);
    }

    public function testPosVerifyQrRejectsInvalidOrder()
    {
        $result = $this->asTenant()->post('tenant/pos/verify-qr', [
            'qr_code' => 'ORD-INVALID-99999',
        ]);
        $result->assertStatus(404);
        $json = json_decode($result->response()->getBody(), true);
        $this->assertFalse($json['success']);
    }

    public function testPosVerifyQrRejectsCrossShopOrder()
    {
        // Order ORD-88102 belongs to shop_id 12, whereas tenant is shop_id 1
        $result = $this->asTenant()->post('tenant/pos/verify-qr', [
            'qr_code' => 'ORD-88102',
        ]);
        $result->assertStatus(403);
        $json = json_decode($result->response()->getBody(), true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('belongs to another shop', $json['error']);
    }

    public function testPosVerifyQrRejectsDeliveryOrder()
    {
        // Order ORD-88294 or ORD-0922 belongs to shop_id 1, but fulfillment is delivery
        $result = $this->asTenant()->post('tenant/pos/verify-qr', [
            'qr_code' => 'ORD-0922',
        ]);
        $result->assertStatus(400);
        $json = json_decode($result->response()->getBody(), true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('Doorstep Delivery', $json['error']);
    }

    public function testPosVerifyQrRejectsPendingOrder()
    {
        // Order ORD-90097 is pickup for shop_id 1, but status is pending
        $result = $this->asTenant()->post('tenant/pos/verify-qr', [
            'qr_code' => 'ORD-90097',
        ]);
        $result->assertStatus(400);
        $json = json_decode($result->response()->getBody(), true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('PENDING', $json['error']);
    }
}
