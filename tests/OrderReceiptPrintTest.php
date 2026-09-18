<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class OrderReceiptPrintTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $db = \Config\Database::connect();

        // 1. Ensure shop 1 exists
        $shop1 = $db->table('shops')->where('id', 1)->get()->getRowArray();
        if (!$shop1) {
            $db->table('shops')->insert([
                'id'           => 1,
                'user_id'      => 2,
                'shop_name'    => 'Blax General Store',
                'street'       => 'Purok Pioneer',
                'barangay'     => 'Poblacion',
                'city'         => 'Polomolok',
                'province'     => 'South Cotabato',
                'phone_number' => '09123456789',
                'status'       => 'active',
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        // 2. Ensure shop 12 exists
        $shop12 = $db->table('shops')->where('id', 12)->get()->getRowArray();
        if (!$shop12) {
            $db->table('shops')->insert([
                'id'           => 12,
                'user_id'      => 12,
                'shop_name'    => 'Other Tenant Shop',
                'city'         => 'Polomolok',
                'province'     => 'South Cotabato',
                'status'       => 'active',
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        // 3. Ensure test order for shop 1 with items
        $order = $db->table('orders')->where('order_number', 'ORD-TEST-REC01')->get()->getRowArray();
        if (!$order) {
            $db->table('orders')->insert([
                'order_number'          => 'ORD-TEST-REC01',
                'customer_id'           => 3,
                'shop_id'               => 1,
                'fulfillment_method'    => 'pickup',
                'payment_method'        => 'cash',
                'subtotal'              => 450.00,
                'shipping_fee'          => 0.00,
                'tax_amount'            => 0.00,
                'total_amount'          => 450.00,
                'pos_additional_amount' => 0.00,
                'pos_payment_method'    => 'cash',
                'pos_payment_status'    => 'paid',
                'status'                => 'completed',
                'payment_status'        => 'paid',
                'placed_at'             => '2026-09-18 14:30:00',
                'completed_at'          => '2026-09-18 14:35:00',
            ]);
            $orderId = $db->insertID();
        } else {
            $orderId = (int) $order['id'];
        }

        // Ensure order items exist
        $existingItem = $db->table('order_items')->where('order_id', $orderId)->get()->getRowArray();
        if (!$existingItem) {
            $db->table('order_items')->insert([
                'order_id'     => $orderId,
                'product_id'   => 1,
                'product_name' => 'Bond Paper A4 70gsm',
                'quantity'     => 2,
                'unit_price'   => 225.00,
                'line_total'   => 450.00,
            ]);
        }

        // 4. Ensure test order for shop 12 (cross-shop)
        $crossOrder = $db->table('orders')->where('order_number', 'ORD-CROSS-REC12')->get()->getRowArray();
        if (!$crossOrder) {
            $db->table('orders')->insert([
                'order_number'       => 'ORD-CROSS-REC12',
                'customer_id'        => 3,
                'shop_id'            => 12,
                'fulfillment_method' => 'pickup',
                'payment_method'     => 'cash',
                'subtotal'           => 100.00,
                'shipping_fee'       => 0.00,
                'tax_amount'         => 0.00,
                'total_amount'       => 100.00,
                'status'             => 'completed',
                'payment_status'     => 'paid',
                'placed_at'          => '2026-09-18 10:00:00',
            ]);
        }

        // 5. Ensure printing request for shop 1
        $pr = $db->table('printing_requests')->where('request_number', 'PR-TEST-REC01')->get()->getRowArray();
        if (!$pr) {
            $db->table('printing_requests')->insert([
                'request_number'       => 'PR-TEST-REC01',
                'customer_id'          => 3,
                'shop_id'              => 1,
                'file_name'            => 'Final_Thesis_Manuscript.pdf',
                'file_url'             => 'uploads/sample.pdf',
                'page_count'           => 45,
                'copies'               => 2,
                'color_mode'           => 'color',
                'paper_size'           => 'A4',
                'binding_option'       => 'spiral',
                'fulfillment_method'   => 'pickup',
                'total_price'          => 350.00,
                'down_payment'         => 175.00,
                'status'               => 'completed',
                'created_at'           => '2026-09-18 11:00:00',
            ]);
        }

        // 6. Ensure printing request for shop 12
        $crossPr = $db->table('printing_requests')->where('request_number', 'PR-CROSS-REC12')->get()->getRowArray();
        if (!$crossPr) {
            $db->table('printing_requests')->insert([
                'request_number'       => 'PR-CROSS-REC12',
                'customer_id'          => 3,
                'shop_id'              => 12,
                'file_name'            => 'Cross_Shop_Doc.pdf',
                'file_url'             => 'uploads/sample2.pdf',
                'page_count'           => 10,
                'copies'               => 1,
                'color_mode'           => 'bw',
                'paper_size'           => 'A4',
                'binding_option'       => 'none',
                'fulfillment_method'   => 'pickup',
                'total_price'          => 50.00,
                'status'               => 'completed',
                'created_at'           => '2026-09-18 11:00:00',
            ]);
        }
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
        ]);
    }

    public function testOrderReceiptRendersCleanStandaloneDocument(): void
    {
        $result = $this->asTenant()->get('tenant/orders/receipt/ORD-TEST-REC01');
        $result->assertOK();

        $body = $result->getBody();

        $db = \Config\Database::connect();
        $shop = $db->table('shops')->where('id', 1)->get()->getRowArray();
        $shopName = $shop['shop_name'] ?? 'Shop';

        // 1. Must include shop details
        $this->assertStringContainsString($shopName, $body);
        $this->assertStringContainsString('Polomolok', $body);

        // 2. Must include order reference & product items
        $this->assertStringContainsString('ORD-TEST-REC01', $body);
        $this->assertStringContainsString('Bond Paper A4 70gsm', $body);
        $this->assertStringContainsString('450.00', $body);

        // 3. Must include formatted date/time & QR code
        $this->assertStringContainsString('Sep 18, 2026', $body);
        $this->assertStringContainsString('api.qrserver.com', $body);
        $this->assertStringContainsString('window.print()', $body);

        // 4. Must be a clean standalone view without layout navigation sidebar
        $this->assertStringNotContainsString('<aside', $body);
        $this->assertStringNotContainsString('Inventory Management', $body);
    }

    public function testOrderReceiptByIdParameter(): void
    {
        $db = \Config\Database::connect();
        $order = $db->table('orders')->where('order_number', 'ORD-TEST-REC01')->get()->getRowArray();
        $this->assertNotNull($order);

        $result = $this->asTenant()->get('tenant/orders/receipt/' . (int) $order['id']);
        $result->assertOK();
        $this->assertStringContainsString('ORD-TEST-REC01', $result->getBody());
        $this->assertStringContainsString('Bond Paper A4 70gsm', $result->getBody());
    }

    public function testOrderReceiptRejectsCrossShopAccess(): void
    {
        $result = $this->asTenant()->get('tenant/orders/receipt/ORD-CROSS-REC12');
        $result->assertRedirectTo(base_url('tenant/orders'));
    }

    public function testPrintingReceiptRendersCleanStandaloneDocument(): void
    {
        $result = $this->asTenant()->get('tenant/printing/receipt/PR-TEST-REC01');
        $result->assertOK();

        $body = $result->getBody();

        $db = \Config\Database::connect();
        $shop = $db->table('shops')->where('id', 1)->get()->getRowArray();
        $shopName = $shop['shop_name'] ?? 'Shop';

        // 1. Must include shop details and printing specs
        $this->assertStringContainsString($shopName, $body);
        $this->assertStringContainsString('PR-TEST-REC01', $body);
        $this->assertStringContainsString('Final_Thesis_Manuscript.pdf', $body);
        $this->assertStringContainsString('45 pages', $body);
        $this->assertStringContainsString('2 copies', $body);
        $this->assertStringContainsString('350.00', $body);

        // 2. Must include QR code and print trigger
        $this->assertStringContainsString('api.qrserver.com', $body);
        $this->assertStringContainsString('window.print()', $body);
    }

    public function testPrintingReceiptRejectsCrossShopAccess(): void
    {
        $result = $this->asTenant()->get('tenant/printing/receipt/PR-CROSS-REC12');
        $result->assertRedirectTo(base_url('tenant/printing'));
    }
}
