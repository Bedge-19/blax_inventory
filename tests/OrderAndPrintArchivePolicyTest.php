<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\ArchivedItemModel;
use App\Models\OrderModel;
use App\Models\PrintingRequestModel;

class OrderAndPrintArchivePolicyTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $db = \Config\Database::connect();
        $db->table('orders')->like('order_number', 'ORD-ARCH-TEST-')->delete();
        $db->table('orders')->like('order_number', 'ORD-RECENT-TEST-')->delete();
        $db->table('orders')->like('order_number', 'ORD-PENDING-TEST-')->delete();
        $db->table('printing_requests')->like('request_number', 'PR-ARCH-TEST-')->delete();
        $db->table('printing_requests')->like('request_number', 'PR-PENDING-TEST-')->delete();
    }

    protected function tearDown(): void
    {
        $db = \Config\Database::connect();
        $db->table('orders')->like('order_number', 'ORD-ARCH-TEST-')->delete();
        $db->table('orders')->like('order_number', 'ORD-RECENT-TEST-')->delete();
        $db->table('orders')->like('order_number', 'ORD-PENDING-TEST-')->delete();
        $db->table('printing_requests')->like('request_number', 'PR-ARCH-TEST-')->delete();
        $db->table('printing_requests')->like('request_number', 'PR-PENDING-TEST-')->delete();

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

    public function testAutoArchiveCompletedOrdersAndPrintingOlderThan3Days()
    {
        $db = \Config\Database::connect();
        $shopId = 1;
        $archiveModel = new ArchivedItemModel();
        $suffix = uniqid();

        // 1. Create a completed order completed 4 days ago
        $fourDaysAgo = date('Y-m-d H:i:s', strtotime('-4 days'));
        $db->table('orders')->insert([
            'order_number'       => 'ORD-ARCH-TEST-' . $suffix,
            'customer_id'        => 3,
            'shop_id'            => $shopId,
            'fulfillment_method' => 'delivery',
            'payment_method'     => 'cod',
            'subtotal'           => 100.00,
            'total_amount'       => 100.00,
            'status'             => 'completed',
            'payment_status'     => 'paid',
            'placed_at'          => $fourDaysAgo,
            'completed_at'       => $fourDaysAgo,
        ]);
        $orderId = (int) $db->insertID();

        // 2. Create a completed order completed 1 day ago (should NOT be auto-archived yet)
        $oneDayAgo = date('Y-m-d H:i:s', strtotime('-1 day'));
        $db->table('orders')->insert([
            'order_number'       => 'ORD-RECENT-TEST-' . $suffix,
            'customer_id'        => 3,
            'shop_id'            => $shopId,
            'fulfillment_method' => 'delivery',
            'payment_method'     => 'cod',
            'subtotal'           => 150.00,
            'total_amount'       => 150.00,
            'status'             => 'completed',
            'payment_status'     => 'paid',
            'placed_at'          => $oneDayAgo,
            'completed_at'       => $oneDayAgo,
        ]);
        $recentOrderId = (int) $db->insertID();

        // 3. Create a pending order created 10 days ago (MUST STAY PENDING, never archived)
        $tenDaysAgo = date('Y-m-d H:i:s', strtotime('-10 days'));
        $db->table('orders')->insert([
            'order_number'       => 'ORD-PENDING-TEST-' . $suffix,
            'customer_id'        => 3,
            'shop_id'            => $shopId,
            'fulfillment_method' => 'delivery',
            'payment_method'     => 'cod',
            'subtotal'           => 200.00,
            'total_amount'       => 200.00,
            'status'             => 'pending',
            'payment_status'     => 'unpaid',
            'placed_at'          => $tenDaysAgo,
        ]);
        $pendingOrderId = (int) $db->insertID();

        // 4. Create a completed printing request completed 4 days ago
        $db->table('printing_requests')->insert([
            'request_number' => 'PR-ARCH-TEST-' . $suffix,
            'shop_id'        => $shopId,
            'customer_id'    => 3,
            'file_url'       => 'writable/uploads/test.pdf',
            'file_name'      => 'test.pdf',
            'paper_size'     => 'A4',
            'color_mode'     => 'B&W',
            'page_count'     => 5,
            'copies'         => 1,
            'total_price'    => 25.00,
            'status'         => 'completed',
            'created_at'     => $fourDaysAgo,
            'completed_at'   => $fourDaysAgo,
        ]);
        $printId = (int) $db->insertID();

        // 5. Create a new/pending printing request created 10 days ago (MUST STAY NEW/PENDING)
        $db->table('printing_requests')->insert([
            'request_number' => 'PR-PENDING-TEST-' . $suffix,
            'shop_id'        => $shopId,
            'customer_id'    => 3,
            'file_url'       => 'writable/uploads/pending.pdf',
            'file_name'      => 'pending.pdf',
            'paper_size'     => 'A4',
            'color_mode'     => 'Color',
            'page_count'     => 2,
            'copies'         => 1,
            'total_price'    => 30.00,
            'status'         => 'new',
            'created_at'     => $tenDaysAgo,
        ]);
        $pendingPrintId = (int) $db->insertID();

        // Run autoArchiveCompletedItems for shop
        $res = $archiveModel->autoArchiveCompletedItems($shopId, 3);
        $this->assertGreaterThanOrEqual(1, $res['archived_orders']);
        $this->assertGreaterThanOrEqual(1, $res['archived_printing']);

        // Check archived_items table
        $archivedOrder = $archiveModel->where('item_type', 'order')->where('item_id', $orderId)->first();
        $this->assertNotNull($archivedOrder, '4-day old completed order should be in archived_items');

        $archivedPrint = $archiveModel->where('item_type', 'printing_request')->where('item_id', $printId)->first();
        $this->assertNotNull($archivedPrint, '4-day old completed printing request should be in archived_items');

        $recentOrderArchived = $archiveModel->where('item_type', 'order')->where('item_id', $recentOrderId)->first();
        $this->assertNull($recentOrderArchived, '1-day old completed order should NOT be archived');

        $pendingOrderArchived = $archiveModel->where('item_type', 'order')->where('item_id', $pendingOrderId)->first();
        $this->assertNull($pendingOrderArchived, 'Pending order should NEVER be archived');

        $pendingPrintArchived = $archiveModel->where('item_type', 'printing_request')->where('item_id', $pendingPrintId)->first();
        $this->assertNull($pendingPrintArchived, 'Pending/new printing request should NEVER be archived');

        // Check active orders query excludes archived order
        $orderModel = new OrderModel();
        $activeOrders = $orderModel->getOrdersByShop($shopId);
        $activeOrderIds = array_column($activeOrders, 'id');
        $this->assertNotContains((string) $orderId, array_map('strval', $activeOrderIds));
        $this->assertContains((string) $recentOrderId, array_map('strval', $activeOrderIds));
        $this->assertContains((string) $pendingOrderId, array_map('strval', $activeOrderIds));

        // Check pending order and request statuses are unchanged
        $checkPendingOrder = $orderModel->find($pendingOrderId);
        $this->assertSame('pending', $checkPendingOrder['status']);

        $checkPendingPrint = (new PrintingRequestModel())->find($pendingPrintId);
        $this->assertSame('new', $checkPendingPrint['status']);

        // Test restoring from archive page
        $restoreRes = $this->asTenant($shopId)->post('tenant/archive/restore/' . $archivedOrder['id']);
        $restoreRes->assertRedirect();
        
        $restoredCheck = $archiveModel->find($archivedOrder['id']);
        $this->assertNull($restoredCheck, 'Restored archive record should be removed from active archive table');
    }
}
