<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\ProductImageModel;

class ProductVariantsAndImagesTest extends CIUnitTestCase
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

    public function testGetProductWithDetailsDirectLookup()
    {
        $productModel = new ProductModel();
        $product = $productModel->getProductWithDetails(1);
        $this->assertNotNull($product);
        $this->assertEquals(1, (int) $product['id']);
        $this->assertArrayHasKey('shop_name', $product);
        $this->assertArrayHasKey('category_name', $product);
    }

    public function testProductVariantModelLifecycle()
    {
        $variantModel = new ProductVariantModel();

        // Insert test variant
        $id = $variantModel->insert([
            'product_id'     => 1,
            'name'           => 'Color',
            'value'          => 'Midnight Blue',
            'sku_suffix'     => 'BLU',
            'stock_quantity' => 25,
            'price_override' => 19.99,
        ]);

        $this->assertIsNumeric($id);
        $this->assertGreaterThan(0, (int) $id);

        // Fetch
        $found = $variantModel->find($id);
        $this->assertNotNull($found);
        $this->assertEquals('Color', $found['name']);
        $this->assertEquals('Midnight Blue', $found['value']);
        $this->assertEquals(25, (int) $found['stock_quantity']);
        $this->assertEquals(19.99, (float) $found['price_override']);

        // Clean up test variant
        $variantModel->delete($id);
        $this->assertNull($variantModel->find($id));
    }

    public function testDeleteProductImageRequiresAuth()
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        $result = $this->withHeaders([
            'X-CSRF-TOKEN' => $hash,
        ])->post('tenant/products/images/delete/99999');

        // Unauthenticated access must redirect to login
        $result->assertRedirectTo('/login');
    }

    public function testDeleteProductImagePreventsCrossTenantDeletion()
    {
        // Tenant from shop 99 cannot delete image for product belonging to shop 1
        $result = $this->asTenant(99)->post('tenant/products/images/delete/99999');
        $this->assertEquals(403, $result->response()->getStatusCode());
    }
}
