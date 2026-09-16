<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\ShopModel;
use App\Models\UserModel;
use App\Models\NotificationModel;
use App\Models\PayoutModel;

class AdminNotificationsTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    private function asAdmin(): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => 1,
            'user_role'  => 'admin',
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN'     => $hash,
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    public function testToggleTenantStatusCreatesNotification()
    {
        $shop = (new ShopModel())->where('status', 'active')->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];
        $ownerId = (int) $shop['owner_id'];

        $notifModel = new NotificationModel();
        $beforeCount = $notifModel->where('user_id', $ownerId)->where('type', 'shop_status')->countAllResults();

        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;
        $res = $this->asAdmin()->post('admin/tenants/toggle-status', [
            'tenant_id'  => $shopId,
            csrf_token() => $hash,
        ]);
        $res->assertSessionHas('success');

        $afterCount = $notifModel->where('user_id', $ownerId)->where('type', 'shop_status')->countAllResults();
        $this->assertGreaterThan($beforeCount, $afterCount, 'Toggling tenant status must notify shop owner');

        // Restore back to active
        $hash2 = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash2;
        $this->asAdmin()->post('admin/tenants/toggle-status', [
            'tenant_id'  => $shopId,
            csrf_token() => $hash2,
        ]);
    }

    public function testToggleCustomerStatusCreatesNotification()
    {
        $customer = (new UserModel())->where('role', 'customer')->where('status', 'active')->first();
        $this->assertNotNull($customer);
        $customerId = (int) $customer['id'];

        $notifModel = new NotificationModel();
        $beforeCount = $notifModel->where('user_id', $customerId)->where('type', 'account_status')->countAllResults();

        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;
        $res = $this->asAdmin()->post('admin/customers/toggle-status', [
            'customer_id' => $customerId,
            csrf_token()  => $hash,
        ]);
        $res->assertSessionHas('success');

        $afterCount = $notifModel->where('user_id', $customerId)->where('type', 'account_status')->countAllResults();
        $this->assertGreaterThan($beforeCount, $afterCount, 'Toggling customer status must notify customer');

        // Restore back to active
        $hash2 = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash2;
        $this->asAdmin()->post('admin/customers/toggle-status', [
            'customer_id' => $customerId,
            csrf_token()  => $hash2,
        ]);
    }
}
