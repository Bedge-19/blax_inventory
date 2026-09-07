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

    private function asTenant(): self
    {
        return $this->withSession([
            'user_id'    => 2,
            'user_role'  => 'shop_owner',
            'shop_id'    => 1,
            'isLoggedIn' => true,
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
        $this->assertStringContainsString('still pending', $json['error']);
    }
}
