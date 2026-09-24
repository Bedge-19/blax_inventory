<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\DeliveryModel;
use App\Models\ShopModel;
use App\Models\UserModel;

class RealtimeAndLiveRouteTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    private function asTenant(int $userId, int $shopId): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'user_id'    => $userId,
            'user_name'  => 'Test Tenant',
            'user_email' => 'tenant@test.com',
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
            'user_name'  => 'Super Admin',
            'user_email' => 'admin@blax.com',
            'user_role'  => 'admin',
            'role'       => 'admin',
            'isLoggedIn' => true,
        ])->withHeaders([
            'X-CSRF-TOKEN' => $hash,
        ]);
    }

    public function testServiceAreaCoordinateBounds()
    {
        // Polomolok center is within bounds
        $this->assertTrue(DeliveryModel::isPolomolokCoordinate(6.2185, 125.0650));
        // Tupi center is within bounds
        $this->assertTrue(DeliveryModel::isPolomolokCoordinate(6.3333, 124.9500));
        // Outside bounds (e.g. Manila, Davao, Gensan outside bounds)
        $this->assertFalse(DeliveryModel::isPolomolokCoordinate(14.5995, 120.9842));
        $this->assertFalse(DeliveryModel::isPolomolokCoordinate(7.1907, 125.4553));
    }

    public function testBarangayCentroidsForPolomolokAndTupi()
    {
        // Polomolok barangays
        $cannery = DeliveryModel::getBarangayCoordinate('Cannery Site', 'Polomolok');
        $this->assertNotNull($cannery);
        $this->assertEqualsWithDelta(6.2415, $cannery['lat'], 0.01);
        $this->assertEqualsWithDelta(125.0740, $cannery['lng'], 0.01);

        $poblacionPolo = DeliveryModel::getBarangayCoordinate('Poblacion', 'Polomolok');
        $this->assertNotNull($poblacionPolo);
        $this->assertEqualsWithDelta(6.2185, $poblacionPolo['lat'], 0.01);

        // Tupi barangays
        $poblacionTupi = DeliveryModel::getBarangayCoordinate('Poblacion', 'Tupi');
        $this->assertNotNull($poblacionTupi);
        $this->assertEqualsWithDelta(6.3333, $poblacionTupi['lat'], 0.01);
        $this->assertEqualsWithDelta(124.9500, $poblacionTupi['lng'], 0.01);

        $acmonan = DeliveryModel::getBarangayCoordinate('Acmonan', 'Tupi');
        $this->assertNotNull($acmonan);
        $this->assertEqualsWithDelta(6.3350, $acmonan['lat'], 0.01);

        $polonoling = DeliveryModel::getBarangayCoordinate('Polonoling', 'Tupi');
        $this->assertNotNull($polonoling);
        $this->assertEqualsWithDelta(6.3010, $polonoling['lat'], 0.01);
    }

    public function testComputeRouteApiEndpoint()
    {
        // Test route calculation between Polomolok Poblacion and Tupi Poblacion
        $origin = ['lat' => 6.2185, 'lng' => 125.0650];
        $dest = ['lat' => 6.3333, 'lng' => 124.9500];

        $result = $this->withBody(json_encode([
            'origin' => $origin,
            'destination' => $dest,
        ]))->withHeaders([
            'Content-Type' => 'application/json',
        ])->post('api/route');

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);

        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('route', $json);
        $route = $json['route'];
        $this->assertGreaterThan(0, $route['distance_meters']);
        $this->assertNotEmpty($route['distance_text']);
        $this->assertNotEmpty($route['duration_text']);
        $this->assertTrue(!empty($route['points']) || !empty($route['encodedPolyline']));
    }

    public function testTenantRealtimeCheckRequiresAuth()
    {
        $result = $this->get('tenant/realtime/check');
        // Filter or redirect kicks in
        $this->assertTrue($result->isRedirect() || $result->response()->getStatusCode() === 401);
    }

    public function testTenantRealtimeCheckReturnsExpectedStructure()
    {
        $shop = (new ShopModel())->first();
        if (!$shop) {
            $this->markTestSkipped('No shops available in database to test tenant realtime check.');
        }

        $tenantUser = (new UserModel())->find($shop['owner_id']);
        if (!$tenantUser) {
            $this->markTestSkipped('Shop owner not found in users table.');
        }

        $result = $this->asTenant((int) $tenantUser['id'], (int) $shop['id'])
            ->get('tenant/realtime/check?last_order_id=0&last_printing_id=0');

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);

        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('max_order_id', $json);
        $this->assertArrayHasKey('max_printing_id', $json);
        $this->assertArrayHasKey('has_new_orders', $json);
        $this->assertArrayHasKey('has_new_printing', $json);
    }

    public function testAdminRealtimeCheckReturnsExpectedStructure()
    {
        $admin = (new UserModel())->where('role', 'admin')->first();
        $adminId = $admin ? (int) $admin['id'] : 1;

        $result = $this->asAdmin($adminId)->get('admin/realtime/check?last_order_id=0&last_printing_id=0');
        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);

        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('max_order_id', $json);
        $this->assertArrayHasKey('max_printing_id', $json);
        $this->assertArrayHasKey('has_new_orders', $json);
        $this->assertArrayHasKey('has_new_printing', $json);
    }

    public function testAdminLiveTrackingOnlyShowsTenantWhenViewLiveRouteIsOpened()
    {
        $shop = (new ShopModel())->first();
        if (!$shop) {
            $this->markTestSkipped('No shops available in database.');
        }

        $tenantUser = (new UserModel())->find($shop['owner_id']);
        if (!$tenantUser) {
            $this->markTestSkipped('Shop owner not found.');
        }

        $deliveryModel = new DeliveryModel();
        $uniqueTracking = 'TRK-TEST-LIVE-' . time();

        // 1. Insert a shipped delivery that has NOT been viewed/broadcasted yet
        $deliveryId = $deliveryModel->insert([
            'deliverable_type'    => 'order',
            'deliverable_id'      => 999991,
            'tracking_id'         => $uniqueTracking,
            'courier_name'        => 'Store Courier',
            'destination_address' => 'Cannery Site, Polomolok',
            'current_lat'         => null,
            'current_lng'         => null,
            'location_updated_at' => null,
            'status'              => 'shipped',
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        // Link dummy order
        $db = \Config\Database::connect();
        $db->table('orders')->where('id', 999991)->delete();
        $db->table('orders')->insert([
            'id'                 => 999991,
            'shop_id'            => (int) $shop['id'],
            'customer_id'        => (int) $tenantUser['id'],
            'order_number'       => 'ORD-LIVE-TEST-1',
            'fulfillment_method' => 'delivery',
            'status'             => 'shipped',
            'total_amount'       => 100.00,
            'placed_at'          => date('Y-m-d H:i:s'),
        ]);

        // 2. As Admin, check tracking pins: unviewed delivery must NOT appear
        $adminRes = $this->asAdmin()->get('admin/tracking/pins');
        $adminRes->assertStatus(200);
        $adminJson = json_decode($adminRes->getJSON(), true);
        $this->assertTrue($adminJson['success']);

        $foundInPinsBefore = false;
        foreach ($adminJson['pins'] as $p) {
            if ($p['tracking_id'] === $uniqueTracking) {
                $foundInPinsBefore = true;
                break;
            }
        }
        $this->assertFalse($foundInPinsBefore, 'Unviewed/unbroadcasted delivery should NOT appear on admin live map.');

        // 3. As Tenant, click "View Live Route & Map" (open delivery detail page)
        $tenantRes = $this->asTenant((int) $tenantUser['id'], (int) $shop['id'])
            ->get('tenant/deliveries/' . $deliveryId);
        $tenantRes->assertStatus(200);

        // 4. As Admin, check tracking pins again: delivery MUST now appear in pins and groupedShops!
        $adminResAfter = $this->asAdmin()->get('admin/tracking/pins');
        $adminResAfter->assertStatus(200);
        $adminJsonAfter = json_decode($adminResAfter->getJSON(), true);

        $foundInPinsAfter = false;
        foreach ($adminJsonAfter['pins'] as $p) {
            if ($p['tracking_id'] === $uniqueTracking) {
                $foundInPinsAfter = true;
                $this->assertNotEmpty($p['current_lat']);
                $this->assertNotEmpty($p['current_lng']);
                $this->assertNotNull($p['location_updated_at']);
                break;
            }
        }
        $this->assertTrue($foundInPinsAfter, 'Delivery MUST appear on admin live map after tenant views live route.');

        // 5. As Tenant, exit/leave live route (invoke stop-broadcast endpoint)
        $stopRes = $this->asTenant((int) $tenantUser['id'], (int) $shop['id'])
            ->post('tenant/deliveries/stop-broadcast', [
                'delivery_id' => $deliveryId,
            ]);
        $stopRes->assertStatus(200);
        $stopJson = json_decode($stopRes->getJSON(), true);
        $this->assertEquals('success', $stopJson['status']);

        // 6. As Admin, verify delivery is immediately removed from tracking pins
        $adminResExit = $this->asAdmin()->get('admin/tracking/pins');
        $adminResExit->assertStatus(200);
        $adminJsonExit = json_decode($adminResExit->getJSON(), true);

        $foundInPinsExit = false;
        foreach ($adminJsonExit['pins'] as $p) {
            if ($p['tracking_id'] === $uniqueTracking) {
                $foundInPinsExit = true;
                break;
            }
        }
        $this->assertFalse($foundInPinsExit, 'Delivery MUST disappear from admin live map immediately when tenant exits live route.');

        // 7. Clean up
        $deliveryModel->delete($deliveryId);
        $db->table('orders')->where('id', 999991)->delete();
    }
}
