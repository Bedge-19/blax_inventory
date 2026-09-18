<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeductionPercentToPayoutRequests extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('deduction_percent', 'payout_requests')) {
            $this->forge->addColumn('payout_requests', [
                'deduction_percent' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 3.00,
                    'null'       => false,
                    'after'      => 'fee',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('deduction_percent', 'payout_requests')) {
            $this->forge->dropColumn('payout_requests', 'deduction_percent');
        }
    }
}
