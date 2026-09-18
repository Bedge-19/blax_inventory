<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class OrderArchiveCommand extends BaseCommand
{
    protected $group       = 'Orders';
    protected $name        = 'orders:process-archive';
    protected $description = 'Archive completed product orders and completed printing requests (> 3 days) to tenant archive. Pending orders and requests stay active.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        // 1. Archive Completed / Delivered Orders older than 3 days (72 hours)
        $orderCutoff = date('Y-m-d H:i:s', strtotime('-3 days'));
        
        $ordersToArchive = $db->table('orders')
            ->select('id, shop_id, order_number, status, placed_at, completed_at, cancelled_at')
            ->whereIn('status', ['completed', 'delivered'])
            ->groupStart()
                ->where('completed_at IS NOT NULL AND completed_at <=', $orderCutoff)
                ->orWhere('completed_at IS NULL AND placed_at <=', $orderCutoff)
            ->groupEnd()
            ->get()->getResultArray();

        $archivedOrdersCount = 0;
        foreach ($ordersToArchive as $ord) {
            $exists = $db->table('archived_items')
                ->where('shop_id', (int) ($ord['shop_id'] ?? 1))
                ->where('item_type', 'order')
                ->where('item_id', $ord['id'])
                ->countAllResults();

            if ($exists === 0) {
                $label = !empty($ord['order_number']) ? ('#' . ltrim($ord['order_number'], '#')) : ('#ORD-' . $ord['id']);
                $db->table('archived_items')->insert([
                    'shop_id'     => $ord['shop_id'] ?? 1,
                    'item_type'   => 'order',
                    'item_id'     => $ord['id'],
                    'item_label'  => $label,
                    'archived_at' => date('Y-m-d H:i:s'),
                ]);
                $archivedOrdersCount++;
            }
        }
        CLI::write("Archived {$archivedOrdersCount} eligible completed product order(s) (> 3 days).", 'green');

        // 2. Archive Completed Printing Requests older than 3 days (72 hours)
        $printCutoff = date('Y-m-d H:i:s', strtotime('-3 days'));

        $printToArchive = $db->table('printing_requests')
            ->select('id, shop_id, request_number, status, created_at, completed_at')
            ->where('status', 'completed')
            ->groupStart()
                ->where('completed_at IS NOT NULL AND completed_at <=', $printCutoff)
                ->orWhere('completed_at IS NULL AND created_at <=', $printCutoff)
            ->groupEnd()
            ->get()->getResultArray();

        $archivedPrintCount = 0;
        foreach ($printToArchive as $req) {
            $exists = $db->table('archived_items')
                ->where('shop_id', (int) ($req['shop_id'] ?? 1))
                ->where('item_type', 'printing_request')
                ->where('item_id', $req['id'])
                ->countAllResults();

            if ($exists === 0) {
                $label = !empty($req['request_number']) ? ('#' . ltrim($req['request_number'], '#')) : ('#PR-' . $req['id']);
                $db->table('archived_items')->insert([
                    'shop_id'     => $req['shop_id'] ?? 1,
                    'item_type'   => 'printing_request',
                    'item_id'     => $req['id'],
                    'item_label'  => $label,
                    'archived_at' => date('Y-m-d H:i:s'),
                ]);
                $archivedPrintCount++;
            }
        }
        CLI::write("Archived {$archivedPrintCount} eligible completed printing request(s) (> 3 days).", 'green');

        // Note: Pending product orders and pending printing requests are intentionally kept active indefinitely.
        CLI::write("Pending product orders and pending printing requests remain active indefinitely.", 'yellow');

        return EXIT_SUCCESS;
    }
}

