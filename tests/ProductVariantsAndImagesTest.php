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

    public function testCartAndOrderItemsAllowedFieldsContainVariants()
    {
        $cartItemModel = new \App\Models\CartItemModel();
        $orderItemModel = new \App\Models\OrderItemModel();

        // Check allowedFields
        $cartFields = (new \ReflectionClass($cartItemModel))->getProperty('allowedFields')->getValue($cartItemModel);
        $orderFields = (new \ReflectionClass($orderItemModel))->getProperty('allowedFields')->getValue($orderItemModel);

        $this->assertContains('variant_id', $cartFields);
        $this->assertContains('variant_label', $cartFields);
        $this->assertContains('variant_id', $orderFields);
        $this->assertContains('variant_label', $orderFields);
    }

    public function testCartItemModelGetCartItemsWithProductsJoinsVariant()
    {
        $cartModel = new \App\Models\CartModel();
        $cartItemModel = new \App\Models\CartItemModel();
        $variantModel = new ProductVariantModel();

        $cart = $cartModel->getOrCreateCart(1);

        $variantId = $variantModel->insert([
            'product_id'     => 1,
            'name'           => 'Color',
            'value'          => 'Ruby Red',
            'stock_quantity' => 12,
            'price_override' => 25.50,
        ]);

        $itemId = $cartItemModel->insert([
            'cart_id'       => $cart['id'],
            'product_id'    => 1,
            'variant_id'    => $variantId,
            'variant_label' => 'Color: Ruby Red',
            'quantity'      => 2,
            'unit_price'    => 25.50,
            'is_selected'   => 1,
        ]);

        $items = $cartItemModel->getCartItemsWithProducts($cart['id']);
        $found = null;
        foreach ($items as $it) {
            if ((int)$it['id'] === (int)$itemId) {
                $found = $it;
                break;
            }
        }

        $this->assertNotNull($found);
        $this->assertEquals($variantId, (int)$found['variant_id']);
        $this->assertEquals('Color: Ruby Red', $found['variant_label']);
        $this->assertEquals(25.50, (float)$found['price']);
        $this->assertEquals(12, (int)$found['stock_quantity']);

        // Clean up
        $cartItemModel->delete($itemId);
        $variantModel->delete($variantId);
    }

    public function testVariantStableIdOnUpsert()
    {
        $variantModel = new ProductVariantModel();

        // 1. Initial insert
        $id1 = $variantModel->insert([
            'product_id'     => 1,
            'name'           => 'Color',
            'value'          => 'Black',
            'stock_quantity' => 10,
            'price_override' => 50.00,
        ]);

        // 2. Simulate Tenant upsert-by-(name, value)
        $existingVariants = $variantModel->where('product_id', 1)->findAll();
        $existingMap = [];
        foreach ($existingVariants as $ev) {
            $key = mb_strtolower(trim($ev['name'])) . '|||' . mb_strtolower(trim($ev['value']));
            $existingMap[$key] = $ev;
        }

        $submitted = [
            ['name' => 'Color', 'value' => 'Black', 'stock_quantity' => 20, 'price_override' => 55.00]
        ];

        $keptIds = [];
        foreach ($submitted as $sub) {
            $key = mb_strtolower(trim($sub['name'])) . '|||' . mb_strtolower(trim($sub['value']));
            if (isset($existingMap[$key])) {
                $existingId = (int) $existingMap[$key]['id'];
                $variantModel->update($existingId, $sub);
                $keptIds[] = $existingId;
            }
        }

        $this->assertContains($id1, $keptIds);

        // Verify ID did not change and values updated
        $updated = $variantModel->find($id1);
        $this->assertNotNull($updated);
        $this->assertEquals(20, (int)$updated['stock_quantity']);
        $this->assertEquals(55.00, (float)$updated['price_override']);

        // Clean up
        $variantModel->delete($id1);
    }
}
