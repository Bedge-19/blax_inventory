<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class AuthFiltersTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    public function testLoggedOutRedirectsFromAdmin()
    {
        $result = $this->get('admin/dashboard');
        $result->assertRedirectTo('/login');
    }

    public function testLoggedOutRedirectsFromTenant()
    {
        $result = $this->get('tenant/dashboard');
        $result->assertRedirectTo('/login');
    }

    public function testCustomerRedirectedFromAdmin()
    {
        $result = $this->withSession([
            'user_id'    => 5,
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ])->get('admin/dashboard');

        $result->assertRedirectTo('/');
    }

    public function testCustomerRedirectedFromTenant()
    {
        $result = $this->withSession([
            'user_id'    => 5,
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ])->get('tenant/dashboard');

        $result->assertRedirectTo('/');
    }

    public function testTenantAccessesTenantDashboard()
    {
        $result = $this->withSession([
            'user_id'    => 2,
            'user_role'  => 'shop_owner',
            'shop_id'    => 1,
            'isLoggedIn' => true,
        ])->get('tenant/dashboard');

        $result->assertOK();
    }

    public function testTenantRedirectedFromAdmin()
    {
        $result = $this->withSession([
            'user_id'    => 2,
            'user_role'  => 'shop_owner',
            'shop_id'    => 1,
            'isLoggedIn' => true,
        ])->get('admin/dashboard');

        $result->assertRedirectTo('/');
    }

    public function testAdminAccessesAdminDashboard()
    {
        $result = $this->withSession([
            'user_id'    => 1,
            'user_role'  => 'admin',
            'isLoggedIn' => true,
        ])->get('admin/dashboard');

        $result->assertOK();
    }
}
