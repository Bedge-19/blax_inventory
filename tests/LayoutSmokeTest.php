<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class LayoutSmokeTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    private function assertBodyContains(string $needle, \CodeIgniter\Test\TestResponse $result, bool $not = false): void
    {
        $found = strpos($result->getBody(), $needle) !== false;
        if ($not) {
            $this->assertFalse($found, "Text '{$needle}' unexpectedly seen in response body.");
        } else {
            $this->assertTrue($found, "Text '{$needle}' is not seen in response body.");
        }
    }

    public function testAuthLoginRenders()
    {
        $result = $this->get('login');
        $result->assertOK();
        $this->assertBodyContains('Blax', $result);
    }

    public function testAuthSignupRenders()
    {
        $result = $this->get('signup');
        $result->assertOK();
        $this->assertBodyContains('Customer', $result);
    }

    public function testAuthMerchantSignupRenders()
    {
        $result = $this->get('merchant-signup');
        $result->assertOK();
        $this->assertBodyContains('Shop Owner', $result);
    }

    public function testHomepageRenders()
    {
        $result = $this->get('/');
        $result->assertOK();
        $this->assertBodyContains('RHK', $result);
    }

    public function testCategoriesRenders()
    {
        $result = $this->get('categories');
        $result->assertOK();
    }

    public function testShopsRenders()
    {
        $result = $this->get('shops');
        $result->assertOK();
    }

    public function testPrintingServicesRenders()
    {
        $result = $this->get('printing-services');
        $result->assertOK();
    }

    public function testShopStorefrontRenders()
    {
        $result = $this->get('shop/inkmaster');
        $result->assertOK();
        $this->assertBodyContains('InkMaster', $result);
        $this->assertBodyContains('Product Catalog', $result);
        $this->assertBodyContains('Printing Services', $result);
        $this->assertBodyContains('Pay 50% Down Payment via GCash', $result);

        // New paper sizes preserved alongside existing options.
        foreach (['Letter', 'Legal', 'A4', 'A3', 'A2', 'A1', 'A0', 'A5', 'B5', 'B4'] as $size) {
            $this->assertBodyContains($size, $result);
        }

        // Hidden page-count field + search form are present.
        $this->assertBodyContains('name="page_count"', $result);
        $this->assertBodyContains('name="q"', $result);
        $this->assertBodyContains('selectPdfBtn', $result);

        // Pagination is rendered (22 products / 6 per page = 4 pages).
        $this->assertBodyContains('aria-label="Pagination"', $result);
        $this->assertBodyContains('?page=2', $result);
    }

    public function testShopStorefrontPaginationServesLaterPages()
    {
        $result = $this->get('shop/inkmaster?page=2');
        $result->assertOK();
        $this->assertBodyContains('Mechanical Pencil Kit', $result);
        $this->assertBodyContains('Premium A5 Notebook', $result, true);
    }

    public function testShopStorefrontSearchFiltersProducts()
    {
        $result = $this->get('shop/inkmaster?q=Notebook');
        $result->assertOK();
        $this->assertBodyContains('Premium A5 Notebook', $result);
        $this->assertBodyContains('Executive Pen Set', $result, true);
    }

    public function testCountPrintingPagesRejectsMissingFile()
    {
        $result = $this->post('printing/count-pages');
        $result->assertStatus(400);
        $this->assertBodyContains('No PDF file received', $result);
    }

    public function testCustomerPagesRedirectWhenLoggedOut()
    {
        foreach (['customer/orders', 'customer/printing', 'customer/favorites', 'customer/addresses', 'customer/profile'] as $path) {
            $this->get($path)->assertRedirect();
        }
    }

    public function testTenantPagesRedirectWhenLoggedOut()
    {
        $this->get('tenant/dashboard')->assertRedirect();
    }

    public function testCustomerAccountPagesRender()
    {
        $this->withSession([
            'user_id'    => 14,
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ]);

        $result = $this->get('customer/orders');
        $result->assertOK();
        $this->assertBodyContains('My Orders', $result);

        $this->get('customer/printing')->assertOK();
        $this->get('customer/favorites')->assertOK();
        $this->get('customer/addresses')->assertOK();
        $this->get('customer/profile')->assertOK();
    }

    public function testTenantPagesRender()
    {
        $this->withSession([
            'user_id'    => 2,
            'user_role'  => 'shop_owner',
            'isLoggedIn' => true,
        ]);

        $this->get('tenant/dashboard')->assertOK();
        $this->get('tenant/inventory')->assertOK();
        $this->get('tenant/orders')->assertOK();
        $this->get('tenant/pos')->assertOK();
        $this->get('tenant/printing')->assertOK();
        $this->get('tenant/deliveries')->assertOK();
        $this->get('tenant/withdrawals')->assertOK();
        $this->get('tenant/analytics')->assertOK();
        $this->get('tenant/settings')->assertOK();
        $this->get('tenant/archive')->assertOK();
    }

    public function testAdminPagesRender()
    {
        $this->withSession([
            'user_id'    => 1,
            'user_role'  => 'admin',
            'isLoggedIn' => true,
        ]);

        $result = $this->get('admin/dashboard');
        $result->assertOK();
        $this->assertBodyContains('Platform Overview', $result);

        $this->get('admin/tenants')->assertOK();
        $this->get('admin/customers')->assertOK();
        $this->get('admin/payments')->assertOK();
        $this->get('admin/compliance')->assertOK();
        $this->get('admin/tracking')->assertOK();
        $this->get('admin/audit-log')->assertOK();
        $this->get('admin/analytics')->assertOK();
    }
}
