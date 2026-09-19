<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLocationUpdatedAtToDeliveries extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('location_updated_at', 'deliveries')) {
            $this->forge->addColumn('deliveries', [
                'location_updated_at' => [
                    'type'  => 'DATETIME',
                    'null'  => true,
                    'after' => 'current_lng',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('location_updated_at', 'deliveries')) {
            $this->forge->dropColumn('deliveries', 'location_updated_at');
        }
    }
}
