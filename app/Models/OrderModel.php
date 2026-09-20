<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table            = 'orders';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'order_number',
        'customer_id',
        'shop_id',
        'shipping_address_id',
        'fulfillment_method',
        'payment_method',
        'subtotal',
        'shipping_fee',
        'tax_amount',
        'total_amount',
        'pos_additional_amount',
        'pos_payment_method',
        'pos_payment_status',
        'status',
        'payment_status',
        'placed_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
    ];

    public function getOrdersByCustomer(int $customerId)
    {
        return $this->db->table('orders o')
            ->select('o.*, s.shop_name, s.logo_url as shop_logo, s.address_line as shop_address, s.city as shop_city, s.province as shop_province')
            ->join('shops s', 's.id = o.shop_id', 'left')
            ->where('o.customer_id', $customerId)
            ->orderBy('o.placed_at', 'DESC')
            ->get()->getResultArray();
    }

    public function getOrdersByShop(int $shopId)
    {
        $archived = $this->db->table('archived_items')
            ->select('item_id')
            ->where('shop_id', $shopId)
            ->where('item_type', 'order');

        return $this->db->table('orders o')
            ->select('o.*, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = o.customer_id', 'left')
            ->where('o.shop_id', $shopId)
            ->whereNotIn('o.id', $archived, false)
            ->orderBy("CASE 
                WHEN LOWER(o.status) = 'pending' THEN 1 
                WHEN LOWER(o.status) IN ('processing', 'in_progress') THEN 2 
                WHEN LOWER(o.status) = 'ready_for_pickup' THEN 3
                WHEN LOWER(o.status) = 'shipped' THEN 4
                WHEN LOWER(o.status) = 'delivered' THEN 5
                WHEN LOWER(o.status) = 'completed' THEN 6 
                ELSE 7 
            END", 'ASC', false)
            ->orderBy('o.placed_at', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Shared, tenant-scoped order query for the Orders table and CSV export.
     * Search matches order number, customer name/email, and product
     * name/SKU (via order_items). Dates use inclusive placed_at windows.
     */
    private function buildOrderQuery(
        int $shopId,
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $fulfillment = null
    ) {
        // NOTE: the primary table must be referenced by its real name
        // (orders.*) because Model::paginate() regenerates a count query
        // that strips table aliases.
        $builder = $this->builder()
            ->select('orders.*, u.first_name, u.last_name, u.email, u.profile_image_url, COALESCE(sa.phone, u.phone) as customer_phone, sa.label as address_label, sa.address_line1, sa.city, sa.province')
            ->join('users u', 'u.id = orders.customer_id', 'left')
            ->join('shipping_addresses sa', 'sa.id = orders.shipping_address_id', 'left')
            ->where('orders.shop_id', $shopId);

        $archived = $this->db->table('archived_items')
            ->select('item_id')
            ->where('shop_id', $shopId)
            ->where('item_type', 'order');
        $builder->whereNotIn('orders.id', $archived, false);

        if ($search !== null && $search !== '') {
            $itemSub = function ($q) use ($search) {
                return $q->select('oi.order_id')
                    ->from('order_items oi')
                    ->join('products p', 'p.id = oi.product_id', 'left')
                    ->groupStart()
                    ->like('oi.product_name', $search)
                    ->orLike('p.sku', $search)
                    ->groupEnd();
            };

            $builder
                ->groupStart()
                ->like('orders.order_number', $search)
                ->orLike('u.first_name', $search)
                ->orLike('u.last_name', $search)
                ->orLike('u.email', $search)
                ->orWhereIn('orders.id', $itemSub, false)
                ->groupEnd();
        }

        if ($status !== null && $status !== '') {
            $builder->where('orders.status', $status);
        }
        if ($fulfillment !== null && in_array($fulfillment, ['pickup', 'delivery'], true)) {
            $builder->where('orders.fulfillment_method', $fulfillment);
        }
        if ($dateFrom !== null && $dateFrom !== '') {
            $builder->where('orders.placed_at >=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo !== null && $dateTo !== '') {
            $builder->where('orders.placed_at <=', $dateTo . ' 23:59:59');
        }

        return $builder;
    }

    public function getOrdersByShopPaginated(
        int $shopId,
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 10,
        int $page = 1,
        string $group = 'orders',
        ?string $fulfillment = null
    ): array {
        $this->buildOrderQuery($shopId, $search, $status, $dateFrom, $dateTo, $fulfillment)
            ->orderBy("CASE 
                WHEN LOWER(orders.status) = 'pending' THEN 1 
                WHEN LOWER(orders.status) IN ('processing', 'in_progress') THEN 2 
                WHEN LOWER(orders.status) = 'ready_for_pickup' THEN 3
                WHEN LOWER(orders.status) = 'shipped' THEN 4
                WHEN LOWER(orders.status) = 'delivered' THEN 5
                WHEN LOWER(orders.status) = 'completed' THEN 6 
                ELSE 7 
            END", 'ASC', false)
            ->orderBy('orders.placed_at', 'DESC');

        $orders = $this->paginate($perPage, $group, $page);

        if (!empty($orders)) {
            $orderIds = array_column($orders, 'id');
            $items = $this->db->table('order_items')
                ->select('order_id, product_name, quantity')
                ->whereIn('order_id', $orderIds)
                ->get()
                ->getResultArray();

            $grouped = [];
            $unitTotals = [];
            foreach ($items as $it) {
                $oid = (int) $it['order_id'];
                $grouped[$oid][] = $it['product_name'] . ((int) $it['quantity'] > 1 ? ' (' . (int) $it['quantity'] . 'x)' : '');
                $unitTotals[$oid] = ($unitTotals[$oid] ?? 0) + (int) $it['quantity'];
            }

            foreach ($orders as &$ord) {
                $oid = (int) $ord['id'];
                $list = $grouped[$oid] ?? [];
                $ord['items_summary'] = !empty($list) ? implode(', ', array_slice($list, 0, 2)) . (count($list) > 2 ? ' +' . (count($list) - 2) . ' more' : '') : '';
                $ord['total_units'] = $unitTotals[$oid] ?? 0;
                $ord['items_count'] = count($list);
            }
            unset($ord);
        }

        return [
            'orders' => $orders ?: [],
            'pager'  => $this->pager,
        ];
    }

    public function getOrdersForExport(
        int $shopId,
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        return $this->buildOrderQuery($shopId, $search, $status, $dateFrom, $dateTo)
            ->orderBy("CASE 
                WHEN LOWER(orders.status) = 'pending' THEN 1 
                WHEN LOWER(orders.status) IN ('processing', 'in_progress') THEN 2 
                WHEN LOWER(orders.status) = 'ready_for_pickup' THEN 3
                WHEN LOWER(orders.status) = 'shipped' THEN 4
                WHEN LOWER(orders.status) = 'delivered' THEN 5
                WHEN LOWER(orders.status) = 'completed' THEN 6 
                ELSE 7 
            END", 'ASC', false)
            ->orderBy('orders.placed_at', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Retrieves all active processing orders for a given shop along with their
     * order items and customer info for the dedicated processing fulfillment queue.
     */
    public function getProcessingOrdersForShop(int $shopId): array
    {
        $archived = $this->db->table('archived_items')
            ->select('item_id')
            ->where('shop_id', $shopId)
            ->where('item_type', 'order');

        $orders = $this->db->table('orders')
            ->select('orders.*, u.first_name, u.last_name, u.email, u.profile_image_url, COALESCE(sa.phone, u.phone) as customer_phone, sa.label as address_label, sa.address_line1, sa.city, sa.province')
            ->join('users u', 'u.id = orders.customer_id', 'left')
            ->join('shipping_addresses sa', 'sa.id = orders.shipping_address_id', 'left')
            ->where('orders.shop_id', $shopId)
            ->whereIn('orders.status', ['pending', 'processing'])
            ->whereNotIn('orders.id', $archived, false)
            ->orderBy('orders.placed_at', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($orders)) {
            return [];
        }

        $orderIds = array_column($orders, 'id');
        $items = $this->db->table('order_items')
            ->select('order_items.*, (SELECT image_url FROM product_images WHERE product_id = order_items.product_id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as product_image')
            ->whereIn('order_items.order_id', $orderIds)
            ->get()
            ->getResultArray();

        $itemsByOrder = [];
        foreach ($items as $it) {
            $itemsByOrder[$it['order_id']][] = $it;
        }

        foreach ($orders as &$ord) {
            $ord['items'] = $itemsByOrder[$ord['id']] ?? [];
        }
        unset($ord);

        return $orders;
    }

    /**
     * Orders-page summary cards, all tenant-scoped and computed with
     * aggregate SQL (no full-table loads).
     */
    public function getOrdersSummary(int $shopId): array
    {
        $db    = $this->db;
        $today = $this->getLocalToday();

        $totalOrders = $this->getOrderCountForShop($shopId);
        $readyPickup = $this->getOrderCountForShop($shopId, ['ready_for_pickup']);

        $pendingShip = (int) ($db->table('deliveries d')
            ->select('COUNT(*) AS c')
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'inner')
            ->where('o.shop_id', $shopId)
            ->whereIn('d.status', ['ready_for_pickup', 'shipped', 'in_transit'])
            ->get()->getRow()->c ?? 0);

        $archived = $this->db->table('archived_items')
            ->select('item_id')
            ->where('shop_id', $shopId)
            ->where('item_type', 'order');

        $statusRow = $db->table('orders')
            ->select("
                COUNT(*) as all_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'ready_for_pickup' THEN 1 ELSE 0 END) as ready_for_pickup,
                SUM(CASE WHEN status IN ('shipped', 'in_transit') THEN 1 ELSE 0 END) as in_transit,
                SUM(CASE WHEN status IN ('delivered', 'completed') THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->where('shop_id', $shopId)
            ->whereNotIn('id', $archived, false)
            ->get()->getRowArray() ?: [];

        return [
            'total_orders'      => $totalOrders,
            'pending_shipments' => $pendingShip,
            'ready_for_pickup'  => $readyPickup,
            'revenue_today'     => $this->getRevenueTodayForShop($shopId),
            'status_counts'     => [
                'all'              => (int) ($statusRow['all_count'] ?? $totalOrders),
                'pending'          => (int) ($statusRow['pending'] ?? 0),
                'processing'       => (int) ($statusRow['processing'] ?? 0),
                'ready_for_pickup' => (int) ($statusRow['ready_for_pickup'] ?? 0),
                'in_transit'       => (int) ($statusRow['in_transit'] ?? 0),
                'completed'        => (int) ($statusRow['completed'] ?? 0),
                'cancelled'        => (int) ($statusRow['cancelled'] ?? 0),
            ],
        ];
    }

    /**
     * Compute today's revenue for a tenant shop within the full 24-hour window
     * of the local store date (Asia/Manila).
     * Includes:
     * - Orders completed today (e.g. store pickups fulfilled today, walk-ins, delivered orders).
     * - Orders placed today that are paid or delivered.
     * - Printing requests completed today or created today with paid status.
     */
    public function getRevenueTodayForShop(int $shopId): float
    {
        $today = date('Y-m-d');
        $startOfDay = $today . ' 00:00:00';
        $endOfDay   = $today . ' 23:59:59';

        $orderBuilder = $this->db->table('orders')
            ->selectSum('total_amount', 'rev')
            ->where('shop_id', $shopId)
            ->where('status !=', 'cancelled')
            ->groupStart()
                ->groupStart()
                    ->where('completed_at >=', $startOfDay)
                    ->where('completed_at <=', $endOfDay)
                    ->whereIn('status', ['completed', 'delivered'])
                ->groupEnd()
                ->orGroupStart()
                    ->where('placed_at >=', $startOfDay)
                    ->where('placed_at <=', $endOfDay)
                    ->groupStart()
                        ->where('payment_status', 'paid')
                        ->orWhereIn('status', ['completed', 'delivered'])
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd();

        $orderRev = (float) ($orderBuilder->get()->getRow()->rev ?? 0.0);

        // Printing requests revenue today
        $prBuilder = $this->db->table('printing_requests')
            ->selectSum('total_price', 'rev')
            ->where('shop_id', $shopId)
            ->where('status !=', 'cancelled')
            ->groupStart()
                ->groupStart()
                    ->where('completed_at >=', $startOfDay)
                    ->where('completed_at <=', $endOfDay)
                    ->where('status', 'completed')
                ->groupEnd()
                ->orGroupStart()
                    ->where('created_at >=', $startOfDay)
                    ->where('created_at <=', $endOfDay)
                    ->groupStart()
                        ->where('down_payment >', 0)
                        ->orWhere('status', 'completed')
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd();

        $prRev = (float) ($prBuilder->get()->getRow()->rev ?? 0.0);

        return round($orderRev + $prRev, 2);
    }

    /**
     * Revenue and units sold grouped by product category (non-cancelled
     * orders only). Uncategorized products roll into "Uncategorized".
     */
    public function getCategorySales(int $shopId, ?string $from = null, ?string $to = null): array
    {
        $builder = $this->db->table('order_items oi')
            ->select('COALESCE(c.name, "Uncategorized") AS category, SUM(oi.quantity) AS units, SUM(oi.line_total) AS revenue')
            ->join('orders o', 'o.id = oi.order_id')
            ->join('products p', 'p.id = oi.product_id', 'left')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->where('o.shop_id', $shopId)
            ->where('o.status !=', 'cancelled');

        if ($from !== null) {
            $builder->where('o.placed_at >=', $from . ' 00:00:00');
        }
        if ($to !== null) {
            $builder->where('o.placed_at <=', $to . ' 23:59:59');
        }

        return $builder->groupBy('category')
            ->orderBy('revenue', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Order count grouped by each existing order status.
     */
    public function getOrderStatusDistribution(int $shopId): array
    {
        return $this->db->table('orders')
            ->select('status, COUNT(*) AS count')
            ->where('shop_id', $shopId)
            ->groupBy('status')
            ->get()->getResultArray();
    }

    /**
     * Top products by revenue for a shop (non-cancelled orders). Stock
     * values are carried from the product row so the view can derive the
     * stock status without extra queries.
     */
    public function getTopProductsForShop(int $shopId, int $limit = 5, ?string $from = null, ?string $to = null): array
    {
        $builder = $this->db->table('order_items oi')
            ->select('MAX(COALESCE(p.name, oi.product_name)) AS name, SUM(oi.quantity) AS units, SUM(oi.line_total) AS revenue, MAX(p.stock_quantity) AS stock_quantity, MAX(p.low_stock_threshold) AS low_stock_threshold, MAX(p.status) AS product_status')
            ->join('orders o', 'o.id = oi.order_id')
            ->join('products p', 'p.id = oi.product_id', 'left')
            ->where('o.shop_id', $shopId)
            ->where('o.status !=', 'cancelled')
            ->groupBy('oi.product_id')
            ->orderBy('revenue', 'DESC')
            ->limit($limit);

        if ($from !== null) {
            $builder->where('o.placed_at >=', $from . ' 00:00:00');
        }
        if ($to !== null) {
            $builder->where('o.placed_at <=', $to . ' 23:59:59');
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Total revenue for a shop (non-cancelled orders), optionally bounded
     * by an inclusive "from" and exclusive "to" placed_at window.
     */
    public function getRevenueForShop(int $shopId, ?string $from = null, ?string $to = null): float
    {
        $builder = $this->db->table('orders')
            ->selectSum('total_amount', 'rev')
            ->where('shop_id', $shopId)
            ->groupStart()
                ->whereIn('orders.status', ['completed', 'paid', 'delivered', 'COMPLETED', 'PAID', 'DELIVERED'])
                ->orWhere('orders.payment_status', 'paid')
                ->orWhere('orders.pos_payment_status', 'paid')
            ->groupEnd()
            ->whereNotIn('orders.status', ['cancelled']);

        if ($from !== null) {
            $builder->where('COALESCE(orders.completed_at, orders.placed_at) >=', $from);
        }
        if ($to !== null) {
            $builder->where('COALESCE(orders.completed_at, orders.placed_at) <', $to);
        }

        $orderRev = (float) ($builder->get()->getRow()->rev ?? 0.0);

        // Include completed and active printing requests
        $prBuilder = $this->db->table('printing_requests')
            ->selectSum('total_price', 'rev')
            ->where('shop_id', $shopId)
            ->whereIn('printing_requests.status', ['completed', 'paid', 'released', 'COMPLETED', 'PAID', 'RELEASED'])
            ->whereNotIn('printing_requests.status', ['cancelled']);

        if ($from !== null) {
            $prBuilder->where('COALESCE(printing_requests.completed_at, printing_requests.created_at) >=', $from);
        }
        if ($to !== null) {
            $prBuilder->where('COALESCE(printing_requests.completed_at, printing_requests.created_at) <', $to);
        }

        $prRev = (float) ($prBuilder->get()->getRow()->rev ?? 0.0);

        return $orderRev + $prRev;
    }

    /**
     * Count orders for a shop, optionally filtered by statuses and a
     * placed_at window.
     */
    public function getOrderCountForShop(int $shopId, array $statuses = [], ?string $from = null, ?string $to = null): int
    {
        $builder = $this->db->table('orders')->where('shop_id', $shopId);

        if ($statuses !== []) {
            $builder->whereIn('status', $statuses);
        }
        if ($from !== null) {
            $builder->where('placed_at >=', $from);
        }
        if ($to !== null) {
            $builder->where('placed_at <', $to);
        }

        return (int) $builder->countAllResults();
    }

    /**
     * Current calendar date in the application's timezone (Asia/Manila).
     */
    public function getLocalToday(): string
    {
        return date('Y-m-d');
    }

    /**
     * Daily/monthly revenue aggregates for the Sales Overview chart.
     * Combines completed product orders and completed printing requests.
     * Range: '7' (last 7 calendar days), '30' (last 30), 'year' (current year by month).
     * When $withPrevious is true, also returns 'previous_values' and
     * 'previous_total' for the immediately preceding equal-length window.
     */
    public function getSalesChartData(int $shopId, string $range = '7', bool $withPrevious = false): array
    {
        $today = $this->getLocalToday();
        if (empty($today)) {
            $today = date('Y-m-d');
        }

        $normalizedRange = match (strtolower(trim($range))) {
            'year', '1y', '12m', 'this_year' => 'year',
            '30', '30d', '30_days', 'month'  => '30',
            default                          => '7',
        };

        if ($normalizedRange === 'year') {
            $curYear = (int) date('Y', strtotime($today));
            $prevYear = $curYear - 1;

            $rows = $this->db->table('orders')
                ->select('MONTH(COALESCE(orders.completed_at, orders.placed_at)) AS m, SUM(total_amount) AS rev')
                ->where('shop_id', $shopId)
                ->groupStart()
                    ->whereIn('orders.status', ['completed', 'paid', 'delivered', 'COMPLETED', 'PAID', 'DELIVERED'])
                    ->orWhere('orders.payment_status', 'paid')
                    ->orWhere('orders.pos_payment_status', 'paid')
                ->groupEnd()
                ->whereNotIn('orders.status', ['cancelled'])
                ->where('YEAR(COALESCE(orders.completed_at, orders.placed_at))', $curYear)
                ->groupBy('m')
                ->get()->getResultArray();

            $prRows = $this->db->table('printing_requests')
                ->select('MONTH(COALESCE(printing_requests.completed_at, printing_requests.created_at)) AS m, SUM(total_price) AS rev')
                ->where('shop_id', $shopId)
                ->whereIn('printing_requests.status', ['completed', 'paid', 'released', 'COMPLETED', 'PAID', 'RELEASED'])
                ->whereNotIn('printing_requests.status', ['cancelled'])
                ->where('YEAR(COALESCE(printing_requests.completed_at, printing_requests.created_at))', $curYear)
                ->groupBy('m')
                ->get()->getResultArray();

            $prevRows = [];
            $prevPrRows = [];
            if ($withPrevious) {
                $prevRows = $this->db->table('orders')
                    ->select('MONTH(COALESCE(orders.completed_at, orders.placed_at)) AS m, SUM(total_amount) AS rev')
                    ->where('shop_id', $shopId)
                    ->groupStart()
                        ->whereIn('orders.status', ['completed', 'paid', 'delivered', 'COMPLETED', 'PAID', 'DELIVERED'])
                        ->orWhere('orders.payment_status', 'paid')
                        ->orWhere('orders.pos_payment_status', 'paid')
                    ->groupEnd()
                    ->whereNotIn('orders.status', ['cancelled'])
                    ->where('YEAR(COALESCE(orders.completed_at, orders.placed_at))', $prevYear)
                    ->groupBy('m')
                    ->get()->getResultArray();

                $prevPrRows = $this->db->table('printing_requests')
                    ->select('MONTH(COALESCE(printing_requests.completed_at, printing_requests.created_at)) AS m, SUM(total_price) AS rev')
                    ->where('shop_id', $shopId)
                    ->whereIn('printing_requests.status', ['completed', 'paid', 'released', 'COMPLETED', 'PAID', 'RELEASED'])
                    ->whereNotIn('printing_requests.status', ['cancelled'])
                    ->where('YEAR(COALESCE(printing_requests.completed_at, printing_requests.created_at))', $prevYear)
                    ->groupBy('m')
                    ->get()->getResultArray();
            }

            $byMonth = [];
            foreach ($rows as $r) {
                $byMonth[(int) $r['m']] = (float) $r['rev'];
            }
            foreach ($prRows as $r) {
                $byMonth[(int) $r['m']] = ($byMonth[(int) $r['m']] ?? 0.0) + (float) $r['rev'];
            }

            $byPrevMonth = [];
            foreach ($prevRows as $r) {
                $byPrevMonth[(int) $r['m']] = (float) $r['rev'];
            }
            foreach ($prevPrRows as $r) {
                $byPrevMonth[(int) $r['m']] = ($byPrevMonth[(int) $r['m']] ?? 0.0) + (float) $r['rev'];
            }

            $labels   = [];
            $values   = [];
            $previous = [];
            for ($m = 1; $m <= 12; $m++) {
                $labels[]   = date('M', mktime(0, 0, 0, $m, 1));
                $values[]   = round((float) ($byMonth[$m] ?? 0.0), 2);
                $previous[] = round((float) ($byPrevMonth[$m] ?? 0.0), 2);
            }

            $out = ['labels' => $labels, 'values' => $values];
            if ($withPrevious) {
                $out['previous_values'] = $previous;
                $out['previous_total']  = round(array_sum($previous), 2);
            }

            return $out;
        }

        $days               = $normalizedRange === '30' ? 30 : 7;
        $start              = date('Y-m-d', strtotime($today . ' -' . ($days - 1) . ' days'));
        $startInclusive     = $start . ' 00:00:00';
        $endExclusive       = date('Y-m-d 00:00:00', strtotime($today . ' +1 day'));
        $prevStart          = date('Y-m-d', strtotime($start . ' -' . $days . ' days'));
        $prevStartInclusive = $prevStart . ' 00:00:00';

        $rows = $this->db->table('orders')
            ->select('DATE(COALESCE(orders.completed_at, orders.placed_at)) AS d, SUM(total_amount) AS rev')
            ->where('shop_id', $shopId)
            ->groupStart()
                ->whereIn('orders.status', ['completed', 'paid', 'delivered', 'COMPLETED', 'PAID', 'DELIVERED'])
                ->orWhere('orders.payment_status', 'paid')
                ->orWhere('orders.pos_payment_status', 'paid')
            ->groupEnd()
            ->whereNotIn('orders.status', ['cancelled'])
            ->where('COALESCE(orders.completed_at, orders.placed_at) >=', $startInclusive)
            ->where('COALESCE(orders.completed_at, orders.placed_at) <', $endExclusive)
            ->groupBy('d')
            ->get()->getResultArray();

        $prRows = $this->db->table('printing_requests')
            ->select('DATE(COALESCE(printing_requests.completed_at, printing_requests.created_at)) AS d, SUM(total_price) AS rev')
            ->where('shop_id', $shopId)
            ->whereIn('printing_requests.status', ['completed', 'paid', 'released', 'COMPLETED', 'PAID', 'RELEASED'])
            ->whereNotIn('printing_requests.status', ['cancelled'])
            ->where('COALESCE(printing_requests.completed_at, printing_requests.created_at) >=', $startInclusive)
            ->where('COALESCE(printing_requests.completed_at, printing_requests.created_at) <', $endExclusive)
            ->groupBy('d')
            ->get()->getResultArray();

        $prevRows = [];
        $prevPrRows = [];
        if ($withPrevious) {
            $prevRows = $this->db->table('orders')
                ->select('DATE(COALESCE(orders.completed_at, orders.placed_at)) AS d, SUM(total_amount) AS rev')
                ->where('shop_id', $shopId)
                ->groupStart()
                    ->whereIn('orders.status', ['completed', 'paid', 'delivered', 'COMPLETED', 'PAID', 'DELIVERED'])
                    ->orWhere('orders.payment_status', 'paid')
                    ->orWhere('orders.pos_payment_status', 'paid')
                ->groupEnd()
                ->whereNotIn('orders.status', ['cancelled'])
                ->where('COALESCE(orders.completed_at, orders.placed_at) >=', $prevStartInclusive)
                ->where('COALESCE(orders.completed_at, orders.placed_at) <', $startInclusive)
                ->groupBy('d')
                ->get()->getResultArray();

            $prevPrRows = $this->db->table('printing_requests')
                ->select('DATE(COALESCE(printing_requests.completed_at, printing_requests.created_at)) AS d, SUM(total_price) AS rev')
                ->where('shop_id', $shopId)
                ->whereIn('printing_requests.status', ['completed', 'paid', 'released', 'COMPLETED', 'PAID', 'RELEASED'])
                ->whereNotIn('printing_requests.status', ['cancelled'])
                ->where('COALESCE(printing_requests.completed_at, printing_requests.created_at) >=', $prevStartInclusive)
                ->where('COALESCE(printing_requests.completed_at, printing_requests.created_at) <', $startInclusive)
                ->groupBy('d')
                ->get()->getResultArray();
        }

        $byDay = [];
        foreach ($rows as $r) {
            $byDay[$r['d']] = (float) $r['rev'];
        }
        foreach ($prRows as $r) {
            $byDay[$r['d']] = ($byDay[$r['d']] ?? 0.0) + (float) $r['rev'];
        }

        $byPrevDay = [];
        foreach ($prevRows as $r) {
            $byPrevDay[$r['d']] = (float) $r['rev'];
        }
        foreach ($prevPrRows as $r) {
            $byPrevDay[$r['d']] = ($byPrevDay[$r['d']] ?? 0.0) + (float) $r['rev'];
        }

        $labels   = [];
        $values   = [];
        $previous = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d          = date('Y-m-d', strtotime($today . ' -' . $i . ' days'));
            $pd         = date('Y-m-d', strtotime($today . ' -' . ($i + $days) . ' days'));
            $labels[]   = date('M d', strtotime($d));
            $values[]   = round((float) ($byDay[$d] ?? 0.0), 2);
            $previous[] = round((float) ($byPrevDay[$pd] ?? 0.0), 2);
        }

        $out = ['labels' => $labels, 'values' => $values];
        if ($withPrevious) {
            $out['previous_values'] = $previous;
            $out['previous_total']  = round(array_sum($previous), 2);
        }

        return $out;
    }

    /**
     * Check if a user has a verified purchase of a specific product from a shop.
     * Verified = order status is 'delivered' or 'completed'.
     */
    public function isVerifiedBuyer(int $userId, int $productId, int $shopId): bool
    {
        return (bool) $this->db->table('orders o')
            ->select('o.id')
            ->join('order_items oi', 'oi.order_id = o.id', 'inner')
            ->where('o.customer_id', $userId)
            ->where('oi.product_id', $productId)
            ->where('o.shop_id', $shopId)
            ->whereIn('o.status', ['delivered', 'completed'])
            ->countAllResults() > 0;
    }

    /**
     * Check if a user has any verified purchase from a shop.
     */
    public function hasVerifiedShopPurchase(int $userId, int $shopId): bool
    {
        return (bool) $this->where('customer_id', $userId)
            ->where('shop_id', $shopId)
            ->whereIn('status', ['delivered', 'completed'])
            ->countAllResults() > 0;
    }
}
