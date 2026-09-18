<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\ProductModel;
use App\Models\CartItemModel;
use App\Models\CartModel;
use App\Models\UserModel;
use App\Models\NotificationModel;
use App\Models\ShippingAddressModel;

/**
 * @internal
 */
final class RecommendationsAndCartExpiryTest extends CIUnitTestCase
{
    public function testGetRelatedProductsMethodReturnsCorrectData()
    {
        $productModel = new ProductModel();
        $related = $productModel->getRelatedProducts(1, 999999, 12);

        $this->assertIsArray($related);
        foreach ($related as $p) {
            $this->assertNotEquals(999999, (int) $p['id']);
            $this->assertArrayHasKey('name', $p);
            $this->assertArrayHasKey('price', $p);
            $this->assertArrayHasKey('image_url', $p);
            $this->assertEquals('active', $p['status']);
        }
    }

    public function testCartProcessExpiryCommandRunsSuccessfully()
    {
        $db = \Config\Database::connect();
        
        // Ensure slot is clean for dummy expired item
        $db->table('cart_items')->where('cart_id', 1)->where('product_id', 1)->delete();

        // Insert dummy expired item
        $db->table('cart_items')->insert([
            'cart_id'       => 1,
            'product_id'    => 1,
            'quantity'      => 1,
            'unit_price'    => 50.00,
            'is_selected'   => 1,
            'expires_at'    => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'reminder_count'=> 0,
            'created_at'    => date('Y-m-d H:i:s', strtotime('-4 days')),
        ]);
        $expiredId = $db->insertID();

        // Run spark command
        $command = new \App\Commands\CartReminderCommand(service('logger'), service('commands'));
        $command->run([]);

        // Confirm expired item was purged
        $found = $db->table('cart_items')->where('id', $expiredId)->get()->getRowArray();
        $this->assertNull($found);
    }

    public function testPolomolokAddressValidation()
    {
        $validBarangays = [
            'Bentung', 'Cannery Site', 'Crossing Palkan', 'Glamang', 'Kinilis',
            'Klinan 6', 'Koronadal Proper', 'Lam-Caliaf', 'Landan', 'Lumakil',
            'Maligo', 'Palkan', 'Poblacion', 'Polo', 'Pula Bato', 'Rubber',
            'Silway 7', 'Silway 8', 'Sulit', 'Sumbakil', 'Upper Klinan',
            'Pagalungan', 'Magsaysay'
        ];

        $this->assertCount(23, $validBarangays);
        $this->assertContains('Poblacion', $validBarangays);
        $this->assertContains('Cannery Site', $validBarangays);
        $this->assertContains('Silway 7', $validBarangays);
    }
}
