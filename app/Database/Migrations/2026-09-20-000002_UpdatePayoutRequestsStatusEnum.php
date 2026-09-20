<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdatePayoutRequestsStatusEnum extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `payout_requests` MODIFY COLUMN `status` ENUM('pending', 'processing', 'transfer_pending', 'completed', 'failed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `payout_requests` MODIFY COLUMN `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending'");
    }
}
