<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePrintingEnhancementsAndShopPricing extends Migration
{
    public function up()
    {
        // 1. Update printing_requests table
        $prFields = [];
        if (!$this->db->fieldExists('document_type', 'printing_requests')) {
            $prFields['document_type'] = [
                'type'       => 'ENUM',
                'constraint' => ['pdf', 'docx'],
                'default'    => 'pdf',
                'null'       => false,
                'after'      => 'file_url',
            ];
        }
        if (!$this->db->fieldExists('doc_change_type', 'printing_requests')) {
            $prFields['doc_change_type'] = [
                'type'       => 'ENUM',
                'constraint' => ['as_is', 'has_changes'],
                'null'       => true,
                'default'    => null,
                'after'      => 'document_type',
            ];
        }
        if (!$this->db->fieldExists('special_instructions', 'printing_requests')) {
            $prFields['special_instructions'] = [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
                'after'   => 'paper_stock',
            ];
        }
        if (!empty($prFields)) {
            $this->forge->addColumn('printing_requests', $prFields);
        }

        // 2. Create printing_request_attachments table
        if (!$this->db->tableExists('printing_request_attachments')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'printing_request_id' => [
                    'type'     => 'BIGINT',
                    'unsigned' => true,
                    'null'     => false,
                ],
                'image_url' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 500,
                    'null'       => false,
                ],
                'created_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('printing_request_id');
            $this->forge->addForeignKey('printing_request_id', 'printing_requests', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('printing_request_attachments', true);
        }

        // 3. Create shop_printing_settings table
        if (!$this->db->tableExists('shop_printing_settings')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'shop_id' => [
                    'type'     => 'BIGINT',
                    'unsigned' => true,
                    'null'     => false,
                ],
                'down_payment_percent' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 50.00,
                ],
                'price_staple' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 10.00,
                ],
                'price_spiral' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 35.00,
                ],
                'created_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
                'updated_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('shop_id');
            $this->forge->addForeignKey('shop_id', 'shops', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('shop_printing_settings', true);
        }

        // 4. Create shop_printing_paper_sizes table
        if (!$this->db->tableExists('shop_printing_paper_sizes')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'shop_id' => [
                    'type'     => 'BIGINT',
                    'unsigned' => true,
                    'null'     => false,
                ],
                'paper_size' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                ],
                'is_enabled' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'price_color' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                ],
                'price_bw' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['shop_id', 'paper_size'], 'uq_pshop_size');
            $this->forge->addForeignKey('shop_id', 'shops', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('shop_printing_paper_sizes', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('shop_printing_paper_sizes')) {
            $this->forge->dropTable('shop_printing_paper_sizes', true);
        }

        if ($this->db->tableExists('shop_printing_settings')) {
            $this->forge->dropTable('shop_printing_settings', true);
        }

        if ($this->db->tableExists('printing_request_attachments')) {
            $this->forge->dropTable('printing_request_attachments', true);
        }

        if ($this->db->fieldExists('special_instructions', 'printing_requests')) {
            $this->forge->dropColumn('printing_requests', 'special_instructions');
        }
        if ($this->db->fieldExists('doc_change_type', 'printing_requests')) {
            $this->forge->dropColumn('printing_requests', 'doc_change_type');
        }
        if ($this->db->fieldExists('document_type', 'printing_requests')) {
            $this->forge->dropColumn('printing_requests', 'document_type');
        }
    }
}
