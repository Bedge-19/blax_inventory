<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class TenantPaginationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    private function asTenant(int $shopId = 1, int $userId = 2): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => $userId,
            'user_role'  => 'shop_owner',
            'role'       => 'shop_owner',
            'shop_id'    => $shopId,
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN' => $hash,
        ]);
    }

    private function asAdmin(int $userId = 1): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => $userId,
            'user_role'  => 'admin',
            'role'       => 'admin',
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN' => $hash,
        ]);
    }

    public function testFakeTransactionsRemovedFromPayoutRequests()
    {
        $db = \Config\Database::connect();
        $count = $db->table('payout_requests')->countAllResults();
        $this->assertSame(0, $count, 'All fake transactions in payout_requests must be removed.');
    }

    public function testTenantWithdrawalsPaginationAndPerPage()
    {
        $res = $this->asTenant()->get('tenant/withdrawals?per_page=5');
        $res->assertStatus(200);
        $body = $res->getBody();
        $this->assertStringContainsString('id="withdrawals_per_page"', $body);
        $this->assertStringContainsString('<option value="5" selected>5</option>', $body);
        $this->assertStringContainsString('<option value="10"', $body);
        $this->assertStringContainsString('<option value="15"', $body);
        $this->assertStringContainsString('<option value="20"', $body);

        $res15 = $this->asTenant()->get('tenant/withdrawals?per_page=15');
        $this->assertStringContainsString('<option value="15" selected>15</option>', $res15->getBody());
    }

    public function testTenantArchivePaginationAndPerPage()
    {
        $res = $this->asTenant()->get('tenant/archive?per_page=5');
        $res->assertStatus(200);
        $body = $res->getBody();
        $this->assertStringContainsString('id="archive_per_page"', $body);
        $this->assertStringContainsString('<option value="5" selected>5</option>', $body);
        $this->assertStringContainsString('<option value="10"', $body);
        $this->assertStringContainsString('<option value="15"', $body);
        $this->assertStringContainsString('<option value="20"', $body);

        $res15 = $this->asTenant()->get('tenant/archive?per_page=15');
        $this->assertStringContainsString('<option value="15" selected>15</option>', $res15->getBody());
    }

    public function testTenantOrdersPaginationNoResetAndPerPage()
    {
        $res = $this->asTenant()->get('tenant/orders?per_page=5');
        $res->assertStatus(200);
        $body = $res->getBody();
        $this->assertStringNotContainsString('>Reset</a>', $body);
        $this->assertStringContainsString('id="orders_per_page"', $body);
        $this->assertStringContainsString('<option value="5" selected>5</option>', $body);
        $this->assertStringContainsString('<option value="10"', $body);
        $this->assertStringContainsString('<option value="15"', $body);
        $this->assertStringContainsString('<option value="20"', $body);

        $res15 = $this->asTenant()->get('tenant/orders?per_page=15');
        $this->assertStringContainsString('<option value="15" selected>15</option>', $res15->getBody());
    }

    public function testAdminPaymentsSupports15PerPage()
    {
        $res15 = $this->asAdmin()->get('admin/payments?per_page=15');
        $res15->assertStatus(200);
        $this->assertStringContainsString('<option value="15" selected>15</option>', $res15->getBody());
    }
}
