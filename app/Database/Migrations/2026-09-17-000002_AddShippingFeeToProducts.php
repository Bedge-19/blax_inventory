<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddShippingFeeToProducts extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('shipping_fee', 'products')) {
            $this->forge->addColumn('products', [
                'shipping_fee' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                    'null'       => false,
                    'after'      => 'price',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('shipping_fee', 'products')) {
            $this->forge->dropColumn('products', 'shipping_fee');
        }
    }
}
