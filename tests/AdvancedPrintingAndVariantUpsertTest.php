<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\ShopModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\ShopPrintingSettingModel;
use App\Models\ShopPaperSizeSettingModel;
use App\Models\PrintingRequestModel;
use App\Models\PrintingRequestAttachmentModel;

class AdvancedPrintingAndVariantUpsertTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    public function testShopPrintingSettingsModel()
    {
        $settingModel = new ShopPrintingSettingModel();
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $settings = $settingModel->getForShop($shopId);
        $this->assertIsArray($settings);
        $this->assertArrayHasKey('down_payment_percent', $settings);
        $this->assertArrayHasKey('price_staple', $settings);
        $this->assertArrayHasKey('price_spiral', $settings);

        // Save custom settings
        $ok = $settingModel->saveForShop($shopId, [
            'down_payment_percent' => 30.00,
            'price_staple'         => 12.00,
            'price_spiral'         => 40.00,
        ]);
        $this->assertTrue($ok);

        $reloaded = $settingModel->getForShop($shopId);
        $this->assertEquals(30.00, (float) $reloaded['down_payment_percent']);
        $this->assertEquals(12.00, (float) $reloaded['price_staple']);
        $this->assertEquals(40.00, (float) $reloaded['price_spiral']);

        // Restore to 50%
        $settingModel->saveForShop($shopId, [
            'down_payment_percent' => 50.00,
            'price_staple'         => 10.00,
            'price_spiral'         => 35.00,
        ]);
    }

    public function testShopPaperSizeSettingsModel()
    {
        $paperModel = new ShopPaperSizeSettingModel();
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $sizes = $paperModel->getForShop($shopId);
        $this->assertIsArray($sizes);
        $this->assertArrayHasKey('letter', $sizes);
        $this->assertArrayHasKey('legal', $sizes);
        $this->assertArrayHasKey('a4', $sizes);

        // Update letter and legal
        $ok = $paperModel->saveForShop($shopId, [
            'letter' => ['is_enabled' => 1, 'price_color' => 6.00, 'price_bw' => 2.50],
            'legal'  => ['is_enabled' => 0, 'price_color' => 7.00, 'price_bw' => 3.00],
        ]);
        $this->assertTrue($ok);

        $reloaded = $paperModel->getForShop($shopId);
        $this->assertEquals(6.00, (float) $reloaded['letter']['price_color']);
        $this->assertEquals(0, (int) $reloaded['legal']['is_enabled']);

        // Restore legal back to enabled so other tests aren't affected
        $paperModel->saveForShop($shopId, [
            'legal' => ['is_enabled' => 1, 'price_color' => 7.50, 'price_bw' => 3.00],
        ]);
    }

    public function testVariantUpsertPreservesExistingVariantIds()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $productModel = new ProductModel();
        $product = $productModel->where('shop_id', $shopId)->first();
        $this->assertNotNull($product);
        $productId = (int) $product['id'];

        $variantModel = new ProductVariantModel();
        $variantModel->where('product_id', $productId)->delete();

        $tenantCtrl = new \App\Controllers\Tenant();
        $ref = new \ReflectionClass($tenantCtrl);
        $method = $ref->getMethod('handleProductVariantsSave');
        $method->setAccessible(true);

        // 1. Initial 3 variants
        $initial = [
            ['name' => 'Size', 'value' => 'Small', 'stock' => 10, 'price' => 100.00],
            ['name' => 'Size', 'value' => 'Medium', 'stock' => 20, 'price' => 150.00],
            ['name' => 'Size', 'value' => 'Large', 'stock' => 5, 'price' => 200.00],
        ];
        $method->invoke($tenantCtrl, $productId, $initial);

        $saved1 = $variantModel->where('product_id', $productId)->orderBy('id', 'ASC')->findAll();
        $this->assertCount(3, $saved1);
        $smallId  = (int) $saved1[0]['id'];
        $mediumId = (int) $saved1[1]['id'];
        $largeId  = (int) $saved1[2]['id'];

        // 2. Second batch:
        // - Small: updated price to 120, stock to 88 -> ID MUST REMAIN $smallId
        // - Medium: stock to 45 -> ID MUST REMAIN $mediumId
        // - Large: omitted -> MUST BE DELETED
        // - Extra Large: new -> MUST BE INSERTED
        $second = [
            ['name' => 'Size', 'value' => 'Small', 'stock' => 88, 'price' => 120.00],
            ['name' => 'Size', 'value' => 'Medium', 'stock' => 45, 'price' => 150.00],
            ['name' => 'Size', 'value' => 'Extra Large', 'stock' => 15, 'price' => 250.00],
        ];
        $method->invoke($tenantCtrl, $productId, $second);

        $saved2 = $variantModel->where('product_id', $productId)->orderBy('id', 'ASC')->findAll();
        $this->assertCount(3, $saved2);

        $byId = [];
        $byVal = [];
        foreach ($saved2 as $row) {
            $byId[(int) $row['id']] = $row;
            $byVal[$row['value']] = $row;
        }

        // Check ID preservation
        $this->assertArrayHasKey($smallId, $byId, 'Small variant ID must be preserved!');
        $this->assertEquals(88, (int) $byId[$smallId]['stock_quantity']);
        $this->assertEquals(120.00, (float) $byId[$smallId]['price_override']);

        $this->assertArrayHasKey($mediumId, $byId, 'Medium variant ID must be preserved!');
        $this->assertEquals(45, (int) $byId[$mediumId]['stock_quantity']);

        // Check deletion of Large
        $this->assertArrayNotHasKey($largeId, $byId, 'Large variant must be deleted when omitted!');
        $this->assertArrayNotHasKey('Large', $byVal);

        // Check insertion of Extra Large
        $this->assertArrayHasKey('Extra Large', $byVal);
        $this->assertNotEquals($largeId, (int) $byVal['Extra Large']['id']);
    }

    public function testPrintingRequestDocxAndAttachmentsPersistence()
    {
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $prModel  = new PrintingRequestModel();
        $attModel = new PrintingRequestAttachmentModel();

        $reqNum = 'PR-TEST-' . date('YmdHis') . '-' . rand(100, 999);
        $id = $prModel->insert([
            'request_number'       => $reqNum,
            'customer_id'          => 1,
            'shop_id'              => $shopId,
            'file_name'            => 'report_draft.docx',
            'file_url'             => 'uploads/printing/test_draft.docx',
            'document_type'        => 'docx',
            'doc_change_type'      => 'has_changes',
            'special_instructions' => 'Change cover title font and re-align page numbers.',
            'page_count'           => 12,
            'paper_size'           => 'letter',
            'color_mode'           => 'color',
            'copies'               => 1,
            'binding_option'       => 'staple',
            'paper_stock'          => 'standard',
            'fulfillment_method'   => 'pickup',
            'total_price'          => 70.00,
            'down_payment'         => 35.00,
            'status'               => 'Paid (Down Payment)',
            'progress_percent'     => 0,
        ]);

        $this->assertGreaterThan(0, (int) $id);

        $saved = $prModel->find($id);
        $this->assertEquals('docx', $saved['document_type']);
        $this->assertEquals('has_changes', $saved['doc_change_type']);
        $this->assertEquals('Change cover title font and re-align page numbers.', $saved['special_instructions']);

        // Test Attachment persistence
        $attId = $attModel->insert([
            'printing_request_id' => $id,
            'image_url'           => 'uploads/printing_attachments/test_markup.png',
            'created_at'          => date('Y-m-d H:i:s'),
        ]);
        $this->assertGreaterThan(0, (int) $attId);

        $atts = $attModel->where('printing_request_id', $id)->findAll();
        $this->assertCount(1, $atts);
        $this->assertEquals('uploads/printing_attachments/test_markup.png', $atts[0]['image_url']);

        // Clean up
        $attModel->where('printing_request_id', $id)->delete();
        $prModel->delete($id);
    }
}
