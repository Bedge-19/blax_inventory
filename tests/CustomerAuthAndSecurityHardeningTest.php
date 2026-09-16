<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;

class CustomerAuthAndSecurityHardeningTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    public function testCustomerRoutesRejectUnauthenticatedGuest()
    {
        $result = $this->withSession([])->get('customer/orders');
        $result->assertRedirectTo('/login');

        $result2 = $this->withSession([])->get('customer/profile');
        $result2->assertRedirectTo('/login');
    }

    public function testCustomerRoutesRejectNonCustomerRole()
    {
        // Merchant/shop_owner attempting to access customer profile
        $result = $this->withSession([
            'user_id'    => 2,
            'user_role'  => 'shop_owner',
            'isLoggedIn' => true,
        ])->get('customer/profile');

        $result->assertRedirectTo('/');
    }

    public function testCustomerRoutesAllowAuthenticatedCustomer()
    {
        $customer = (new UserModel())->where('role', 'customer')->first();
        $this->assertNotNull($customer);

        $result = $this->withSession([
            'user_id'    => (int) $customer['id'],
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ])->get('customer/profile');

        $result->assertOK();
    }

    public function testAiAssistantChatRequiresCsrf()
    {
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);

        // POST without CSRF token must throw SecurityException (rejected by CSRF filter)
        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('ai-assistant/chat', [
            'message' => 'hello',
        ]);
    }

    public function testTenantCannotDeleteOtherShopProduct()
    {
        $shopModel = new \App\Models\ShopModel();
        $shops = $shopModel->findAll(2);
        if (count($shops) < 2) {
            $this->markTestSkipped('Need at least 2 shops to test cross-tenant isolation.');
        }

        $shop1 = $shops[0];
        $shop2 = $shops[1];

        $productModel = new \App\Models\ProductModel();
        $prodShop2 = $productModel->where('shop_id', $shop2['id'])->where('status', 'active')->first();
        if (!$prodShop2) {
            $prodId = $productModel->insert([
                'shop_id' => $shop2['id'],
                'category_id' => 1,
                'name' => 'Isolation Test Product',
                'sku' => 'ISO-' . uniqid(),
                'price' => 100,
                'stock_quantity' => 10,
                'status' => 'active',
            ]);
            $prodShop2 = $productModel->find($prodId);
        }

        // Shop 1 owner tries to delete Shop 2 product
        $result = $this->withSession([
            'user_id'    => (int) $shop1['owner_id'],
            'user_role'  => 'shop_owner',
            'isLoggedIn' => true,
        ])->post('tenant/products/delete/' . $prodShop2['id'], [
            csrf_token() => csrf_hash(),
        ]);

        $result->assertSessionHas('error', 'Product not found.');
    }

    public function testCustomerCannotUseOtherCustomerAddressOnCheckout()
    {
        $userModel = new UserModel();
        $customers = $userModel->where('role', 'customer')->findAll(2);
        if (count($customers) < 2) {
            $this->markTestSkipped('Need at least 2 customers to test cross-customer address isolation.');
        }

        $cust1 = $customers[0];
        $cust2 = $customers[1];

        $addrModel = new \App\Models\ShippingAddressModel();
        $addrCust2 = $addrModel->where('user_id', $cust2['id'])->first();
        if (!$addrCust2) {
            $aId = $addrModel->insert([
                'user_id' => $cust2['id'],
                'recipient_name' => 'Victim',
                'phone' => '09123456789',
                'street_address' => 'Street 1',
                'barangay' => 'Poblacion',
                'city' => 'Polomolok',
                'province' => 'South Cotabato',
                'postal_code' => '9504',
                'is_default' => 1,
            ]);
            $addrCust2 = $addrModel->find($aId);
        }

        $prod = (new \App\Models\ProductModel())->where('status', 'active')->where('stock_quantity >', 2)->first();

        // Customer 1 tries to use Customer 2's address ID
        $result = $this->withSession([
            'user_id'    => (int) $cust1['id'],
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ])->post('buy-now/place', [
            csrf_token()          => csrf_hash(),
            'product_id'          => $prod['id'],
            'quantity'            => 1,
            'payment_method'      => 'cod',
            'fulfillment_method'  => 'delivery',
            'shipping_address_id' => $addrCust2['id'],
        ]);

        $result->assertSessionHas('error', 'Invalid shipping address selected.');
    }
}
