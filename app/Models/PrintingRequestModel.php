<?php

namespace App\Models;

use CodeIgniter\Model;

class PrintingRequestModel extends Model
{
    protected $table            = 'printing_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'request_number',
        'customer_id',
        'shop_id',
        'file_name',
        'file_url',
        'document_type',
        'doc_change_type',
        'special_instructions',
        'page_count',
        'paper_size',
        'color_mode',
        'copies',
        'binding_option',
        'paper_stock',
        'fulfillment_method',
        'total_price',
        'down_payment',
        'status',
        'printer_assigned',
        'progress_percent',
        'created_at',
        'completed_at',
    ];

    public function getRequestsByCustomer(int $customerId)
    {
        return $this->db->table('printing_requests pr')
            ->select('pr.*, s.shop_name')
            ->join('shops s', 's.id = pr.shop_id', 'left')
            ->where('pr.customer_id', $customerId)
            ->orderBy('pr.created_at', 'DESC')
            ->get()->getResultArray();
    }

    public function getRequestsByShop(int $shopId)
    {
        return $this->db->table('printing_requests pr')
            ->select('pr.*, u.first_name, u.last_name')
            ->join('users u', 'u.id = pr.customer_id', 'left')
            ->where('pr.shop_id', $shopId)
            ->orderBy('pr.created_at', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Count printing requests for a shop, optionally bounded by a
     * created_at window.
     */
    public function countByShop(int $shopId, ?string $from = null, ?string $to = null): int
    {
        $builder = $this->db->table('printing_requests')->where('shop_id', $shopId);

        if ($from !== null) {
            $builder->where('created_at >=', $from);
        }
        if ($to !== null) {
            $builder->where('created_at <', $to);
        }

        return (int) $builder->countAllResults();
    }

    /**
     * Paginated request listing for a shop with optional search and status
     * filters. The given group keeps independent paginators on one page apart.
     *
     * @return array{requests: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getRequestsByShopPaginated(
        int $shopId,
        ?string $search = null,
        ?string $status = null,
        int $perPage = 10,
        int $page = 1,
        string $group = 'recent'
    ) {
        $this->builder()
            ->select('printing_requests.*, u.first_name, u.last_name, u.profile_image_url')
            ->join('users u', 'u.id = printing_requests.customer_id', 'left')
            ->where('printing_requests.shop_id', $shopId)
            ->orderBy("CASE 
                WHEN LOWER(printing_requests.status) = 'new' THEN 1 
                WHEN LOWER(printing_requests.status) = 'in_production' THEN 2 
                WHEN LOWER(printing_requests.status) = 'ready_for_pickup' THEN 3 
                WHEN LOWER(printing_requests.status) = 'ready_for_delivery' THEN 4 
                WHEN LOWER(printing_requests.status) = 'completed' THEN 5 
                ELSE 6 
            END", 'ASC', false)
            ->orderBy('printing_requests.created_at', 'DESC');

        $archived = $this->db->table('archived_items')
            ->select('item_id')
            ->where('shop_id', $shopId)
            ->where('item_type', 'printing_request');
        $this->builder()->whereNotIn('printing_requests.id', $archived, false);

        if ($status !== null && $status !== '') {
            $this->builder()->where('printing_requests.status', $status);
        }
        if ($search !== null && $search !== '') {
            $this->builder()
                ->groupStart()
                ->like('printing_requests.request_number', $search)
                ->orLike('printing_requests.file_name', $search)
                ->groupStart()
                ->like('u.first_name', $search)
                ->orLike('u.last_name', $search)
                ->groupEnd()
                ->groupEnd();
        }

        $requests = $this->paginate($perPage, $group, $page);

        return [
            'requests' => $requests ?: [],
            'pager'    => $this->pager,
        ];
    }

    /**
     * Counts of printing requests per status for a shop.
     *
     * @return array{new: int, in_production: int, ready_for_pickup: int, ready_for_delivery: int, completed: int, cancelled: int}
     */
    public function getPrintSummary(int $shopId): array
    {
        $rows = $this->db->table('printing_requests')
            ->select('status, COUNT(*) AS c')
            ->where('shop_id', $shopId)
            ->groupBy('status')
            ->get()->getResultArray();

        $summary = [
            'new'               => 0,
            'in_production'     => 0,
            'ready_for_pickup'  => 0,
            'ready_for_delivery'=> 0,
            'completed'         => 0,
            'cancelled'         => 0,
        ];

        foreach ($rows as $r) {
            if (isset($summary[$r['status']])) {
                $summary[$r['status']] = (int) $r['c'];
            }
        }

        return $summary;
    }

    /**
     * Requests currently in production for a shop, oldest first, used by
     * the production queue timeline.
     */
    public function getProductionQueue(int $shopId, int $limit = 10): array
    {
        return $this->db->table('printing_requests pr')
            ->select('pr.*, u.first_name, u.last_name')
            ->join('users u', 'u.id = pr.customer_id', 'left')
            ->where('pr.shop_id', $shopId)
            ->where('pr.status', 'in_production')
            ->orderBy('pr.created_at', 'ASC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /**
     * Paginated completed requests for a shop with optional search.
     *
     * @return array{requests: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getCompletedPaginated(
        int $shopId,
        ?string $search = null,
        int $perPage = 8,
        int $page = 1,
        string $group = 'completed'
    ) {
        $this->builder()
            ->select('printing_requests.*, u.first_name, u.last_name')
            ->join('users u', 'u.id = printing_requests.customer_id', 'left')
            ->where('printing_requests.shop_id', $shopId)
            ->where('printing_requests.status', 'completed')
            ->orderBy('printing_requests.completed_at', 'DESC')
            ->orderBy('printing_requests.created_at', 'DESC');

        $archived = $this->db->table('archived_items')
            ->select('item_id')
            ->where('shop_id', $shopId)
            ->where('item_type', 'printing_request');
        $this->builder()->whereNotIn('printing_requests.id', $archived, false);

        if ($search !== null && $search !== '') {
            $this->builder()
                ->groupStart()
                ->like('printing_requests.request_number', $search)
                ->orLike('printing_requests.file_name', $search)
                ->groupStart()
                ->like('u.first_name', $search)
                ->orLike('u.last_name', $search)
                ->groupEnd()
                ->groupEnd();
        }

        $requests = $this->paginate($perPage, $group, $page);

        return [
            'requests' => $requests ?: [],
            'pager'    => $this->pager,
        ];
    }
}
