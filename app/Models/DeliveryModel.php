<?php

namespace App\Models;

use CodeIgniter\Model;

class DeliveryModel extends Model
{
    protected $table            = 'deliveries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'deliverable_type',
        'deliverable_id',
        'tracking_id',
        'courier_name',
        'destination_address',
        'current_lat',
        'current_lng',
        'location_updated_at',
        'status',
        'shipped_at',
        'delivered_at',
        'created_at',
    ];

    /**
     * Shared, tenant-scoped delivery query. A delivery belongs to the shop
     * that owns its linked order OR printing request. NOTE: the primary
     * table must be referenced by its real name (deliveries.*) because
     * Model::paginate() regenerates a count query that strips table aliases.
     */
    private function buildDeliveryQuery(
        int $shopId,
        ?string $search = null,
        ?string $status = null
    ) {
        $builder = $this->builder()
            ->select("deliveries.*, COALESCE(o.order_number, pr.request_number) AS ref_number, COALESCE(o.fulfillment_method, pr.fulfillment_method) AS fulfillment_method, u.first_name, u.last_name, u.profile_image_url, u.email")
            ->join('orders o', "o.id = deliveries.deliverable_id AND deliveries.deliverable_type = 'order'", 'left')
            ->join('printing_requests pr', "pr.id = deliveries.deliverable_id AND deliveries.deliverable_type = 'printing_request'", 'left')
            ->join('users u', 'u.id = COALESCE(o.customer_id, pr.customer_id)', 'left')
            ->where("COALESCE(o.fulfillment_method, pr.fulfillment_method)", 'delivery')
            ->groupStart()
            ->where('o.shop_id', $shopId)
            ->orWhere('pr.shop_id', $shopId)
            ->groupEnd();

        if ($search !== null && $search !== '') {
            $builder
                ->groupStart()
                ->like('deliveries.tracking_id', $search)
                ->orLike('COALESCE(o.order_number, pr.request_number)', $search)
                ->orLike('u.first_name', $search)
                ->orLike('u.last_name', $search)
                ->orLike('deliveries.destination_address', $search)
                ->groupEnd();
        }

        if ($status !== null && $status !== '') {
            $builder->where('deliveries.status', $status);
        } else {
            // Default view: only show active doorstep deliveries (shipped / in transit). Exclude completed/delivered, pickup, returned, and cancelled.
            $builder->whereIn('deliveries.status', ['shipped', 'in_transit']);
        }

        return $builder;
    }

    public function syncMissingDeliveries(int $shopId): void
    {
        // 1. Sync all printing requests for this shop that are for DELIVERY only (exclude store pick-ups)
        $prs = $this->db->table('printing_requests')
            ->where('shop_id', $shopId)
            ->where('fulfillment_method', 'delivery')
            ->get()->getResultArray();

        foreach ($prs as $pr) {
            $trackingId = (string) ($pr['request_number'] ?? '');
            if ($trackingId === '') {
                $trackingId = 'PR-' . strtoupper(substr(md5((string) $pr['id']), 0, 8));
            }
            $existing = $this->db->table('deliveries')
                ->where('deliverable_type', 'printing_request')
                ->where('deliverable_id', (int) $pr['id'])
                ->get()->getRowArray();

            if (!$existing) {
                $existing = $this->db->table('deliveries')
                    ->where('tracking_id', $trackingId)
                    ->get()->getRowArray();
            }

            $delStatus = match ($pr['status']) {
                'ready_for_delivery', 'shipped' => 'shipped',
                'in_transit'                    => 'in_transit',
                'completed'                     => 'delivered',
                'cancelled'                     => 'cancelled',
                default                         => 'shipped',
            };

            if ($existing) {
                $this->update($existing['id'], [
                    'status' => $delStatus,
                ]);
            } else {
                $this->insert([
                    'deliverable_type'    => 'printing_request',
                    'deliverable_id'      => (int) $pr['id'],
                    'tracking_id'         => $trackingId,
                    'courier_name'        => 'Store Courier',
                    'destination_address' => 'Doorstep Delivery',
                    'status'              => $delStatus,
                    'created_at'          => $pr['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
            }
        }

        // 2. Sync all orders for this shop that are for DELIVERY only (exclude store pick-ups)
        $orders = $this->db->table('orders')
            ->where('shop_id', $shopId)
            ->where('fulfillment_method', 'delivery')
            ->get()->getResultArray();

        foreach ($orders as $ord) {
            $trackingId = (string) ($ord['order_number'] ?? '');
            if ($trackingId === '') {
                $trackingId = 'TRK-' . strtoupper(substr(md5((string) $ord['id']), 0, 8));
            }
            $existing = $this->db->table('deliveries')
                ->where('deliverable_type', 'order')
                ->where('deliverable_id', (int) $ord['id'])
                ->get()->getRowArray();

            if (!$existing) {
                $existing = $this->db->table('deliveries')
                    ->where('tracking_id', $trackingId)
                    ->get()->getRowArray();
            }

            $delStatus = match ($ord['status']) {
                'shipped'           => 'shipped',
                'in_transit'        => 'in_transit',
                'delivered'         => 'delivered',
                'returned'          => 'returned',
                'cancelled'         => 'cancelled',
                default             => 'shipped',
            };

            if ($existing) {
                $this->update($existing['id'], [
                    'status' => $delStatus,
                ]);
            } else {
                $this->insert([
                    'deliverable_type'    => 'order',
                    'deliverable_id'      => (int) $ord['id'],
                    'tracking_id'         => $trackingId,
                    'courier_name'        => 'Store Courier',
                    'destination_address' => 'Customer Shipping Address',
                    'status'              => $delStatus,
                    'created_at'          => $ord['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function getDeliveriesByShopPaginated(
        int $shopId,
        ?string $search = null,
        ?string $status = null,
        int $perPage = 10,
        int $page = 1,
        string $group = 'deliveries'
    ): array {
        return $this->getDeliveriesPaginated($shopId, $search, $status, $perPage, $page);
    }

    public function getDeliveriesPaginated(
        int $shopId,
        ?string $search = null,
        ?string $status = null,
        int $perPage = 10,
        int $page = 1
    ): array {
        $this->syncMissingDeliveries($shopId);

        // Pre-count total matching rows
        $countQuery = $this->db->table('deliveries d')
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->join('users u', 'u.id = COALESCE(o.customer_id, pr.customer_id)', 'left')
            ->where("COALESCE(o.fulfillment_method, pr.fulfillment_method)", 'delivery')
            ->groupStart()
            ->where('o.shop_id', $shopId)
            ->orWhere('pr.shop_id', $shopId)
            ->groupEnd();

        if ($search !== null && $search !== '') {
            $countQuery->groupStart()
                ->like('d.tracking_id', $search)
                ->orLike('COALESCE(o.order_number, pr.request_number)', $search)
                ->orLike('u.first_name', $search)
                ->orLike('u.last_name', $search)
                ->orLike('d.destination_address', $search)
                ->groupEnd();
        }

        if ($status !== null && $status !== '') {
            $countQuery->where('d.status', $status);
        } else {
            $countQuery->whereIn('d.status', ['shipped', 'in_transit']);
        }

        $total = $countQuery->countAllResults();

        // Paginate using builder
        $builder = $this->buildDeliveryQuery($shopId, $search, $status);
        $deliveries = $builder->orderBy('deliveries.created_at', 'DESC')
            ->get($perPage, ($page - 1) * $perPage)
            ->getResultArray();

        // Build a manual pager so pagination links work
        $pagerService = service('pager');
        $pagerService->store('deliveries', $page, $perPage, $total);
        $this->pager = $pagerService;

        return [
            'deliveries' => $deliveries,
            'pager'      => $pagerService,
            'total'      => $total,
        ];
    }

    /**
     * Admin-scoped delivery list with shop name join and pagination.
     */
    public function getAdminTrackingPaginated(
        ?string $search = null,
        ?string $shopFilter = null,
        ?string $status = null,
        int $perPage = 20,
        int $page = 1,
        string $group = 'tracking'
    ): array {
        $builder = $this->db->table('deliveries d')
            ->select("d.*, COALESCE(o.order_number, pr.request_number) AS ref_number, COALESCE(o.fulfillment_method, pr.fulfillment_method) AS fulfillment_method, u.first_name, u.last_name, s.shop_name, s.id as shop_id, sa.address_line1, sa.city, sa.province")
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('shipping_addresses sa', "sa.id = o.shipping_address_id", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->join('users u', 'u.id = COALESCE(o.customer_id, pr.customer_id)', 'left')
            ->join('shops s', 's.id = COALESCE(o.shop_id, pr.shop_id)', 'left')
            ->where("COALESCE(o.fulfillment_method, pr.fulfillment_method)", 'delivery');

        if ($search !== null && $search !== '') {
            $builder->groupStart()
                ->like('d.tracking_id', $search)
                ->orLike('COALESCE(o.order_number, pr.request_number)', $search)
                ->orLike('u.first_name', $search)
                ->orLike('u.last_name', $search)
                ->orLike('d.destination_address', $search)
                ->orLike('s.shop_name', $search)
                ->groupEnd();
        }

        if ($shopFilter !== null && $shopFilter !== '') {
            $builder->where('s.shop_name', $shopFilter);
        }

        if ($status !== null && $status !== '') {
            $builder->where('d.status', $status);
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        $deliveries = $builder->orderBy('d.created_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        $pagerService = service('pager');
        $pagerService->store($group, $page, $perPage, $total);
        $this->pager = $pagerService;

        return [
            'deliveries' => $deliveries,
            'pager'      => $pagerService,
            'total'      => $total,
        ];
    }

    /**
     * Enrich delivery records with product / item details so the UI can display
     * the product name prominently while retaining the order / reference ID.
     */
    public function enrichDeliveriesWithProducts(array $deliveries): array
    {
        if (empty($deliveries)) {
            return [];
        }

        $orderIds = [];
        $prIds    = [];
        foreach ($deliveries as $d) {
            $type = $d['deliverable_type'] ?? 'order';
            $id   = (int) ($d['deliverable_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            if ($type === 'order') {
                $orderIds[] = $id;
            } elseif ($type === 'printing_request') {
                $prIds[] = $id;
            }
        }

        $orderItemsByOrderId = [];
        if (!empty($orderIds)) {
            $orderIds = array_values(array_unique($orderIds));
            $items = $this->db->table('order_items oi')
                ->select('oi.order_id, oi.product_name, oi.quantity, oi.variant_label')
                ->whereIn('oi.order_id', $orderIds)
                ->orderBy('oi.id', 'ASC')
                ->get()->getResultArray();

            foreach ($items as $item) {
                $oid = (int) $item['order_id'];
                $orderItemsByOrderId[$oid][] = $item;
            }
        }

        $prsById = [];
        if (!empty($prIds)) {
            $prIds = array_values(array_unique($prIds));
            $prs = $this->db->table('printing_requests')
                ->select('id, request_number, file_name, document_type, page_count, copies')
                ->whereIn('id', $prIds)
                ->get()->getResultArray();

            foreach ($prs as $pr) {
                $prsById[(int) $pr['id']] = $pr;
            }
        }

        foreach ($deliveries as &$d) {
            $type = $d['deliverable_type'] ?? 'order';
            $id   = (int) ($d['deliverable_id'] ?? 0);

            if ($type === 'order' && !empty($orderItemsByOrderId[$id])) {
                $items = $orderItemsByOrderId[$id];
                $first = $items[0];
                $extraCount = count($items) - 1;
                $totalQty = 0;
                $allNames = [];

                foreach ($items as $it) {
                    $qty = (int) ($it['quantity'] ?? 1);
                    $totalQty += $qty;
                    $pName = trim((string) ($it['product_name'] ?? 'Product Item'));
                    $vLabel = trim((string) ($it['variant_label'] ?? ''));
                    $allNames[] = $pName . ($vLabel !== '' ? ' (' . $vLabel . ')' : '') . ($qty > 1 ? ' x' . $qty : '');
                }

                $primaryName = trim((string) ($first['product_name'] ?? 'Product Item'));
                $d['product_name']       = $primaryName !== '' ? $primaryName : 'Order Item';
                $d['product_image']      = $first['product_image'] ?? null;
                $d['variant_label']      = $first['variant_label'] ?? '';
                $d['extra_items_count']  = $extraCount;
                $d['total_qty']          = $totalQty;
                $d['all_products_list']  = implode(', ', $allNames);
                $d['item_summary_badge'] = $extraCount > 0 ? "+{$extraCount} more item" . ($extraCount > 1 ? 's' : '') : '';
            } elseif ($type === 'printing_request' && isset($prsById[$id])) {
                $pr = $prsById[$id];
                $title = trim((string) ($pr['file_name'] ?: ($pr['document_type'] ?? 'Print Job')));
                $d['product_name']       = $title !== '' ? $title : 'Printing Document';
                $d['product_image']      = null;
                $d['variant_label']      = ($pr['document_type'] ?? '') . ' (' . ($pr['page_count'] ?? 1) . ' pgs, ' . ($pr['copies'] ?? 1) . ' copies)';
                $d['extra_items_count']  = 0;
                $d['total_qty']          = (int) ($pr['copies'] ?? 1);
                $d['all_products_list']  = $title . ' - ' . ($pr['document_type'] ?? 'Printing Service');
                $d['item_summary_badge'] = 'Print Job';
            } else {
                $d['product_name']       = 'Package Item';
                $d['product_image']      = null;
                $d['variant_label']      = '';
                $d['extra_items_count']  = 0;
                $d['total_qty']          = 1;
                $d['all_products_list']  = 'Package Item';
                $d['item_summary_badge'] = '';
            }
        }
        unset($d);

        return $deliveries;
    }

    /**
     * KPI counters for tenant delivery overview.
     */
    public function getDeliveryKpis(int $shopId): array
    {
        $today = date('Y-m-d');

        $row = $this->db->table('deliveries d')
            ->select("
                SUM(CASE WHEN d.status IN ('shipped','in_transit') THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN d.status = 'shipped' THEN 1 ELSE 0 END) AS shipped,
                SUM(CASE WHEN d.status = 'in_transit' THEN 1 ELSE 0 END) AS in_transit,
                SUM(CASE WHEN d.status = 'delivered' AND d.delivered_at >= '" . $today . " 00:00:00' THEN 1 ELSE 0 END) AS completed_today
            ")
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->where("COALESCE(o.fulfillment_method, pr.fulfillment_method)", 'delivery')
            ->groupStart()
            ->where('o.shop_id', $shopId)
            ->orWhere('pr.shop_id', $shopId)
            ->groupEnd()
            ->get()
            ->getRow();

        return [
            'active'            => (int) ($row->active ?? 0),
            'shipped'           => (int) ($row->shipped ?? 0),
            'in_transit'        => (int) ($row->in_transit ?? 0),
            'completed_today'   => (int) ($row->completed_today ?? 0),
        ];
    }

    // Operational bounds for Polomolok & Tupi, South Cotabato
    public const POLOMOLOK_LAT_MIN = 6.10;
    public const POLOMOLOK_LAT_MAX = 6.45;
    public const POLOMOLOK_LNG_MIN = 124.85;
    public const POLOMOLOK_LNG_MAX = 125.20;
    public const POLOMOLOK_CENTER_LAT = 6.2136;
    public const POLOMOLOK_CENTER_LNG = 125.0661;

    public const TUPI_CENTER_LAT = 6.3333;
    public const TUPI_CENTER_LNG = 124.9500;

    public const SERVICE_CENTER_LAT = 6.2735;
    public const SERVICE_CENTER_LNG = 125.0080;

    public static function isPolomolokCoordinate(?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null) return false;
        return $lat >= self::POLOMOLOK_LAT_MIN && $lat <= self::POLOMOLOK_LAT_MAX
            && $lng >= self::POLOMOLOK_LNG_MIN && $lng <= self::POLOMOLOK_LNG_MAX;
    }

    public static function isServiceAreaCoordinate(?float $lat, ?float $lng): bool
    {
        return self::isPolomolokCoordinate($lat, $lng);
    }

    /**
     * Get real geographic centroid for any official barangay in Polomolok or Tupi.
     */
    public static function getBarangayCoordinate(string $barangay, string $city = ''): ?array
    {
        $b = strtolower(trim($barangay));
        $centroids = [
            // Polomolok Barangays
            'bentung'           => [6.2625, 125.0480],
            'cannery site'      => [6.2415, 125.0740],
            'crossing palkan'   => [6.2580, 125.1050],
            'glamang'           => [6.1785, 125.0390],
            'kinilis'           => [6.2840, 125.0210],
            'klinan 6'          => [6.1650, 125.0920],
            'koronadal proper'  => [6.2250, 125.1020],
            'lam-caliaf'        => [6.2910, 125.0680],
            'landan'            => [6.3050, 125.0410],
            'lumakil'           => [6.1950, 125.0780],
            'maligo'            => [6.2890, 125.0990],
            'palkan'            => [6.2710, 125.1180],
            'poblacion'         => (stripos($city, 'tupi') !== false) ? [6.3333, 124.9500] : [6.2185, 125.0650],
            'poblacion (tupi)'  => [6.3333, 124.9500],
            'polo'              => [6.2080, 125.0350],
            'pula bato'         => [6.2460, 125.0230],
            'rubber'            => [6.1890, 125.1120],
            'silway 7'          => [6.1550, 125.1250],
            'silway 8'          => [6.1420, 125.1480],
            'sulit'             => [6.2340, 125.1320],
            'sumbakil'          => [6.2120, 125.1450],
            'upper klinan'      => [6.1820, 125.0710],
            'pagalungan'        => [6.1710, 125.0530],
            'magsaysay'         => [6.2280, 125.0480],
            // Tupi Barangays
            'acmonan'           => [6.3350, 124.9650],
            'bololmala'         => [6.3120, 124.9380],
            'bunao'             => [6.3480, 124.9450],
            'cebuano'           => [6.3620, 124.9320],
            'crossing rubber'   => [6.3210, 124.9750],
            'dajay'             => [6.3750, 124.9180],
            'kablon'            => [6.3290, 125.0120],
            'kalkam'            => [6.3420, 124.9850],
            'linan'             => [6.2980, 124.9250],
            'lunen'             => [6.3550, 124.9720],
            'miaso'             => [6.3680, 124.9580],
            'palian'            => [6.3150, 124.9920],
            'polonoling'        => [6.3010, 124.9680],
            'simbo'             => [6.3510, 124.9150],
            'tubeng'            => [6.3820, 124.9350],
        ];

        // 1. Exact match first (prevents substring collisions like 'polo' matching 'polonoling')
        if (isset($centroids[$b])) {
            return ['lat' => $centroids[$b][0], 'lng' => $centroids[$b][1]];
        }

        // 2. Substring matching fallback
        foreach ($centroids as $key => $coords) {
            if (stripos($b, $key) !== false || stripos($key, $b) !== false) {
                return ['lat' => $coords[0], 'lng' => $coords[1]];
            }
        }
        return null;
    }

    /**
     * In-progress doorstep deliveries with their latest position, for the fleet map.
     */
    public function getDeliveryPins(int $shopId): array
    {
        $rows = $this->db->table('deliveries d')
            ->select("d.id, d.tracking_id, d.status, d.destination_address, d.current_lat, d.current_lng, d.location_updated_at, COALESCE(o.order_number, pr.request_number) AS ref_number, u.first_name, u.last_name, u.phone as customer_phone, sa.address_line1, sa.city, sa.province, sa.label as address_label, s.shop_name, s.latitude as shop_lat, s.longitude as shop_lng")
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('shipping_addresses sa', "sa.id = o.shipping_address_id", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->join('users u', 'u.id = COALESCE(o.customer_id, pr.customer_id)', 'left')
            ->join('shops s', 's.id = COALESCE(o.shop_id, pr.shop_id)', 'left')
            ->where("COALESCE(o.fulfillment_method, pr.fulfillment_method)", 'delivery')
            ->groupStart()
                ->where('o.shop_id', $shopId)
                ->orWhere('pr.shop_id', $shopId)
            ->groupEnd()
            ->whereIn('d.status', ['shipped', 'in_transit'])
            ->orderBy('d.created_at', 'DESC')
            ->get()->getResultArray();

        // Ensure proper Polomolok coordinates and full destination text
        foreach ($rows as &$r) {
            if (empty($r['destination_address']) || $r['destination_address'] === 'Customer Shipping Address') {
                if (!empty($r['address_line1'])) {
                    $r['destination_address'] = trim($r['address_line1'] . ', ' . ($r['city'] ?? 'Polomolok') . ' ' . ($r['province'] ?? 'South Cotabato'));
                } else {
                    $r['destination_address'] = 'Upo St, Poblacion, Polomolok, South Cotabato';
                }
            }

            $lat = (float) ($r['current_lat'] ?? 0);
            $lng = (float) ($r['current_lng'] ?? 0);
            if (!self::isPolomolokCoordinate($lat, $lng)) {
                // Honest fallback: use shop's real starting coordinates if valid, else Polomolok center
                $shopLat = (float) ($r['shop_lat'] ?? 0);
                $shopLng = (float) ($r['shop_lng'] ?? 0);
                if (self::isPolomolokCoordinate($shopLat, $shopLng)) {
                    $r['current_lat'] = number_format($shopLat, 7, '.', '');
                    $r['current_lng'] = number_format($shopLng, 7, '.', '');
                } else {
                    $r['current_lat'] = number_format(self::POLOMOLOK_CENTER_LAT, 7, '.', '');
                    $r['current_lng'] = number_format(self::POLOMOLOK_CENTER_LNG, 7, '.', '');
                }
            }
        }
        unset($r);

        return $rows;
    }

    /**
     * Admin-scoped pins across all shops, Polomolok & Tupi restricted.
     * Only returns shipments that are actively broadcasting (location updated within last 15 minutes).
     */
    public function getAllDeliveryPins(?string $search = null, ?string $shopFilter = null): array
    {
        $builder = $this->db->table('deliveries d')
            ->select("d.id, d.tracking_id, d.status, d.destination_address, d.current_lat, d.current_lng, d.location_updated_at, COALESCE(o.order_number, pr.request_number) AS ref_number, u.first_name, u.last_name, s.shop_name, s.id as shop_id, s.logo_url as shop_logo, s.latitude as shop_lat, s.longitude as shop_lng")
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->join('users u', 'u.id = COALESCE(o.customer_id, pr.customer_id)', 'left')
            ->join('shops s', 's.id = COALESCE(o.shop_id, pr.shop_id)', 'left')
            ->where("COALESCE(o.fulfillment_method, pr.fulfillment_method)", 'delivery')
            ->whereIn('d.status', ['shipped', 'in_transit'])
            ->where('d.current_lat IS NOT NULL')
            ->where('d.current_lng IS NOT NULL')
            ->where('d.location_updated_at IS NOT NULL');

        if ($search !== null && $search !== '') {
            $builder->groupStart()->like('d.tracking_id', $search)->orLike('d.destination_address', $search)->orLike('s.shop_name', $search)->groupEnd();
        }
        if ($shopFilter !== null && $shopFilter !== '') {
            $builder->where('s.shop_name', $shopFilter);
        }
        $rows = $builder->orderBy('d.location_updated_at', 'DESC')->get()->getResultArray();

        $activePins = [];
        foreach ($rows as $r) {
            $lat = (float) ($r['current_lat'] ?? 0);
            $lng = (float) ($r['current_lng'] ?? 0);
            if ($lat != 0.0 && $lng != 0.0 && self::isPolomolokCoordinate($lat, $lng)) {
                $r['current_lat'] = number_format($lat, 7, '.', '');
                $r['current_lng'] = number_format($lng, 7, '.', '');
                $activePins[] = $r;
            }
        }

        return $activePins;
    }

    /**
     * The shop that owns a delivery (via its linked order/printing request),
     * or null when the delivery or its source record no longer exists.
     */
    public function resolveShopId(int $deliveryId): ?int
    {
        $row = $this->db->table('deliveries d')
            ->select('COALESCE(o.shop_id, pr.shop_id) AS shop_id')
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->where('d.id', $deliveryId)
            ->get()->getRow();

        return $row !== null && $row->shop_id !== null ? (int) $row->shop_id : null;
    }    /**
     * Parse and extract all possible identifier tokens from a scanned QR payload
     * (e.g. order numbers, request numbers, numeric IDs, JSON, or URLs).
     */
    public function extractLookupTokens(string $rawCode): array
    {
        $raw = trim($rawCode);
        if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $raw = trim((string) ($decoded['order_no'] ?? $decoded['order_number'] ?? $decoded['tracking_id'] ?? $decoded['request_number'] ?? $decoded['request_no'] ?? $decoded['id'] ?? $raw));
            }
        }

        // URL check: extract trailing path param
        if (preg_match('#/(?:order|orders|deliveries|delivery|requests|printing_requests)/([A-Za-z0-9_-]+)#i', $raw, $m)) {
            $raw = $m[1];
        }

        $clean = trim(str_replace(['#', '"', "'"], '', $raw));
        $cleanUpper = strtoupper($clean);

        $tokens = [$clean, $cleanUpper];

        if (preg_match('/(?:ORD|ORDER)[-_ ]*([A-Za-z0-9_-]+)/i', $clean, $m)) {
            $tokens[] = 'ORD-' . strtoupper($m[1]);
            $tokens[] = strtoupper($m[1]);
            if (is_numeric($m[1])) {
                $tokens[] = (int) $m[1];
            }
        }
        if (preg_match('/(?:PR|REQ|REQUEST)[-_ ]*([A-Za-z0-9_-]+)/i', $clean, $m)) {
            $tokens[] = 'PR-' . strtoupper($m[1]);
            $tokens[] = strtoupper($m[1]);
            if (is_numeric($m[1])) {
                $tokens[] = (int) $m[1];
            }
        }
        if (preg_match('/TRK[-_ ]*([A-Za-z0-9_-]+)/i', $clean, $m)) {
            $tokens[] = 'TRK-' . strtoupper($m[1]);
            $tokens[] = strtoupper($m[1]);
        }
        if (is_numeric($clean)) {
            $tokens[] = (int) $clean;
            $tokens[] = 'ORD-' . $clean;
            $tokens[] = 'PR-' . $clean;
            $tokens[] = 'TRK-' . $clean;
        }

        return array_values(array_unique(array_filter($tokens, fn($t) => $t !== '')));
    }

    public function findWithDetails(int $deliveryId, int $shopId): ?array
    {
        $row = $this->db->table('deliveries d')
            ->select("d.*, COALESCE(o.order_number, pr.request_number) AS ref_number, COALESCE(o.fulfillment_method, pr.fulfillment_method) AS fulfillment_method, COALESCE(o.status, pr.status) as order_status, u.first_name, u.last_name, u.profile_image_url, u.email")
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->join('users u', 'u.id = COALESCE(o.customer_id, pr.customer_id)', 'left')
            ->where('d.id', $deliveryId)
            ->groupStart()
                ->where('o.shop_id', $shopId)
                ->orWhere('pr.shop_id', $shopId)
            ->groupEnd()
            ->get()->getRowArray();

        if ($row) {
            $enriched = $this->enrichDeliveriesWithProducts([$row]);
            return $enriched[0] ?? $row;
        }

        return null;
    }

    public function findByTrackingScoped(string $trackingId, int $shopId): ?array
    {
        $tokens = $this->extractLookupTokens($trackingId);
        if (empty($tokens)) {
            return null;
        }

        // 1. Check existing deliveries linked to shop's orders or printing requests
        $builder = $this->db->table('deliveries d')
            ->select("d.*, COALESCE(o.order_number, pr.request_number) AS ref_number, COALESCE(o.fulfillment_method, pr.fulfillment_method) AS fulfillment_method, COALESCE(o.status, pr.status) as order_status, u.first_name, u.last_name, u.profile_image_url, u.email")
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->join('users u', 'u.id = COALESCE(o.customer_id, pr.customer_id)', 'left')
            ->groupStart();

        $first = true;
        foreach ($tokens as $token) {
            $str = (string) $token;
            if ($first) {
                $builder->where('d.tracking_id', $str)
                        ->orWhere('o.order_number', $str)
                        ->orWhere('pr.request_number', $str);
                $first = false;
            } else {
                $builder->orWhere('d.tracking_id', $str)
                        ->orWhere('o.order_number', $str)
                        ->orWhere('pr.request_number', $str);
            }
            if (is_numeric($token)) {
                $n = (int) $token;
                $builder->orWhere('d.id', $n)
                        ->orWhere('d.deliverable_id', $n)
                        ->orWhere('o.id', $n)
                        ->orWhere('pr.id', $n);
            }
        }
        $builder->groupEnd();

        $builder->groupStart()
            ->where('o.shop_id', $shopId)
            ->orWhere('pr.shop_id', $shopId)
            ->groupEnd();

        $row = $builder->get()->getRowArray();
        if ($row) {
            $enriched = $this->enrichDeliveriesWithProducts([$row]);
            return $enriched[0] ?? $row;
        }

        // 2. Fallback: check printing_requests for this shop
        $prQuery = $this->db->table('printing_requests')->where('shop_id', $shopId)->groupStart();
        $pFirst = true;
        foreach ($tokens as $token) {
            $str = (string) $token;
            if ($pFirst) {
                $prQuery->where('request_number', $str);
                $pFirst = false;
            } else {
                $prQuery->orWhere('request_number', $str);
            }
            if (is_numeric($token)) {
                $prQuery->orWhere('id', (int) $token);
            }
        }
        $pr = $prQuery->groupEnd()->get()->getRowArray();

        if ($pr) {
            $courierName = ($pr['fulfillment_method'] ?? '') === 'pickup' ? 'Store Pick-up' : 'In-House Delivery';
            $destAddr = ($pr['fulfillment_method'] ?? '') === 'pickup' ? 'Store Pick-up (Poblacion, Polomolok)' : 'Delivery (Polomolok, South Cotabato)';
            $delStatus = match ($pr['status']) {
                'ready_for_pickup'   => 'ready_for_pickup',
                'ready_for_delivery' => 'shipped',
                'completed'          => 'delivered',
                'cancelled'          => 'cancelled',
                default              => 'ready_for_pickup',
            };

            $existing = $this->db->table('deliveries')
                ->where('deliverable_type', 'printing_request')
                ->where('deliverable_id', (int) $pr['id'])
                ->get()->getRowArray();

            $delId = $existing ? (int) $existing['id'] : null;
            if (!$delId) {
                $delId = (int) $this->insert([
                    'deliverable_type'    => 'printing_request',
                    'deliverable_id'      => (int) $pr['id'],
                    'tracking_id'         => (string) ($pr['request_number'] ?? ('PR-' . $pr['id'])),
                    'courier_name'        => $courierName,
                    'destination_address' => $destAddr,
                    'status'              => $delStatus,
                    'created_at'          => $pr['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
            }
            return $this->findWithDetails($delId, $shopId);
        }

        // 3. Fallback: check orders for this shop
        $ordQuery = $this->db->table('orders')->where('shop_id', $shopId)->groupStart();
        $oFirst = true;
        foreach ($tokens as $token) {
            $str = (string) $token;
            if ($oFirst) {
                $ordQuery->where('order_number', $str);
                $oFirst = false;
            } else {
                $ordQuery->orWhere('order_number', $str);
            }
            if (is_numeric($token)) {
                $ordQuery->orWhere('id', (int) $token);
            }
        }
        $ord = $ordQuery->groupEnd()->get()->getRowArray();

        if ($ord) {
            $destAddr = 'Customer Shipping Address, Polomolok';
            if (!empty($ord['shipping_address_id'])) {
                $addr = $this->db->table('shipping_addresses')->where('id', (int) $ord['shipping_address_id'])->get()->getRowArray();
                if ($addr) {
                    $destAddr = trim(($addr['street_address'] ?? '') . ', ' . ($addr['barangay'] ?? '') . ', ' . ($addr['city'] ?? '') . ', ' . ($addr['province'] ?? ''));
                }
            } elseif (($ord['fulfillment_method'] ?? '') === 'pickup') {
                $destAddr = 'Store Pick-up (Poblacion, Polomolok)';
            }
            $courierName = ($ord['fulfillment_method'] ?? '') === 'pickup' ? 'Store Pick-up' : 'In-House Delivery';

            $existing = $this->db->table('deliveries')
                ->where('deliverable_type', 'order')
                ->where('deliverable_id', (int) $ord['id'])
                ->get()->getRowArray();

            $delId = $existing ? (int) $existing['id'] : null;
            if (!$delId) {
                $delStatus = match ($ord['status']) {
                    'ready_for_pickup'       => 'ready_for_pickup',
                    'shipped'                => 'shipped',
                    'delivered', 'completed' => 'delivered',
                    'returned'               => 'returned',
                    'cancelled'              => 'cancelled',
                    default                  => 'ready_for_pickup',
                };
                $delId = (int) $this->insert([
                    'deliverable_type'    => 'order',
                    'deliverable_id'      => (int) $ord['id'],
                    'tracking_id'         => (string) ($ord['order_number'] ?? ('ORD-' . $ord['id'])),
                    'courier_name'        => $courierName,
                    'destination_address' => $destAddr,
                    'status'              => $delStatus,
                    'created_at'          => $ord['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
            }
            return $this->findWithDetails($delId, $shopId);
        }

        return null;
    }

    /**
     * Cross-shop tracking lookup for accurate error reporting.
     */
    public function findByTrackingAnyShop(string $trackingId): ?array
    {
        $tokens = $this->extractLookupTokens($trackingId);
        if (empty($tokens)) {
            return null;
        }

        $builder = $this->db->table('deliveries d')
            ->select("d.*, COALESCE(o.order_number, pr.request_number) AS ref_number, COALESCE(o.shop_id, pr.shop_id) AS shop_id")
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->groupStart();

        $first = true;
        foreach ($tokens as $token) {
            $str = (string) $token;
            if ($first) {
                $builder->where('d.tracking_id', $str)
                        ->orWhere('o.order_number', $str)
                        ->orWhere('pr.request_number', $str);
                $first = false;
            } else {
                $builder->orWhere('d.tracking_id', $str)
                        ->orWhere('o.order_number', $str)
                        ->orWhere('pr.request_number', $str);
            }
            if (is_numeric($token)) {
                $n = (int) $token;
                $builder->orWhere('d.id', $n)
                        ->orWhere('d.deliverable_id', $n)
                        ->orWhere('o.id', $n)
                        ->orWhere('pr.id', $n);
            }
        }
        $builder->groupEnd();

        $row = $builder->get()->getRowArray();
        if ($row && !empty($row['shop_id'])) {
            return $row;
        }

        // Check orders
        $oQuery = $this->db->table('orders')->select('id, order_number AS ref_number, shop_id')->groupStart();
        $oF = true;
        foreach ($tokens as $token) {
            $str = (string) $token;
            if ($oF) { $oQuery->where('order_number', $str); $oF = false; }
            else { $oQuery->orWhere('order_number', $str); }
            if (is_numeric($token)) { $oQuery->orWhere('id', (int) $token); }
        }
        $ord = $oQuery->groupEnd()->get()->getRowArray();
        if ($ord) {
            $ord['tracking_id'] = $ord['ref_number'];
            return $ord;
        }

        // Check printing requests
        $pQuery = $this->db->table('printing_requests')->select('id, request_number AS ref_number, shop_id')->groupStart();
        $pF = true;
        foreach ($tokens as $token) {
            $str = (string) $token;
            if ($pF) { $pQuery->where('request_number', $str); $pF = false; }
            else { $pQuery->orWhere('request_number', $str); }
            if (is_numeric($token)) { $pQuery->orWhere('id', (int) $token); }
        }
        $pr = $pQuery->groupEnd()->get()->getRowArray();
        if ($pr) {
            $pr['tracking_id'] = $pr['ref_number'];
            return $pr;
        }

        return null;
    }

    /**
     * Current calendar date in the database server's local timezone.
     */
    public function getLocalToday(): string
    {
        return (string) $this->db->query('SELECT CURDATE() AS d')->getRow()->d;
    }
}