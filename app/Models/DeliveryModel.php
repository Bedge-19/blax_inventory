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
            $existing = $this->db->table('deliveries')
                ->where('deliverable_type', 'printing_request')
                ->where('deliverable_id', (int) $pr['id'])
                ->get()->getRowArray();

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
                    'tracking_id'         => (string) ($pr['request_number'] ?? ('PR-' . $pr['id'])),
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
            $existing = $this->db->table('deliveries')
                ->where('deliverable_type', 'order')
                ->where('deliverable_id', (int) $ord['id'])
                ->get()->getRowArray();

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
                    'tracking_id'         => (string) ($ord['order_number'] ?? ('ORD-' . $ord['id'])),
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
            'deliveries' => $deliveries ?: [],
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
        return $this->buildDeliveryQuery($shopId, $search, $status)
            ->orderBy('deliveries.created_at', 'DESC')
            ->get()->getResultArray();
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
    }

    public function findByTrackingScoped(string $trackingId, int $shopId): ?array
    {
        $cleanId = trim($trackingId);
        if (preg_match('#/order/([A-Za-z0-9_-]+)#', $cleanId, $matches)) {
            $cleanId = $matches[1];
        }
        $cleanId = ltrim($cleanId, '#');

        $row = $this->db->table('deliveries d')
            ->select("d.*, COALESCE(o.order_number, pr.request_number) AS ref_number, o.status as order_status, u.first_name, u.last_name, u.profile_image_url, u.email")
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->join('users u', 'u.id = COALESCE(o.customer_id, pr.customer_id)', 'left')
            ->groupStart()
                ->where('d.tracking_id', $cleanId)
                ->orWhere('o.order_number', $cleanId)
                ->orWhere('pr.request_number', $cleanId)
            ->groupEnd()
            ->groupStart()
                ->where('o.shop_id', $shopId)
                ->orWhere('pr.shop_id', $shopId)
            ->groupEnd()
            ->get()->getRowArray();

        if ($row) {
            return $row;
        }

        // Check if printing request exists for this shop
        $pr = $this->db->table('printing_requests')
            ->where('request_number', $cleanId)
            ->where('shop_id', $shopId)
            ->get()->getRowArray();

        if ($pr) {
            $newDelId = $this->insert([
                'deliverable_type'    => 'printing_request',
                'deliverable_id'      => (int) $pr['id'],
                'tracking_id'         => (string) $pr['request_number'],
                'courier_name'        => 'Store Pick-up',
                'destination_address' => 'Store Pick-up (Poblacion, Polomolok)',
                'status'              => $pr['status'] === 'completed' ? 'delivered' : ($pr['status'] === 'cancelled' ? 'cancelled' : 'ready_for_pickup'),
                'created_at'          => date('Y-m-d H:i:s'),
            ]);

            return $this->findByTrackingScoped($cleanId, $shopId);
        }

        // Check if order exists for this shop
        $ord = $this->db->table('orders')
            ->where('order_number', $cleanId)
            ->where('shop_id', $shopId)
            ->get()->getRowArray();

        if ($ord) {
            $newDelId = $this->insert([
                'deliverable_type'    => 'order',
                'deliverable_id'      => (int) $ord['id'],
                'tracking_id'         => (string) $ord['order_number'],
                'courier_name'        => 'Store Pick-up',
                'destination_address' => 'Store Pick-up (Poblacion, Polomolok)',
                'status'              => $ord['status'] === 'delivered' ? 'delivered' : ($ord['status'] === 'returned' ? 'returned' : 'ready_for_pickup'),
                'created_at'          => date('Y-m-d H:i:s'),
            ]);

            return $this->findByTrackingScoped($cleanId, $shopId);
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