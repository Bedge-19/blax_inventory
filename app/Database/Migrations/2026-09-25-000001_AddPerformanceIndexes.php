<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        $db = $this->db;

        // Helper to safely add an index if it doesn't already exist
        $safeAddIndex = function (string $table, string $indexName, string $columns) use ($db) {
            if (! $db->tableExists($table)) {
                return;
            }

            try {
                // Check if index already exists
                $existing = $db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'")->getResultArray();
                if (empty($existing)) {
                    $db->query("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` ({$columns})");
                }
            } catch (\Throwable $e) {
                log_message('notice', "Index {$indexName} on {$table} notice: " . $e->getMessage());
            }
        };

        // 1. Orders table performance indexes
        $safeAddIndex('orders', 'idx_orders_shop_status_placed', '`shop_id`, `status`, `placed_at`');
        $safeAddIndex('orders', 'idx_orders_customer_status', '`customer_id`, `status`');
        $safeAddIndex('orders', 'idx_orders_shop_created', '`shop_id`, `created_at`');

        // 2. Products table performance indexes
        $safeAddIndex('products', 'idx_products_shop_del_stock', '`shop_id`, `deleted_at`, `stock_quantity`');
        $safeAddIndex('products', 'idx_products_category_del', '`category_id`, `deleted_at`');
        $safeAddIndex('products', 'idx_products_bestseller', '`is_bestseller`, `deleted_at`');

        // 3. Printing Requests table performance indexes
        $safeAddIndex('printing_requests', 'idx_printing_shop_status_created', '`shop_id`, `status`, `created_at`');
        $safeAddIndex('printing_requests', 'idx_printing_customer_status', '`customer_id`, `status`');

        // 4. Order Items table performance indexes
        $safeAddIndex('order_items', 'idx_order_items_order_prod', '`order_id`, `product_id`');

        // 5. Notifications table performance indexes
        $safeAddIndex('notifications', 'idx_notifications_user_read_created', '`user_id`, `is_read`, `created_at`');

        // 6. Reviews table performance indexes
        $safeAddIndex('reviews', 'idx_reviews_product_rating', '`product_id`, `rating`');
        $safeAddIndex('reviews', 'idx_reviews_shop_rating', '`shop_id`, `rating`');
    }

    public function down()
    {
        $db = $this->db;

        $safeDropIndex = function (string $table, string $indexName) use ($db) {
            if (! $db->tableExists($table)) {
                return;
            }

            try {
                $existing = $db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'")->getResultArray();
                if (! empty($existing)) {
                    $db->query("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
                }
            } catch (\Throwable $e) {}
        };

        $safeDropIndex('orders', 'idx_orders_shop_status_placed');
        $safeDropIndex('orders', 'idx_orders_customer_status');
        $safeDropIndex('orders', 'idx_orders_shop_created');
        $safeDropIndex('products', 'idx_products_shop_del_stock');
        $safeDropIndex('products', 'idx_products_category_del');
        $safeDropIndex('products', 'idx_products_bestseller');
        $safeDropIndex('printing_requests', 'idx_printing_shop_status_created');
        $safeDropIndex('printing_requests', 'idx_printing_customer_status');
        $safeDropIndex('order_items', 'idx_order_items_order_prod');
        $safeDropIndex('notifications', 'idx_notifications_user_read_created');
        $safeDropIndex('reviews', 'idx_reviews_product_rating');
        $safeDropIndex('reviews', 'idx_reviews_shop_rating');
    }
}
