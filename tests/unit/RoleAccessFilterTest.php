<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;

/**
 * Regression tests for strict role isolation via RoleAccessFilter.
 * Enforces boundary separation across customer, tenant (shop_owner), and admin roles.
 */
class RoleAccessFilterTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    /**
     * Unauthenticated guest users should always be redirected to /login.
     */
    public function testGuestRedirectedToLoginFromAllProtectedAreas()
    {
        $this->get('customer/profile')->assertRedirectTo('/login');
        $this->get('tenant/dashboard')->assertRedirectTo('/login');
        $this->get('admin/dashboard')->assertRedirectTo('/login');
    }

    /**
     * Unauthenticated AJAX requests receive a 401 JSON response with login redirect.
     */
    public function testGuestAjaxRequestsReturn401()
    {
        $routes = ['customer/profile', 'tenant/dashboard', 'admin/dashboard'];

        foreach ($routes as $route) {
            $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get($route);
            $response->assertStatus(401);
            $json = json_decode($response->response()->getBody(), true);
            $this->assertFalse($json['success']);
            $this->assertEquals('Authentication required.', $json['error']);
            $this->assertStringContainsString('login', $json['redirect']);
        }
    }

    /**
     * Customer can access customer pages, but is rejected from tenant and admin.
     */
    public function testCustomerRoleBoundaryEnforcement()
    {
        $customer = (new UserModel())->where('role', 'customer')->first();
        $customerId = $customer ? (int) $customer['id'] : 1;

        $customerSession = [
            'user_id'    => $customerId,
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ];

        // Customer permitted on customer routes
        $custProfile = $this->withSession($customerSession)->get('customer/profile');
        $custProfile->assertOK();

        // Customer rejected from tenant
        $tenantAttempt = $this->withSession($customerSession)->get('tenant/dashboard');
        $tenantAttempt->assertRedirectTo('/');
        $tenantAttempt->assertSessionHas('error');

        // Customer rejected from admin
        $adminAttempt = $this->withSession($customerSession)->get('admin/dashboard');
        $adminAttempt->assertRedirectTo('/');
        $adminAttempt->assertSessionHas('error');
    }

    /**
     * Tenant (shop_owner) can access tenant pages, but is rejected from customer and admin.
     */
    public function testTenantRoleBoundaryEnforcement()
    {
        $tenantSession = [
            'user_id'    => 202,
            'user_role'  => 'shop_owner',
            'shop_id'    => 1,
            'isLoggedIn' => true,
        ];

        // Tenant permitted on tenant routes
        $tenantDash = $this->withSession($tenantSession)->get('tenant/dashboard');
        $tenantDash->assertOK();

        // Tenant rejected from customer routes
        $custAttempt = $this->withSession($tenantSession)->get('customer/profile');
        $custAttempt->assertRedirectTo('/');
        $custAttempt->assertSessionHas('error');

        // Tenant rejected from admin routes
        $adminAttempt = $this->withSession($tenantSession)->get('admin/dashboard');
        $adminAttempt->assertRedirectTo('/');
        $adminAttempt->assertSessionHas('error');
    }

    /**
     * Admin can access admin pages, but is rejected from customer and tenant areas.
     */
    public function testAdminRoleBoundaryEnforcement()
    {
        $adminSession = [
            'user_id'    => 303,
            'user_role'  => 'admin',
            'isLoggedIn' => true,
        ];

        // Admin permitted on admin routes
        $adminDash = $this->withSession($adminSession)->get('admin/dashboard');
        $adminDash->assertOK();

        // Admin rejected from customer routes
        $custAttempt = $this->withSession($adminSession)->get('customer/profile');
        $custAttempt->assertRedirectTo('/');
        $custAttempt->assertSessionHas('error');

        // Admin rejected from tenant routes
        $tenantAttempt = $this->withSession($adminSession)->get('tenant/dashboard');
        $tenantAttempt->assertRedirectTo('/');
        $tenantAttempt->assertSessionHas('error');
    }

    /**
     * Unauthorized AJAX requests receive 403 Forbidden with proper error message.
     */
    public function testUnauthorizedAjaxRequestsReturn403()
    {
        // 1. Customer hitting tenant AJAX
        $custToTenant = $this->withSession([
            'user_id'    => 101,
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ])->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get('tenant/analytics/data');

        $custToTenant->assertStatus(403);
        $json = json_decode($custToTenant->response()->getBody(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals('Access forbidden. Tenant access required.', $json['error']);

        // 2. Tenant hitting customer AJAX
        $tenantToCust = $this->withSession([
            'user_id'    => 202,
            'user_role'  => 'shop_owner',
            'isLoggedIn' => true,
        ])->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get('customer/profile');

        $tenantToCust->assertStatus(403);
        $json2 = json_decode($tenantToCust->response()->getBody(), true);
        $this->assertFalse($json2['success']);
        $this->assertEquals('Access forbidden. Customer account required.', $json2['error']);

        // 3. Customer hitting admin AJAX
        $custToAdmin = $this->withSession([
            'user_id'    => 101,
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ])->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get('admin/dashboard');

        $custToAdmin->assertStatus(403);
        $json3 = json_decode($custToAdmin->response()->getBody(), true);
        $this->assertFalse($json3['success']);
        $this->assertEquals('Access forbidden. Admin privileges required.', $json3['error']);
    }

    /**
     * Test role synonym: 'shop_owner' and 'tenant' should both be accepted for tenant routes.
     */
    public function testShopOwnerAndTenantRoleSynonyms()
    {
        // Using 'tenant'
        $resp1 = $this->withSession([
            'user_id'    => 202,
            'user_role'  => 'tenant',
            'shop_id'    => 1,
            'isLoggedIn' => true,
        ])->get('tenant/dashboard');
        $resp1->assertOK();

        // Using 'shop_owner'
        $resp2 = $this->withSession([
            'user_id'    => 202,
            'user_role'  => 'shop_owner',
            'shop_id'    => 1,
            'isLoggedIn' => true,
        ])->get('tenant/dashboard');
        $resp2->assertOK();
    }
}
