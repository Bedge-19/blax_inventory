<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\PrintingRequestModel;
use App\Models\PaymentModel;
use App\Models\PayoutModel;
use App\Controllers\Tenant;

class TenantGcashWithdrawalTest extends CIUnitTestCase
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
     * Test 1: Completed cash and COD orders are excluded from Available Balance.
     */
    public function testCashAndCodOrdersAreExcludedFromAvailableBalance()
    {
        $orderModel = new OrderModel();
        $db = \Config\Database::connect();

        // Create a unique temporary shop or use a clean shop ID
        $uniq = time() . rand(1000, 9999);
        $db->table('shops')->insert([
            'owner_id'      => 2,
            'shop_name'     => 'CashTestShop_' . $uniq,
            'slug'          => 'cashtestshop-' . $uniq,
            'status'        => 'active',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $shopId = (int) $db->insertID();

        // Insert a completed Cash on Delivery order
        $orderModel->insert([
            'order_number'       => 'ORD-COD-' . $uniq,
            'customer_id'        => 5,
            'shop_id'            => $shopId,
            'fulfillment_method' => 'delivery',
            'payment_method'     => 'cod',
            'subtotal'           => 150.00,
            'total_amount'       => 150.00,
            'status'             => 'delivered',
            'payment_status'     => 'paid',
            'placed_at'          => date('Y-m-d H:i:s'),
            'completed_at'       => date('Y-m-d H:i:s'),
        ]);

        // Insert a completed Store Pick-up cash order
        $orderModel->insert([
            'order_number'       => 'ORD-PICKUP-' . $uniq,
            'customer_id'        => 5,
            'shop_id'            => $shopId,
            'fulfillment_method' => 'pickup',
            'payment_method'     => 'pickup',
            'pos_payment_method' => 'cash',
            'pos_payment_status' => 'paid',
            'subtotal'           => 250.00,
            'total_amount'       => 250.00,
            'status'             => 'completed',
            'payment_status'     => 'paid',
            'placed_at'          => date('Y-m-d H:i:s'),
            'completed_at'       => date('Y-m-d H:i:s'),
        ]);

        $tenantController = new Tenant();
        $balanceData = $tenantController->getShopEscrowAndBalance($shopId);

        // Neither cash order should count towards Available Balance or Escrow
        $this->assertEquals(0.00, (float) $balanceData['available_balance']);
        $this->assertEquals(0.00, (float) $balanceData['escrow_holding']);
        $this->assertEquals(0.00, (float) $balanceData['released_earnings']);
        $this->assertEmpty($balanceData['settled_records']);
        $this->assertEmpty($balanceData['escrow_records']);
    }

    /**
     * Test 2: Completed GCash orders are credited to Available Balance and listed in settled_records.
     */
    public function testCompletedGcashOrdersAreCreditedToAvailableBalance()
    {
        $orderModel = new OrderModel();
        $paymentModel = new PaymentModel();
        $db = \Config\Database::connect();

        $uniq = time() . rand(1000, 9999);
        $db->table('shops')->insert([
            'owner_id'      => 2,
            'shop_name'     => 'GcashTestShop_' . $uniq,
            'slug'          => 'gcashtestshop-' . $uniq,
            'status'        => 'active',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $shopId = (int) $db->insertID();

        $ordNum = 'ORD-GCASH-' . $uniq;
        $orderId = $orderModel->insert([
            'order_number'       => $ordNum,
            'customer_id'        => 5,
            'shop_id'            => $shopId,
            'fulfillment_method' => 'delivery',
            'payment_method'     => 'gcash',
            'subtotal'           => 450.00,
            'total_amount'       => 450.00,
            'status'             => 'delivered',
            'payment_status'     => 'paid',
            'placed_at'          => date('Y-m-d H:i:s'),
            'completed_at'       => date('Y-m-d H:i:s'),
        ]);

        // Insert verified GCash payment
        $paymentModel->insert([
            'payable_type'     => 'order',
            'payable_id'       => $orderId,
            'method'           => 'gcash',
            'amount'           => 450.00,
            'reference_number' => 'ref_gcash_' . $uniq,
            'status'           => 'verified',
            'processed_at'     => date('Y-m-d H:i:s'),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        $tenantController = new Tenant();
        $balanceData = $tenantController->getShopEscrowAndBalance($shopId);

        $this->assertEquals(450.00, (float) $balanceData['available_balance']);
        $this->assertEquals(0.00, (float) $balanceData['escrow_holding']);
        $this->assertEquals(450.00, (float) $balanceData['released_earnings']);
        $this->assertCount(1, $balanceData['settled_records']);
        $this->assertEquals($ordNum, $balanceData['settled_records'][0]['reference']);
        $this->assertEquals(450.00, (float) $balanceData['settled_records'][0]['amount']);
        $this->assertEquals('completed', $balanceData['settled_records'][0]['status']);
    }

    /**
     * Test 3: In-progress GCash orders remain in Escrow until delivered/completed.
     */
    public function testInProgressGcashOrdersHeldInEscrowUntilDelivered()
    {
        $orderModel = new OrderModel();
        $paymentModel = new PaymentModel();
        $db = \Config\Database::connect();

        $uniq = time() . rand(1000, 9999);
        $db->table('shops')->insert([
            'owner_id'      => 2,
            'shop_name'     => 'EscrowTestShop_' . $uniq,
            'slug'          => 'escrowtestshop-' . $uniq,
            'status'        => 'active',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $shopId = (int) $db->insertID();

        $ordNum = 'ORD-ESCROW-' . $uniq;
        $orderId = $orderModel->insert([
            'order_number'       => $ordNum,
            'customer_id'        => 5,
            'shop_id'            => $shopId,
            'fulfillment_method' => 'delivery',
            'payment_method'     => 'gcash',
            'subtotal'           => 300.00,
            'total_amount'       => 300.00,
            'status'             => 'processing',
            'payment_status'     => 'paid',
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        $paymentModel->insert([
            'payable_type'     => 'order',
            'payable_id'       => $orderId,
            'method'           => 'gcash',
            'amount'           => 300.00,
            'reference_number' => 'ref_escrow_' . $uniq,
            'status'           => 'verified',
            'processed_at'     => date('Y-m-d H:i:s'),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        $tenantController = new Tenant();

        // 1. While processing, funds must be in escrow_holding
        $data1 = $tenantController->getShopEscrowAndBalance($shopId);
        $this->assertEquals(0.00, (float) $data1['available_balance']);
        $this->assertEquals(300.00, (float) $data1['escrow_holding']);
        $this->assertCount(1, $data1['escrow_records']);
        $this->assertEmpty($data1['settled_records']);

        // 2. Once marked delivered, funds move to available_balance
        $orderModel->update($orderId, [
            'status'       => 'delivered',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        $data2 = $tenantController->getShopEscrowAndBalance($shopId);
        $this->assertEquals(300.00, (float) $data2['available_balance']);
        $this->assertEquals(0.00, (float) $data2['escrow_holding']);
        $this->assertCount(1, $data2['settled_records']);
        $this->assertEmpty($data2['escrow_records']);
    }

    /**
     * Test 4: Printing requests paid through GCash are recorded in escrow and balance.
     */
    public function testPrintingGcashPaymentsRecordedAccurately()
    {
        $prModel = new PrintingRequestModel();
        $paymentModel = new PaymentModel();
        $db = \Config\Database::connect();

        $uniq = time() . rand(1000, 9999);
        $db->table('shops')->insert([
            'owner_id'      => 2,
            'shop_name'     => 'PrintTestShop_' . $uniq,
            'slug'          => 'printtestshop-' . $uniq,
            'status'        => 'active',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $shopId = (int) $db->insertID();

        $prNum = 'PR-WITHDRAW-' . $uniq;
        $prId = $prModel->insert([
            'request_number'     => $prNum,
            'customer_id'        => 5,
            'shop_id'            => $shopId,
            'file_name'          => 'Module_' . $uniq . '.pdf',
            'file_url'           => 'uploads/printing/test.pdf',
            'document_type'      => 'pdf',
            'fulfillment_method' => 'pickup',
            'status'             => 'in_production',
            'total_price'        => 120.00,
            'down_payment'       => 60.00,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $paymentModel->insert([
            'payable_type'     => 'printing_request',
            'payable_id'       => $prId,
            'method'           => 'gcash',
            'amount'           => 60.00,
            'reference_number' => 'ref_pr_down_' . $uniq,
            'status'           => 'verified',
            'processed_at'     => date('Y-m-d H:i:s'),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        $tenantController = new Tenant();

        // While in production, down payment is held in escrow
        $data1 = $tenantController->getShopEscrowAndBalance($shopId);
        $this->assertEquals(60.00, (float) $data1['escrow_holding']);
        $this->assertEquals(0.00, (float) $data1['available_balance']);

        // Customer pays remaining 60.00 via GCash at pickup
        $paymentModel->insert([
            'payable_type'     => 'printing_request',
            'payable_id'       => $prId,
            'method'           => 'gcash',
            'amount'           => 60.00,
            'reference_number' => 'ref_pr_pickup_' . $uniq,
            'status'           => 'verified',
            'processed_at'     => date('Y-m-d H:i:s'),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        $prModel->update($prId, [
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        $data2 = $tenantController->getShopEscrowAndBalance($shopId);
        // Both payments (total 120.00) are released to available balance
        $this->assertEquals(120.00, (float) $data2['available_balance']);
        $this->assertEquals(0.00, (float) $data2['escrow_holding']);
        $this->assertCount(1, $data2['settled_records']);
        $this->assertEquals($prNum, $data2['settled_records'][0]['reference']);
        $this->assertEquals(120.00, (float) $data2['settled_records'][0]['amount']);
    }

    /**
     * Test 5: The tenant/withdrawals page renders all 3 tabs and table ledger.
     */
    public function testWithdrawalsPageRendersAllTabsAndLedger()
    {
        $res = $this->asTenant(1)->get('tenant/withdrawals');
        $res->assertOK();
        $body = $res->response()->getBody();

        // Check tab buttons
        $this->assertStringContainsString('id="tabBtnSettled"', $body);
        $this->assertStringContainsString('id="tabBtnEscrow"', $body);
        $this->assertStringContainsString('id="tabBtnHistory"', $body);
        $this->assertStringContainsString('Settled GCash Earnings', $body);
        $this->assertStringContainsString('Held in Escrow', $body);
        $this->assertStringContainsString('Withdrawal Requests', $body);

        // Check tab panes
        $this->assertStringContainsString('id="tabPaneSettled"', $body);
        $this->assertStringContainsString('id="tabPaneEscrow"', $body);
        $this->assertStringContainsString('id="tabPaneHistory"', $body);
        $this->assertStringContainsString('Completed GCash Transactions', $body);
        $this->assertStringContainsString('GCash Funds in Escrow', $body);
        $this->assertStringContainsString('Withdrawal History', $body);

        // Check script functions
        $this->assertStringContainsString('function switchWithdrawalTab(tab)', $body);
    }
}
