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
        }

        return $builder;
    }

    public function syncMissingDeliveries(int $shopId): void
    {
        // 1. Sync all printing requests for this shop
        $prs = $this->db->table('printing_requests')
            ->where('shop_id', $shopId)
            ->get()->getResultArray();

        foreach ($prs as $pr) {
            $trackingId = (string) ($pr['request_number'] ?? ('PR-' . $pr['id']));
            $existing = $this->db->table('deliveries')
                ->where('deliverable_type', 'printing_request')
                ->where('deliverable_id', (int) $pr['id'])
                ->get()->getRowArray();

            if (!$existing) {
                $existing = $this->db->table('deliveries')
                    ->where('tracking_id', $trackingId)
                    ->get()->getRowArray();
            }

            $isDelivery = ($pr['fulfillment_method'] ?? 'pickup') === 'delivery';
            $delStatus = match ($pr['status']) {
                'ready_for_pickup'   => 'ready_for_pickup',
                'ready_for_delivery' => 'shipped',
                'completed'          => $isDelivery ? 'shipped' : 'ready_for_pickup',
                'cancelled'          => 'cancelled',
                default              => $isDelivery ? 'shipped' : 'ready_for_pickup',
            };

            if ($existing) {
                if ($existing['status'] !== $delStatus && !in_array($existing['status'], ['delivered', 'returned'], true)) {
                    $this->update($existing['id'], ['status' => $delStatus]);
                }
            } else {
                $this->insert([
                    'deliverable_type'    => 'printing_request',
                    'deliverable_id'      => (int) $pr['id'],
                    'tracking_id'         => $trackingId,
                    'courier_name'        => ($pr['fulfillment_method'] ?? 'pickup') === 'delivery' ? 'Store Courier' : 'Store Pick-up',
                    'destination_address' => ($pr['fulfillment_method'] ?? 'pickup') === 'delivery' ? 'Doorstep Delivery' : 'Store Pick-up (Poblacion, Polomolok)',
                    'status'              => $delStatus,
                    'created_at'          => $pr['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
            }
        }

        // 2. Sync all orders for this shop
        $orders = $this->db->table('orders')
            ->where('shop_id', $shopId)
            ->get()->getResultArray();

        foreach ($orders as $ord) {
            $trackingId = (string) ($ord['order_number'] ?? ('ORD-' . $ord['id']));
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
                'ready_for_pickup'  => 'ready_for_pickup',
                'shipped'           => 'shipped',
                'delivered'         => 'delivered',
                'returned'          => 'returned',
                'cancelled'         => 'cancelled',
                default             => 'ready_for_pickup',
            };

            if ($existing) {
                if ($existing['status'] !== $delStatus && !in_array($existing['status'], ['delivered', 'returned'], true)) {
                    $this->update($existing['id'], ['status' => $delStatus]);
                }
            } else {
                $this->insert([
                    'deliverable_type'    => 'order',
                    'deliverable_id'      => (int) $ord['id'],
                    'tracking_id'         => $trackingId,
                    'courier_name'        => ($ord['fulfillment_method'] ?? 'pickup') === 'delivery' ? 'Store Courier' : 'Store Pick-up',
                    'destination_address' => ($ord['fulfillment_method'] ?? 'pickup') === 'delivery' ? 'Customer Shipping Address' : 'Store Pick-up (Poblacion, Polomolok)',
                    'status'              => $delStatus,
                    'created_at'          => $ord['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /**
     * Paginated, tenant-scoped delivery list for the Delivery Management page.
     */
    public function getDeliveriesByShopPaginated(
        int $shopId,
        ?string $search = null,
        ?string $status = null,
        int $perPage = 12,
        int $page = 1,
        string $group = 'deliveries'
    ): array {
        $this->syncMissingDeliveries($shopId);
        $this->buildDeliveryQuery($shopId, $search, $status)
            ->orderBy('deliveries.created_at', 'DESC');

        $deliveries = $this->paginate($perPage, $group, $page);

        return [
            'deliveries' => $this->enrichDeliveriesWithProducts($deliveries ?: []),
            'pager'      => $this->pager,
        ];
    }

    /**
     * Unpaginated, tenant-scoped delivery list for the CSV export.
     */
    public function getDeliveriesForExport(
        int $shopId,
        ?string $search = null,
        ?string $status = null
    ): array {
        $deliveries = $this->buildDeliveryQuery($shopId, $search, $status)
            ->orderBy('deliveries.created_at', 'DESC')
            ->get()->getResultArray();

        return $this->enrichDeliveriesWithProducts($deliveries ?: []);
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
     * Delivery-page KPI cards, all tenant-scoped aggregate SQL.
     */
    public function getDeliveryKPIs(int $shopId): array
    {
        $today = $this->getLocalToday();

        $row = $this->db->table('deliveries d')
            ->select("
                SUM(CASE WHEN d.status IN ('ready_for_pickup','shipped','in_transit') THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN d.status = 'ready_for_pickup' THEN 1 ELSE 0 END) AS ready_for_pickup,
                SUM(CASE WHEN d.status = 'shipped' THEN 1 ELSE 0 END) AS shipped,
                SUM(CASE WHEN d.status = 'delivered' AND d.delivered_at >= '" . $today . " 00:00:00' THEN 1 ELSE 0 END) AS completed_today
            ")
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->groupStart()
            ->where('o.shop_id', $shopId)
            ->orWhere('pr.shop_id', $shopId)
            ->groupEnd()
            ->get()->getRow();

        return [
            'active'            => (int) ($row->active ?? 0),
            'ready_for_pickup'  => (int) ($row->ready_for_pickup ?? 0),
            'shipped'           => (int) ($row->shipped ?? 0),
            'completed_today'   => (int) ($row->completed_today ?? 0),
        ];
    }

    // Polomolok municipality bounds (derived from system centre 6.2136,125.0661 tenant/deliveries.php:354)
    public const POLOMOLOK_LAT_MIN = 6.10;
    public const POLOMOLOK_LAT_MAX = 6.32;
    public const POLOMOLOK_LNG_MIN = 124.95;
    public const POLOMOLOK_LNG_MAX = 125.18;
    public const POLOMOLOK_CENTER_LAT = 6.2136;
    public const POLOMOLOK_CENTER_LNG = 125.0661;

    public static function isPolomolokCoordinate(?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null) return false;
        return $lat >= self::POLOMOLOK_LAT_MIN && $lat <= self::POLOMOLOK_LAT_MAX
            && $lng >= self::POLOMOLOK_LNG_MIN && $lng <= self::POLOMOLOK_LNG_MAX;
    }

    /**
     * In-progress deliveries with their latest position, for the fleet map.
     * Courier removed — shops handle own deliveries.
     */
    public function getDeliveryPins(int $shopId): array
    {
        $rows = $this->db->table('deliveries d')
            ->select("d.id, d.tracking_id, d.status, d.destination_address, d.current_lat, d.current_lng, COALESCE(o.order_number, pr.request_number) AS ref_number, u.first_name, u.last_name, u.phone as customer_phone, sa.address_line1, sa.city, sa.province, sa.label as address_label, s.shop_name")
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('shipping_addresses sa', "sa.id = o.shipping_address_id", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->join('users u', 'u.id = COALESCE(o.customer_id, pr.customer_id)', 'left')
            ->join('shops s', 's.id = COALESCE(o.shop_id, pr.shop_id)', 'left')
            ->groupStart()
                ->where('o.shop_id', $shopId)
                ->orWhere('pr.shop_id', $shopId)
            ->groupEnd()
            ->whereIn('d.status', ['ready_for_pickup', 'shipped', 'in_transit', 'delivered', 'returned'])
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
                // Generate realistic coordinate in Polomolok area based on delivery id
                $seed = (int) $r['id'];
                $r['current_lat'] = number_format(6.2136 + ((($seed * 17) % 30) - 15) * 0.0015, 7, '.', '');
                $r['current_lng'] = number_format(125.0661 + ((($seed * 23) % 30) - 15) * 0.0015, 7, '.', '');
            }
        }
        unset($r);

        return $rows;
    }

    /**
     * Admin-scoped pins across all shops, Polomolok-restricted.
     */
    public function getAllDeliveryPins(?string $search = null, ?string $shopFilter = null): array
    {
        $builder = $this->db->table('deliveries d')
            ->select("d.id, d.tracking_id, d.status, d.destination_address, d.current_lat, d.current_lng, COALESCE(o.order_number, pr.request_number) AS ref_number, u.first_name, u.last_name, s.shop_name, s.id as shop_id")
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->join('users u', 'u.id = COALESCE(o.customer_id, pr.customer_id)', 'left')
            ->join('shops s', 's.id = COALESCE(o.shop_id, pr.shop_id)', 'left')
            ->whereIn('d.status', ['ready_for_pickup', 'shipped', 'in_transit']);
        if ($search !== null && $search !== '') {
            $builder->groupStart()->like('d.tracking_id', $search)->orLike('d.destination_address', $search)->orLike('s.shop_name', $search)->groupEnd();
        }
        if ($shopFilter !== null && $shopFilter !== '') {
            $builder->where('s.shop_name', $shopFilter);
        }
        return $builder->orderBy('d.created_at', 'DESC')->get()->getResultArray();
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

    /**
     * Paginated deliveries for Admin live tracking / fleet monitor table.
     *
     * @return array{deliveries: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getAdminTrackingPaginated(
        ?string $search = null,
        ?string $shopFilter = null,
        ?string $status = null,
        int $perPage = 20,
        int $page = 1,
        string $group = 'tracking'
    ): array {
        $this->builder()
            ->select("deliveries.*, deliveries.deliverable_id as order_id, deliveries.status as delivery_status, COALESCE(deliveries.destination_address, sa.address_line1) as shipping_address, deliveries.created_at as updated_at, s.shop_name, u.first_name, u.last_name, COALESCE(o.order_number, pr.request_number) as ref_number, deliveries.deliverable_type")
            ->join('orders o', "o.id = deliveries.deliverable_id AND deliveries.deliverable_type = 'order'", 'left')
            ->join('printing_requests pr', "pr.id = deliveries.deliverable_id AND deliveries.deliverable_type = 'printing_request'", 'left')
            ->join('shipping_addresses sa', 'sa.id = o.shipping_address_id', 'left')
            ->join('users u', 'u.id = COALESCE(o.customer_id, pr.customer_id)', 'left')
            ->join('shops s', 's.id = COALESCE(o.shop_id, pr.shop_id)', 'left')
            ->orderBy('deliveries.created_at', 'DESC');

        if ($search !== null && $search !== '') {
            $this->builder()->groupStart()
                ->like('deliveries.tracking_id', $search)
                ->orLike('s.shop_name', $search)
                ->orLike('deliveries.destination_address', $search)
                ->groupEnd();
        }

        if ($shopFilter !== null && $shopFilter !== '') {
            $this->builder()->where('s.shop_name', $shopFilter);
        }

        if ($status !== null && in_array($status, ['ready_for_pickup', 'shipped', 'in_transit', 'delivered', 'cancelled'], true)) {
            $this->builder()->where('deliveries.status', $status);
        }

        // Restrict to Polomolok destinations
        $this->builder()->groupStart()
            ->like('COALESCE(deliveries.destination_address, sa.city)', 'Polomolok')
            ->orLike('sa.province', 'South Cotabato')
            ->orWhere('deliveries.destination_address IS NULL')
            ->groupEnd();

        $deliveries = $this->paginate($perPage, $group, $page);

        return [
            'deliveries' => $deliveries ?: [],
            'pager'      => $this->pager,
        ];
    }
}