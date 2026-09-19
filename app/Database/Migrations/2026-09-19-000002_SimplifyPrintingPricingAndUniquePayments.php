<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SimplifyPrintingPricingAndUniquePayments extends Migration
{
    public function up()
    {
        // 1. Add global color & B&W rates to shop_printing_settings
        if ($this->db->tableExists('shop_printing_settings')) {
            $fields = [];
            if (!$this->db->fieldExists('price_color_per_page', 'shop_printing_settings')) {
                $fields['price_color_per_page'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 5.00,
                    'null'       => false,
                    'after'      => 'price_spiral',
                ];
            }
            if (!$this->db->fieldExists('price_bw_per_page', 'shop_printing_settings')) {
                $fields['price_bw_per_page'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 2.00,
                    'null'       => false,
                    'after'      => 'price_color_per_page',
                ];
            }
            if (!empty($fields)) {
                $this->forge->addColumn('shop_printing_settings', $fields);
            }
        }

        // 2. Make price_color and price_bw nullable in shop_printing_paper_sizes
        if ($this->db->tableExists('shop_printing_paper_sizes')) {
            $modFields = [];
            if ($this->db->fieldExists('price_color', 'shop_printing_paper_sizes')) {
                $modFields['price_color'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                    'default'    => null,
                ];
            }
            if ($this->db->fieldExists('price_bw', 'shop_printing_paper_sizes')) {
                $modFields['price_bw'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                    'default'    => null,
                ];
            }
            if (!empty($modFields)) {
                $this->forge->modifyColumn('shop_printing_paper_sizes', $modFields);
            }
        }

        // 3. Ensure payments.reference_number has a unique constraint
        if ($this->db->tableExists('payments')) {
            // First sanitize any empty strings to NULL so multiple empty/cash entries do not conflict in unique index
            $this->db->query("UPDATE payments SET reference_number = NULL WHERE reference_number = '' OR TRIM(reference_number) = ''");

            // Check if unique index already exists
            $indexExists = false;
            $indexes = $this->db->query("SHOW INDEX FROM payments WHERE Key_name = 'uq_payments_reference_number'")->getResultArray();
            if (!empty($indexes)) {
                $indexExists = true;
            }

            if (!$indexExists) {
                $this->db->query("ALTER TABLE payments ADD UNIQUE KEY uq_payments_reference_number (reference_number)");
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('payments')) {
            $indexes = $this->db->query("SHOW INDEX FROM payments WHERE Key_name = 'uq_payments_reference_number'")->getResultArray();
            if (!empty($indexes)) {
                $this->db->query("ALTER TABLE payments DROP INDEX uq_payments_reference_number");
            }
        }

        if ($this->db->tableExists('shop_printing_settings')) {
            if ($this->db->fieldExists('price_bw_per_page', 'shop_printing_settings')) {
                $this->forge->dropColumn('shop_printing_settings', 'price_bw_per_page');
            }
            if ($this->db->fieldExists('price_color_per_page', 'shop_printing_settings')) {
                $this->forge->dropColumn('shop_printing_settings', 'price_color_per_page');
            }
        }
    }
}
