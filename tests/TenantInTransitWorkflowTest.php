<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\ShopModel;
use App\Models\UserModel;
use App\Models\OrderModel;
use App\Models\PrintingRequestModel;
use App\Models\DeliveryModel;

class TenantInTransitWorkflowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        $db = \Config\Database::connect();
        $db->table('deliveries')->like('tracking_id', 'TEST-TRK-')->delete();
        $db->table('orders')->like('order_number', 'TEST-ORD-')->delete();
        $db->table('printing_requests')->like('request_number', 'TEST-PR-')->delete();
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

    public function testBulkInTransitUpdatesAllShippedDeliveriesAndSyncsOrdersAndPrints(): void
    {
        $db = \Config\Database::connect();
        $shop = (new ShopModel())->first();
        $customer = (new UserModel())->where('role', 'customer')->first();
        $this->assertNotNull($shop);
        $this->assertNotNull($customer);

        $shopId = (int) $shop['id'];
        $customerId = (int) $customer['id'];

        // 1. Create a Doorstep Delivery Order in shipped status
        $orderModel = new OrderModel();
        $orderId = $orderModel->insert([
            'order_number'       => 'TEST-ORD-' . uniqid('', false),
            'customer_id'        => $customerId,
            'shop_id'            => $shopId,
            'fulfillment_method' => 'delivery',
            'payment_method'     => 'cod',
            'subtotal'           => 300.00,
            'shipping_fee'       => 50.00,
            'total_amount'       => 350.00,
            'status'             => 'shipped',
            'payment_status'     => 'unpaid',
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        // 2. Create a Doorstep Delivery Printing Request in ready_for_delivery
        $prModel = new PrintingRequestModel();
        $prId = $prModel->insert([
            'request_number'     => 'TEST-PR-' . uniqid('', false),
            'customer_id'        => $customerId,
            'shop_id'            => $shopId,
            'fulfillment_method' => 'delivery',
            'file_name'          => 'test_document.pdf',
            'file_path'          => 'uploads/printing/test_document.pdf',
            'total_price'        => 150.00,
            'down_payment'       => 150.00,
            'status'             => 'ready_for_delivery',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        // 3. Create linked deliveries in shipped status
        $delModel = new DeliveryModel();
        $delId1 = $delModel->insert([
            'deliverable_type'    => 'order',
            'deliverable_id'      => $orderId,
            'tracking_id'         => 'TEST-TRK-' . uniqid('', false),
            'courier_name'        => 'Standard Courier',
            'destination_address' => 'Purok 1, Polomolok',
            'status'              => 'shipped',
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        $delId2 = $delModel->insert([
            'deliverable_type'    => 'printing_request',
            'deliverable_id'      => $prId,
            'tracking_id'         => 'TEST-TRK-' . uniqid('', false),
            'courier_name'        => 'Standard Courier',
            'destination_address' => 'Purok 2, Polomolok',
            'status'              => 'shipped',
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        // 4. Call bulk-in-transit endpoint
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;
        $res = $this->asTenant($shopId)->post('tenant/deliveries/bulk-in-transit', [
            csrf_token() => $hash,
        ]);

        $res->assertSessionHas('success');

        // 5. Verify both deliveries transitioned to in_transit
        $del1 = $delModel->find($delId1);
        $del2 = $delModel->find($delId2);
        $this->assertEquals('in_transit', $del1['status']);
        $this->assertEquals('in_transit', $del2['status']);

        // 6. Verify linked order and printing request statuses were synced to in_transit
        $order = $orderModel->find($orderId);
        $pr = $prModel->find($prId);
        $this->assertEquals('in_transit', $order['status']);
        $this->assertEquals('in_transit', $pr['status']);
    }

    public function testDeliveriesPageRendersInTransitBulkButtonAndCardAction(): void
    {
        $shop = (new ShopModel())->first();
        $customer = (new UserModel())->where('role', 'customer')->first();
        $shopId = (int) $shop['id'];

        // Create a shipped delivery to ensure bulk and card button render
        $orderModel = new OrderModel();
        $orderId = $orderModel->insert([
            'order_number'       => 'TEST-ORD-' . uniqid('', false),
            'customer_id'        => $customer['id'],
            'shop_id'            => $shopId,
            'fulfillment_method' => 'delivery',
            'payment_method'     => 'cod',
            'subtotal'           => 100.00,
            'shipping_fee'       => 50.00,
            'total_amount'       => 150.00,
            'status'             => 'shipped',
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        $delModel = new DeliveryModel();
        $delModel->insert([
            'deliverable_type'    => 'order',
            'deliverable_id'      => $orderId,
            'tracking_id'         => 'TEST-TRK-' . uniqid('', false),
            'courier_name'        => 'Standard Courier',
            'destination_address' => 'Polomolok',
            'status'              => 'shipped',
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        $res = $this->asTenant($shopId)->get('tenant/deliveries');
        $res->assertOK();

        $body = $res->getBody();
        $this->assertStringContainsString('Delivery Management', $body);
        $this->assertStringContainsString('tenant/deliveries/bulk-in-transit', $body);
        $this->assertStringContainsString('In Transit', $body);
    }

    public function testOrdersPageRendersInTransitButtonInQueueContainerAndTable(): void
    {
        $shop = (new ShopModel())->first();
        $customer = (new UserModel())->where('role', 'customer')->first();
        $shopId = (int) $shop['id'];

        // Insert a processing delivery order to guarantee rendering of In Transit buttons
        $orderModel = new OrderModel();
        $orderModel->insert([
            'order_number'       => 'TEST-ORD-' . uniqid('', false),
            'customer_id'        => $customer['id'],
            'shop_id'            => $shopId,
            'fulfillment_method' => 'delivery',
            'payment_method'     => 'cod',
            'subtotal'           => 200.00,
            'shipping_fee'       => 50.00,
            'total_amount'       => 250.00,
            'status'             => 'processing',
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        $res = $this->asTenant($shopId)->get('tenant/orders');
        $res->assertOK();

        $body = $res->getBody();
        $this->assertStringContainsString('Active Processing &amp; Packing Queue', $body);
        $this->assertStringContainsString('In Transit', $body);
    }

    public function testPrintingPageRendersInTransitButtonForDeliveryRequests(): void
    {
        $shop = (new ShopModel())->first();
        $customer = (new UserModel())->where('role', 'customer')->first();
        $shopId = (int) $shop['id'];

        // Insert an in_production delivery printing request to guarantee rendering of In Transit button
        $prModel = new PrintingRequestModel();
        $prModel->insert([
            'request_number'     => 'TEST-PR-' . uniqid('', false),
            'customer_id'        => $customer['id'],
            'shop_id'            => $shopId,
            'fulfillment_method' => 'delivery',
            'file_name'          => 'test_document.pdf',
            'file_path'          => 'uploads/printing/test_document.pdf',
            'total_price'        => 100.00,
            'down_payment'       => 100.00,
            'status'             => 'in_production',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $res = $this->asTenant($shopId)->get('tenant/printing');
        $res->assertOK();

        $body = $res->getBody();
        $this->assertStringContainsString('In Transit', $body);
    }
}
