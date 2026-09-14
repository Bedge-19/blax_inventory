<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\ShopModel;
use App\Models\UserModel;
use App\Models\OrderModel;
use App\Models\PrintingRequestModel;
use App\Models\DeliveryModel;
use App\Services\TextBeeService;

class TenantOrdersAndTextBeeSmsTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
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

    public function testPhoneNumberNormalization()
    {
        $service = new TextBeeService();

        // Philippine mobile number standard formats
        $this->assertEquals('+639171234567', $service->normalizePhoneNumber('09171234567'));
        $this->assertEquals('+639171234567', $service->normalizePhoneNumber('9171234567'));
        $this->assertEquals('+639171234567', $service->normalizePhoneNumber('639171234567'));
        $this->assertEquals('+639171234567', $service->normalizePhoneNumber('+639171234567'));
        $this->assertEquals('+639171234567', $service->normalizePhoneNumber(' 0917-123-4567 '));
        $this->assertEquals('+639171234567', $service->normalizePhoneNumber('(0917) 123 4567'));
        $this->assertEquals('+639171234567', $service->normalizePhoneNumber('+63 917 123 4567'));

        // International standard format
        $this->assertEquals('+14155552671', $service->normalizePhoneNumber('+14155552671'));

        // Invalid inputs
        $this->assertNull($service->normalizePhoneNumber(''));
        $this->assertNull($service->normalizePhoneNumber(null));
        $this->assertNull($service->normalizePhoneNumber('N/A'));
        $this->assertNull($service->normalizePhoneNumber('12345'));
        $this->assertNull($service->normalizePhoneNumber('invalid-phone'));
    }

    public function testTenantOrdersTableDisplaysFulfillmentColumn()
    {
        $result = $this->asTenant(1)->get('tenant/orders');
        $result->assertOK();

        $body = $result->getBody();

        // Check header column exists
        $this->assertStringContainsString('Fulfillment', $body);

        // Check delivery badges or empty state rendered
        $hasDeliveryBadge = str_contains($body, 'Doorstep Delivery') || str_contains($body, 'Store Pick-up') || str_contains($body, 'No orders match your filters');
        $this->assertTrue($hasDeliveryBadge, 'Orders table must display Fulfillment badges or empty state');
    }

    public function testUpdateOrderStatusRejectsCrossFulfillmentStatuses()
    {
        $orderModel = new OrderModel();
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $customer = (new UserModel())->where('role', 'customer')->first();
        $this->assertNotNull($customer);

        // 1. Create a Store Pick-up order
        $pickupOrderId = $orderModel->insert([
            'order_number'       => 'TEST-PKP-' . time(),
            'customer_id'        => $customer['id'],
            'shop_id'            => $shop['id'],
            'fulfillment_method' => 'pickup',
            'payment_method'     => 'pickup',
            'subtotal'           => 100.00,
            'shipping_fee'       => 0.00,
            'tax_amount'         => 0.00,
            'total_amount'       => 100.00,
            'status'             => 'processing',
            'payment_status'     => 'unpaid',
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        // Attempt to set Store Pick-up order to 'shipped' (must be rejected)
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;
        $res = $this->asTenant((int) $shop['id'])->post('tenant/orders/update-status', [
            'order_id'       => $pickupOrderId,
            'status'         => 'shipped',
            csrf_token()     => $hash,
        ]);

        $res->assertSessionHas('error');
        $unmodifiedOrder = $orderModel->find($pickupOrderId);
        $this->assertEquals('processing', $unmodifiedOrder['status'], 'Store Pick-up order should NOT transition to shipped');

        // 2. Create a Doorstep Delivery order
        $deliveryOrderId = $orderModel->insert([
            'order_number'       => 'TEST-DEL-' . time(),
            'customer_id'        => $customer['id'],
            'shop_id'            => $shop['id'],
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

        // Attempt to set Doorstep Delivery order to 'ready_for_pickup' (must be rejected)
        $hash2 = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash2;
        $res2 = $this->asTenant((int) $shop['id'])->post('tenant/orders/update-status', [
            'order_id'       => $deliveryOrderId,
            'status'         => 'ready_for_pickup',
            csrf_token()     => $hash2,
        ]);

        $res2->assertSessionHas('error');
        $unmodifiedDelOrder = $orderModel->find($deliveryOrderId);
        $this->assertEquals('processing', $unmodifiedDelOrder['status'], 'Doorstep Delivery order should NOT transition to ready_for_pickup');

        // Clean up
        $orderModel->delete($pickupOrderId);
        $orderModel->delete($deliveryOrderId);
    }

    public function testUpdateOrderStatusAllowsValidStatusesAndTriggersWorkflow()
    {
        $orderModel = new OrderModel();
        $shop = (new ShopModel())->first();
        $customer = (new UserModel())->where('role', 'customer')->first();

        // 1. Valid Pick-up transition: processing -> ready_for_pickup
        $pickupOrderId = $orderModel->insert([
            'order_number'       => 'TEST-PKP2-' . time(),
            'customer_id'        => $customer['id'],
            'shop_id'            => $shop['id'],
            'fulfillment_method' => 'pickup',
            'payment_method'     => 'pickup',
            'subtotal'           => 120.00,
            'shipping_fee'       => 0.00,
            'total_amount'       => 120.00,
            'status'             => 'processing',
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;
        $res = $this->asTenant((int) $shop['id'])->post('tenant/orders/update-status', [
            'order_id'       => $pickupOrderId,
            'status'         => 'ready_for_pickup',
            csrf_token()     => $hash,
        ]);

        $res->assertSessionHas('success');
        $updatedPkp = $orderModel->find($pickupOrderId);
        $this->assertEquals('ready_for_pickup', $updatedPkp['status']);

        // 2. Valid Delivery transition: processing -> shipped
        $deliveryOrderId = $orderModel->insert([
            'order_number'       => 'TEST-DEL2-' . time(),
            'customer_id'        => $customer['id'],
            'shop_id'            => $shop['id'],
            'fulfillment_method' => 'delivery',
            'payment_method'     => 'cod',
            'subtotal'           => 200.00,
            'shipping_fee'       => 50.00,
            'total_amount'       => 250.00,
            'status'             => 'processing',
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        $hash2 = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash2;
        $res2 = $this->asTenant((int) $shop['id'])->post('tenant/orders/update-status', [
            'order_id'       => $deliveryOrderId,
            'status'         => 'shipped',
            csrf_token()     => $hash2,
        ]);

        $updatedDel = $orderModel->find($deliveryOrderId);
        $this->assertEquals('shipped', $updatedDel['status']);

        // Verify delivery record was created for shipped delivery order
        $delModel = new DeliveryModel();
        $delRecord = $delModel->where('deliverable_type', 'order')->where('deliverable_id', $deliveryOrderId)->first();
        $this->assertNotNull($delRecord);
        $this->assertEquals('shipped', $delRecord['status']);

        // Clean up
        $delModel->delete($delRecord['id']);
        $orderModel->delete($pickupOrderId);
        $orderModel->delete($deliveryOrderId);
    }

    public function testTextBeeNotificationHelpers()
    {
        $service = new TextBeeService();
        $shop = (new ShopModel())->first();
        $customer = (new UserModel())->where('role', 'customer')->first();

        // Test with invalid status (should cleanly return false without error)
        $dummyOrder = [
            'id'             => 99999,
            'order_number'   => 'ORD-99999',
            'customer_id'    => $customer['id'],
            'shop_id'        => $shop['id'],
            'customer_phone' => '09171234567',
        ];

        $res = $service->sendOrderNotification($dummyOrder, 'processing', $shop);
        $this->assertFalse($res['success']);
        $this->assertEquals('Status does not trigger SMS notification', $res['error']);

        // Test with no phone available
        $dummyNoPhone = [
            'id'             => 99998,
            'order_number'   => 'ORD-99998',
            'customer_id'    => null,
            'shop_id'        => $shop['id'],
            'customer_phone' => null,
        ];
        $res2 = $service->sendOrderNotification($dummyNoPhone, 'ready_for_pickup', $shop);
        $this->assertFalse($res2['success']);
        $this->assertEquals('Customer phone number not available', $res2['error']);

        // Test printing notification with non-triggering status
        $dummyReq = [
            'id'                 => 99997,
            'request_number'     => 'PR-99997',
            'customer_id'        => $customer['id'],
            'shop_id'            => $shop['id'],
            'fulfillment_method' => 'pickup',
        ];
        $res3 = $service->sendPrintingNotification($dummyReq, 'in_production', $shop);
        $this->assertFalse($res3['success']);
        $this->assertEquals('Printing status does not trigger SMS notification', $res3['error']);
    }
}
