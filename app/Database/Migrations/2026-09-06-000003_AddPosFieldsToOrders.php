<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPosFieldsToOrders extends Migration
{
    public function up()
    {
        // 1. Add is_pos_addition to order_items
        if (!$this->db->fieldExists('is_pos_addition', 'order_items')) {
            $this->forge->addColumn('order_items', [
                'is_pos_addition' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'after'      => 'line_total',
                ],
            ]);
        }

        // 2. Add POS fields to orders
        $orderFields = [];
        if (!$this->db->fieldExists('pos_additional_amount', 'orders')) {
            $orderFields['pos_additional_amount'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
                'after'      => 'total_amount',
            ];
        }

        if (!$this->db->fieldExists('pos_payment_method', 'orders')) {
            $orderFields['pos_payment_method'] = [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'default'    => 'none',
                'after'      => 'pos_additional_amount',
            ];
        }

        if (!$this->db->fieldExists('pos_payment_status', 'orders')) {
            $orderFields['pos_payment_status'] = [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'default'    => 'not_applicable',
                'after'      => 'pos_payment_method',
            ];
        }

        if (!empty($orderFields)) {
            $this->forge->addColumn('orders', $orderFields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('is_pos_addition', 'order_items')) {
            $this->forge->dropColumn('order_items', 'is_pos_addition');
        }

        $orderCols = ['pos_additional_amount', 'pos_payment_method', 'pos_payment_status'];
        foreach ($orderCols as $col) {
            if ($this->db->fieldExists($col, 'orders')) {
                $this->forge->dropColumn('orders', $col);
            }
        }
    }
}
