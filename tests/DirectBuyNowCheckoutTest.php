<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\ShopModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\CartModel;
use App\Models\CartItemModel;
use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\UserModel;
use App\Models\ShippingAddressModel;

class DirectBuyNowCheckoutTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    public function testDirectBuyRequiresLogin()
    {
        $result = $this->withSession([])->get('buy-now?product_id=1&quantity=1');
        $result->assertRedirectTo('/login');
    }

    public function testDirectBuyRendersProductAndVariant()
    {
        $customer = (new UserModel())->where('role', 'customer')->first();
        $this->assertNotNull($customer);

        $product = (new ProductModel())->where('status', 'active')->where('stock_quantity >', 2)->first();
        $this->assertNotNull($product);

        $result = $this->withSession([
            'user_id'    => (int) $customer['id'],
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ])->get('buy-now?product_id=' . $product['id'] . '&quantity=2');

        $result->assertOK();
        $result->assertSee($product['name']);
        $result->assertSee('Instant Direct Checkout');
        $result->assertSee('Payment');
    }

    public function testDirectBuyRejectsOutOfStock()
    {
        $customer = (new UserModel())->where('role', 'customer')->first();
        $this->assertNotNull($customer);

        $shop = (new ShopModel())->first();
        $productModel = new ProductModel();
        $pId = $productModel->insert([
            'shop_id'             => $shop['id'],
            'category_id'         => 1,
            'sku'                 => 'TEST-OOS-' . rand(1000, 9999),
            'name'                => 'Out of Stock Test Item ' . rand(1000, 9999),
            'description'         => 'Zero stock test',
            'price'               => 150.00,
            'stock_quantity'      => 0,
            'status'              => 'active',
            'is_featured'         => 0,
            'low_stock_threshold' => 5,
        ]);
        $this->assertIsNumeric($pId);

        $result = $this->withSession([
            'user_id'    => (int) $customer['id'],
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ])->get('buy-now?product_id=' . $pId . '&quantity=1');

        $result->assertRedirect();
        $result->assertSessionHas('error');

        $productModel->delete($pId, true);
    }

    public function testPlaceOrderCodCreatesOrderDecrementsStockAndKeepsCartIntact()
    {
        $customer = (new UserModel())->where('role', 'customer')->first();
        $this->assertNotNull($customer);
        $userId = (int) $customer['id'];

        $shop = (new ShopModel())->first();
        $productModel = new ProductModel();
        $pId = $productModel->insert([
            'shop_id'             => $shop['id'],
            'category_id'         => 1,
            'sku'                 => 'TEST-COD-' . rand(1000, 9999),
            'name'                => 'Direct Buy COD Test ' . rand(1000, 9999),
            'description'         => 'COD test item',
            'price'               => 200.00,
            'stock_quantity'      => 10,
            'status'              => 'active',
            'is_featured'         => 0,
            'low_stock_threshold' => 2,
        ]);
        $this->assertIsNumeric($pId);

        // Place an item into the customer's cart first to verify it remains untouched
        $cartModel = new CartModel();
        $cartItemModel = new CartItemModel();
        $cart = $cartModel->getOrCreateCart($userId);
        $dummyCartItemId = $cartItemModel->insert([
            'cart_id'     => $cart['id'],
            'product_id'  => $pId,
            'quantity'    => 1,
            'unit_price'  => 200.00,
            'is_selected' => 1,
        ]);

        // Execute Direct Buy Now place order with COD and pickup
        $result = $this->withSession([
            'user_id'    => $userId,
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ])->post('buy-now/place', [
            csrf_token()         => csrf_hash(),
            'product_id'         => $pId,
            'quantity'           => 3,
            'payment_method'     => 'pickup',
            'fulfillment_method' => 'pickup',
        ]);

        $error = session()->getFlashdata('error');
        $this->assertNull($error, 'Unexpected error redirect: ' . ($error ?? 'none'));
        $result->assertRedirectTo('/customer/orders');
        $result->assertSessionHas('success');

        // Check stock was decremented: 10 - 3 = 7
        $updatedProd = $productModel->find($pId);
        $this->assertEquals(7, (int) $updatedProd['stock_quantity']);

        // Check customer's cart item was NOT deleted!
        $cartItemAfter = $cartItemModel->find($dummyCartItemId);
        $this->assertNotNull($cartItemAfter, 'Cart item should remain intact after direct Buy Now purchase');

        // Clean up created orders & order_items
        $oiModel = new OrderItemModel();
        $oModel = new OrderModel();
        $createdOrders = $oModel->where('customer_id', $userId)->where('shop_id', $shop['id'])->findAll();
        foreach ($createdOrders as $co) {
            $oiModel->where('order_id', $co['id'])->delete();
            $oModel->delete($co['id'], true);
        }

        $cartItemModel->delete($dummyCartItemId);
        $productModel->delete($pId, true);
    }

    public function testPlaceOrderGcashStashesDirectBuyPayload()
    {
        $customer = (new UserModel())->where('role', 'customer')->first();
        $userId = (int) $customer['id'];

        $product = (new ProductModel())->where('status', 'active')->where('stock_quantity >', 5)->first();
        $this->assertNotNull($product);

        $address = (new ShippingAddressModel())->where('user_id', $userId)->first();
        $addrId = $address ? (int) $address['id'] : null;

        // Place direct buy order with GCash
        $result = $this->withSession([
            'user_id'    => $userId,
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ])->post('buy-now/place', [
            csrf_token()          => csrf_hash(),
            'product_id'          => $product['id'],
            'quantity'            => 2,
            'payment_method'      => 'gcash',
            'fulfillment_method'  => 'pickup',
            'shipping_address_id' => $addrId,
        ]);

        // GCash redirects to PayMongo checkout or stashes pending data
        $pending = session()->get('pending_cart_checkout_' . $userId);
        if ($pending) {
            $this->assertTrue($pending['is_direct_buy']);
            $this->assertEmpty($pending['selected_ids']);
            $this->assertStringContainsString('buy-now', $pending['cancel_redirect']);
        }
    }

    public function testPlaceOrderCodWithVariantDecrementsBothVariantAndProductStock()
    {
        $customer = (new UserModel())->where('role', 'customer')->first();
        $this->assertNotNull($customer);
        $userId = (int) $customer['id'];

        $shop = (new ShopModel())->first();
        $productModel = new ProductModel();
        $pId = $productModel->insert([
            'shop_id'             => $shop['id'],
            'category_id'         => 1,
            'sku'                 => 'TEST-VAR-' . rand(1000, 9999),
            'name'                => 'Direct Buy Variant Test ' . rand(1000, 9999),
            'description'         => 'Variant test item',
            'price'               => 100.00,
            'stock_quantity'      => 20,
            'status'              => 'active',
            'is_featured'         => 0,
            'low_stock_threshold' => 2,
        ]);
        $this->assertIsNumeric($pId);

        $varModel = new ProductVariantModel();
        $vId = $varModel->insert([
            'product_id'     => $pId,
            'name'           => 'Size',
            'value'          => 'XL',
            'sku_suffix'     => 'XL',
            'stock_quantity' => 8,
            'price_override' => 120.00,
            'is_active'      => 1,
            'sort_order'     => 1,
        ]);
        $this->assertIsNumeric($vId);

        $result = $this->withSession([
            'user_id'    => $userId,
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ])->post('buy-now/place', [
            csrf_token()         => csrf_hash(),
            'product_id'         => $pId,
            'variant_id'         => $vId,
            'quantity'           => 3,
            'payment_method'     => 'pickup',
            'fulfillment_method' => 'pickup',
        ]);

        $result->assertRedirectTo('/customer/orders');
        $result->assertSessionHas('success');

        // Check product stock: 20 - 3 = 17
        $updatedProd = $productModel->find($pId);
        $this->assertEquals(17, (int) $updatedProd['stock_quantity']);

        // Check variant stock: 8 - 3 = 5
        $updatedVar = $varModel->find($vId);
        $this->assertEquals(5, (int) $updatedVar['stock_quantity']);

        // Check order item has variant details
        $lastOrder = (new OrderModel())->where('customer_id', $userId)->orderBy('id', 'DESC')->first();
        $this->assertNotNull($lastOrder);
        $this->assertMatchesRegularExpression('/^ORD-[A-F0-9]{8}$/', $lastOrder['order_number']);
        $orderItem = (new OrderItemModel())->where('order_id', $lastOrder['id'])->first();
        $this->assertNotNull($orderItem);
        $this->assertEquals($vId, (int) $orderItem['variant_id']);
        $this->assertEquals(120.00, (float) $orderItem['unit_price']);

        // Clean up
        (new OrderItemModel())->where('order_id', $lastOrder['id'])->delete();
        (new OrderModel())->delete($lastOrder['id'], true);
        $varModel->delete($vId, true);
        $productModel->delete($pId, true);
    }
}
