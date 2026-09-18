<?php

namespace App\Models;

use CodeIgniter\Model;

class ArchivedItemModel extends Model
{
    protected $table            = 'archived_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'shop_id',
        'item_type',
        'item_id',
        'item_label',
        'archived_by',
        'archived_at',
        'restored_at',
    ];

    /**
     * Paginated, tenant-scoped archive log with the archiver's name.
     * NOTE: the primary table must be referenced by its real name
     * (archived_items.*) because Model::paginate() regenerates a count
     * query that strips table aliases.
     */
    public function getArchivedForShopPaginated(
        int $shopId,
        int $perPage = 10,
        int $page = 1,
        string $group = 'archive'
    ): array {
        $this->builder()
            ->select('archived_items.*, u.first_name AS archived_by_first, u.last_name AS archived_by_last, u.profile_image_url AS archived_by_image')
            ->join('users u', 'u.id = archived_items.archived_by', 'left')
            ->where('archived_items.shop_id', $shopId)
            ->orderBy('archived_items.archived_at', 'DESC');

        $items = $this->paginate($perPage, $group, $page);

        return [
            'items' => $items ?: [],
            'pager' => $this->pager,
        ];
    }

    /**
     * Automatically archives completed orders and completed printing requests
     * for a shop that completed >= $days ago (default 3 days).
     * Records are NEVER deleted; they are logged to archived_items and
     * remain in the shop's archive history indefinitely.
     *
     * @return array{archived_orders: int, archived_printing: int}
     */
    public function autoArchiveCompletedItems(int $shopId, int $days = 3): array
    {
        $db = $this->db;
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // 1. Completed / Delivered orders older than 3 days
        $ordersToArchive = $db->table('orders')
            ->select('id, shop_id, order_number, status, completed_at, placed_at')
            ->where('shop_id', $shopId)
            ->whereIn('status', ['completed', 'delivered'])
            ->groupStart()
                ->where('completed_at IS NOT NULL AND completed_at <=', $cutoff)
                ->orWhere('completed_at IS NULL AND placed_at <=', $cutoff)
            ->groupEnd()
            ->get()->getResultArray();

        $archivedOrders = 0;
        foreach ($ordersToArchive as $ord) {
            $exists = $this->where('shop_id', $shopId)
                ->where('item_type', 'order')
                ->where('item_id', (int) $ord['id'])
                ->first();

            if (!$exists) {
                $label = !empty($ord['order_number']) ? ('#' . ltrim($ord['order_number'], '#')) : ('#ORD-' . $ord['id']);
                $this->insert([
                    'shop_id'     => $shopId,
                    'item_type'   => 'order',
                    'item_id'     => (int) $ord['id'],
                    'item_label'  => $label,
                    'archived_by' => (int) (session()->get('user_id') ?? 0) ?: null,
                    'archived_at' => date('Y-m-d H:i:s'),
                ]);
                $archivedOrders++;
            }
        }

        // 2. Completed printing requests older than 3 days
        $printToArchive = $db->table('printing_requests')
            ->select('id, shop_id, request_number, status, completed_at, created_at')
            ->where('shop_id', $shopId)
            ->where('status', 'completed')
            ->groupStart()
                ->where('completed_at IS NOT NULL AND completed_at <=', $cutoff)
                ->orWhere('completed_at IS NULL AND created_at <=', $cutoff)
            ->groupEnd()
            ->get()->getResultArray();

        $archivedPrint = 0;
        foreach ($printToArchive as $pr) {
            $exists = $this->where('shop_id', $shopId)
                ->where('item_type', 'printing_request')
                ->where('item_id', (int) $pr['id'])
                ->first();

            if (!$exists) {
                $label = !empty($pr['request_number']) ? ('#' . ltrim($pr['request_number'], '#')) : ('#PR-' . $pr['id']);
                $this->insert([
                    'shop_id'     => $shopId,
                    'item_type'   => 'printing_request',
                    'item_id'     => (int) $pr['id'],
                    'item_label'  => $label,
                    'archived_by' => (int) (session()->get('user_id') ?? 0) ?: null,
                    'archived_at' => date('Y-m-d H:i:s'),
                ]);
                $archivedPrint++;
            }
        }

        return [
            'archived_orders'   => $archivedOrders,
            'archived_printing' => $archivedPrint,
        ];
    }
}