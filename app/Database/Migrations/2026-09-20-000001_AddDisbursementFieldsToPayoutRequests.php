<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDisbursementFieldsToPayoutRequests extends Migration
{
    public function up()
    {
        $fieldsToAdd = [];

        if (!$this->db->fieldExists('recipient_account_name', 'payout_requests')) {
            $fieldsToAdd['recipient_account_name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'destination_detail',
            ];
        }

        if (!$this->db->fieldExists('recipient_institution', 'payout_requests')) {
            $fieldsToAdd['recipient_institution'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'G-Xchange, Inc.',
                'null'       => false,
                'after'      => 'recipient_account_name',
            ];
        }

        if (!$this->db->fieldExists('net_amount', 'payout_requests')) {
            $fieldsToAdd['net_amount'] = [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
                'after'      => 'fee',
            ];
        }

        if (!$this->db->fieldExists('processed_at', 'payout_requests')) {
            $fieldsToAdd['processed_at'] = [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'completed_at',
            ];
        }

        if (!$this->db->fieldExists('processed_by', 'payout_requests')) {
            $fieldsToAdd['processed_by'] = [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'processed_at',
            ];
        }

        if (!$this->db->fieldExists('paymongo_transfer_id', 'payout_requests')) {
            $fieldsToAdd['paymongo_transfer_id'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'processed_by',
            ];
        }

        if (!$this->db->fieldExists('paymongo_batch_id', 'payout_requests')) {
            $fieldsToAdd['paymongo_batch_id'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'paymongo_transfer_id',
            ];
        }

        if (!$this->db->fieldExists('transfer_initiated_at', 'payout_requests')) {
            $fieldsToAdd['transfer_initiated_at'] = [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'paymongo_batch_id',
            ];
        }

        if (!$this->db->fieldExists('transfer_status', 'payout_requests')) {
            $fieldsToAdd['transfer_status'] = [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'transfer_initiated_at',
            ];
        }

        if (!$this->db->fieldExists('failure_reason', 'payout_requests')) {
            $fieldsToAdd['failure_reason'] = [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'transfer_status',
            ];
        }

        if (!$this->db->fieldExists('idempotency_key', 'payout_requests')) {
            $fieldsToAdd['idempotency_key'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'failure_reason',
            ];
        }

        if (!$this->db->fieldExists('last_webhook_event_id', 'payout_requests')) {
            $fieldsToAdd['last_webhook_event_id'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'idempotency_key',
            ];
        }

        if (!empty($fieldsToAdd)) {
            $this->forge->addColumn('payout_requests', $fieldsToAdd);
        }
    }

    public function down()
    {
        $columns = [
            'recipient_account_name',
            'recipient_institution',
            'net_amount',
            'processed_at',
            'processed_by',
            'paymongo_transfer_id',
            'paymongo_batch_id',
            'transfer_initiated_at',
            'transfer_status',
            'failure_reason',
            'idempotency_key',
            'last_webhook_event_id',
        ];

        foreach ($columns as $column) {
            if ($this->db->fieldExists($column, 'payout_requests')) {
                $this->forge->dropColumn('payout_requests', $column);
            }
        }
    }
}
