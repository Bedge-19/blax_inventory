<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateShopNotificationPreferences extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'shop_id'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'new_orders'      => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'low_stock'       => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'weekly_summary'  => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'created_at'      => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at'      => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('shop_id');
        $this->forge->createTable('shop_notification_preferences', true);
    }

    public function down()
    {
        $this->forge->dropTable('shop_notification_preferences', true);
    }
}