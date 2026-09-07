<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymongoEscrowFields extends Migration
{
    public function up()
    {
        // 1. Add payment_status to orders if it does not exist
        if (!$this->db->fieldExists('payment_status', 'orders')) {
            $this->forge->addColumn('orders', [
                'payment_status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '30',
                    'default'    => 'unpaid',
                    'after'      => 'status',
                ],
            ]);
        }

        // 2. Widen status on printing_requests to allow custom statuses like 'Paid (50% Down Payment)'
        $this->forge->modifyColumn('printing_requests', [
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '60',
                'default'    => 'new',
            ],
        ]);

        // 3. Widen status on payments to allow 'paid' / 'verified'
        $this->forge->modifyColumn('payments', [
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'default'    => 'pending',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->fieldExists('payment_status', 'orders')) {
            $this->forge->dropColumn('orders', 'payment_status');
        }
    }
}
