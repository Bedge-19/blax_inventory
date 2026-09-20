<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class AdminPaginationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
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

    public function testDashboardPaginationAndPerPage()
    {
        $res = $this->asAdmin()->get('admin/dashboard?per_page=5');
        $res->assertStatus(200);
        $body = $res->getBody();
        $this->assertStringContainsString('Active Tenants &amp; Recent Registrations', $body);
        $this->assertStringContainsString('id="dashboard_per_page"', $body);
        $this->assertStringContainsString('<option value="5" selected>5</option>', $body);
        $this->assertStringContainsString('<option value="10"', $body);
        $this->assertStringContainsString('<option value="20"', $body);

        $res10 = $this->asAdmin()->get('admin/dashboard?per_page=10');
        $this->assertStringContainsString('<option value="10" selected>10</option>', $res10->getBody());

        $res20 = $this->asAdmin()->get('admin/dashboard?per_page=20');
        $this->assertStringContainsString('<option value="20" selected>20</option>', $res20->getBody());
    }

    public function testPaymentsPaginationNoResetAndPerPage()
    {
        $res = $this->asAdmin()->get('admin/payments?per_page=5');
        $res->assertStatus(200);
        $body = $res->getBody();
        $this->assertStringNotContainsString('>Reset</a>', $body);
        $this->assertStringContainsString('name="per_page"', $body);
        $this->assertStringContainsString('<option value="5" selected>5</option>', $body);
        $this->assertStringContainsString('<option value="10"', $body);
        $this->assertStringContainsString('<option value="20"', $body);

        $res10 = $this->asAdmin()->get('admin/payments?per_page=10');
        $this->assertStringContainsString('<option value="10" selected>10</option>', $res10->getBody());

        $res20 = $this->asAdmin()->get('admin/payments?per_page=20');
        $this->assertStringContainsString('<option value="20" selected>20</option>', $res20->getBody());
    }

    public function testTenantsPaginationNoResetAndPerPage()
    {
        $res = $this->asAdmin()->get('admin/tenants?per_page=5');
        $res->assertStatus(200);
        $body = $res->getBody();
        $this->assertStringNotContainsString('>Reset</a>', $body);
        $this->assertStringContainsString('name="per_page"', $body);
        $this->assertStringContainsString('<option value="5" selected>5</option>', $body);
        $this->assertStringContainsString('<option value="10"', $body);
        $this->assertStringContainsString('<option value="20"', $body);

        $res10 = $this->asAdmin()->get('admin/tenants?per_page=10');
        $this->assertStringContainsString('<option value="10" selected>10</option>', $res10->getBody());

        $res20 = $this->asAdmin()->get('admin/tenants?per_page=20');
        $this->assertStringContainsString('<option value="20" selected>20</option>', $res20->getBody());
    }

    public function testCustomersPaginationNoResetAndPerPage()
    {
        $res = $this->asAdmin()->get('admin/customers?per_page=5');
        $res->assertStatus(200);
        $body = $res->getBody();
        $this->assertStringNotContainsString('>Reset</a>', $body);
        $this->assertStringContainsString('name="per_page"', $body);
        $this->assertStringContainsString('<option value="5" selected>5</option>', $body);
        $this->assertStringContainsString('<option value="10"', $body);
        $this->assertStringContainsString('<option value="20"', $body);

        $res10 = $this->asAdmin()->get('admin/customers?per_page=10');
        $this->assertStringContainsString('<option value="10" selected>10</option>', $res10->getBody());

        $res20 = $this->asAdmin()->get('admin/customers?per_page=20');
        $this->assertStringContainsString('<option value="20" selected>20</option>', $res20->getBody());
    }

    public function testCompliancePaginationNoResetAndPerPage()
    {
        $res = $this->asAdmin()->get('admin/compliance?per_page=5');
        $res->assertStatus(200);
        $body = $res->getBody();
        $this->assertStringNotContainsString('>Reset</a>', $body);
        $this->assertStringContainsString('name="per_page"', $body);
        $this->assertStringContainsString('<option value="5" selected>5</option>', $body);
        $this->assertStringContainsString('<option value="10"', $body);
        $this->assertStringContainsString('<option value="20"', $body);

        $res10 = $this->asAdmin()->get('admin/compliance?per_page=10');
        $this->assertStringContainsString('<option value="10" selected>10</option>', $res10->getBody());

        $res20 = $this->asAdmin()->get('admin/compliance?per_page=20');
        $this->assertStringContainsString('<option value="20" selected>20</option>', $res20->getBody());
    }

    public function testAuditLogPaginationNoResetAndPerPage()
    {
        $res = $this->asAdmin()->get('admin/audit-log?per_page=5');
        $res->assertStatus(200);
        $body = $res->getBody();
        $this->assertStringNotContainsString('>Reset</a>', $body);
        $this->assertStringContainsString('id="audit_per_page"', $body);
        $this->assertStringContainsString('<option value="5" selected>5</option>', $body);
        $this->assertStringContainsString('<option value="10"', $body);
        $this->assertStringContainsString('<option value="20"', $body);

        $res10 = $this->asAdmin()->get('admin/audit-log?per_page=10');
        $this->assertStringContainsString('<option value="10" selected>10</option>', $res10->getBody());

        $res20 = $this->asAdmin()->get('admin/audit-log?per_page=20');
        $this->assertStringContainsString('<option value="20" selected>20</option>', $res20->getBody());
    }
}
