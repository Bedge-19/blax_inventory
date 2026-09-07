<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTenantVerification extends Migration
{
    public function up()
    {
        $this->forge->addColumn('shops', [
            'rejection_reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'status',
            ],
            'verified_at' => [
                'type'  => 'TIMESTAMP',
                'null'  => true,
                'after' => 'rejection_reason',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('shops', ['rejection_reason', 'verified_at']);
    }
}
