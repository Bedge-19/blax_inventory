<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\ShippingAddressModel;
use App\Models\DeliveryModel;
use App\Models\UserModel;

class ShippingAddressAndToastTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    private function asCustomer(int $userId = 1): self
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        return $this->withSession([
            'customer_id'    => $userId,
            'user_id'        => $userId,
            'user_name'      => 'Test Customer',
            'user_email'     => 'customer@test.com',
            'user_role'      => 'customer',
            'role'           => 'customer',
            'isCustomer'     => true,
            'isLoggedIn'     => true,
        ])->withHeaders([
            'X-CSRF-TOKEN' => $hash,
        ]);
    }

    public function testPolomolokAndTupiBarangayCentroidsCoverage()
    {
        $polomolokBarangays = [
            'Bentung', 'Cannery Site', 'Crossing Palkan', 'Glamang', 'Kinilis',
            'Klinan 6', 'Koronadal Proper', 'Lam-Caliaf', 'Landan', 'Lumakil',
            'Magsaysay', 'Maligo', 'Pagalungan', 'Palkan', 'Poblacion',
            'Polo', 'Pula Bato', 'Rubber', 'Silway 7', 'Silway 8',
            'Sulit', 'Sumbakil', 'Upper Klinan'
        ];

        foreach ($polomolokBarangays as $brgy) {
            $coord = DeliveryModel::getBarangayCoordinate($brgy, 'Polomolok');
            $this->assertNotNull($coord, "Barangay {$brgy} in Polomolok should have centroid coordinates");
            $this->assertTrue(DeliveryModel::isPolomolokCoordinate($coord['lat'], $coord['lng']), "Centroid for {$brgy} must be inside service bounds");
        }

        $tupiBarangays = [
            'Acmonan', 'Bololmala', 'Bunao', 'Cebuano', 'Crossing Rubber',
            'Dajay', 'Kablon', 'Kalkam', 'Linan', 'Lunen',
            'Miaso', 'Palian', 'Poblacion', 'Polonoling', 'Simbo', 'Tubeng'
        ];

        foreach ($tupiBarangays as $brgy) {
            $coord = DeliveryModel::getBarangayCoordinate($brgy, 'Tupi');
            $this->assertNotNull($coord, "Barangay {$brgy} in Tupi should have centroid coordinates");
            $this->assertTrue(DeliveryModel::isPolomolokCoordinate($coord['lat'], $coord['lng']), "Centroid for {$brgy} (Tupi) must be inside service bounds");
        }
    }

    public function testCustomerSaveAddressWithPolomolokCentroidFallback()
    {
        $userModel = new UserModel();
        $customer = $userModel->where('role', 'customer')->first();
        $customerId = $customer ? (int)$customer['id'] : 1;

        $addressModel = new ShippingAddressModel();

        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        $postData = [
            csrf_token()     => $hash,
            'label'          => 'Home',
            'recipient_name' => 'Juan Dela Cruz',
            'phone'          => '09171234567',
            'address_line1'  => 'Purok 3, Pineapple Road',
            'barangay'       => 'Cannery Site',
            'city'           => 'Polomolok',
            'province'       => 'South Cotabato',
            'postal_code'    => '9504',
            'latitude'       => '',
            'longitude'      => '',
            'is_default'     => 1,
        ];

        $result = $this->asCustomer($customerId)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'X-CSRF-TOKEN' => $hash])
            ->post('customer/addresses/save', $postData);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertTrue($body['success']);
        $this->assertStringContainsString('successfully', $body['message']);

        // Verify record in database has assigned centroid
        $saved = $addressModel->where('user_id', $customerId)->orderBy('id', 'DESC')->first();
        $this->assertNotNull($saved);
        $this->assertEquals('Cannery Site', $saved['address_line2']);
        $this->assertEquals('Polomolok', $saved['city']);
        $this->assertEquals('9504', $saved['postal_code']);
        $this->assertEqualsWithDelta(6.2415, (float)$saved['latitude'], 0.01);
        $this->assertEqualsWithDelta(125.0740, (float)$saved['longitude'], 0.01);

        // Cleanup
        $addressModel->delete($saved['id']);
    }

    public function testCustomerSaveAddressWithTupiAndExactCoordinates()
    {
        $userModel = new UserModel();
        $customer = $userModel->where('role', 'customer')->first();
        $customerId = $customer ? (int)$customer['id'] : 1;

        $addressModel = new ShippingAddressModel();

        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        $postData = [
            csrf_token()     => $hash,
            'label'          => 'Office',
            'recipient_name' => 'Maria Santos',
            'phone'          => '09289876543',
            'address_line1'  => 'Block 5, National Highway',
            'barangay'       => 'Polonoling',
            'city'           => 'Tupi',
            'province'       => 'South Cotabato',
            'postal_code'    => '9505',
            'latitude'       => '6.301500',
            'longitude'      => '124.952000',
            'is_default'     => 0,
        ];

        $result = $this->asCustomer($customerId)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'X-CSRF-TOKEN' => $hash])
            ->post('customer/addresses/save', $postData);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertTrue($body['success']);

        $saved = $addressModel->where('user_id', $customerId)->orderBy('id', 'DESC')->first();
        $this->assertNotNull($saved);
        $this->assertEquals('Polonoling', $saved['address_line2']);
        $this->assertEquals('Tupi', $saved['city']);
        $this->assertEquals('9505', $saved['postal_code']);
        $this->assertEqualsWithDelta(6.3015, (float)$saved['latitude'], 0.001);
        $this->assertEqualsWithDelta(124.9520, (float)$saved['longitude'], 0.001);

        // Cleanup
        $addressModel->delete($saved['id']);
    }

    public function testCustomerSaveAddressValidationFailsForInvalidBarangay()
    {
        $userModel = new UserModel();
        $customer = $userModel->where('role', 'customer')->first();
        $customerId = $customer ? (int)$customer['id'] : 1;

        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        $postData = [
            csrf_token()     => $hash,
            'label'          => 'Home',
            'recipient_name' => 'Invalid Test',
            'phone'          => '09123456789',
            'address_line1'  => 'Somewhere outside',
            'barangay'       => 'Dadiangas North', // Invalid for Polomolok/Tupi
            'city'           => 'Polomolok',
            'province'       => 'South Cotabato',
            'postal_code'    => '9504',
        ];

        $result = $this->asCustomer($customerId)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'X-CSRF-TOKEN' => $hash])
            ->post('customer/addresses/save', $postData);

        $result->assertStatus(422);
        $body = json_decode($result->getJSON(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Please select a valid official barangay', $body['error']);
    }

    public function testCustomerRealtimeCheckEndpoint()
    {
        $userModel = new UserModel();
        $customer = $userModel->where('role', 'customer')->first();
        $customerId = $customer ? (int)$customer['id'] : 1;

        $result = $this->asCustomer($customerId)
            ->get('customer/realtime/check');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('unread_count', $body);
        $this->assertArrayHasKey('new_notifs', $body);
        $this->assertArrayHasKey('active_orders', $body);
        $this->assertArrayHasKey('timestamp', $body);
    }
}
