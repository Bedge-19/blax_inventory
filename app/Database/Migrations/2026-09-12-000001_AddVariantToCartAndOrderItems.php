<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVariantToCartAndOrderItems extends Migration
{
    public function up()
    {
        // 1. cart_items columns
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

        // Drop old unique index on (cart_id, product_id) if present
        try {
            $this->db->query("ALTER TABLE `cart_items` DROP INDEX `uq_cart_product`");
        } catch (\Throwable $e) {
            // Index might not exist or already dropped
        }

        // Add composite unique key on (cart_id, product_id, variant_id)
        try {
            $this->db->query("ALTER TABLE `cart_items` ADD UNIQUE KEY `uq_cart_product_variant` (`cart_id`, `product_id`, `variant_id`)");
        } catch (\Throwable $e) {
            // Index might already exist
        }

        // 2. order_items columns
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
        try {
            $this->db->query("ALTER TABLE `cart_items` DROP INDEX `uq_cart_product_variant`");
        } catch (\Throwable $e) {
        }

        try {
            $this->db->query("ALTER TABLE `cart_items` ADD UNIQUE KEY `uq_cart_product` (`cart_id`, `product_id`)");
        } catch (\Throwable $e) {
        }

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
    }
}
