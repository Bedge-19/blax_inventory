<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\ShopModel;

class TenantAnalyticsDataTest extends CIUnitTestCase
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
            'X-CSRF-TOKEN'     => $hash,
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    public function testAnalyticsDataReturnsJsonForRanges()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        foreach (['7', '30', 'year'] as $range) {
            $result = $this->asTenant($shopId)->get('tenant/analytics/data?range=' . $range);
            $result->assertOK();
            $result->assertHeader('Content-Type', 'application/json; charset=UTF-8');

            $body = json_decode($result->response()->getBody(), true);
            $this->assertIsArray($body);
            $this->assertTrue($body['success']);
            $this->assertEquals($range, $body['range']);
            $this->assertArrayHasKey('labels', $body);
            $this->assertArrayHasKey('values', $body);
            $this->assertArrayHasKey('previous_values', $body);
            $this->assertArrayHasKey('total', $body);
            $this->assertArrayHasKey('previous_total', $body);
            $this->assertArrayHasKey('growth_pct', $body);

            if ($range === 'year') {
                $this->assertCount(12, $body['labels']);
                $this->assertCount(12, $body['values']);
            } elseif ($range === '7') {
                $this->assertCount(7, $body['labels']);
                $this->assertCount(7, $body['values']);
            } elseif ($range === '30') {
                $this->assertCount(30, $body['labels']);
                $this->assertCount(30, $body['values']);
            }
        }
    }

    public function testAnalyticsDataSupportsPeriodAliases()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        // Test ?period=7d
        $res7 = $this->asTenant($shopId)->get('tenant/analytics/data?period=7d');
        $res7->assertOK();
        $b7 = json_decode($res7->response()->getBody(), true);
        $this->assertEquals('7', $b7['range']);
        $this->assertCount(7, $b7['values']);

        // Test ?range=30d
        $res30 = $this->asTenant($shopId)->get('tenant/analytics/data?range=30d');
        $res30->assertOK();
        $b30 = json_decode($res30->response()->getBody(), true);
        $this->assertEquals('30', $b30['range']);
        $this->assertCount(30, $b30['values']);

        // Test dashboard sales data ?period=year
        $dashRes = $this->asTenant($shopId)->get('tenant/dashboard/sales?period=year');
        $dashRes->assertOK();
        $bDash = json_decode($dashRes->response()->getBody(), true);
        $this->assertEquals('year', $bDash['range']);
        $this->assertCount(12, $bDash['values']);
    }

    public function testRbacTenantAuthReturnsJson401And403OnAjax()
    {
        // 1. Unauthenticated AJAX request
        $unauth = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get('tenant/analytics/data');
        $unauth->assertStatus(401);
        $b401 = json_decode($unauth->response()->getBody(), true);
        $this->assertFalse($b401['success']);
        $this->assertEquals('Authentication required.', $b401['error']);

        // 2. Customer user attempting to access tenant route via AJAX
        $custAuth = $this->withSession([
            'user_id'    => 10,
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ])->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get('tenant/analytics/data');
        $custAuth->assertStatus(403);
        $b403 = json_decode($custAuth->response()->getBody(), true);
        $this->assertFalse($b403['success']);
        $this->assertEquals('Access forbidden. Tenant access required.', $b403['error']);
    }
}
