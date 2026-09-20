<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\OrderModel;
use App\Models\PaymentModel;
use App\Models\PayoutModel;
use App\Models\ShopModel;
use App\Models\SiteContentModel;
use App\Controllers\Tenant;
use App\Controllers\Admin;

class DisbursementWorkflowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PAYMONGO_WEBHOOK_SECRET=test_secret_key_123');
        $_ENV['PAYMONGO_WEBHOOK_SECRET'] = 'test_secret_key_123';
    }

    protected function tearDown(): void
    {
        $db = \Config\Database::connect();
        $db->table('payout_requests')->like('reference_number', 'WD-TEST-')->orLike('reference_number', 'WD-202')->delete();
        $testShops = $db->table('shops')->like('shop_name', 'DisburseShop_')->get()->getResultArray();
        $shopIds = array_column($testShops, 'id');
        if (!empty($shopIds)) {
            $testOrders = $db->table('orders')->whereIn('shop_id', $shopIds)->get()->getResultArray();
            $orderIds = array_column($testOrders, 'id');
            if (!empty($orderIds)) {
                $db->table('payments')->where('payable_type', 'order')->whereIn('payable_id', $orderIds)->delete();
                $db->table('order_items')->whereIn('order_id', $orderIds)->delete();
                $db->table('orders')->whereIn('id', $orderIds)->delete();
            }
            $db->table('shops')->whereIn('id', $shopIds)->delete();
        }
        $db->table('orders')->like('order_number', 'ORD-DISB-')->delete();
        $db->table('payments')->like('reference_number', 'pay_ref_')->delete();

        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    private function asTenant(int $shopId, int $userId = 2): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => $userId,
            'user_role'  => 'shop_owner',
            'role'       => 'shop_owner',
            'shop_id'    => $shopId,
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN' => $hash,
            'Referer'      => base_url('tenant/withdrawals'),
        ]);
    }

    private function asAdmin(int $userId = 1): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => $userId,
            'user_role'  => 'admin',
            'role'       => 'admin',
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN' => $hash,
            'Referer'      => base_url('admin/payments'),
        ]);
    }

    private function createTestShopWithBalance(float $balance = 1000.00, string $gcashName = 'Juan Dela Cruz', string $gcashNumber = '09171234567'): array
    {
        $db = \Config\Database::connect();
        $uniq = time() . '_' . rand(1000, 9999);

        $db->table('shops')->insert([
            'owner_id'           => 2,
            'shop_name'          => 'DisburseShop_' . $uniq,
            'slug'               => 'disburseshop-' . $uniq,
            'gcash_account_name' => $gcashName,
            'gcash_number'       => $gcashNumber,
            'status'             => 'active',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
        $shopId = (int) $db->insertID();

        if ($balance > 0) {
            $orderModel = new OrderModel();
            $paymentModel = new PaymentModel();

            $orderId = $orderModel->insert([
                'order_number'       => 'ORD-DISB-' . $uniq,
                'customer_id'        => 5,
                'shop_id'            => $shopId,
                'fulfillment_method' => 'delivery',
                'payment_method'     => 'gcash',
                'subtotal'           => $balance,
                'total_amount'       => $balance,
                'status'             => 'delivered',
                'payment_status'     => 'paid',
                'placed_at'          => date('Y-m-d H:i:s'),
                'completed_at'       => date('Y-m-d H:i:s'),
            ]);

            $paymentModel->insert([
                'payable_type'     => 'order',
                'payable_id'       => $orderId,
                'method'           => 'gcash',
                'amount'           => $balance,
                'reference_number' => 'pay_ref_' . $uniq,
                'status'           => 'verified',
                'processed_at'     => date('Y-m-d H:i:s'),
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        return ['shop_id' => $shopId, 'uniq' => $uniq];
    }

    /**
     * Test 1: Withdrawal is blocked when GCash information is missing or incomplete.
     */
    public function testTenantCannotRequestWithdrawalWithoutGcashInfo()
    {
        $setup = $this->createTestShopWithBalance(500.00, '', '');
        $shopId = $setup['shop_id'];

        $res = $this->asTenant($shopId)->post('tenant/withdrawals/request', [
            'amount' => 100.00,
            'method' => 'GCash',
        ]);

        $res->assertRedirect();
        $this->assertEquals('Please complete your GCash account information before requesting a withdrawal.', session()->getFlashdata('error'));
    }

    /**
     * Test 2: Saving payment details requires matching confirmation, ownership checkbox, and normalizes +63.
     */
    public function testSavePaymentDetailsValidation()
    {
        $setup = $this->createTestShopWithBalance(0, '', '');
        $shopId = $setup['shop_id'];

        // Submitting with mismatched confirmation fails
        $res1 = $this->asTenant($shopId)->post('tenant/settings/save', [
            'section'                 => 'payment',
            'gcash_account_name'      => 'Pedro Penduko',
            'gcash_number'            => '09181112222',
            'confirm_gcash_number'    => '09183334444',
            'gcash_confirm_ownership' => '1',
        ]);
        $res1->assertRedirect();
        $this->assertStringContainsString('do not match', (string) session()->getFlashdata('error'));

        // Submitting without ownership confirmation fails
        $res2 = $this->asTenant($shopId)->post('tenant/settings/save', [
            'section'                 => 'payment',
            'gcash_account_name'      => 'Pedro Penduko',
            'gcash_number'            => '09181112222',
            'confirm_gcash_number'    => '09181112222',
        ]);
        $res2->assertRedirect();
        $this->assertStringContainsString('belongs to you', (string) session()->getFlashdata('error'));

        // Submitting valid details with +63 normalizes to 09XXXXXXXXX
        $res3 = $this->asTenant($shopId)->post('tenant/settings/save', [
            'section'                 => 'payment',
            'gcash_account_name'      => 'Pedro Penduko',
            'gcash_number'            => '+63 918 111 2222',
            'confirm_gcash_number'    => '09181112222',
            'gcash_confirm_ownership' => '1',
        ]);
        $res3->assertRedirect();
        $this->assertStringContainsString('updated successfully', (string) session()->getFlashdata('success'));

        $shop = (new ShopModel())->find($shopId);
        $this->assertEquals('Pedro Penduko', $shop['gcash_account_name']);
        $this->assertEquals('09181112222', $shop['gcash_number']);
    }

    /**
     * Test 3: Withdrawal request creates an immutable recipient snapshot and reserves funds.
     */
    public function testTenantRequestWithdrawalCreatesImmutableSnapshot()
    {
        $setup = $this->createTestShopWithBalance(1000.00, 'Maria Clara', '09289876543');
        $shopId = $setup['shop_id'];

        $res = $this->asTenant($shopId)->post('tenant/withdrawals/request', [
            'amount' => 500.00,
            'method' => 'GCash',
        ]);

        $res->assertRedirect();
        $this->assertStringContainsString('submitted successfully', (string) session()->getFlashdata('success'));

        $payoutModel = new PayoutModel();
        $payout = $payoutModel->where('shop_id', $shopId)->orderBy('id', 'DESC')->first();

        $this->assertNotNull($payout);
        $this->assertEquals('pending', $payout['status']);
        $this->assertEquals(500.00, (float) $payout['amount']);

        $deductionPercent = (new SiteContentModel())->getPlatformDeductionPercent();
        $expectedFee = round(500.00 * ($deductionPercent / 100), 2);
        $expectedNet = max(0.0, round(500.00 - $expectedFee, 2));

        $this->assertEquals($deductionPercent, (float) $payout['deduction_percent']);
        $this->assertEquals($expectedFee, (float) $payout['fee']);
        $this->assertEquals($expectedNet, (float) $payout['net_amount']);
        $this->assertEquals('Maria Clara', $payout['recipient_account_name']);
        $this->assertEquals('09289876543', $payout['destination_detail']);
        $this->assertEquals('G-Xchange, Inc.', $payout['recipient_institution']);

        // Available balance is reduced from 1000 to 500 while request is pending
        $tenantController = new Tenant();
        $balanceData = $tenantController->getShopEscrowAndBalance($shopId);
        $this->assertEquals(500.00, (float) $balanceData['available_balance']);
    }

    /**
     * Test 4: Admin Process Payout transitions pending to processing without calling PayMongo.
     */
    public function testAdminProcessPayoutMovesPendingToProcessingWithoutPaymongoCall()
    {
        $setup = $this->createTestShopWithBalance(1000.00, 'Crisostomo Ibarra', '09191234567');
        $shopId = $setup['shop_id'];
        $uniq = $setup['uniq'];

        $payoutModel = new PayoutModel();
        $payoutId = $payoutModel->insert([
            'shop_id'                => $shopId,
            'reference_number'       => 'WD-TEST-' . $uniq,
            'amount'                 => 600.00,
            'fee'                    => 18.00,
            'deduction_percent'      => 3.00,
            'net_amount'             => 582.00,
            'destination_method'     => 'gcash',
            'destination_detail'     => '09191234567',
            'recipient_account_name' => 'Crisostomo Ibarra',
            'recipient_institution'  => 'G-Xchange, Inc.',
            'status'                 => 'pending',
            'created_at'             => date('Y-m-d H:i:s'),
        ]);

        $res = $this->asAdmin(1)->post('admin/payments/process', [
            'payout_id' => $payoutId,
        ]);

        $res->assertRedirect();
        $this->assertStringContainsString('Processing', (string) session()->getFlashdata('success'));

        $updated = $payoutModel->find($payoutId);
        $this->assertEquals('processing', $updated['status']);
        $this->assertNotNull($updated['processed_at']);
        $this->assertEquals(1, (int) $updated['processed_by']);
        $this->assertNull($updated['paymongo_transfer_id'], 'Process step MUST NOT call PayMongo API');
    }

    /**
     * Test 5: Admin Reject Payout restores tenant balance and records rejection reason.
     */
    public function testAdminRejectPayoutRestoresBalance()
    {
        $setup = $this->createTestShopWithBalance(1000.00, 'Sisa Santos', '09201234567');
        $shopId = $setup['shop_id'];
        $uniq = $setup['uniq'];

        $payoutModel = new PayoutModel();
        $payoutId = $payoutModel->insert([
            'shop_id'                => $shopId,
            'reference_number'       => 'WD-TEST-' . $uniq,
            'amount'                 => 400.00,
            'fee'                    => 12.00,
            'deduction_percent'      => 3.00,
            'net_amount'             => 388.00,
            'destination_method'     => 'gcash',
            'destination_detail'     => '09201234567',
            'recipient_account_name' => 'Sisa Santos',
            'recipient_institution'  => 'G-Xchange, Inc.',
            'status'                 => 'processing',
            'created_at'             => date('Y-m-d H:i:s'),
        ]);

        $res = $this->asAdmin(1)->post('admin/payments/reject', [
            'payout_id'        => $payoutId,
            'rejection_reason' => 'Invalid GCash account details provided.',
        ]);

        $res->assertRedirect();
        $this->assertStringContainsString('rejected', (string) session()->getFlashdata('success'));

        $rejected = $payoutModel->find($payoutId);
        $this->assertEquals('rejected', $rejected['status']);
        $this->assertEquals('Invalid GCash account details provided.', $rejected['failure_reason']);

        // Balance restored to full 1000.00
        $tenantController = new Tenant();
        $balanceData = $tenantController->getShopEscrowAndBalance($shopId);
        $this->assertEquals(1000.00, (float) $balanceData['available_balance']);
    }

    /**
     * Test 6: PayMongo Webhook signature validation and transfer status updates.
     */
    public function testPaymongoTransferWebhook()
    {
        $setup = $this->createTestShopWithBalance(1000.00, 'Basilio Santos', '09211234567');
        $shopId = $setup['shop_id'];
        $uniq = $setup['uniq'];

        $payoutModel = new PayoutModel();
        $transferId = 'tr_test_' . time() . '_' . rand(100, 999);
        $payoutId = $payoutModel->insert([
            'shop_id'                => $shopId,
            'reference_number'       => 'WD-TEST-' . $uniq,
            'amount'                 => 300.00,
            'fee'                    => 9.00,
            'deduction_percent'      => 3.00,
            'net_amount'             => 291.00,
            'destination_method'     => 'gcash',
            'destination_detail'     => '09211234567',
            'recipient_account_name' => 'Basilio Santos',
            'recipient_institution'  => 'G-Xchange, Inc.',
            'status'                 => 'transfer_pending',
            'paymongo_transfer_id'   => $transferId,
            'created_at'             => date('Y-m-d H:i:s'),
        ]);

        $payload = json_encode([
            'data' => [
                'id' => 'evt_' . time(),
                'type' => 'event',
                'attributes' => [
                    'type' => 'transfer.outward.successful',
                    'data' => [
                        'id' => $transferId,
                        'type' => 'transfer',
                        'attributes' => [
                            'status' => 'successful',
                            'amount' => 29100,
                        ],
                    ],
                ],
            ],
        ]);

        $timestamp = time();
        $secret = 'test_secret_key_123';
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        $sigHeader = "t={$timestamp},te={$signature},li={$signature}";

        // Webhook request with signature
        $res = $this->withHeaders([
            'Paymongo-Signature' => $sigHeader,
            'Content-Type'       => 'application/json',
        ])->withBody($payload)->post('payment/transfer-webhook');

        $res->assertOK();
        $resData = json_decode($res->response()->getBody(), true);
        $this->assertEquals('success', $resData['status'] ?? '');

        $completed = $payoutModel->find($payoutId);
        $this->assertEquals('completed', $completed['status']);
        $this->assertEquals('succeeded', $completed['transfer_status']);
    }

    /**
     * Test 7: PayMongo send transfer fails safely when wallet balance is 0 or unconfigured.
     */
    public function testSendTransferHandlesUnfundedOrUnconfiguredWalletSafely()
    {
        $setup = $this->createTestShopWithBalance(1000.00, 'Simoun Ibarra', '09221234567');
        $shopId = $setup['shop_id'];
        $uniq = $setup['uniq'];

        $payoutModel = new PayoutModel();
        $payoutId = $payoutModel->insert([
            'shop_id'                => $shopId,
            'reference_number'       => 'WD-TEST-' . $uniq,
            'amount'                 => 500.00,
            'fee'                    => 15.00,
            'deduction_percent'      => 3.00,
            'net_amount'             => 485.00,
            'destination_method'     => 'gcash',
            'destination_detail'     => '09221234567',
            'recipient_account_name' => 'Simoun Ibarra',
            'recipient_institution'  => 'G-Xchange, Inc.',
            'status'                 => 'processing',
            'created_at'             => date('Y-m-d H:i:s'),
        ]);

        // Attempt transfer without live funded wallet: Service returns error
        $res = $this->asAdmin(1)->post('admin/payments/send', [
            'payout_id' => $payoutId,
        ]);

        $res->assertRedirect();
        $error = session()->getFlashdata('error');
        $this->assertNotEmpty($error);

        // Withdrawal must NOT be marked completed; remains in processing with failure reason logged
        $payout = $payoutModel->find($payoutId);
        $this->assertEquals('processing', $payout['status']);
        $this->assertEquals('failed', $payout['transfer_status']);
        $this->assertNotEmpty($payout['failure_reason']);
    }
}
