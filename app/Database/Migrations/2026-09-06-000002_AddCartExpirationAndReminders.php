<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCartExpirationAndReminders extends Migration
{
    public function up()
    {
        $fieldsToAdd = [];

        if (!$this->db->fieldExists('expires_at', 'cart_items')) {
            $fieldsToAdd['expires_at'] = [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'is_selected',
            ];
        }

        if (!$this->db->fieldExists('last_reminder_at', 'cart_items')) {
            $fieldsToAdd['last_reminder_at'] = [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'expires_at',
            ];
        }

        if (!$this->db->fieldExists('reminder_count', 'cart_items')) {
            $fieldsToAdd['reminder_count'] = [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'last_reminder_at',
            ];
        }

        if (!empty($fieldsToAdd)) {
            $this->forge->addColumn('cart_items', $fieldsToAdd);
        }
    }

    public function down()
    {
        $columns = ['expires_at', 'last_reminder_at', 'reminder_count'];
        foreach ($columns as $col) {
            if ($this->db->fieldExists($col, 'cart_items')) {
                $this->forge->dropColumn('cart_items', $col);
            }
        }
    }
}
