<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\OrderModel;
use App\Models\ShopModel;
use App\Models\UserModel;
use App\Models\PrintingRequestModel;

class TenantStatusSortingAndRevenueTest extends CIUnitTestCase
{
    public function testOrderTablePrioritizesUrgentStatusesFirst()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop, 'Shop must exist');
        $shopId = (int) $shop['id'];

        $customer = (new UserModel())->where('role', 'customer')->first();
        $customerId = $customer ? (int) $customer['id'] : null;

        $orderModel = new OrderModel();

        // Insert test orders with different statuses
        $baseTime = time();
        $insertedIds = [];

        $statusesToTest = [
            'completed',
            'pending',
            'shipped',
            'processing',
            'ready_for_pickup',
            'delivered',
            'cancelled'
        ];

        foreach ($statusesToTest as $idx => $st) {
            $id = $orderModel->insert([
                'order_number'       => 'TEST-PRIO-' . $baseTime . '-' . $idx,
                'customer_id'        => $customerId,
                'shop_id'            => $shopId,
                'fulfillment_method' => 'delivery',
                'payment_method'     => 'cod',
                'subtotal'           => 100.00,
                'shipping_fee'       => 0.00,
                'tax_amount'         => 0.00,
                'total_amount'       => 100.00,
                'status'             => $st,
                'placed_at'          => date('Y-m-d H:i:s', $baseTime - ($idx * 60)),
            ]);
            $insertedIds[$st] = $id;
        }

        try {
            // Fetch paginated orders with search query for this batch
            $result = $orderModel->getOrdersByShopPaginated(
                $shopId,
                'TEST-PRIO-' . $baseTime,
                null,
                null,
                null,
                20,
                1
            );

            $orders = $result['orders'];
            $this->assertCount(count($statusesToTest), $orders);

            $orderedStatuses = array_map(fn($o) => strtolower($o['status']), $orders);

            // Expected order:
            // 1. pending
            // 2. processing
            // 3. ready_for_pickup
            // 4. shipped
            // 5. delivered
            // 6. completed
            // 7. cancelled
            $expectedOrder = [
                'pending',
                'processing',
                'ready_for_pickup',
                'shipped',
                'delivered',
                'completed',
                'cancelled'
            ];

            $this->assertEquals($expectedOrder, $orderedStatuses, 'Orders must be sorted by status priority');
        } finally {
            foreach ($insertedIds as $id) {
                $orderModel->delete($id, true);
            }
        }
    }

    public function testRevenueReflectsCompletedOrderAndPrintingRequestToday()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $customer = (new UserModel())->where('role', 'customer')->first();
        $customerId = $customer ? (int) $customer['id'] : null;

        $orderModel = new OrderModel();
        $prModel    = new PrintingRequestModel();

        // Baseline chart
        $baseline = $orderModel->getSalesChartData($shopId, '7');
        $todayIdx = count($baseline['labels']) - 1;
        $baseTodayVal = (float) ($baseline['values'][$todayIdx] ?? 0.0);

        // 1. Order placed 3 days ago, completed TODAY
        $threeDaysAgo = date('Y-m-d H:i:s', strtotime('-3 days'));
        $now = date('Y-m-d H:i:s');
        $orderAmount = 350.00;

        $orderId = $orderModel->insert([
            'order_number'       => 'TEST-COMPL-ORD-' . uniqid('', false) . rand(100, 999),
            'customer_id'        => $customerId,
            'shop_id'            => $shopId,
            'fulfillment_method' => 'delivery',
            'payment_method'     => 'cod',
            'subtotal'           => $orderAmount,
            'shipping_fee'       => 0.00,
            'tax_amount'         => 0.00,
            'total_amount'       => $orderAmount,
            'status'             => 'completed',
            'placed_at'          => $threeDaysAgo,
            'completed_at'       => $now,
        ]);

        // 2. Printing request created 2 days ago, completed TODAY
        $twoDaysAgo = date('Y-m-d H:i:s', strtotime('-2 days'));
        $printAmount = 150.00;

        $prId = $prModel->insert([
            'request_number'     => 'TEST-COMPL-PR-' . uniqid('', false) . rand(100, 999),
            'customer_id'        => $customerId,
            'shop_id'            => $shopId,
            'file_name'          => 'test.pdf',
            'file_url'           => 'uploads/test.pdf',
            'document_type'      => 'pdf',
            'page_count'         => 10,
            'paper_size'         => 'A4',
            'color_mode'         => 'bw',
            'copies'             => 1,
            'total_price'        => $printAmount,
            'status'             => 'completed',
            'created_at'         => $twoDaysAgo,
            'completed_at'       => $now,
        ]);

        try {
            $updated = $orderModel->getSalesChartData($shopId, '7');
            $updatedTodayVal = (float) ($updated['values'][$todayIdx] ?? 0.0);

            $expectedTodayVal = round($baseTodayVal + $orderAmount + $printAmount, 2);
            $this->assertEquals(
                $expectedTodayVal,
                round($updatedTodayVal, 2),
                "Today's revenue must include completed orders and completed printing requests completed today"
            );
        } finally {
            if ($orderId) {
                $orderModel->delete($orderId, true);
            }
            if ($prId) {
                $prModel->delete($prId, true);
            }
        }
    }
}
