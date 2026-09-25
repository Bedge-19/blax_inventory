<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\ShopModel;
use App\Models\PrintingRequestModel;
use App\Models\DeliveryModel;
use App\Models\NotificationModel;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Controllers\Customer;
use App\Controllers\CustomerOrderController;

class CustomerPrintingWorkflowTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    public function testPrintingRequestCancellationAndDeliverySync()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $customer = (new UserModel())->where('role', 'customer')->first() ?? (new UserModel())->first();
        $this->assertNotNull($customer);
        $customerId = (int) $customer['id'];

        $prModel = new PrintingRequestModel();
        $delModel = new DeliveryModel();

        // Create an active printing request for delivery
        $reqId = $prModel->insert([
            'request_number'     => 'PR-' . date('Ymd') . '-' . rand(10000, 99999),
            'shop_id'            => $shopId,
            'customer_id'        => $customerId,
            'file_name'          => 'test_cancel_doc.pdf',
            'document_type'      => 'pdf',
            'paper_size'         => 'A4',
            'color_mode'         => 'bw',
            'copies'             => 1,
            'page_count'         => 3,
            'total_price'        => 15.00,
            'down_payment'       => 7.50,
            'status'             => 'new',
            'fulfillment_method' => 'delivery',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $delId = $delModel->insert([
            'deliverable_type'    => 'printing_request',
            'deliverable_id'      => $reqId,
            'tracking_id'         => 'PR-TRACK-' . $reqId,
            'courier_name'        => 'Store Courier',
            'destination_address' => 'Doorstep Delivery',
            'status'              => 'shipped',
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        // Mock customer session
        session()->set([
            'user_id'    => $customerId,
            'user_role'  => 'customer',
            'role'       => 'customer',
            'isLoggedIn' => true,
        ]);

        $request = \Config\Services::request();
        $request->setMethod('POST');
        $request->setHeader('Accept', 'application/json');

        $customerCtrl = new Customer();
        $customerCtrl->initController($request, \Config\Services::response(), \Config\Services::logger());

        // Cancel the printing request
        $response = $customerCtrl->cancelPrintingRequest($reqId);
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);

        // Verify status in DB
        $updatedReq = $prModel->find($reqId);
        $this->assertEquals('cancelled', $updatedReq['status']);

        // Verify linked delivery status in DB
        $updatedDel = $delModel->find($delId);
        $this->assertEquals('cancelled', $updatedDel['status']);

        // Clean up
        $delModel->delete($delId, true);
        $prModel->delete($reqId, true);
    }

    public function testDoorstepPrintingTrackingLiveViewAndPosition()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $customer = (new UserModel())->where('role', 'customer')->first() ?? (new UserModel())->first();
        $this->assertNotNull($customer);
        $customerId = (int) $customer['id'];

        $prModel = new PrintingRequestModel();
        $delModel = new DeliveryModel();

        $reqNumber = 'PR-TRACK-TEST-' . rand(1000, 9999);
        $reqId = $prModel->insert([
            'request_number'     => $reqNumber,
            'shop_id'            => $shopId,
            'customer_id'        => $customerId,
            'file_name'          => 'live_tracking_doc.pdf',
            'document_type'      => 'pdf',
            'paper_size'         => 'A4',
            'color_mode'         => 'color',
            'copies'             => 2,
            'page_count'         => 10,
            'total_price'        => 100.00,
            'down_payment'       => 50.00,
            'status'             => 'in_transit',
            'fulfillment_method' => 'delivery',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $delId = $delModel->insert([
            'deliverable_type'    => 'printing_request',
            'deliverable_id'      => $reqId,
            'tracking_id'         => $reqNumber,
            'courier_name'        => 'Rider Juan',
            'courier_phone'       => '0917-000-1111',
            'destination_address' => 'Poblacion, Polomolok',
            'current_lat'         => 6.2200,
            'current_lng'         => 125.0600,
            'status'              => 'in_transit',
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        session()->set([
            'user_id'    => $customerId,
            'user_role'  => 'customer',
            'role'       => 'customer',
            'isLoggedIn' => true,
        ]);

        $orderCtrl = new CustomerOrderController();
        $orderCtrl->initController(\Config\Services::request(), \Config\Services::response(), \Config\Services::logger());

        // Test position endpoint
        $posRes = $orderCtrl->getPrintingDeliveryPosition($reqNumber);
        $this->assertEquals(200, $posRes->getStatusCode());
        $posData = json_decode($posRes->getBody(), true);
        $this->assertTrue($posData['success']);
        $this->assertArrayHasKey('lat', $posData);
        $this->assertArrayHasKey('lng', $posData);
        $this->assertEquals('in_transit', $posData['status']);

        // Test view rendering
        $viewOutput = $orderCtrl->trackPrintingRequest($reqNumber);
        $this->assertIsString($viewOutput);
        $this->assertStringContainsString('live_tracking_doc.pdf', $viewOutput);
        $this->assertStringContainsString('Back to Printing Requests', $viewOutput);

        // Clean up
        $delModel->delete($delId, true);
        $prModel->delete($reqId, true);
    }

    public function testPendingOrdersAppearInTenantProcessingQueue()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $customer = (new UserModel())->where('role', 'customer')->first() ?? (new UserModel())->first();
        $this->assertNotNull($customer);
        $customerId = (int) $customer['id'];

        $orderModel = new OrderModel();
        $orderId = $orderModel->insert([
            'order_number'        => 'ORD-PENDING-' . rand(1000, 9999),
            'customer_id'         => $customerId,
            'shop_id'             => $shopId,
            'fulfillment_method'  => 'pickup',
            'payment_method'      => 'pickup',
            'subtotal'            => 120.00,
            'shipping_fee'        => 0.00,
            'total_amount'        => 120.00,
            'status'              => 'pending',
            'payment_status'      => 'unpaid',
            'placed_at'           => date('Y-m-d H:i:s'),
        ]);

        $queue = $orderModel->getProcessingOrdersForShop($shopId);
        $queueIds = array_column($queue, 'id');

        $this->assertContains((int) $orderId, array_map('intval', $queueIds), 'Pending order must appear in tenant orders queue');

        // Clean up
        $orderModel->delete($orderId, true);
    }

    public function testPrintingRequestEditAllowedWhenNew(): void
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $customer = (new UserModel())->where('role', 'customer')->first() ?? (new UserModel())->first();
        $this->assertNotNull($customer);
        $customerId = (int) $customer['id'];

        $prModel = new PrintingRequestModel();
        $reqId = $prModel->insert([
            'request_number'     => 'PR-EDIT-TEST-' . rand(1000, 9999),
            'shop_id'            => $shopId,
            'customer_id'        => $customerId,
            'file_name'          => 'original_draft.pdf',
            'document_type'      => 'pdf',
            'paper_size'         => 'letter',
            'color_mode'         => 'bw',
            'copies'             => 1,
            'page_count'         => 4,
            'binding_option'     => 'none',
            'total_price'        => 8.00,
            'down_payment'       => 4.00,
            'status'             => 'new',
            'fulfillment_method' => 'pickup',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        session()->set([
            'user_id'    => $customerId,
            'user_role'  => 'customer',
            'role'       => 'customer',
            'isLoggedIn' => true,
        ]);

        $request = \Config\Services::request();
        $request->setMethod('POST');
        $request->setHeader('Accept', 'application/json');
        $request->setGlobal('post', [
            'request_id'   => $reqId,
            'paper_size'   => 'a4',
            'color_mode'   => 'color',
            'copies'       => 3,
            'binding'      => 'staple',
            'paper_stock'  => 'standard_80gsm',
            'notes'        => 'Updated notes by customer',
        ]);

        $ctrl = new Customer();
        $ctrl->initController($request, \Config\Services::response(), \Config\Services::logger());

        $response = $ctrl->updatePrintingRequest();
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);

        // Check updated fields in DB
        $updated = $prModel->find($reqId);
        $this->assertEquals('a4', $updated['paper_size']);
        $this->assertEquals('color', $updated['color_mode']);
        $this->assertEquals(3, (int) $updated['copies']);
        $this->assertEquals('staple', $updated['binding_option']);
        $this->assertEquals('Updated notes by customer', $updated['special_instructions']);

        $prModel->delete($reqId, true);
    }

    public function testPrintingRequestEditForbiddenWhenProcessing(): void
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $customer = (new UserModel())->where('role', 'customer')->first() ?? (new UserModel())->first();
        $this->assertNotNull($customer);
        $customerId = (int) $customer['id'];

        $prModel = new PrintingRequestModel();
        $reqId = $prModel->insert([
            'request_number'     => 'PR-LOCK-TEST-' . rand(1000, 9999),
            'shop_id'            => $shopId,
            'customer_id'        => $customerId,
            'file_name'          => 'in_production_doc.pdf',
            'document_type'      => 'pdf',
            'paper_size'         => 'letter',
            'color_mode'         => 'bw',
            'copies'             => 1,
            'page_count'         => 5,
            'binding_option'     => 'none',
            'total_price'        => 10.00,
            'down_payment'       => 5.00,
            'status'             => 'in_production', // Shop has moved to processing/production!
            'fulfillment_method' => 'pickup',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        session()->set([
            'user_id'    => $customerId,
            'user_role'  => 'customer',
            'role'       => 'customer',
            'isLoggedIn' => true,
        ]);

        $request = \Config\Services::request();
        $request->setMethod('POST');
        $request->setHeader('Accept', 'application/json');
        $request->setGlobal('post', [
            'request_id' => $reqId,
            'copies'     => 10,
        ]);

        $ctrl = new Customer();
        $ctrl->initController($request, \Config\Services::response(), \Config\Services::logger());

        $response = $ctrl->updatePrintingRequest();
        $this->assertEquals(400, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('cannot be edited', $body['error']);

        // Check DB copies was NOT altered
        $row = $prModel->find($reqId);
        $this->assertEquals(1, (int) $row['copies']);

        $prModel->delete($reqId, true);
    }
}
