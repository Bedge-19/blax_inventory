<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class TenantBulkActionsTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    private function asTenant(): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => 2,
            'user_role'  => 'shop_owner',
            'shop_id'    => 1,
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN' => $hash,
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    public function testBulkStockAdjustRequiresSelection()
    {
        $result = $this->asTenant()->post('tenant/products/bulk-adjust-stock', [
            'product_ids' => [],
            'delta'       => 5,
        ]);
        $result->assertStatus(422);
    }

    public function testBulkArchiveRequiresSelection()
    {
        $result = $this->asTenant()->post('tenant/products/bulk-archive', [
            'product_ids' => [],
        ]);
        $result->assertStatus(422);
    }

    public function testBulkStockAdjustSuccess()
    {
        $result = $this->asTenant()->post('tenant/products/bulk-adjust-stock', [
            'product_ids' => [1],
            'delta'       => 5,
        ]);
        $result->assertOK();
        $json = json_decode($result->response()->getBody(), true);
        $this->assertTrue($json['success']);
    }
}
