<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\ShopModel;
use App\Models\UserModel;

class CustomerOrderCancellationAndQrTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    private function asCustomer(int $userId, string $email = 'customer@test.com'): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => $userId,
            'user_name'  => 'Test Customer',
            'user_email' => $email,
            'user_role'  => 'customer',
            'role'       => 'customer',
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN'     => $hash,
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    public function testCustomerCanCancelPendingOrderAndRestoreStock()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $userModel = new UserModel();
        $customer = $userModel->where('role', 'customer')->first();
        $this->assertNotNull($customer);
        $customerId = (int) $customer['id'];

        // Create a test product
        $productModel = new ProductModel();
        $initialStock = 25;
        $productId = $productModel->insert([
            'shop_id'        => $shopId,
            'name'           => 'Test Stock Product ' . uniqid('', false) . rand(100, 999),
            'slug'           => 'test-stock-product-' . uniqid('', false) . rand(100, 999),
            'sku'            => 'SKU-STK-' . uniqid('', false) . rand(100, 999),
            'price'          => 120.00,
            'stock_quantity' => $initialStock,
            'is_active'      => 1,
        ]);
        $this->assertIsNumeric($productId);

        // Create an order for this customer with 3 quantity
        $orderModel = new OrderModel();
        $orderItemModel = new OrderItemModel();

        $orderQty = 3;
        $orderId = $orderModel->insert([
            'order_number'        => 'TEST-CANCEL-' . uniqid('', false) . rand(100, 999),
            'customer_id'         => $customerId,
            'shop_id'             => $shopId,
            'fulfillment_method'  => 'pickup',
            'payment_method'      => 'pickup',
            'subtotal'            => 120.00 * $orderQty,
            'shipping_fee'        => 0.00,
            'tax_amount'          => 0.00,
            'total_amount'        => 120.00 * $orderQty,
            'status'              => 'pending',
            'payment_status'      => 'unpaid',
            'placed_at'           => date('Y-m-d H:i:s'),
        ]);
        $this->assertIsNumeric($orderId);

        $orderItemModel->insert([
            'order_id'     => $orderId,
            'product_id'   => $productId,
            'product_name' => 'Test Stock Product',
            'quantity'     => $orderQty,
            'unit_price'   => 120.00,
            'line_total'   => 120.00 * $orderQty,
        ]);

        try {
            // Cancel the order via POST endpoint with customer session
            $result = $this->asCustomer($customerId, (string)$customer['email'])
                ->post("customer/orders/{$orderId}/cancel", [
                    'order_id' => $orderId,
                ]);

            $result->assertStatus(200);
            $json = json_decode($result->getJSON(), true);
            $this->assertTrue($json['success']);
            $this->assertEquals('cancelled', $json['status']);

            // Verify order in database is cancelled
            $updatedOrder = $orderModel->find($orderId);
            $this->assertEquals('cancelled', $updatedOrder['status']);
            $this->assertNotNull($updatedOrder['cancelled_at']);
            $this->assertEquals('Cancelled by customer', $updatedOrder['cancel_reason']);

            // Verify product stock is restored (initialStock + orderQty)
            $updatedProduct = $productModel->find($productId);
            $this->assertEquals($initialStock + $orderQty, (int) $updatedProduct['stock_quantity']);
        } finally {
            $orderModel->delete($orderId, true);
            $orderItemModel->where('order_id', $orderId)->delete();
            $productModel->delete($productId, true);
        }
    }

    public function testCustomerCannotCancelShippedOrReadyOrder()
    {
        $shop = (new ShopModel())->first();
        $shopId = (int) $shop['id'];

        $customer = (new UserModel())->where('role', 'customer')->first();
        $customerId = (int) $customer['id'];

        $orderModel = new OrderModel();
        $orderId = $orderModel->insert([
            'order_number'        => 'TEST-SHIPPED-' . uniqid('', false) . rand(100, 999),
            'customer_id'         => $customerId,
            'shop_id'             => $shopId,
            'fulfillment_method'  => 'delivery',
            'payment_method'      => 'cod',
            'subtotal'            => 250.00,
            'shipping_fee'        => 50.00,
            'tax_amount'          => 0.00,
            'total_amount'        => 300.00,
            'status'              => 'shipped',
            'payment_status'      => 'unpaid',
            'placed_at'           => date('Y-m-d H:i:s'),
        ]);

        try {
            $result = $this->asCustomer($customerId, (string)$customer['email'])
                ->post("customer/orders/{$orderId}/cancel", [
                    'order_id' => $orderId,
                ]);

            // Must return 422 Unprocessable Entity
            $result->assertStatus(422);
            $json = json_decode($result->getJSON(), true);
            $this->assertFalse($json['success']);

            // Status must still be shipped
            $order = $orderModel->find($orderId);
            $this->assertEquals('shipped', $order['status']);
        } finally {
            $orderModel->delete($orderId, true);
        }
    }

    public function testCustomerCannotCancelAnotherCustomersOrder()
    {
        $shop = (new ShopModel())->first();
        $shopId = (int) $shop['id'];

        $customers = (new UserModel())->where('role', 'customer')->findAll();
        $this->assertGreaterThanOrEqual(1, count($customers));
        $customer1 = $customers[0];

        $orderModel = new OrderModel();
        $orderId = $orderModel->insert([
            'order_number'        => 'TEST-OTHER-' . uniqid('', false) . rand(100, 999),
            'customer_id'         => (int) $customer1['id'],
            'shop_id'             => $shopId,
            'fulfillment_method'  => 'pickup',
            'payment_method'      => 'pickup',
            'subtotal'            => 100.00,
            'shipping_fee'        => 0.00,
            'tax_amount'          => 0.00,
            'total_amount'        => 100.00,
            'status'              => 'pending',
            'payment_status'      => 'unpaid',
            'placed_at'           => date('Y-m-d H:i:s'),
        ]);

        try {
            // Attempt to cancel with another user ID
            $result = $this->asCustomer(999999, 'other@test.com')
                ->post("customer/orders/{$orderId}/cancel", [
                    'order_id' => $orderId,
                ]);

            // Must return 404 (or 403)
            $result->assertStatus(404);

            // Status must still be pending
            $order = $orderModel->find($orderId);
            $this->assertEquals('pending', $order['status']);
        } finally {
            $orderModel->delete($orderId, true);
        }
    }
}
