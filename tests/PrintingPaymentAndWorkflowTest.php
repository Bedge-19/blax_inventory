<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\ShopModel;
use App\Models\ShopPrintingSettingModel;
use App\Models\ShopPaperSizeSettingModel;
use App\Models\PaymentModel;
use App\Models\PrintingRequestModel;
use App\Models\UserModel;

class PrintingPaymentAndWorkflowTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    public function testGlobalPageRateCalculationAndDefaults()
    {
        $settingModel = new ShopPrintingSettingModel();
        $paperModel   = new ShopPaperSizeSettingModel();

        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $settings = $settingModel->getForShop($shopId);
        $this->assertEquals(5.00, (float) $settings['price_color_per_page']);
        $this->assertEquals(2.00, (float) $settings['price_bw_per_page']);

        // Color pricing: 10 pages, 3 copies, staple binding (10.00)
        // Per copy = (10 * 5.00) + 10.00 = 60.00; Total = 60.00 * 3 = 180.00; Down payment 50% = 90.00
        $pages = 10;
        $copies = 3;
        $colorRate = (float) $settings['price_color_per_page'];
        $staple = (float) $settings['price_staple'];
        $totalColor = round((($pages * $colorRate) + $staple) * $copies, 2);
        $downColor = round($totalColor * 0.50, 2);

        $this->assertEquals(180.00, $totalColor);
        $this->assertEquals(90.00, $downColor);

        // B&W pricing: 10 pages, 3 copies, staple binding (10.00)
        // Per copy = (10 * 2.00) + 10.00 = 30.00; Total = 30.00 * 3 = 90.00; Down payment 50% = 45.00
        $bwRate = (float) $settings['price_bw_per_page'];
        $totalBw = round((($pages * $bwRate) + $staple) * $copies, 2);
        $downBw = round($totalBw * 0.50, 2);

        $this->assertEquals(90.00, $totalBw);
        $this->assertEquals(45.00, $downBw);
    }

    public function testPaperSizeEnabledStatusToggling()
    {
        $paperModel = new ShopPaperSizeSettingModel();
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        // Disable a3
        $paperModel->saveForShop($shopId, [
            'a3' => ['is_enabled' => 0]
        ]);

        $sizes = $paperModel->getForShop($shopId);
        $this->assertEquals(0, (int) $sizes['a3']['is_enabled']);
        $this->assertEquals(1, (int) $sizes['letter']['is_enabled']);

        // Re-enable a3
        $paperModel->saveForShop($shopId, [
            'a3' => ['is_enabled' => 1]
        ]);
        $sizesAfter = $paperModel->getForShop($shopId);
        $this->assertEquals(1, (int) $sizesAfter['a3']['is_enabled']);
    }

    public function testUniqueConstraintOnPaymentReferenceNumber()
    {
        $db = \Config\Database::connect();
        $paymentModel = new PaymentModel();

        $ref = 'test_paymongo_ref_' . bin2hex(random_bytes(6));

        // Insert first payment
        $paymentId1 = $paymentModel->insert([
            'payable_type'     => 'printing_request',
            'payable_id'       => 999991,
            'method'           => 'gcash',
            'amount'           => 50.00,
            'reference_number' => $ref,
            'status'           => 'verified',
            'processed_at'     => date('Y-m-d H:i:s'),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
        $this->assertIsNumeric($paymentId1);

        // Attempt second insert with identical reference_number should fail due to unique constraint
        $caught = false;
        try {
            $paymentModel->insert([
                'payable_type'     => 'printing_request',
                'payable_id'       => 999992,
                'method'           => 'gcash',
                'amount'           => 50.00,
                'reference_number' => $ref,
                'status'           => 'verified',
                'processed_at'     => date('Y-m-d H:i:s'),
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $caught = true;
            $this->assertTrue(
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), '1062') ||
                str_contains($e->getMessage(), 'UNIQUE')
            );
        }

        $this->assertTrue($caught, 'Inserting a duplicate reference_number must trigger unique constraint violation');

        // Clean up
        $paymentModel->delete($paymentId1);
    }

    public function testCacheCleanupFlow()
    {
        $token = 'tok_' . bin2hex(random_bytes(8));
        $sessionId = 'cs_' . bin2hex(random_bytes(8));

        $data = [
            'shop_id'     => 1,
            'session_id'  => $sessionId,
            'token'       => $token,
            'total_price' => 100.00,
        ];

        cache()->save('pending_printing_' . $sessionId, $data, 3600);
        cache()->save('pending_printing_token_' . $token, $data, 3600);

        $this->assertNotEmpty(cache()->get('pending_printing_' . $sessionId));
        $this->assertNotEmpty(cache()->get('pending_printing_token_' . $token));

        // Delete cache explicitly (mirroring callback logic)
        cache()->delete('pending_printing_' . $sessionId);
        cache()->delete('pending_printing_token_' . $token);

        $this->assertNull(cache()->get('pending_printing_' . $sessionId));
        $this->assertNull(cache()->get('pending_printing_token_' . $token));
    }

    public function testCleanupAbandonedCommand()
    {
        $docsDir = WRITEPATH . 'uploads/printing';
        if (!is_dir($docsDir)) {
            mkdir($docsDir, 0777, true);
        }

        // Create an unlinked dummy file with mtime backdated by 25 hours
        $dummyFile = $docsDir . DIRECTORY_SEPARATOR . 'abandoned_test_' . uniqid() . '.pdf';
        file_put_contents($dummyFile, '%PDF-1.4 dummy content');
        touch($dummyFile, time() - (25 * 3600));

        $this->assertFileExists($dummyFile);

        // Run spark printing:cleanup-abandoned
        command('printing:cleanup-abandoned');

        // File should have been deleted by the cleanup command
        $this->assertFileDoesNotExist($dummyFile);
    }

    public function testProductionQueueAndFulfillmentDependentTransitions()
    {
        $requestModel = new PrintingRequestModel();
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $customer = (new UserModel())->where('role', 'customer')->first() ?? (new UserModel())->first();
        $this->assertNotNull($customer);
        $customerId = (int) $customer['id'];

        // Create 2 in_production items: one pickup, one delivery
        $pickupId = $requestModel->insert([
            'request_number'     => 'PR-' . date('Ymd') . '-' . rand(10000, 99999),
            'shop_id'            => $shopId,
            'customer_id'        => $customerId,
            'color_mode'         => 'bw',
            'paper_size'         => 'A4',
            'page_count'         => 5,
            'copies'             => 1,
            'binding_type'       => 'none',
            'total_price'        => 10.00,
            'downpayment_amount' => 5.00,
            'downpayment_status' => 'paid',
            'status'             => 'in_production',
            'fulfillment_method' => 'pickup',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $deliveryId = $requestModel->insert([
            'request_number'     => 'PR-' . date('Ymd') . '-' . rand(10000, 99999),
            'shop_id'            => $shopId,
            'customer_id'        => $customerId,
            'color_mode'         => 'color',
            'paper_size'         => 'Short',
            'page_count'         => 8,
            'copies'             => 2,
            'binding_type'       => 'staple',
            'total_price'        => 50.00,
            'downpayment_amount' => 25.00,
            'downpayment_status' => 'paid',
            'status'             => 'in_production',
            'fulfillment_method' => 'delivery',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $queue = $requestModel->getProductionQueue($shopId, 100);
        $queueIds = array_column($queue, 'id');

        $this->assertContains((string) $pickupId, array_map('strval', $queueIds));
        $this->assertContains((string) $deliveryId, array_map('strval', $queueIds));

        // Test transition logic (reflecting canTransitionPrint)
        $tenantCtrl = new \App\Controllers\Tenant();
        $ref = new \ReflectionClass($tenantCtrl);
        $method = $ref->getMethod('canTransitionPrint');
        $method->setAccessible(true);

        // Pickup in_production -> ready_for_pickup is allowed
        $this->assertTrue($method->invoke($tenantCtrl, 'in_production', 'ready_for_pickup'));
        // Delivery in_production -> ready_for_delivery is allowed
        $this->assertTrue($method->invoke($tenantCtrl, 'in_production', 'ready_for_delivery'));
        // Cannot transition backwards to new
        $this->assertFalse($method->invoke($tenantCtrl, 'in_production', 'new'));

        // Clean up
        $requestModel->delete($pickupId, true);
        $requestModel->delete($deliveryId, true);
    }

    public function testDocumentTypeFilteringAndAttachmentPreview()
    {
        $requestModel = new PrintingRequestModel();
        $attModel     = new \App\Models\PrintingRequestAttachmentModel();
        $shop = (new ShopModel())->first();
        $this->assertNotNull($shop);
        $shopId = (int) $shop['id'];

        $customer = (new UserModel())->where('role', 'customer')->first() ?? (new UserModel())->first();
        $this->assertNotNull($customer);
        $customerId = (int) $customer['id'];

        // 1. Create a PDF request
        $pdfId = $requestModel->insert([
            'request_number'     => 'PR-' . date('Ymd') . '-' . rand(10000, 99999),
            'shop_id'            => $shopId,
            'customer_id'        => $customerId,
            'document_type'      => 'pdf',
            'file_name'          => 'report.pdf',
            'color_mode'         => 'bw',
            'paper_size'         => 'A4',
            'page_count'         => 4,
            'copies'             => 1,
            'binding_type'       => 'none',
            'total_price'        => 8.00,
            'downpayment_amount' => 4.00,
            'downpayment_status' => 'paid',
            'status'             => 'new',
            'fulfillment_method' => 'pickup',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        // 2. Create a DOCX request with instructions and attachment
        $docxId = $requestModel->insert([
            'request_number'       => 'PR-' . date('Ymd') . '-' . rand(10000, 99999),
            'shop_id'              => $shopId,
            'customer_id'          => $customerId,
            'document_type'        => 'docx',
            'file_name'            => 'thesis_draft.docx',
            'doc_change_type'      => 'has_changes',
            'special_instructions' => 'Change header title to 2026 Edition and align table.',
            'color_mode'           => 'color',
            'paper_size'           => 'Short',
            'page_count'           => 12,
            'copies'               => 1,
            'binding_type'         => 'spiral',
            'total_price'          => 95.00,
            'downpayment_amount'   => 47.50,
            'downpayment_status'   => 'paid',
            'status'               => 'in_production',
            'fulfillment_method'   => 'delivery',
            'created_at'           => date('Y-m-d H:i:s'),
        ]);

        // Insert reference photo for docx request
        $attId = $attModel->insert([
            'printing_request_id' => $docxId,
            'image_url'           => 'uploads/printing_attachments/ref_test_diagram.jpg',
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        // 3. Test filtering by PDF
        $pdfResults = $requestModel->getRequestsByShopPaginated($shopId, null, null, 50, 1, 'recent', 'pdf');
        $pdfIds = array_column($pdfResults['requests'], 'id');
        $this->assertContains((string) $pdfId, array_map('strval', $pdfIds), 'PDF filter must contain PDF request');
        $this->assertNotContains((string) $docxId, array_map('strval', $pdfIds), 'PDF filter must NOT contain DOCX request');

        // 4. Test filtering by DOCX
        $docxResults = $requestModel->getRequestsByShopPaginated($shopId, null, null, 50, 1, 'recent', 'docx');
        $docxIds = array_column($docxResults['requests'], 'id');
        $this->assertContains((string) $docxId, array_map('strval', $docxIds), 'DOCX filter must contain DOCX request');
        $this->assertNotContains((string) $pdfId, array_map('strval', $docxIds), 'DOCX filter must NOT contain PDF request');

        // 5. Test attachment retrieval
        $atts = $attModel->getForRequest($docxId);
        $this->assertCount(1, $atts);
        $this->assertEquals('uploads/printing_attachments/ref_test_diagram.jpg', $atts[0]['image_url']);

        // Clean up
        $attModel->delete($attId, true);
        $requestModel->delete($pdfId, true);
        $requestModel->delete($docxId, true);
    }
}
