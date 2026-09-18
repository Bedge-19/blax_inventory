<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\ShopModel;

final class ShopProfileAddressTest extends CIUnitTestCase
{
    public function testShopModelAllowsStreetAndBarangay(): void
    {
        $shopModel = new ShopModel();
        $this->assertContains('street', $shopModel->allowedFields);
        $this->assertContains('barangay', $shopModel->allowedFields);

        $shop = $shopModel->first();
        if ($shop) {
            $shopId = (int) $shop['id'];
            $origStreet = $shop['street'] ?? null;
            $origBarangay = $shop['barangay'] ?? null;

            $updated = $shopModel->update($shopId, [
                'street'   => 'Purok Pioneer',
                'barangay' => 'Poblacion',
            ]);
            $this->assertTrue($updated);

            $fresh = $shopModel->find($shopId);
            $this->assertSame('Purok Pioneer', $fresh['street']);
            $this->assertSame('Poblacion', $fresh['barangay']);

            // Revert back
            $shopModel->update($shopId, [
                'street'   => $origStreet,
                'barangay' => $origBarangay,
            ]);
        }
    }
}
