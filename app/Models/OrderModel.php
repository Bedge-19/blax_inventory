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
            ->select('o.*, s.shop_name, s.logo_url as shop_logo')
            ->join('shops s', 's.id = o.shop_id', 'left')
            ->where('o.customer_id', $customerId)
            ->orderBy('o.placed_at', 'DESC')
            ->get()->getResultArray();
    }

    public function getOrdersByShop(int $shopId)
    {
        return $this->db->table('orders o')
            ->select('o.*, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = o.customer_id', 'left')
            ->where('o.shop_id', $shopId)
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
        ?string $dateTo = null
    ) {
        // NOTE: the primary table must be referenced by its real name
        // (orders.*) because Model::paginate() regenerates a count query
        // that strips table aliases.
        $builder = $this->builder()
            ->select('orders.*, u.first_name, u.last_name, u.email, u.profile_image_url, COALESCE(sa.phone, u.phone) as customer_phone, sa.label as address_label, sa.address_line1, sa.city, sa.province')
            ->join('users u', 'u.id = orders.customer_id', 'left')
            ->join('shipping_addresses sa', 'sa.id = orders.shipping_address_id', 'left')
            ->where('orders.shop_id', $shopId);

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
        string $group = 'orders'
    ): array {
        $this->buildOrderQuery($shopId, $search, $status, $dateFrom, $dateTo)
            ->orderBy('orders.placed_at', 'DESC');

        $orders = $this->paginate($perPage, $group, $page);

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
            ->orderBy('orders.placed_at', 'DESC')
            ->get()->getResultArray();
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

        return [
            'total_orders'     => $totalOrders,
            'pending_shipments' => $pendingShip,
            'ready_for_pickup' => $readyPickup,
            'revenue_today'    => $this->getRevenueForShop($shopId, $today . ' 00:00:00'),
        ];
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
            ->whereIn('status', ['delivered', 'completed']);

        if ($from !== null) {
            $builder->where('placed_at >=', $from);
        }
        if ($to !== null) {
            $builder->where('placed_at <', $to);
        }

        $orderRev = (float) ($builder->get()->getRow()->rev ?? 0.0);

        // Include completed printing requests
        $prBuilder = $this->db->table('printing_requests')
            ->selectSum('total_price', 'rev')
            ->where('shop_id', $shopId)
            ->where('status', 'completed');

        if ($from !== null) {
            $prBuilder->where('created_at >=', $from);
        }
        if ($to !== null) {
            $prBuilder->where('created_at <', $to);
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
     * Current calendar date in the database server's local timezone, so
     * date-window boundaries always align with the stored timestamps.
     */
    public function getLocalToday(): string
    {
        return (string) $this->db->query('SELECT CURDATE() AS d')->getRow()->d;
    }

    /**
     * Daily/monthly revenue aggregates for the Sales Overview chart.
     * Counts only delivered/completed orders and completed print requests.
     * Range: '7' (last 7 calendar days), '30' (last 30), 'year' (current year by month).
     * When $withPrevious is true, also returns 'previous_values' and
     * 'previous_total' for the immediately preceding equal-length window.
     */
    public function getSalesChartData(int $shopId, string $range = '7', bool $withPrevious = false): array
    {
        $today = $this->getLocalToday();

        if ($range === 'year') {
            $rows = $this->db->table('orders')
                ->select('MONTH(placed_at) AS m, SUM(total_amount) AS rev')
                ->where('shop_id', $shopId)
                ->whereIn('status', ['delivered', 'completed'])
                ->where('YEAR(placed_at) = YEAR(CURDATE())', null, false)
                ->groupBy('m')
                ->get()->getResultArray();

            $prRows = $this->db->table('printing_requests')
                ->select('MONTH(created_at) AS m, SUM(total_price) AS rev')
                ->where('shop_id', $shopId)
                ->where('status', 'completed')
                ->where('YEAR(created_at) = YEAR(CURDATE())', null, false)
                ->groupBy('m')
                ->get()->getResultArray();

            $prevRows = [];
            $prevPrRows = [];
            if ($withPrevious) {
                $prevRows = $this->db->table('orders')
                    ->select('MONTH(placed_at) AS m, SUM(total_amount) AS rev')
                    ->where('shop_id', $shopId)
                    ->whereIn('status', ['delivered', 'completed'])
                    ->where('YEAR(placed_at) = YEAR(CURDATE()) - 1', null, false)
                    ->groupBy('m')
                    ->get()->getResultArray();

                $prevPrRows = $this->db->table('printing_requests')
                    ->select('MONTH(created_at) AS m, SUM(total_price) AS rev')
                    ->where('shop_id', $shopId)
                    ->where('status', 'completed')
                    ->where('YEAR(created_at) = YEAR(CURDATE()) - 1', null, false)
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
                $values[]   = $byMonth[$m] ?? 0.0;
                $previous[] = $byPrevMonth[$m] ?? 0.0;
            }

            $out = ['labels' => $labels, 'values' => $values];
            if ($withPrevious) {
                $out['previous_values'] = $previous;
                $out['previous_total']  = array_sum($previous);
            }

            return $out;
        }

        $days      = $range === '30' ? 30 : 7;
        $start     = date('Y-m-d', strtotime($today . ' -' . ($days - 1) . ' days'));
        $prevStart = date('Y-m-d', strtotime($start . ' -' . $days . ' days'));

        $rows = $this->db->table('orders')
            ->select('DATE(placed_at) AS d, SUM(total_amount) AS rev')
            ->where('shop_id', $shopId)
            ->whereIn('status', ['delivered', 'completed'])
            ->where('placed_at >=', $start . ' 00:00:00')
            ->groupBy('d')
            ->get()->getResultArray();

        $prRows = $this->db->table('printing_requests')
            ->select('DATE(created_at) AS d, SUM(total_price) AS rev')
            ->where('shop_id', $shopId)
            ->where('status', 'completed')
            ->where('created_at >=', $start . ' 00:00:00')
            ->groupBy('d')
            ->get()->getResultArray();

        $prevRows = [];
        $prevPrRows = [];
        if ($withPrevious) {
            $prevRows = $this->db->table('orders')
                ->select('DATE(placed_at) AS d, SUM(total_amount) AS rev')
                ->where('shop_id', $shopId)
                ->whereIn('status', ['delivered', 'completed'])
                ->where('placed_at >=', $prevStart . ' 00:00:00')
                ->where('placed_at <', $start . ' 00:00:00')
                ->groupBy('d')
                ->get()->getResultArray();

            $prevPrRows = $this->db->table('printing_requests')
                ->select('DATE(created_at) AS d, SUM(total_price) AS rev')
                ->where('shop_id', $shopId)
                ->where('status', 'completed')
                ->where('created_at >=', $prevStart . ' 00:00:00')
                ->where('created_at <', $start . ' 00:00:00')
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
            $d        = date('Y-m-d', strtotime($today . ' -' . $i . ' days'));
            $pd       = date('Y-m-d', strtotime($today . ' -' . ($i + $days) . ' days'));
            $labels[] = date('M d', strtotime($d));
            $values[] = $byDay[$d] ?? 0.0;
            $previous[] = $byPrevDay[$pd] ?? 0.0;
        }

        $out = ['labels' => $labels, 'values' => $values];
        if ($withPrevious) {
            $out['previous_values'] = $previous;
            $out['previous_total']  = array_sum($previous);
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
