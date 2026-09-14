<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductVariantsAndItemColumns extends Migration
{
    public function up()
    {
        // 1. product_variants table
        if (!$this->db->tableExists('product_variants')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'product_id' => [
                    'type'     => 'BIGINT',
                    'unsigned' => true,
                    'null'     => false,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'default'    => 'Type',
                    'null'       => false,
                ],
                'value' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => false,
                ],
                'sku_suffix' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 60,
                    'null'       => true,
                    'default'    => null,
                ],
                'stock_quantity' => [
                    'type'    => 'INT',
                    'null'    => false,
                    'default' => 0,
                ],
                'price_override' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                    'default'    => null,
                ],
                'created_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
                'updated_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('product_id');
            $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('product_variants', true);
        } else {
            // Table already exists from earlier migration; modify column definitions if needed
            $modifyFields = [];
            $modifyFields['name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'Type',
                'null'       => false,
            ];
            $modifyFields['value'] = [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ];
            $modifyFields['sku_suffix'] = [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
                'default'    => null,
            ];
            $this->forge->modifyColumn('product_variants', $modifyFields);
        }

        // 2. cart_items columns & unique constraint
        $cartFields = [];
        if (!$this->db->fieldExists('variant_id', 'cart_items')) {
            $cartFields['variant_id'] = [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'after'    => 'product_id',
            ];
        }
        if (!$this->db->fieldExists('variant_label', 'cart_items')) {
            $cartFields['variant_label'] = [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
                'default'    => null,
                'after'      => 'variant_id',
            ];
        }
        if (!empty($cartFields)) {
            $this->forge->addColumn('cart_items', $cartFields);
        }

        // Drop uq_cart_product if it exists
        try {
            $this->db->query("ALTER TABLE `cart_items` DROP INDEX `uq_cart_product`");
        } catch (\Throwable $e) {
            // Already dropped or does not exist
        }

        // Add composite unique key on (cart_id, product_id, variant_id) if not present
        try {
            $this->db->query("ALTER TABLE `cart_items` ADD UNIQUE KEY `uq_cart_product_variant` (`cart_id`, `product_id`, `variant_id`)");
        } catch (\Throwable $e) {
            // Already present
        }

        // 3. order_items columns (without foreign key)
        $orderFields = [];
        if (!$this->db->fieldExists('variant_id', 'order_items')) {
            $orderFields['variant_id'] = [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'after'    => 'product_id',
            ];
        }
        if (!$this->db->fieldExists('variant_label', 'order_items')) {
            $orderFields['variant_label'] = [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
                'default'    => null,
                'after'      => 'variant_id',
            ];
        }
        if (!empty($orderFields)) {
            $this->forge->addColumn('order_items', $orderFields);
        }
    }

    public function down()
    {
        // Revert cart_items index
        try {
            $this->db->query("ALTER TABLE `cart_items` DROP INDEX `uq_cart_product_variant`");
        } catch (\Throwable $e) {}

        try {
            $this->db->query("ALTER TABLE `cart_items` ADD UNIQUE KEY `uq_cart_product` (`cart_id`, `product_id`)");
        } catch (\Throwable $e) {}

        if ($this->db->fieldExists('variant_label', 'cart_items')) {
            $this->forge->dropColumn('cart_items', 'variant_label');
        }
        if ($this->db->fieldExists('variant_id', 'cart_items')) {
            $this->forge->dropColumn('cart_items', 'variant_id');
        }

        if ($this->db->fieldExists('variant_label', 'order_items')) {
            $this->forge->dropColumn('order_items', 'variant_label');
        }
        if ($this->db->fieldExists('variant_id', 'order_items')) {
            $this->forge->dropColumn('order_items', 'variant_id');
        }

        if ($this->db->tableExists('product_variants')) {
            $this->forge->dropTable('product_variants', true);
        }
    }
}
