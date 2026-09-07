<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRatingSupport extends Migration
{
    public function up()
    {
        // Add updated_at column
        $this->forge->addColumn('reviews', [
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);

        // Clean up any existing duplicate (user_id, product_id) pairs before adding unique constraint
        $this->db->query("
            DELETE r1 FROM reviews r1
            JOIN reviews r2
            ON r1.user_id = r2.user_id
            AND r1.product_id <=> r2.product_id
            AND r1.id < r2.id
        ");

        // Clean up any existing duplicate (user_id, shop_id) pairs
        $this->db->query("
            DELETE r1 FROM reviews r1
            JOIN reviews r2
            ON r1.user_id = r2.user_id
            AND r1.shop_id <=> r2.shop_id
            AND r1.id < r2.id
        ");

        // Add unique constraints to prevent duplicate ratings
        $this->forge->addUniqueKey(['user_id', 'product_id'], 'uq_review_user_product');
        $this->forge->addUniqueKey(['user_id', 'shop_id'], 'uq_review_user_shop');
    }

    public function down()
    {
        try {
            $this->forge->dropKey('reviews', 'uq_review_user_product', false);
        } catch (\Throwable $e) {
        }

        try {
            $this->forge->dropKey('reviews', 'uq_review_user_shop', false);
        } catch (\Throwable $e) {
        }

        if ($this->db->fieldExists('updated_at', 'reviews')) {
            $this->forge->dropColumn('reviews', 'updated_at');
        }
    }
}