<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStreetAndBarangayToShops extends Migration
{
    public function up()
    {
        $fields = [];
        if (!$this->db->fieldExists('street', 'shops')) {
            $fields['street'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'address_line',
            ];
        }
        if (!$this->db->fieldExists('barangay', 'shops')) {
            $fields['barangay'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'street',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('shops', $fields);
        }
    }

    public function down()
    {
        $drop = [];
        if ($this->db->fieldExists('street', 'shops')) {
            $drop[] = 'street';
        }
        if ($this->db->fieldExists('barangay', 'shops')) {
            $drop[] = 'barangay';
        }

        if (!empty($drop)) {
            $this->forge->dropColumn('shops', $drop);
        }
    }
}
