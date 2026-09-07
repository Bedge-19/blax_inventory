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
}