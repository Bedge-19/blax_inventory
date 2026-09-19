<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\ProductModel;
use App\Models\OrderModel;
use App\Models\PrintingRequestModel;
use App\Models\ArchivedItemModel;
use App\Models\AuditLogModel;

class AdminAuditAndTenantArchiveTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    private function asTenant(int $shopId = 1): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => 2,
            'user_role'  => 'shop_owner',
            'shop_id'    => $shopId,
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN' => $hash,
        ]);
    }

    private function asAdmin(): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => 1,
            'user_role'  => 'admin',
            'role'       => 'admin',
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN' => $hash,
        ]);
    }

    /**
     * Test 1: Tenant Archive page renders KPI summary cards, tab counters, and floating batch bar.
     */
    public function testTenantArchiveRendersKpisAndToolbar()
    {
        $res = $this->asTenant(1)->get('tenant/archive');
        $res->assertOK();
        $body = $res->response()->getBody();

        // Check header and KPI cards
        $this->assertStringContainsString('Archive & Recovery Hub', $body);
        $this->assertStringContainsString('Total Stored', $body);
        $this->assertStringContainsString('Archived Orders', $body);
        $this->assertStringContainsString('Archived Products', $body);
        $this->assertStringContainsString('Archived Prints', $body);

        // Check batch action toolbar and search
        $this->assertStringContainsString('floatingArchiveBar', $body);
        $this->assertStringContainsString('Restore Selected', $body);
        $this->assertStringNotContainsString('delete_forever', $body);
        $this->assertStringNotContainsString('Permanently Purge', $body);
        $this->assertStringContainsString('archiveSearch', $body);
        $this->assertStringNotContainsString('Export CSV', $body);
        $this->assertStringContainsString('archiveDetailModal', $body);
    }

    /**
     * Test 2: Archive item detail JSON endpoint returns rich item telemetry.
     */
    public function testArchiveItemDetailJsonEndpoint()
    {
        $db = \Config\Database::connect();
        $uniq = time() . rand(100, 999);

        // Insert dummy product
        $productId = (new ProductModel())->insert([
            'shop_id'        => 1,
            'name'           => 'Test Archived Widget ' . $uniq,
            'sku'            => 'SKU-ARCH-' . $uniq,
            'price'          => 88.50,
            'stock_quantity' => 12,
            'status'         => 'archived',
            'deleted_at'     => date('Y-m-d H:i:s'),
        ]);

        // Insert into archived_items
        $archiveId = (new ArchivedItemModel())->insert([
            'shop_id'     => 1,
            'item_type'   => 'inventory',
            'item_id'     => $productId,
            'item_label'  => 'Test Archived Widget ' . $uniq,
            'archived_by' => 2,
            'archived_at' => date('Y-m-d H:i:s'),
        ]);

        $res = $this->asTenant(1)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('tenant/archive/detail/' . $archiveId);

        $res->assertOK();
        $json = json_decode($res->response()->getBody(), true);
        $this->assertTrue($json['success']);
        $this->assertEquals('inventory', $json['data']['item_type']);
        $this->assertEquals((int) $productId, (int) $json['data']['item_id']);
        $this->assertEquals(88.50, (float) $json['data']['entity']['price']);
    }

    /**
     * Test 3: Restoring an inventory item reactivates product and removes archive record.
     */
    public function testRestoreInventoryItem()
    {
        $uniq = time() . rand(100, 999);
        $productModel = new ProductModel();
        $archiveModel = new ArchivedItemModel();

        $productId = $productModel->insert([
            'shop_id'        => 1,
            'name'           => 'Restore Pen ' . $uniq,
            'sku'            => 'SKU-RESTORE-' . $uniq,
            'price'          => 25.00,
            'stock_quantity' => 5,
            'status'         => 'archived',
            'deleted_at'     => date('Y-m-d H:i:s'),
        ]);

        $archiveId = $archiveModel->insert([
            'shop_id'     => 1,
            'item_type'   => 'inventory',
            'item_id'     => $productId,
            'item_label'  => 'Restore Pen ' . $uniq,
            'archived_by' => 2,
            'archived_at' => date('Y-m-d H:i:s'),
        ]);

        $res = $this->asTenant(1)->post('tenant/archive/restore/' . $archiveId);
        $res->assertRedirect();

        // Product should be active with deleted_at null
        $updatedProduct = $productModel->find($productId);
        $this->assertEquals('active', $updatedProduct['status']);
        $this->assertNull($updatedProduct['deleted_at']);

        // Archive row should be deleted
        $this->assertNull($archiveModel->find($archiveId));
    }

    /**
     * Test 4: Bulk restore restores multiple items in one request.
     */
    public function testBulkRestoreItems()
    {
        $uniq = time() . rand(100, 999);
        $productModel = new ProductModel();
        $archiveModel = new ArchivedItemModel();

        $p1 = $productModel->insert([
            'shop_id'        => 1,
            'name'           => 'Bulk P1 ' . $uniq,
            'sku'            => 'SKU-B1-' . $uniq,
            'price'          => 10.00,
            'stock_quantity' => 2,
            'status'         => 'archived',
            'deleted_at'     => date('Y-m-d H:i:s'),
        ]);
        $a1 = $archiveModel->insert([
            'shop_id'     => 1,
            'item_type'   => 'inventory',
            'item_id'     => $p1,
            'item_label'  => 'Bulk P1 ' . $uniq,
            'archived_by' => 2,
            'archived_at' => date('Y-m-d H:i:s'),
        ]);

        $p2 = $productModel->insert([
            'shop_id'        => 1,
            'name'           => 'Bulk P2 ' . $uniq,
            'sku'            => 'SKU-B2-' . $uniq,
            'price'          => 20.00,
            'stock_quantity' => 3,
            'status'         => 'archived',
            'deleted_at'     => date('Y-m-d H:i:s'),
        ]);
        $a2 = $archiveModel->insert([
            'shop_id'     => 1,
            'item_type'   => 'inventory',
            'item_id'     => $p2,
            'item_label'  => 'Bulk P2 ' . $uniq,
            'archived_by' => 2,
            'archived_at' => date('Y-m-d H:i:s'),
        ]);

        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;
        $res = $this->asTenant(1)
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'X-CSRF-TOKEN'     => $hash,
            ])
            ->post('tenant/archive/bulk-restore', [
                csrf_token()  => $hash,
                'archive_ids' => [$a1, $a2],
            ]);

        $res->assertOK();
        $json = json_decode($res->response()->getBody(), true);
        $this->assertTrue($json['success']);
        $this->assertEquals(2, $json['count']);

        // Both products should be active
        $this->assertEquals('active', $productModel->find($p1)['status']);
        $this->assertEquals('active', $productModel->find($p2)['status']);
    }

    /**
     * Test 5: Permanent delete purges the record from archived_items.
     */
    public function testPermanentDeleteArchiveItem()
    {
        $archiveModel = new ArchivedItemModel();
        $aid = $archiveModel->insert([
            'shop_id'     => 1,
            'item_type'   => 'order',
            'item_id'     => 99999,
            'item_label'  => 'Dummy Purge Order',
            'archived_by' => 2,
            'archived_at' => date('Y-m-d H:i:s'),
        ]);

        $res = $this->asTenant(1)->post('tenant/archive/delete/' . $aid);
        $res->assertRedirect();

        $this->assertNull($archiveModel->find($aid));
    }

    /**
     * Test 6: Tenant Archive CSV export generates a valid CSV spreadsheet.
     */
    public function testTenantArchiveExportCsv()
    {
        $res = $this->asTenant(1)->get('tenant/archive/export');
        $res->assertOK();
        $this->assertEquals('text/csv; charset=UTF-8', $res->response()->getHeaderLine('Content-Type'));
        $body = $res->response()->getBody();
        $this->assertStringContainsString('"Archive ID","Item Type"', $body);
    }

    /**
     * Test 7: Admin Audit Log page renders Security Operations Center KPI cards and presets.
     */
    public function testAdminAuditLogRendersKpisAndPresets()
    {
        $res = $this->asAdmin()->get('admin/audit-log');
        $res->assertOK();
        $body = $res->response()->getBody();

        $this->assertStringContainsString('Security &amp; Activity Audit Log', $body);
        $this->assertStringContainsString('Total (24h)', $body);
        $this->assertStringContainsString('Failed / Alerts', $body);
        $this->assertStringContainsString('Financial Ops', $body);
        $this->assertStringContainsString('Active Entities', $body);
        $this->assertStringContainsString('Quick Presets:', $body);
        $this->assertStringContainsString('Inspect', $body);
        $this->assertStringContainsString('auditInspectModal', $body);
    }

    /**
     * Test 8: Admin Audit Log detail JSON endpoint returns full event telemetry.
     */
    public function testAdminAuditLogDetailJsonEndpoint()
    {
        $auditModel = new AuditLogModel();
        $logId = $auditModel->log(
            1,
            'admin',
            'Test Security Inspection',
            'shop',
            'success',
            1,
            '192.168.1.100'
        );

        $res = $this->asAdmin()
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('admin/audit-log/detail/' . $logId);

        $res->assertOK();
        $json = json_decode($res->response()->getBody(), true);
        $this->assertTrue($json['success']);
        $this->assertEquals('Test Security Inspection', $json['data']['action']);
        $this->assertEquals('192.168.1.100', $json['data']['ip_address']);
        $this->assertEquals('admin', $json['data']['actor_role']);
    }

    /**
     * Test 9: Admin Audit Log CSV export streams valid CSV file.
     */
    public function testAdminAuditLogExportCsv()
    {
        $res = $this->asAdmin()->get('admin/audit-log/export');
        $res->assertOK();
        $this->assertEquals('text/csv; charset=UTF-8', $res->response()->getHeaderLine('Content-Type'));
        $body = $res->response()->getBody();
        $this->assertStringContainsString('"Log ID",Timestamp,"Actor Name"', $body);
    }
}
