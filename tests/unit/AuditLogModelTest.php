<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\AuditLogModel;
use App\Models\SiteContentModel;

final class AuditLogModelTest extends CIUnitTestCase
{
    public function testAuditLogInsertsSuccessfully(): void
    {
        $model = new AuditLogModel();

        $percent = 4.5;
        $insertId = $model->log(
            1,
            'admin',
            'Updated Platform Deduction Fee',
            "Set platform withdrawal deduction percentage to {$percent}%",
            'success'
        );

        $this->assertNotFalse($insertId);

        $inserted = $model->find($insertId);
        $this->assertNotNull($inserted);
        $this->assertSame('admin', $inserted['actor_role']);
        $this->assertSame('Updated Platform Deduction Fee', $inserted['action']);
        $this->assertSame("Set platform withdrawal deduction percentage to {$percent}%", $inserted['target_type']);
        $this->assertSame('success', $inserted['status']);

        // Clean up
        $model->delete($insertId, true);
    }

    public function testAuditLogNormalizesRoleAndHandlesNullActor(): void
    {
        $model = new AuditLogModel();

        // Non-existent actor ID and custom role
        $insertId = $model->log(
            99999999,
            'shop_owner',
            'Test Action',
            'order',
            'FAILED',
            42
        );

        $this->assertNotFalse($insertId);

        $inserted = $model->find($insertId);
        $this->assertNotNull($inserted);
        // actor_id should be set to null because user 99999999 doesn't exist
        $this->assertNull($inserted['actor_id']);
        // 'shop_owner' should be mapped to 'tenant'
        $this->assertSame('tenant', $inserted['actor_role']);
        $this->assertSame('Test Action', $inserted['action']);
        $this->assertSame('failed', $inserted['status']);
        $this->assertEquals(42, $inserted['target_id']);

        // Clean up
        $model->delete($insertId, true);
    }

    public function testUpdateDeductionPercentEndpointLogsAudit(): void
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        $percent = 3.75;
        $auditLogModel = new AuditLogModel();
        $countBefore = $auditLogModel->where('action', 'Updated Platform Deduction Fee')->countAllResults();

        // Call the controller method directly or instantiate
        $controller = new \App\Controllers\Admin();
        $request = \Config\Services::request();
        $request->setMethod('POST');
        $request->setGlobal('post', ['deduction_percent' => (string) $percent]);

        // Mock session with admin user
        session()->set([
            'user_id'    => 1,
            'user_role'  => 'admin',
            'isLoggedIn' => true,
        ]);

        $controller->initController($request, \Config\Services::response(), \Config\Services::logger());
        $response = $controller->updateDeductionPercent();

        $this->assertNotNull($response);
        $countAfter = $auditLogModel->where('action', 'Updated Platform Deduction Fee')->countAllResults();
        $this->assertGreaterThan($countBefore, $countAfter);

        // Verify site content updated
        $siteContentModel = new \App\Models\SiteContentModel();
        $this->assertEquals($percent, $siteContentModel->getPlatformDeductionPercent());

        // Clean up logged audit entry
        $latest = $auditLogModel->where('action', 'Updated Platform Deduction Fee')->orderBy('id', 'DESC')->first();
        if ($latest) {
            $auditLogModel->delete($latest['id'], true);
        }
    }
}
