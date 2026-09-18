<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class OrderArchiveCommand extends BaseCommand
{
    protected $group       = 'Orders';
    protected $name        = 'orders:process-archive';
    protected $description = 'Archive completed/cancelled orders (> 2 days) and cancelled printing requests (> 5 days).';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        // 1. Archive Completed / Cancelled Orders older than 2 days (48 hours)
        $orderCutoff = date('Y-m-d H:i:s', strtotime('-2 days'));
        
        $ordersToArchive = $db->table('orders')
            ->select('id, shop_id, status, placed_at, completed_at, cancelled_at')
            ->whereIn('status', ['completed', 'delivered', 'cancelled'])
            ->groupStart()
                ->where('completed_at IS NOT NULL AND completed_at <=', $orderCutoff)
                ->orWhere('cancelled_at IS NOT NULL AND cancelled_at <=', $orderCutoff)
                ->orWhere('placed_at <=', $orderCutoff)
            ->groupEnd()
            ->get()->getResultArray();

        $archivedOrdersCount = 0;
        foreach ($ordersToArchive as $ord) {
            $exists = $db->table('archived_items')
                ->where('item_type', 'order')
                ->where('item_id', $ord['id'])
                ->countAllResults();

            if ($exists === 0) {
                $db->table('archived_items')->insert([
                    'shop_id'     => $ord['shop_id'] ?? 1,
                    'item_type'   => 'order',
                    'item_id'     => $ord['id'],
                    'archived_at' => date('Y-m-d H:i:s'),
                ]);
                $archivedOrdersCount++;
            }
        }
        CLI::write("Archived {$archivedOrdersCount} eligible order(s) (> 2 days).", 'green');

        // 2. Archive Cancelled Printing Requests older than 5 days
        $printCutoff = date('Y-m-d H:i:s', strtotime('-5 days'));

        $printToArchive = $db->table('printing_requests')
            ->select('id, shop_id, status, created_at, completed_at')
            ->where('status', 'cancelled')
            ->where('created_at <=', $printCutoff)
            ->get()->getResultArray();

        $archivedPrintCount = 0;
        foreach ($printToArchive as $req) {
            $exists = $db->table('archived_items')
                ->where('item_type', 'printing_request')
                ->where('item_id', $req['id'])
                ->countAllResults();

            if ($exists === 0) {
                $db->table('archived_items')->insert([
                    'shop_id'     => $req['shop_id'] ?? 1,
                    'item_type'   => 'printing_request',
                    'item_id'     => $req['id'],
                    'archived_at' => date('Y-m-d H:i:s'),
                ]);
                $archivedPrintCount++;
            }
        }
        CLI::write("Archived {$archivedPrintCount} eligible cancelled printing request(s) (> 5 days).", 'green');

        // 3. Auto-cancel stale Printing Requests in 'new' status older than 5 days
        $staleNewRequests = $db->table('printing_requests')
            ->select('id, shop_id, customer_id, request_number, created_at')
            ->where('status', 'new')
            ->where('created_at <=', $printCutoff)
            ->get()->getResultArray();

        $autoCancelledCount = 0;
        $notificationModel = new \App\Models\NotificationModel();
        foreach ($staleNewRequests as $stale) {
            $db->table('printing_requests')
                ->where('id', $stale['id'])
                ->update([
                    'status' => 'cancelled',
                ]);

            if (!empty($stale['customer_id'])) {
                $ref = $stale['request_number'] ?? ('PR-' . $stale['id']);
                $notificationModel->create(
                    (int) $stale['customer_id'],
                    'Printing Request Cancelled',
                    "Your printing request #{$ref} was automatically cancelled after remaining unattended for 5 days.",
                    'order',
                    base_url('customer/printing-requests')
                );
            }
            $autoCancelledCount++;
        }
        CLI::write("Auto-cancelled {$autoCancelledCount} stale printing request(s) in 'new' status (> 5 days).", 'green');

        return EXIT_SUCCESS;
    }
}
