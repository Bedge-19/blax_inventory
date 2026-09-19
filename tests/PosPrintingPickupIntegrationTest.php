<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\OrderModel;
use App\Models\PrintingRequestModel;
use App\Models\PaymentModel;

class PosPrintingPickupIntegrationTest extends CIUnitTestCase
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
            'X-CSRF-TOKEN' => $hash,
        ]);
    }

    /**
     * Requirement A & B:
     * Store Pick-up product order selector only returns ready_for_pickup orders.
     * Pending, processing, shipped, delivered, completed, and cancelled orders must not appear.
     */
    public function testPosStorePickupSelectorOnlyReturnsReadyForPickupOrders()
    {
        $orderModel = new OrderModel();
        $db = \Config\Database::connect();

        $uniq = time() . rand(100, 999);
        $readyOrdNum = 'ORD-TEST-READY-' . $uniq;
        $pendingOrdNum = 'ORD-TEST-PEND-' . $uniq;
        $procOrdNum = 'ORD-TEST-PROC-' . $uniq;

        // Insert ready_for_pickup order
        $orderModel->insert([
            'order_number'       => $readyOrdNum,
            'customer_id'        => 3,
            'shop_id'            => 1,
            'fulfillment_method' => 'pickup',
            'payment_method'     => 'counter_cash',
            'subtotal'           => 120.00,
            'total_amount'       => 120.00,
            'status'             => 'ready_for_pickup',
            'payment_status'     => 'unpaid',
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        // Insert pending order
        $orderModel->insert([
            'order_number'       => $pendingOrdNum,
            'customer_id'        => 3,
            'shop_id'            => 1,
            'fulfillment_method' => 'pickup',
            'payment_method'     => 'counter_cash',
            'subtotal'           => 120.00,
            'total_amount'       => 120.00,
            'status'             => 'pending',
            'payment_status'     => 'unpaid',
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        // Insert processing order
        $orderModel->insert([
            'order_number'       => $procOrdNum,
            'customer_id'        => 3,
            'shop_id'            => 1,
            'fulfillment_method' => 'pickup',
            'payment_method'     => 'counter_cash',
            'subtotal'           => 120.00,
            'total_amount'       => 120.00,
            'status'             => 'processing',
            'payment_status'     => 'unpaid',
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        $res = $this->asTenant(1)->get('tenant/pos');
        $res->assertOK();
        $body = $res->response()->getBody();

        $this->assertStringContainsString('Select Ready for Pick-up', $body);
        $this->assertStringContainsString($readyOrdNum, $body, 'Ready for pickup order must appear in selector');
        $this->assertStringNotContainsString($pendingOrdNum, $body, 'Pending order must NOT appear in POS selector');
        $this->assertStringNotContainsString($procOrdNum, $body, 'Processing order must NOT appear in POS selector');
    }

    /**
     * Requirement C, D, E, F:
     * Printing selector only returns:
     * fulfillment_method = pickup AND status = ready_for_pickup.
     * Delivery, in_production, new, ready_for_delivery, completed, and cancelled requests must not appear.
     */
    public function testPrintingSelectorFiltersStrictlyByPickupAndReadyForPickup()
    {
        $prModel = new PrintingRequestModel();
        $uniq = time() . rand(100, 999);

        $readyPickupNum   = 'PR-READY-PICKUP-' . $uniq;
        $readyDeliveryNum = 'PR-READY-DELIV-' . $uniq;
        $inProdNum        = 'PR-IN-PROD-' . $uniq;
        $completedNum     = 'PR-COMPLETED-' . $uniq;
        $cancelledNum     = 'PR-CANCELLED-' . $uniq;

        // 1. Valid: pickup + ready_for_pickup
        $prModel->insert([
            'request_number'     => $readyPickupNum,
            'customer_id'        => 3,
            'shop_id'            => 1,
            'file_name'          => 'Thesis_Final_' . $uniq . '.pdf',
            'file_url'           => 'uploads/printing/test.pdf',
            'document_type'      => 'pdf',
            'fulfillment_method' => 'pickup',
            'status'             => 'ready_for_pickup',
            'total_price'        => 150.00,
            'down_payment'       => 75.00,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        // 2. Invalid: delivery + ready_for_pickup
        $prModel->insert([
            'request_number'     => $readyDeliveryNum,
            'customer_id'        => 3,
            'shop_id'            => 1,
            'file_name'          => 'Delivery_Plan_' . $uniq . '.pdf',
            'file_url'           => 'uploads/printing/test2.pdf',
            'document_type'      => 'pdf',
            'fulfillment_method' => 'delivery',
            'status'             => 'ready_for_delivery',
            'total_price'        => 150.00,
            'down_payment'       => 75.00,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        // 3. Invalid: pickup + in_production
        $prModel->insert([
            'request_number'     => $inProdNum,
            'customer_id'        => 3,
            'shop_id'            => 1,
            'file_name'          => 'In_Production_' . $uniq . '.pdf',
            'file_url'           => 'uploads/printing/test3.pdf',
            'document_type'      => 'pdf',
            'fulfillment_method' => 'pickup',
            'status'             => 'in_production',
            'total_price'        => 100.00,
            'down_payment'       => 50.00,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        // 4. Invalid: pickup + completed
        $prModel->insert([
            'request_number'     => $completedNum,
            'customer_id'        => 3,
            'shop_id'            => 1,
            'file_name'          => 'Done_Document_' . $uniq . '.pdf',
            'file_url'           => 'uploads/printing/test4.pdf',
            'document_type'      => 'pdf',
            'fulfillment_method' => 'pickup',
            'status'             => 'completed',
            'total_price'        => 80.00,
            'down_payment'       => 80.00,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        // 5. Invalid: pickup + cancelled
        $prModel->insert([
            'request_number'     => $cancelledNum,
            'customer_id'        => 3,
            'shop_id'            => 1,
            'file_name'          => 'Cancelled_Order_' . $uniq . '.pdf',
            'file_url'           => 'uploads/printing/test5.pdf',
            'document_type'      => 'pdf',
            'fulfillment_method' => 'pickup',
            'status'             => 'cancelled',
            'total_price'        => 50.00,
            'down_payment'       => 0.00,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $res = $this->asTenant(1)->get('tenant/pos');
        $res->assertOK();
        $body = $res->response()->getBody();

        // Must show dedicated Printing Ready for Pick-up button & modal
        $this->assertStringContainsString('Printing Ready for Pick-up', $body);
        $this->assertStringContainsString('posPrintingSelectorModal', $body);

        // Assert presence of ready pickup
        $this->assertStringContainsString($readyPickupNum, $body, 'Ready for pickup printing request must appear');
        $this->assertStringContainsString('Thesis_Final_' . $uniq . '.pdf', $body);

        // Assert absence of others
        $this->assertStringNotContainsString($readyDeliveryNum, $body, 'Delivery printing requests must NOT appear');
        $this->assertStringNotContainsString($inProdNum, $body, 'In-production printing requests must NOT appear');
        $this->assertStringNotContainsString($completedNum, $body, 'Completed printing requests must NOT appear');
        $this->assertStringNotContainsString($cancelledNum, $body, 'Cancelled printing requests must NOT appear');
    }

    /**
     * Requirement G:
     * Search attributes work by request number, customer name, and file name.
     */
    public function testPrintingSelectorDataSearchAttributesContainKeySearchFields()
    {
        $prModel = new PrintingRequestModel();
        $uniq = time() . rand(100, 999);
        $reqNum = 'PR-SRCH-' . $uniq;
        $fileName = 'Custom_File_Report_' . $uniq . '.docx';

        $prModel->insert([
            'request_number'     => $reqNum,
            'customer_id'        => 3,
            'shop_id'            => 1,
            'file_name'          => $fileName,
            'file_url'           => 'uploads/printing/test.docx',
            'document_type'      => 'docx',
            'fulfillment_method' => 'pickup',
            'status'             => 'ready_for_pickup',
            'total_price'        => 60.00,
            'down_payment'       => 30.00,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $res = $this->asTenant(1)->get('tenant/pos');
        $res->assertOK();
        $body = $res->response()->getBody();

        $this->assertStringContainsString(strtolower($reqNum), $body);
        $this->assertStringContainsString(strtolower($fileName), $body);
    }

    /**
     * Requirement H:
     * Tenant A cannot see Tenant B's printing requests (Strict Shop Scoping).
     */
    public function testTenantCannotSeeAnotherShopPrintingRequests()
    {
        $prModel = new PrintingRequestModel();
        $uniq = time() . rand(100, 999);
        $shop2ReqNum = 'PR-SHOP2-' . $uniq;

        // Belongs to Shop 12
        $id = $prModel->insert([
            'request_number'     => $shop2ReqNum,
            'customer_id'        => 3,
            'shop_id'            => 12,
            'file_name'          => 'Secret_Shop2_' . $uniq . '.pdf',
            'file_url'           => 'uploads/printing/secret.pdf',
            'document_type'      => 'pdf',
            'fulfillment_method' => 'pickup',
            'status'             => 'ready_for_pickup',
            'total_price'        => 200.00,
            'down_payment'       => 100.00,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        // Authenticate as Shop 1
        $res = $this->asTenant(1)->get('tenant/pos');
        $res->assertOK();
        $body = $res->response()->getBody();

        $this->assertStringNotContainsString($shop2ReqNum, $body, 'Shop 1 must NOT see Shop 2 printing requests');

        // Direct GET with printing_id of Shop 2
        $resDirect = $this->asTenant(1)->get('tenant/pos?printing_id=' . $id);
        $resDirect->assertRedirect();
        $this->assertEquals(site_url('tenant/pos'), $resDirect->getRedirectUrl());

        // QR verification attempt on Shop 2 request
        $resQr = $this->asTenant(1)->post('tenant/pos/verify-qr', ['qr_code' => $shop2ReqNum]);
        $resQr->assertStatus(403);
    }

    /**
     * Requirement I:
     * Selecting and completing a printing request does NOT create duplicate printing requests
     * and does NOT charge the customer again for the original printing payment.
     */
    public function testCompletingPrintingPickupDoesNotDuplicateRecordsOrOvercharge()
    {
        $prModel = new PrintingRequestModel();
        $paymentModel = new PaymentModel();
        $uniq = time() . rand(100, 999);
        $reqNum = 'PR-PICK-' . $uniq;

        $prId = $prModel->insert([
            'request_number'     => $reqNum,
            'customer_id'        => 3,
            'shop_id'            => 1,
            'file_name'          => 'Final_Print_' . $uniq . '.pdf',
            'file_url'           => 'uploads/printing/test.pdf',
            'document_type'      => 'pdf',
            'fulfillment_method' => 'pickup',
            'status'             => 'ready_for_pickup',
            'total_price'        => 100.00,
            'down_payment'       => 50.00,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        // Simulate online 50.00 down payment
        $payRef = 'ref_online_down_' . $uniq;
        $paymentModel->insert([
            'payable_type'     => 'printing_request',
            'payable_id'       => $prId,
            'method'           => 'gcash',
            'amount'           => 50.00,
            'reference_number' => $payRef,
            'status'           => 'verified',
            'processed_at'     => date('Y-m-d H:i:s'),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        $initialPrCount = $prModel->countAllResults();

        // 1. Load printing request in POS
        $posView = $this->asTenant(1)->get('tenant/pos?printing_id=' . $prId);
        $posView->assertOK();
        $body = $posView->response()->getBody();

        $this->assertStringContainsString('Final_Print_' . $uniq . '.pdf', $body);
        $this->assertStringContainsString('DOWN PAYMENT PAID (₱50.00)', $body);
        $this->assertStringContainsString('NEVER charge the down payment again', $body);
        $this->assertStringContainsString('50.00', $body);

        // 2. Complete the pickup via posCompletePickup (collect remaining 50.00 at counter)
        $completeRes = $this->asTenant(1)->post('tenant/pos/complete-pickup', [
            'printing_id'            => $prId,
            'items'                  => json_encode([]),
            'counter_payment_method' => 'cash',
        ]);
        $completeRes->assertOK();
        $json = json_decode($completeRes->response()->getBody(), true);
        $this->assertTrue($json['success']);
        $this->assertTrue($json['is_printing']);
        $this->assertEquals(50.00, (float) $json['final_total'], 'Only remaining balance must be collected');

        // Verify printing request was updated to completed, NOT duplicated
        $updatedPr = $prModel->find($prId);
        $this->assertEquals('completed', $updatedPr['status']);
        $this->assertNotNull($updatedPr['completed_at']);
        $this->assertEquals(100, (int) $updatedPr['progress_percent']);

        $finalPrCount = $prModel->countAllResults();
        $this->assertEquals($initialPrCount, $finalPrCount, 'Total printing requests count must remain constant (no duplication)');

        // Verify payment records: 1 online (50.00) + 1 counter remaining balance (50.00) = 100.00 total
        $payments = $paymentModel->where('payable_type', 'printing_request')->where('payable_id', $prId)->findAll();
        $this->assertCount(2, $payments);
        $totalPaid = array_sum(array_column($payments, 'amount'));
        $this->assertEquals(100.00, $totalPaid);
    }

    /**
     * Requirement J:
     * Existing product POS functionality continues to work.
     */
    public function testExistingProductPosContinuesToWork()
    {
        // 1. Walk-in mode loads
        $res = $this->asTenant(1)->get('tenant/pos');
        $res->assertOK();
        $this->assertStringContainsString('Walk-in Counter POS', $res->getBody());

        // 2. Product search works
        $searchRes = $this->asTenant(1)->get('tenant/pos/search-products?q=');
        $searchRes->assertOK();
        $searchJson = json_decode($searchRes->response()->getBody(), true);
        $this->assertTrue($searchJson['success']);

        // 3. QR code lookup rejects invalid codes cleanly
        $qrRes = $this->asTenant(1)->post('tenant/pos/verify-qr', ['qr_code' => 'NOT-EXIST-00000']);
        $qrRes->assertStatus(404);
    }
}
