<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\OrderModel;
use App\Models\ShopModel;
use App\Models\UserModel;

class SalesChartTimezoneTest extends CIUnitTestCase
{
    public function testSalesChartIncludesOrderPlacedToday()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop, 'A shop record must exist in the database');
        $shopId = (int) $shop['id'];

        $customer = (new UserModel())->where('role', 'customer')->first();
        $customerId = $customer ? (int) $customer['id'] : null;

        $orderModel = new OrderModel();

        // 1. Get baseline chart for today
        $baselineChart = $orderModel->getSalesChartData($shopId, '7');
        $todayLabel = date('M d'); // e.g. "Sep 14"
        $todayIndex = count($baselineChart['labels']) - 1;

        $this->assertEquals($todayLabel, $baselineChart['labels'][$todayIndex], 'Last chart label must be today');
        $baselineTodayVal = (float) ($baselineChart['values'][$todayIndex] ?? 0.0);

        // 2. Insert a completed order placed "right now" using PHP local date
        $testAmount = 888.50;
        $orderId = $orderModel->insert([
            'order_number'       => 'TEST-TZ-' . uniqid('', false) . rand(100, 999),
            'customer_id'        => $customerId,
            'shop_id'            => $shopId,
            'fulfillment_method' => 'pickup',
            'payment_method'     => 'pickup',
            'subtotal'           => $testAmount,
            'shipping_fee'       => 0.00,
            'tax_amount'         => 0.00,
            'total_amount'       => $testAmount,
            'status'             => 'completed',
            'payment_status'     => 'paid',
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);
        $this->assertIsNumeric($orderId);

        try {
            // 3. Re-fetch sales chart and assert today's bucket includes the test order
            $updatedChart = $orderModel->getSalesChartData($shopId, '7');
            $updatedTodayVal = (float) ($updatedChart['values'][$todayIndex] ?? 0.0);

            $this->assertEquals(
                round($baselineTodayVal + $testAmount, 2),
                round($updatedTodayVal, 2),
                "Today's sales chart bucket must immediately reflect orders placed today in local time"
            );
        } finally {
            // Clean up
            $orderModel->delete($orderId, true);
        }
    }

    public function testSalesChartZeroFillAndFlexibleRanges()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];
        $orderModel = new OrderModel();

        // 7d flexible range
        $chart7 = $orderModel->getSalesChartData($shopId, '7d');
        $this->assertCount(7, $chart7['labels']);
        $this->assertCount(7, $chart7['values']);
        foreach ($chart7['values'] as $v) {
            $this->assertIsFloat($v);
            $this->assertGreaterThanOrEqual(0.0, $v);
        }

        // 30d flexible range with previous comparison
        $chart30 = $orderModel->getSalesChartData($shopId, '30d', true);
        $this->assertCount(30, $chart30['labels']);
        $this->assertCount(30, $chart30['values']);
        $this->assertArrayHasKey('previous_values', $chart30);
        $this->assertCount(30, $chart30['previous_values']);
        foreach ($chart30['values'] as $v) {
            $this->assertIsFloat($v);
            $this->assertGreaterThanOrEqual(0.0, $v);
        }

        // year flexible range
        $chartYear = $orderModel->getSalesChartData($shopId, '1y', true);
        $this->assertCount(12, $chartYear['labels']);
        $this->assertCount(12, $chartYear['values']);
        $this->assertCount(12, $chartYear['previous_values']);
        foreach ($chartYear['values'] as $v) {
            $this->assertIsFloat($v);
            $this->assertGreaterThanOrEqual(0.0, $v);
        }
    }
}
