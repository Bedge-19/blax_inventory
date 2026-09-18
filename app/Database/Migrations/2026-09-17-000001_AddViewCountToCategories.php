<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddViewCountToCategories extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('view_count', 'categories')) {
            $this->forge->addColumn('categories', [
                'view_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'sort_order',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('view_count', 'categories')) {
            $this->forge->dropColumn('categories', 'view_count');
        }
    }
}
