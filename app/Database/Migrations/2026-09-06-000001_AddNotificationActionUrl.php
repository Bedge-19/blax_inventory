<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNotificationActionUrl extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('action_url', 'notifications')) {
            $this->forge->addColumn('notifications', [
                'action_url' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'message',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('action_url', 'notifications')) {
            $this->forge->dropColumn('notifications', 'action_url');
        }
    }
}
