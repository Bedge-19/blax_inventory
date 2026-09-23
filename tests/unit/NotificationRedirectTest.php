<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regression test for notification redirect scoping.
 * Ensures non-admin users are never redirected to /admin/* pages.
 */
class NotificationRedirectTest extends CIUnitTestCase
{
    /**
     * Invoke the private resolveSafeRedirectUrl() method via reflection.
     */
    private function resolveUrl(array $notification, string $userRole): string
    {
        $controller = new \App\Controllers\NotificationController();
        $ref = new \ReflectionMethod($controller, 'resolveSafeRedirectUrl');
        $ref->setAccessible(true);

        return $ref->invoke($controller, $notification, $userRole);
    }

    /**
     * A notification with "Customer" in the title must NOT redirect
     * a non-admin (tenant/customer) to /admin/customers.
     */
    public function testCustomerTitleDoesNotRedirectNonAdminToAdminPage()
    {
        $notification = [
            'action_url' => null,
            'type'       => 'order_status',
            'title'      => 'Customer Order Delivered',
        ];

        // Tenant should go to tenant dashboard, NOT /admin/customers
        $url = $this->resolveUrl($notification, 'shop_owner');
        $this->assertStringNotContainsString('/admin/', $url,
            'Tenant should never be redirected to an admin page');

        // Customer should go to customer orders
        $url = $this->resolveUrl($notification, 'customer');
        $this->assertStringNotContainsString('/admin/', $url,
            'Customer should never be redirected to an admin page');
    }

    /**
     * A notification with type customer_registration should redirect
     * an admin to /admin/customers.
     */
    public function testCustomerRegistrationRedirectsAdminToAdminCustomers()
    {
        $notification = [
            'action_url' => null,
            'type'       => 'customer_registration',
            'title'      => 'New Customer Registered',
        ];

        $url = $this->resolveUrl($notification, 'admin');
        $this->assertEquals('/admin/customers', $url);
    }

    /**
     * A notification with type customer_registration should NOT redirect
     * a tenant or customer to /admin/customers.
     */
    public function testCustomerRegistrationDoesNotRedirectNonAdminToAdminCustomers()
    {
        $notification = [
            'action_url' => null,
            'type'       => 'customer_registration',
            'title'      => 'New Customer Registered',
        ];

        $url = $this->resolveUrl($notification, 'shop_owner');
        $this->assertNotEquals('/admin/customers', $url,
            'customer_registration type must NOT redirect tenant to /admin/customers');

        $url = $this->resolveUrl($notification, 'customer');
        $this->assertNotEquals('/admin/customers', $url,
            'customer_registration type must NOT redirect customer to /admin/customers');
    }

    /**
     * Notification with an explicit action_url should always use that URL,
     * regardless of type or title content.
     */
    public function testExplicitActionUrlAlwaysTakesPrecedence()
    {
        $notification = [
            'action_url' => '/customer/orders',
            'type'       => 'customer_registration',
            'title'      => 'Some Customer Notification',
        ];

        // Even though type is customer_registration, explicit URL wins
        $url = $this->resolveUrl($notification, 'admin');
        $this->assertEquals('/customer/orders', $url);

        $url = $this->resolveUrl($notification, 'shop_owner');
        $this->assertEquals('/customer/orders', $url);
    }

    /**
     * Test various notification types resolve to correct role-appropriate URLs.
     */
    public function testTypeMappingForDifferentRoles()
    {
        // 'order' type → tenant sees /tenant/orders, customer sees /customer/orders
        $notification = ['action_url' => null, 'type' => 'order', 'title' => 'Order Update'];
        $this->assertEquals('/tenant/orders', $this->resolveUrl($notification, 'shop_owner'));
        $this->assertEquals('/customer/orders', $this->resolveUrl($notification, 'customer'));

        // 'low_stock' → always /tenant/inventory
        $notification = ['action_url' => null, 'type' => 'low_stock', 'title' => 'Low Stock'];
        $this->assertEquals('/tenant/inventory', $this->resolveUrl($notification, 'shop_owner'));

        // 'new_order' → /tenant/orders
        $notification = ['action_url' => null, 'type' => 'new_order', 'title' => 'New Order'];
        $this->assertEquals('/tenant/orders', $this->resolveUrl($notification, 'shop_owner'));

        // Unknown type → role-based dashboard fallback
        $notification = ['action_url' => null, 'type' => 'unknown_type', 'title' => 'Something'];
        $this->assertEquals('/tenant/dashboard', $this->resolveUrl($notification, 'shop_owner'));
        $this->assertEquals('/admin/dashboard', $this->resolveUrl($notification, 'admin'));
        $this->assertEquals('/', $this->resolveUrl($notification, 'customer'));
    }
}
