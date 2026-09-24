<?php

namespace App\Controllers;

use App\Models\ShopModel;
use App\Models\ProductModel;
use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\PrintingRequestModel;
use App\Models\DeliveryModel;
use App\Models\PayoutModel;
use App\Models\ArchivedItemModel;
use App\Models\NotificationModel;
use App\Models\CategoryModel;
use App\Models\UserModel;
use App\Models\ShopBusinessHourModel;
use App\Models\ShopNotificationPreferenceModel;
use App\Models\ProductImageModel;
use App\Models\ProductVariantModel;
use App\Models\PaymentModel;
use App\Models\SiteContentModel;
use App\Models\ComplianceModel;
use App\Models\ShippingAddressModel;
use App\Models\AuditLogModel;

class Tenant extends BaseController
{
    /**
     * The only order statuses that exist in the orders.status enum.
     */
    private const ORDER_STATUSES = [
        'pending'          => 'Pending',
        'processing'       => 'Processing',
        'shipped'          => 'Shipped',
        'in_transit'       => 'In Transit',
        'ready_for_pickup' => 'Ready for Pickup',
        'delivered'        => 'Delivered',
        'completed'        => 'Completed',
        'cancelled'        => 'Cancelled',
    ];

    private function getShopOrRedirect()
    {
        $session = session();
        if (!$session->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $userRole = (string) ($session->get('user_role') ?? '');
        if ($userRole !== 'shop_owner' && $userRole !== 'tenant') {
            return redirect()->to($userRole === 'admin' ? '/admin/dashboard' : '/');
        }

        $shopModel = new ShopModel();
        $shopId    = $session->get('shop_id');
        
        if (!$shopId) {
            $shop = $shopModel->getShopByOwnerId($session->get('user_id'));
            if ($shop) {
                $shopId = $shop['id'];
                $session->set('shop_id', $shopId);
            } else {
                // Return default first shop for testing if non-owner admin
                $shop = $shopModel->first();
                $shopId = $shop['id'] ?? 1;
            }
        } else {
            $shop = $shopModel->find($shopId);
        }

        $userId        = (int) $session->get('user_id');
        $notifModel    = new NotificationModel();
        $unreadCount   = $userId > 0 ? $notifModel->getUnreadCount($userId) : 0;
        $notifications = $userId > 0 ? $notifModel->getRecent($userId, 10) : [];

        service('renderer')->setData([
            'shop'          => $shop,
            'unread_count'  => $unreadCount,
            'notifications' => $notifications,
        ]);

        return ['shop' => $shop, 'shopId' => $shopId];
    }

    public function dashboard()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $shop   = $res['shop'];

        $orderModel = new OrderModel();
        $prModel    = new PrintingRequestModel();
        $productModel = new ProductModel();
        $notifModel = new NotificationModel();

        // ---- Period deltas: last 30 days vs the previous 30 days ----
        $today       = $orderModel->getLocalToday();
        $windowStart = date('Y-m-d 00:00:00', strtotime($today . ' -30 days'));
        $prevStart   = date('Y-m-d 00:00:00', strtotime($today . ' -60 days'));

        // Consolidated KPI and delta metrics (2 queries instead of 12)
        $kpis = $orderModel->getShopDashboardKpis($shopId, $windowStart, $prevStart);

        // ---- Chart data (default: last 7 days) ----
        $chart = $orderModel->getSalesChartData($shopId, '7');
        $chartMax = max($chart['values']);

        // ---- Lists (Directly limited to 8 in SQL) ----
        $recentOrders   = $orderModel->getOrdersByShop($shopId, 8);
        $recentRequests = $prModel->getRequestsByShop($shopId, 8);
        $lowStockItems  = $productModel->getLowStockProducts($shopId);
        $lowStockCount  = $productModel->countLowStockProducts($shopId);

        // ---- Active Compliance Warning & Notifications ----
        $userId        = (int) session()->get('user_id');
        $db = \Config\Database::connect();
        $activeWarning = $userId > 0 ? $db->table('notifications')
            ->where('user_id', $userId)
            ->where('type', 'compliance_warning')
            ->orderBy('created_at', 'DESC')
            ->limit(1)
            ->get()->getRowArray() : null;

        $notifications = $notifModel->getRecent($userId);
        $unreadCount   = $notifModel->getUnreadCount($userId);

        return view('tenant/dashboard', [
            'shop'           => $shop,
            'active_warning' => $activeWarning,
            // KPI values
            'total_revenue'  => $kpis['total_revenue'],
            'total_sales'    => $kpis['total_sales'],
            'pending_orders' => $kpis['pending_orders'],
            'printing_count' => $kpis['printing_count'],
            // KPI deltas (neutral when there is no prior-period data)
            'revenue_delta'  => $this->deltaPercent($kpis['rev_current'], $kpis['rev_previous']),
            'sales_delta'    => $this->deltaPercent($kpis['sales_current'], $kpis['sales_previous']),
            'pending_delta'  => $this->deltaPercent($kpis['pending_current'], $kpis['pending_previous']),
            'printing_delta' => $this->deltaPercent($kpis['print_current'], $kpis['print_previous']),
            // Sales Overview chart
            'chart_labels'   => $chart['labels'],
            'chart_values'   => $chart['values'],
            'chart_max'      => $chartMax,
            'chart_total'    => array_sum($chart['values']),
            // Lists
            'low_stock_items'  => $lowStockItems,
            'low_stock_count'  => $lowStockCount,
            'recent_orders'    => $recentOrders,
            'recent_requests'  => $recentRequests,
            // Header notifications
            'notifications'    => $notifications,
            'unread_count'     => $unreadCount,
        ]);
    }

    /**
     * AJAX endpoint feeding the Sales Overview chart. The selected range
     * (7 / 30 / year) changes the data returned.
     */
    public function dashboardSalesData()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId   = (int) $res['shopId'];
        $rawParam = (string) ($this->request->getGet('range') ?? $this->request->getGet('period') ?? '7');
        $clean    = strtolower(trim($rawParam));

        $range = match ($clean) {
            'year', '1y', '12m', 'this_year' => 'year',
            '30', '30d', '30_days', 'month'  => '30',
            default                          => '7',
        };

        $orderModel = new OrderModel();
        $chart      = $orderModel->getSalesChartData($shopId, $range);

        return $this->response->setJSON([
            'success' => true,
            'range'   => $range,
            'period'  => $clean !== '' ? $clean : ($range . 'd'),
            'labels'  => $chart['labels'],
            'values'  => array_map('floatval', $chart['values']),
            'total'   => round(array_sum($chart['values']), 2),
        ]);
    }

    /**
     * AJAX endpoint returning JSON sales chart and comparative previous period
     * metrics for the Analytics dashboard line chart.
     */
    public function analyticsData()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId   = (int) $res['shopId'];
        $rawParam = (string) ($this->request->getGet('range') ?? $this->request->getGet('period') ?? '30');
        $clean    = strtolower(trim($rawParam));

        $range = match ($clean) {
            'year', '1y', '12m', 'this_year' => 'year',
            '7', '7d', '7_days', 'week'      => '7',
            default                          => '30',
        };

        $orderModel = new OrderModel();
        $chart      = $orderModel->getSalesChartData($shopId, $range, true);

        $chartTotal    = array_sum($chart['values']);
        $previousTotal = (float) ($chart['previous_total'] ?? 0.0);
        $growthPct     = 0.0;
        if ($previousTotal > 0) {
            $growthPct = round((($chartTotal - $previousTotal) / $previousTotal) * 100, 1);
        }

        return $this->response->setJSON([
            'success'          => true,
            'range'            => $range,
            'period'           => $clean !== '' ? $clean : ($range . 'd'),
            'labels'           => $chart['labels'],
            'values'           => array_map('floatval', $chart['values']),
            'previous_values'  => array_map('floatval', $chart['previous_values'] ?? []),
            'total'            => round($chartTotal, 2),
            'previous_total'   => round($previousTotal, 2),
            'growth_pct'       => $growthPct,
        ]);
    }

    /**
     * Marks notifications of the authenticated user as read (all, or a
     * single one when an id is posted). Tenant-scoped to the session user.
     */
    public function markNotificationsRead()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $userId     = (int) session()->get('user_id');
        $notifModel = new NotificationModel();

        $id = (int) $this->request->getPost('id');
        if ($id > 0) {
            $notifModel->markRead($id, $userId);
        } else {
            $notifModel->markAllRead($userId);
        }

        return redirect()->back()->with('success', 'Notifications marked as read.');
    }

    /**
     * Comparison between a current value and the immediately preceding
     * period. Returns a neutral state when there is no prior-period data
     * or no change, so no misleading percentage is ever shown.
     *
     * @return array{pct: ?float, direction: string, has_prior: bool}
     */
    private function deltaPercent(float $current, float $previous): array
    {
        if ($previous <= 0) {
            return ['pct' => null, 'direction' => 'neutral', 'has_prior' => false];
        }

        $pct = round((($current - $previous) / $previous) * 100, 1);

        return [
            'pct'       => abs($pct),
            'direction' => $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'neutral'),
            'has_prior' => true,
        ];
    }

    private function isDate(string $value): bool
    {
        if ($value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        $d = \DateTime::createFromFormat('Y-m-d', $value);

        return $d !== false && $d->format('Y-m-d') === $value;
    }

    /**
     * Neutralize leading formula characters so CSV cells are never
     * interpreted as spreadsheet formulas (CSV injection guard).
     */
    private function csvSafe(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }

    public function inventory()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $shop   = $res['shop'];

        $productModel = new ProductModel();

        $search      = trim((string) $this->request->getGet('q'));
        $sku         = trim((string) $this->request->getGet('sku'));
        $categoryId  = (int) $this->request->getGet('category');
        $stockStatus = (string) $this->request->getGet('stock');
        $stockStatus = in_array($stockStatus, ['in', 'low', 'out'], true) ? $stockStatus : '';
        $bestseller  = (int) $this->request->getGet('bestseller') === 1;
        $page        = max(1, (int) $this->request->getGet('page_inventory'));

        $result = $productModel->getInventoryPaginated(
            $shopId,
            $search !== '' ? $search : null,
            $categoryId ?: null,
            $stockStatus !== '' ? $stockStatus : null,
            $sku !== '' ? $sku : null,
            $bestseller,
            10,
            $page,
            'inventory'
        );

        $productIds = !empty($result['products']) ? array_column($result['products'], 'id') : [];
        $productImages = [];
        $productVariants = [];
        if (!empty($productIds)) {
            $allImages = (new ProductImageModel())
                ->whereIn('product_id', $productIds)
                ->orderBy('sort_order', 'ASC')
                ->findAll();
            foreach ($allImages as $img) {
                $productImages[$img['product_id']][] = $img;
            }

            $allVariants = (new ProductVariantModel())
                ->whereIn('product_id', $productIds)
                ->orderBy('id', 'ASC')
                ->findAll();
            foreach ($allVariants as $var) {
                $productVariants[$var['product_id']][] = $var;
            }
        }

        return view('tenant/inventory', [
            'shop'            => $shop,
            'products'        => $result['products'],
            'productImages'   => $productImages,
            'productVariants' => $productVariants,
            'pager'           => $result['pager'],
            'summary'         => $productModel->getInventorySummary($shopId),
            'categories'      => $productModel->getShopCategories($shopId),
            'all_categories'  => (new CategoryModel())->getAllCached(),
            'filters'         => [
                'q'          => $search,
                'sku'        => $sku,
                'category'   => $categoryId,
                'stock'      => $stockStatus,
                'bestseller' => $bestseller,
            ],
            'activeNav'       => 'inventory',
            'title'           => 'Inventory Management',
        ]);
    }

    public function orders()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $shop   = $res['shop'];

        // Automatically archive completed items older than 3 days
        (new ArchivedItemModel())->autoArchiveCompletedItems($shopId, 3);

        $orderModel = new OrderModel();

        $search      = trim((string) $this->request->getGet('q'));
        $status      = (string) $this->request->getGet('status');
        $status      = in_array($status, array_keys(self::ORDER_STATUSES), true) ? $status : '';
        $fulfillment = (string) $this->request->getGet('fulfillment');
        $fulfillment = in_array($fulfillment, ['pickup', 'delivery'], true) ? $fulfillment : '';
        $dateFrom    = $this->isDate((string) $this->request->getGet('from')) ? (string) $this->request->getGet('from') : '';
        $dateTo      = $this->isDate((string) $this->request->getGet('to')) ? (string) $this->request->getGet('to') : '';
        $perPage     = (int) ($this->request->getGet('per_page') ?: 10);
        if (!in_array($perPage, [5, 10, 15, 20], true)) {
            $perPage = 10;
        }
        $page        = max(1, (int) $this->request->getGet('page_orders'));

        $result = $orderModel->getOrdersByShopPaginated(
            $shopId,
            $search !== '' ? $search : null,
            $status !== '' ? $status : null,
            $dateFrom !== '' ? $dateFrom : null,
            $dateTo !== '' ? $dateTo : null,
            $perPage,
            $page,
            'orders',
            $fulfillment !== '' ? $fulfillment : null
        );

        $processingOrders = $orderModel->getProcessingOrdersForShop($shopId);

        return view('tenant/orders', [
            'shop'             => $shop,
            'orders'           => $result['orders'],
            'processingOrders' => $processingOrders,
            'pager'            => $result['pager'],
            'per_page'         => $perPage,
            'summary'          => $orderModel->getOrdersSummary($shopId),
            'filters'          => ['q' => $search, 'status' => $status, 'fulfillment' => $fulfillment, 'from' => $dateFrom, 'to' => $dateTo],
            'statusOptions'    => [
                'pending'          => 'Pending',
                'processing'       => 'Processing',
                'ready_for_pickup' => 'Ready for Pickup',
                'shipped'          => 'Shipped',
                'in_transit'       => 'In Transit',
                'delivered'        => 'Delivered',
                'completed'        => 'Completed',
                'cancelled'        => 'Cancelled',
            ],
            'activeNav'        => 'orders',
            'title'            => 'Orders Management',
        ]);
    }

    /**
     * AJAX endpoint returning a single order (with its items) for the
     * tenant orders detail modal. Strictly scoped to the authenticated
     * tenant's shop.
     */
    public function orderItems(int $orderId)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId = (int) $res['shopId'];

        $order = (new OrderModel())->find($orderId);
        if (!$order || (int) $order['shop_id'] !== $shopId) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Order not found']);
        }

        $items = (new OrderItemModel())
            ->select('order_items.*, (SELECT image_url FROM product_images WHERE product_id = order_items.product_id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as gallery_image, (SELECT image_url FROM product_images WHERE product_id = order_items.product_id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as product_image')
            ->where('order_items.order_id', $orderId)
            ->findAll();
        $customer = (new UserModel())->find($order['customer_id']);
        $shop     = (new ShopModel())->find($order['shop_id']);

        $shippingAddr = null;
        if (!empty($order['shipping_address_id'])) {
            $shippingAddr = (new ShippingAddressModel())->find($order['shipping_address_id']);
        }
        $addressLabel = 'Home';
        $location     = 'Polomolok, South Cotabato';
        $phone        = !empty($shippingAddr['phone']) ? $shippingAddr['phone'] : (!empty($customer['phone']) ? $customer['phone'] : 'N/A');
        if ($shippingAddr) {
            $addressLabel = !empty($shippingAddr['label']) ? $shippingAddr['label'] : 'Home';
            $addrStr = trim(($shippingAddr['address_line1'] ?? '') . ', ' . ($shippingAddr['city'] ?? '') . ' ' . ($shippingAddr['province'] ?? ''));
            if ($addrStr !== '') {
                $location = $addrStr;
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'order'   => [
                'id'                 => (int) $order['id'],
                'customer_id'        => (int) ($order['customer_id'] ?? 0),
                'order_number'       => $order['order_number'],
                'status'             => humanize_status($order['status']),
                'raw_status'          => $order['status'],
                'payment_status'      => $order['payment_status'] ?? 'unpaid',
                'placed_at'          => date('M d, Y h:i A', strtotime($order['placed_at'])),
                'payment_method'     => !empty($order['payment_method']) ? strtoupper($order['payment_method']) : ($order['fulfillment_method'] === 'pickup' ? 'PAY ON PICK-UP' : 'COD'),
                'fulfillment_method'  => ucfirst(str_replace('_', ' ', $order['fulfillment_method'] ?? 'delivery')),
                'raw_fulfillment_method' => $order['fulfillment_method'] ?? 'delivery',
                'pos_additional_amount' => (float) ($order['pos_additional_amount'] ?? 0),
                'subtotal'           => (float) $order['subtotal'],
                'shipping_fee'       => (float) $order['shipping_fee'],
                'tax_amount'         => (float) $order['tax_amount'],
                'total_amount'       => (float) $order['total_amount'],
                'customer'           => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? 'Customer')),
                'shop_name'          => $shop['shop_name'] ?? 'RHK Storefront',
                'location'           => $location,
                'address_label'      => $addressLabel,
                'phone'              => $phone,
            ],
            'items' => array_map(static function ($i) {
                $img = !empty($i['gallery_image']) ? $i['gallery_image'] : (!empty($i['product_image']) ? $i['product_image'] : '');
                if ($img !== '' && !str_starts_with($img, 'http://') && !str_starts_with($img, 'https://')) {
                    $img = base_url($img);
                }
                return [
                    'product_name'  => $i['product_name'],
                    'image_url'     => $img,
                    'variant_label' => $i['variant_label'] ?? '',
                    'quantity'      => (int) $i['quantity'],
                    'unit_price'    => (float) $i['unit_price'],
                    'line_total'    => (float) $i['line_total'],
                ];
            }, $items),
        ]);
    }

    /**
     * Real-time polling endpoint for tenant orders and printing requests.
     * Route: GET /tenant/realtime/check
     */
    public function realtimeCheck()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId = (int) $res['shopId'];
        $lastOrderId = (int) $this->request->getGet('last_order_id');
        $lastPrintingId = (int) $this->request->getGet('last_printing_id');

        $orderModel = new OrderModel();
        $userModel = new UserModel();
        $printingModel = new PrintingRequestModel();

        // 1. Fetch new orders for this shop
        $newOrders = [];
        if ($lastOrderId > 0) {
            $rawOrders = $orderModel->where('shop_id', $shopId)
                ->where('id >', $lastOrderId)
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($rawOrders as $ord) {
                $cust = $userModel->find($ord['customer_id']);
                $custName = trim(($cust['first_name'] ?? '') . ' ' . ($cust['last_name'] ?? ''));
                if ($custName === '') $custName = 'Customer';
                $initials = strtoupper(substr($cust['first_name'] ?? 'C', 0, 1) . substr($cust['last_name'] ?? 'U', 0, 1));

                $itemsCount = (int) (new OrderItemModel())->where('order_id', $ord['id'])->countAllResults();
                if ($itemsCount === 0) $itemsCount = 1;

                $addr = null;
                if (!empty($ord['shipping_address_id'])) {
                    $addr = (new ShippingAddressModel())->find($ord['shipping_address_id']);
                }
                $deliveryAddr = $addr ? trim(($addr['address_line1'] ?? '') . ', ' . ($addr['address_line2'] ?? '')) : 'Customer Address';

                $isPickup = strtolower($ord['fulfillment_method'] ?? '') === 'pickup';

                $newOrders[] = [
                    'id'                 => (int) $ord['id'],
                    'order_number'       => $ord['order_number'] ?? ('ORD-' . $ord['id']),
                    'customer_name'      => $custName,
                    'customer_initials'  => $initials,
                    'customer_phone'     => $addr['phone'] ?? $cust['phone'] ?? 'N/A',
                    'customer_image'     => $cust['profile_image_url'] ?? '',
                    'total_amount'       => (float) ($ord['total_amount'] ?? 0),
                    'total_amount_fmt'   => number_format((float) ($ord['total_amount'] ?? 0), 2),
                    'items_count'        => $itemsCount,
                    'fulfillment_method' => $ord['fulfillment_method'] ?? 'delivery',
                    'is_pickup'          => $isPickup,
                    'delivery_address'   => $deliveryAddr,
                    'status'             => $ord['status'] ?? 'pending',
                    'created_at'         => $ord['created_at'],
                    'placed_at_fmt'      => date('M d, h:i A', strtotime($ord['created_at'] ?? 'now')),
                ];
            }
        }

        // 2. Fetch new printing requests for this shop
        $newPrinting = [];
        if ($lastPrintingId > 0) {
            $rawPrinting = $printingModel->where('shop_id', $shopId)
                ->where('id >', $lastPrintingId)
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($rawPrinting as $pr) {
                $cust = $userModel->find($pr['customer_id']);
                $custName = trim(($cust['first_name'] ?? '') . ' ' . ($cust['last_name'] ?? ''));
                if ($custName === '') $custName = 'Customer';
                $initials = strtoupper(substr($cust['first_name'] ?? 'C', 0, 1) . substr($cust['last_name'] ?? 'U', 0, 1));

                $newPrinting[] = [
                    'id'                 => (int) $pr['id'],
                    'request_number'     => $pr['request_number'] ?? ('PR-' . $pr['id']),
                    'customer_name'      => $custName,
                    'customer_initials'  => $initials,
                    'service_name'       => $pr['service_type'] ?? 'Printing Request',
                    'total_pages'        => (int) ($pr['total_pages'] ?? 1),
                    'total_amount'       => (float) ($pr['total_price'] ?? 0),
                    'total_amount_fmt'   => number_format((float) ($pr['total_price'] ?? 0), 2),
                    'fulfillment_method' => strtolower($pr['fulfillment_method'] ?? 'delivery'),
                    'status'             => $pr['status'] ?? 'new',
                    'created_at'         => $pr['created_at'],
                    'placed_at_fmt'      => date('M d, h:i A', strtotime($pr['created_at'] ?? 'now')),
                ];
            }
        }

        $summary = $orderModel->getOrdersSummary($shopId);

        $maxOrdRow = $orderModel->where('shop_id', $shopId)->selectMax('id')->first();
        $currentMaxOrderId = (int) ($maxOrdRow['id'] ?? 0);

        $maxPrRow = $printingModel->where('shop_id', $shopId)->selectMax('id')->first();
        $currentMaxPrintId = (int) ($maxPrRow['id'] ?? 0);

        return $this->response->setJSON([
            'success'          => true,
            'max_order_id'     => $currentMaxOrderId,
            'max_printing_id'  => $currentMaxPrintId,
            'has_new_orders'   => !empty($newOrders),
            'new_orders'       => $newOrders,
            'has_new_printing' => !empty($newPrinting),
            'new_printing'     => $newPrinting,
            'summary'          => $summary,
        ]);
    }

    /**
     * Dedicated Standalone Printable Receipt & Waybill for a Product Order / POS Sale.
     * Renders a clean receipt in a new tab without system UI, complete with product
     * names, purchase date/time, QR verification code, and shop details.
     */
    public function orderReceipt($orderRef = null)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $shop   = (new ShopModel())->find($shopId) ?? [];

        if (empty($orderRef)) {
            return redirect()->to(base_url('tenant/orders'))->with('error', 'Please specify an order.');
        }

        $orderModel = new OrderModel();
        $order = null;

        if (is_numeric($orderRef)) {
            $order = $orderModel->find((int) $orderRef);
        }

        if (!$order) {
            $order = $orderModel->where('order_number', (string) $orderRef)->first();
        }

        if (!$order && is_numeric($orderRef)) {
            $order = $orderModel->where('order_number', 'ORD-' . $orderRef)->first();
        }

        if (!$order || (int) $order['shop_id'] !== $shopId) {
            return redirect()->to(base_url('tenant/orders'))->with('error', 'Order not found or access denied.');
        }

        $items = (new OrderItemModel())
            ->where('order_id', (int) $order['id'])
            ->findAll();

        $customer = !empty($order['customer_id']) ? (new UserModel())->find($order['customer_id']) : null;

        $shippingAddr = null;
        if (!empty($order['shipping_address_id'])) {
            $shippingAddr = (new ShippingAddressModel())->find($order['shipping_address_id']);
        }

        $printingId = $this->request->getGet('printing_id');
        $printing = null;
        if (!empty($printingId)) {
            $prModel = new PrintingRequestModel();
            if (is_numeric($printingId)) {
                $printing = $prModel->where('shop_id', $shopId)->find((int) $printingId);
            }
            if (!$printing) {
                $printing = $prModel->where('shop_id', $shopId)->where('request_number', (string) $printingId)->first();
            }
        }

        // Format QR payload for quick scanning and order tracking
        $qrPayload = $order['order_number'];
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($qrPayload);

        return view('tenant/receipt', [
            'type'         => $printing ? 'combined' : 'order',
            'order'        => $order,
            'items'        => $items,
            'printing'     => $printing,
            'customer'     => $customer,
            'shippingAddr' => $shippingAddr,
            'shop'         => $shop,
            'qrUrl'        => $qrUrl,
            'cashierName'  => session()->get('user_name') ?? 'Store Staff',
        ]);
    }

    /**
     * Dedicated Standalone Printable Receipt & Job Ticket for a Printing Request.
     */
    public function printingReceipt($requestRef = null)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $shop   = (new ShopModel())->find($shopId) ?? [];

        if (empty($requestRef)) {
            return redirect()->to(base_url('tenant/printing'))->with('error', 'Please specify a printing request.');
        }

        $prModel = new PrintingRequestModel();
        $pr = null;

        if (is_numeric($requestRef)) {
            $pr = $prModel->find((int) $requestRef);
        }

        if (!$pr) {
            $pr = $prModel->where('request_number', (string) $requestRef)->first();
        }

        if (!$pr && is_numeric($requestRef)) {
            $pr = $prModel->where('request_number', 'PR-' . $requestRef)->first();
        }

        if (!$pr || (int) $pr['shop_id'] !== $shopId) {
            return redirect()->to(base_url('tenant/printing'))->with('error', 'Printing request not found or access denied.');
        }

        $customer = !empty($pr['customer_id']) ? (new UserModel())->find($pr['customer_id']) : null;

        $orderId = $this->request->getGet('order_id');
        $order = null;
        $items = [];
        if (!empty($orderId)) {
            $orderModel = new OrderModel();
            if (is_numeric($orderId)) {
                $order = $orderModel->where('shop_id', $shopId)->find((int) $orderId);
            }
            if (!$order) {
                $order = $orderModel->where('shop_id', $shopId)->where('order_number', (string) $orderId)->first();
            }
            if ($order) {
                $items = (new OrderItemModel())->where('order_id', (int) $order['id'])->findAll();
            }
        }

        $qrPayload = $pr['request_number'] ?? ('PR-' . $pr['id']);
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($qrPayload);

        return view('tenant/receipt', [
            'type'         => $order ? 'combined' : 'printing',
            'printing'     => $pr,
            'order'        => $order,
            'items'        => $items,
            'customer'     => $customer,
            'shop'         => $shop,
            'qrUrl'        => $qrUrl,
            'cashierName'  => session()->get('user_name') ?? 'Store Staff',
        ]);
    }

    /**
     * CSV export of the currently filtered orders, reusing the same
     * tenant-scoped query as the Orders table.
     */
    public function ordersExport()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];

        $search   = trim((string) $this->request->getGet('q'));
        $status   = (string) $this->request->getGet('status');
        $status   = in_array($status, array_keys(self::ORDER_STATUSES), true) ? $status : '';
        $dateFrom = $this->isDate((string) $this->request->getGet('from')) ? (string) $this->request->getGet('from') : '';
        $dateTo   = $this->isDate((string) $this->request->getGet('to')) ? (string) $this->request->getGet('to') : '';

        $rows = (new OrderModel())->getOrdersForExport(
            $shopId,
            $search !== '' ? $search : null,
            $status !== '' ? $status : null,
            $dateFrom !== '' ? $dateFrom : null,
            $dateTo !== '' ? $dateTo : null
        );

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Order ID', 'Customer', 'Email', 'Date', 'Payment', 'Fulfillment', 'Subtotal', 'Shipping', 'Tax', 'Total', 'Status']);

        foreach ($rows as $r) {
            $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            fputcsv($handle, [
                $r['order_number'],
                $this->csvSafe($name),
                $this->csvSafe($r['email'] ?? ''),
                $r['placed_at'],
                $r['payment_method'],
                $r['fulfillment_method'],
                $r['subtotal'],
                $r['shipping_fee'],
                $r['tax_amount'],
                $r['total_amount'],
                $r['status'],
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="orders-' . date('Y-m-d') . '.csv"')
            ->setBody($csv);
    }

    public function printing()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $shop   = $res['shop'];

        // Automatically archive completed items older than 3 days
        (new ArchivedItemModel())->autoArchiveCompletedItems($shopId, 3);

        // Recent requests (all statuses), with independent paginator group.
        $search  = trim((string) ($this->request->getGet('q') ?: $this->request->getGet('search')));
        $status  = (string) $this->request->getGet('status');
        $status  = in_array($status, ['new', 'in_production', 'ready_for_pickup', 'ready_for_delivery', 'completed', 'cancelled'], true) ? $status : '';
        $docType = strtolower((string) $this->request->getGet('doc_type'));
        $docType = in_array($docType, ['pdf', 'docx'], true) ? $docType : '';
        $perPage = max(5, min(100, (int) ($this->request->getGet('per_page') ?? 10)));
        $page    = max(1, (int) $this->request->getGet('page_recent'));

        $recent = (new PrintingRequestModel())->getRequestsByShopPaginated(
            $shopId,
            $search !== '' ? $search : null,
            $status !== '' ? $status : null,
            $perPage,
            $page,
            'recent',
            $docType !== '' ? $docType : null
        );

        // Completed section, separate paginator group.
        $cSearch = trim((string) $this->request->getGet('cq'));
        $cPage   = max(1, (int) $this->request->getGet('page_completed'));

        $completed = (new PrintingRequestModel())->getCompletedPaginated(
            $shopId,
            $cSearch !== '' ? $cSearch : null,
            8,
            $cPage,
            'completed'
        );

        $settingModel = new \App\Models\ShopPrintingSettingModel();
        $paperModel   = new \App\Models\ShopPaperSizeSettingModel();
        $attModel     = new \App\Models\PrintingRequestAttachmentModel();

        $queue = (new PrintingRequestModel())->getProductionQueue($shopId);

        $allReqIds = array_unique(array_filter(array_merge(
            array_column($recent['requests'], 'id'),
            array_column($completed['requests'], 'id'),
            array_column($queue, 'id')
        )));
        $attsByReq = [];
        if (!empty($allReqIds)) {
            $rawAtts = $attModel->whereIn('printing_request_id', $allReqIds)->findAll();
            foreach ($rawAtts as $att) {
                $attsByReq[$att['printing_request_id']][] = $att;
            }
        }
        foreach ($recent['requests'] as &$rq) {
            $rq['attachments'] = $attsByReq[$rq['id']] ?? [];
        }
        unset($rq);
        foreach ($completed['requests'] as &$cq) {
            $cq['attachments'] = $attsByReq[$cq['id']] ?? [];
        }
        unset($cq);
        foreach ($queue as &$qq) {
            $qq['attachments'] = $attsByReq[$qq['id']] ?? [];
        }
        unset($qq);

        return view('tenant/printing', [
            'shop'             => $shop,
            'requests'         => $recent['requests'],
            'pager'            => $recent['pager'],
            'summary'          => (new PrintingRequestModel())->getPrintSummary($shopId),
            'queue'            => $queue,
            'completed'        => $completed['requests'],
            'completed_pager'  => $completed['pager'],
            'filters'          => ['q' => $search, 'status' => $status, 'doc_type' => $docType, 'cq' => $cSearch],
            'printingSettings' => $settingModel->getForShop($shopId),
            'paperSizes'       => $paperModel->getForShop($shopId),
            'activeNav'        => 'printing',
            'title'            => 'Printing Requests Management',
        ]);
    }

    public function deliveries()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $shop   = $res['shop'];

        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');
        $status = in_array($status, ['shipped', 'in_transit'], true) ? $status : '';
        $page   = max(1, (int) ($this->request->getGet('page_deliveries') ?: $this->request->getGet('page') ?: 1));

        $deliveryModel = new DeliveryModel();
        $result = $deliveryModel->getDeliveriesByShopPaginated(
            $shopId,
            $search !== '' ? $search : null,
            $status !== '' ? $status : null,
            12,
            $page,
            'deliveries'
        );

        return view('tenant/deliveries', [
            'shop'          => $shop,
            'deliveries'    => $result['deliveries'],
            'pager'         => $result['pager'],
            'kpis'          => $deliveryModel->getDeliveryKPIs($shopId),
            'pins'          => $deliveryModel->getDeliveryPins($shopId),
            'filters'       => ['q' => $search, 'status' => $status],
            'statusOptions' => [
                'shipped'    => 'Shipped',
                'in_transit' => 'In Transit',
                'delivered'  => 'Mark as Delivered',
            ],
            'activeNav'     => 'delivery',
            'title'         => 'Delivery Management',
        ]);
    }

    /**
     * AJAX endpoint for QR/detail lookup by tracking ID. Strictly scoped to
     * the authenticated tenant's shop; unknown or foreign IDs return 404.
     */
    public function deliveryLookup()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId     = (int) $res['shopId'];
        $rawCode    = trim((string) $this->request->getPost('tracking_id'));

        if ($rawCode === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'Enter a tracking ID or scan a QR code.',
                'message' => 'Enter a tracking ID or scan a QR code.',
            ]);
        }

        $deliveryModel = new DeliveryModel();
        $row           = $deliveryModel->findByTrackingScoped($rawCode, $shopId);
        if (!$row) {
            $crossCheck = $deliveryModel->findByTrackingAnyShop($rawCode);
            if ($crossCheck && (int) ($crossCheck['shop_id'] ?? 0) !== $shopId) {
                $ref = $crossCheck['ref_number'] ?: $crossCheck['tracking_id'];
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'error'   => "Order #{$ref} belongs to another shop, not your shop.",
                    'message' => "Order #{$ref} belongs to another shop, not your shop.",
                ]);
            }

            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'No delivery or order found for scanned code "' . esc($rawCode) . '" in your shop.',
                'message' => 'No delivery or order found for scanned code "' . esc($rawCode) . '" in your shop.',
            ]);
        }

        $isPickup      = ($row['fulfillment_method'] ?? '') === 'pickup';
        $isPrinting    = ($row['deliverable_type'] ?? '') === 'printing_request';
        $typeLabel     = $isPrinting ? 'Printing Request' : 'Order';
        $ref           = $row['ref_number'] ?: $row['tracking_id'];
        $rawStatus     = strtolower((string) ($row['order_status'] ?? $row['status'] ?? ''));

        // Reject orders/requests still in preparation or production
        if (in_array($rawStatus, ['pending', 'processing', 'in_progress', 'new', 'in_production'], true)) {
            $statusName = strtoupper(humanize_status($rawStatus));
            $errMsg = $isPickup
                ? "{$typeLabel} #{$ref} is currently {$statusName}. Hindi pa ito maaaring i-scan o i-claim dahil kasalukuyan pa itong inihahanda / ginagawa. Paki-mark muna bilang Ready for Pick-up bago i-scan."
                : "{$typeLabel} #{$ref} is currently {$statusName}. Hindi pa ito maaaring i-scan o i-deliver dahil kasalukuyan pa itong inihahanda / ginagawa. Paki-mark muna bilang Out for Delivery / Shipped bago i-scan.";

            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => $errMsg,
                'message' => $errMsg,
                'status'  => $rawStatus,
            ]);
        }

        if ($rawStatus === 'cancelled') {
            $errMsg = "{$typeLabel} #{$ref} was CANCELLED. Hindi na ito maaaring i-scan o i-claim.";
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => $errMsg,
                'message' => $errMsg,
                'status'  => 'cancelled',
            ]);
        }

        if ($rawStatus === 'returned') {
            $errMsg = "{$typeLabel} #{$ref} is currently marked as RETURNED.";
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => $errMsg,
                'message' => $errMsg,
                'status'  => 'returned',
            ]);
        }

        if (in_array($rawStatus, ['delivered', 'completed'], true)) {
            $actionType = 'already_delivered';
            $statusMsg = $isPickup
                ? "Store Pick-up {$typeLabel} #{$ref} is already verified as COMPLETED / PICKED UP."
                : "Delivery #{$ref} is already verified as DELIVERED.";

            return $this->response->setJSON([
                'success'        => true,
                'status_updated' => false,
                'new_status'     => null,
                'action_type'    => $actionType,
                'message'        => $statusMsg,
                'delivery'       => [
                    'id'               => (int) $row['id'],
                    'tracking_id'      => $row['tracking_id'],
                    'ref_number'       => $row['ref_number'] ?? '',
                    'product_name'     => $row['product_name'] ?? 'Order Item',
                    'product_details'  => $row['all_products_list'] ?? ($row['product_name'] ?? ''),
                    'deliverable_type' => $row['deliverable_type'],
                    'courier_name'     => $row['courier_name'] ?? '',
                    'destination'      => $row['destination_address'] ?? '',
                    'status'           => humanize_status($rawStatus),
                    'status_key'       => $rawStatus,
                    'customer'         => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                    'created_at'       => date('M d, Y h:i A', strtotime($row['created_at'])),
                    'shipped_at'       => !empty($row['shipped_at']) ? date('M d, Y h:i A', strtotime($row['shipped_at'])) : null,
                    'delivered_at'     => !empty($row['delivered_at']) ? date('M d, Y h:i A', strtotime($row['delivered_at'])) : null,
                ],
            ]);
        }

        // Fulfillable statuses: ['shipped', 'in_transit', 'ready_for_pickup', 'ready_for_delivery', 'ready']
        $newStatus  = 'delivered';
        $actionType = 'delivered';
        $now        = date('Y-m-d H:i:s');

        // Update delivery record
        $deliveryModel->update($row['id'], [
            'status'       => 'delivered',
            'delivered_at' => $now,
        ]);

        // Update linked order or printing request
        if ($row['deliverable_type'] === 'order') {
            (new OrderModel())->update($row['deliverable_id'], [
                'status'       => 'delivered',
                'completed_at' => $now,
            ]);
        } elseif ($row['deliverable_type'] === 'printing_request') {
            (new PrintingRequestModel())->update($row['deliverable_id'], [
                'status'       => 'completed',
                'completed_at' => $now,
            ]);
        }

        $row['delivered_at'] = $now;
        $row['status'] = 'delivered';
        $statusMsg = $isPickup
            ? "Pick-up {$typeLabel} #{$ref} verified & marked as COLLECTED / COMPLETED!"
            : "Delivery #{$ref} verified & marked as DELIVERED!";

        // Send customer notification on QR status update
        $customerId = (int) ($row['customer_id'] ?? 0);
        if ($customerId > 0 && $newStatus !== null) {
            $reqRef = $row['ref_number'] ?: $row['tracking_id'];
            if ($newStatus === 'delivered') {
                (new NotificationModel())->create(
                    $customerId,
                    'delivery',
                    'Order Delivered / Picked Up',
                    'Your order/request #' . $reqRef . ' has been successfully marked as delivered / picked up!',
                    '/customer/orders'
                );
            } elseif ($newStatus === 'returned') {
                (new NotificationModel())->create(
                    $customerId,
                    'delivery',
                    'Order Status Returned',
                    'Your order/request #' . $reqRef . ' status has been updated to returned.',
                    '/customer/orders'
                );
            }
        }

        return $this->response->setJSON([
            'success'        => true,
            'status_updated' => $newStatus !== null,
            'new_status'     => $newStatus,
            'action_type'    => $actionType,
            'message'        => $statusMsg,
            'delivery'       => [
                'id'               => (int) $row['id'],
                'tracking_id'      => $row['tracking_id'],
                'ref_number'       => $row['ref_number'] ?? '',
                'product_name'     => $row['product_name'] ?? 'Order Item',
                'product_details'  => $row['all_products_list'] ?? ($row['product_name'] ?? ''),
                'deliverable_type' => $row['deliverable_type'],
                'courier_name'     => $row['courier_name'] ?? '',
                'destination'      => $row['destination_address'] ?? '',
                'status'           => humanize_status($row['status']),
                'status_key'       => $row['status'],
                'customer'         => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                'created_at'       => date('M d, Y h:i A', strtotime($row['created_at'])),
                'shipped_at'       => !empty($row['shipped_at']) ? date('M d, Y h:i A', strtotime($row['shipped_at'])) : null,
                'delivered_at'     => !empty($row['delivered_at']) ? date('M d, Y h:i A', strtotime($row['delivered_at'])) : null,
            ],
        ]);
    }

    /**
     * Real-time GPS location broadcasting endpoint for delivery rider (shop owner).
     * Route: POST tenant/deliveries/update-location
     */
    public function updateDeliveryLocation()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId     = (int) $res['shopId'];
        $deliveryId = (int) $this->request->getPost('delivery_id');
        $lat        = (float) $this->request->getPost('lat');
        $lng        = (float) $this->request->getPost('lng');

        if ($deliveryId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'Delivery ID is required.',
            ]);
        }

        $deliveryModel = new DeliveryModel();
        $ownerShopId = $deliveryModel->resolveShopId($deliveryId);
        if (!$ownerShopId || $ownerShopId !== $shopId) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error'   => 'Access denied. This delivery does not belong to your shop.',
            ]);
        }

        $delivery = $deliveryModel->find($deliveryId);
        if (!$delivery) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Delivery record not found.',
            ]);
        }

        if (!in_array($delivery['status'], ['shipped', 'in_transit'], true)) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Delivery is no longer active (' . $delivery['status'] . '). Updates ignored.',
                'status'  => $delivery['status'],
            ]);
        }

        if (!DeliveryModel::isPolomolokCoordinate($lat, $lng)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'GPS coordinates are outside the Polomolok operational delivery zone.',
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $updateData = [
            'current_lat'         => $lat,
            'current_lng'         => $lng,
            'location_updated_at' => $now,
        ];
        if ($delivery['status'] === 'shipped') {
            $updateData['status'] = 'in_transit';
        }

        $deliveryModel->update($deliveryId, $updateData);

        return $this->response->setJSON([
            'success'    => true,
            'lat'        => $lat,
            'lng'        => $lng,
            'status'     => $updateData['status'] ?? $delivery['status'],
            'updated_at' => $now,
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    /**
     * CSV export of the tenant's deliveries, honoring the active filters.
     */
    public function deliveriesExport()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];

        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');
        $status = in_array($status, ['ready_for_pickup', 'shipped', 'in_transit', 'delivered', 'cancelled'], true) ? $status : '';

        $rows = (new DeliveryModel())->getDeliveriesForExport(
            $shopId,
            $search !== '' ? $search : null,
            $status !== '' ? $status : null
        );

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Tracking ID', 'Reference', 'Type', 'Customer', 'Destination', 'Courier', 'Status', 'Created At', 'Shipped At', 'Delivered At']);

        foreach ($rows as $r) {
            $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            fputcsv($handle, [
                $this->csvSafe($r['tracking_id'] ?? ''),
                $r['ref_number'] ?? '',
                $r['deliverable_type'] ?? '',
                $this->csvSafe($name),
                $this->csvSafe($r['destination_address'] ?? ''),
                $this->csvSafe($r['courier_name'] ?? ''),
                $r['status'] ?? '',
                $r['created_at'] ?? '',
                $r['shipped_at'] ?? '',
                $r['delivered_at'] ?? '',
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="deliveries-' . date('Y-m-d') . '.csv"')
            ->setBody($csv);
    }

    public function withdrawals()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = $res['shopId'];
        $shop   = $res['shop'];

        $perPage = (int) ($this->request->getGet('per_page') ?: 10);
        if (!in_array($perPage, [5, 10, 15, 20], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $this->request->getGet('page_withdrawals'));

        $payoutModel = new PayoutModel();
        $result      = $payoutModel->getWithdrawalsByShopPaginated($shopId, $perPage, $page, 'withdrawals');
        $payouts     = $result['withdrawals'];

        $balanceData = $this->getShopEscrowAndBalance($shopId);

        // Verify GCash information completeness
        $rawGcashNum  = preg_replace('/\D/', '', (string) ($shop['gcash_number'] ?? ''));
        $rawGcashName = trim((string) ($shop['gcash_account_name'] ?? ''));
        $isGcashComplete = ($rawGcashName !== '' && mb_strlen($rawGcashName) >= 2 && preg_match('/^09\d{9}$/', $rawGcashNum));
        $maskedGcashNum  = $isGcashComplete ? ('09' . str_repeat('•', 5) . substr($rawGcashNum, -4)) : '';

        return view('tenant/withdrawals', [
            'shop'                => $shop,
            'withdrawals'         => $payouts,
            'payouts'             => $payouts,
            'pager'               => $result['pager'],
            'per_page'            => $perPage,
            'available_balance'   => $balanceData['available_balance'],
            'escrow_holding'      => $balanceData['escrow_holding'],
            'released_earnings'   => $balanceData['released_earnings'],
            'settled_records'     => $balanceData['settled_records'] ?? [],
            'escrow_records'      => $balanceData['escrow_records'] ?? [],
            'deduction_percent'   => (new SiteContentModel())->getPlatformDeductionPercent(),
            'is_gcash_complete'   => $isGcashComplete,
            'masked_gcash_number' => $maskedGcashNum,
            'gcash_account_name'  => $rawGcashName,
        ]);
    }

    /**
     * Compute shop owner's available balance and escrow holdings based strictly on GCash payments.
     * Cash and COD payments are directly collected in person by the merchant and are NOT withdrawable.
     * Only completed and delivered transactions paid via GCash are credited to Available Balance.
     * In-progress GCash orders/requests remain safely in Funds in Escrow until fulfilled.
     */
    public function getShopEscrowAndBalance(int $shopId): array
    {
        helper('status');
        $orderModel = new OrderModel();
        $db         = \Config\Database::connect();

        $orders = $orderModel->getOrdersByShop($shopId);
        $printingRequests = $db->table('printing_requests pr')
            ->select('pr.*, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = pr.customer_id', 'left')
            ->where('pr.shop_id', $shopId)
            ->get()->getResultArray();

        $releasedEarnings = 0.0;
        $escrowHolding    = 0.0;
        $settledRecords   = [];
        $escrowRecords    = [];

        // Batch pre-fetch all verified GCash payments in 1 query to avoid N+1 queries
        $orderIds = array_map('intval', array_column($orders, 'id'));
        $prIds    = array_map('intval', array_column($printingRequests, 'id'));
        $orderPaymentsMap = [];
        $prPaymentsMap    = [];

        if (!empty($orderIds) || !empty($prIds)) {
            $pBuilder = $db->table('payments')
                ->select('payable_type, payable_id, SUM(amount) as total_amount')
                ->where('status', 'verified')
                ->where('LOWER(method)', 'gcash')
                ->groupStart();

            if (!empty($orderIds)) {
                $pBuilder->groupStart()
                    ->where('payable_type', 'order')
                    ->whereIn('payable_id', $orderIds)
                    ->groupEnd();
            }
            if (!empty($prIds)) {
                if (!empty($orderIds)) {
                    $pBuilder->orGroupStart()
                        ->where('payable_type', 'printing_request')
                        ->whereIn('payable_id', $prIds)
                        ->groupEnd();
                } else {
                    $pBuilder->groupStart()
                        ->where('payable_type', 'printing_request')
                        ->whereIn('payable_id', $prIds)
                        ->groupEnd();
                }
            }
            $pBuilder->groupEnd();

            $pRows = $pBuilder->groupBy('payable_type, payable_id')->get()->getResultArray();
            foreach ($pRows as $pRow) {
                if ($pRow['payable_type'] === 'order') {
                    $orderPaymentsMap[(int) $pRow['payable_id']] = (float) $pRow['total_amount'];
                } elseif ($pRow['payable_type'] === 'printing_request') {
                    $prPaymentsMap[(int) $pRow['payable_id']] = (float) $pRow['total_amount'];
                }
            }
        }

        // 1. Process Product Orders
        foreach ($orders as $o) {
            $status = strtolower(trim((string) ($o['status'] ?? '')));
            if ($status === 'cancelled' || !empty($o['cancelled_at'])) {
                continue;
            }

            // Check verified GCash records from pre-fetched payments map
            $verifiedOrderGcash = (float) ($orderPaymentsMap[(int) $o['id']] ?? 0.0);

            $orderPayMethod = strtolower(trim((string) ($o['payment_method'] ?? '')));
            $isOnlineGcash  = in_array($orderPayMethod, ['gcash', 'paymongo', 'online_gcash'], true) 
                && strtolower(trim((string) ($o['payment_status'] ?? ''))) === 'paid';

            $posPayMethod = strtolower(trim((string) ($o['pos_payment_method'] ?? '')));
            $isPosGcash   = ($posPayMethod === 'gcash') 
                && in_array(strtolower(trim((string) ($o['pos_payment_status'] ?? ''))), ['paid', 'completed'], true);

            $gcashAmount = 0.0;
            if ($verifiedOrderGcash > 0) {
                $gcashAmount = $verifiedOrderGcash;
            } else {
                if ($isOnlineGcash) {
                    $gcashAmount += (float) ($o['subtotal'] ?? ($o['total_amount'] ?? 0));
                }
                if ($isPosGcash && !$isOnlineGcash) {
                    $gcashAmount += (float) ($o['total_amount'] ?? 0);
                } elseif ($isPosGcash && $isOnlineGcash && (float) ($o['pos_additional_amount'] ?? 0) > 0) {
                    $gcashAmount += (float) $o['pos_additional_amount'];
                }
            }

            // If not paid via GCash (e.g. COD, Pickup Cash, Counter Cash), skip
            if ($gcashAmount <= 0) {
                continue;
            }

            $custName = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
            if ($custName === '' && !empty($o['cancel_reason']) && str_starts_with($o['cancel_reason'], 'Walk-in:')) {
                $custName = substr($o['cancel_reason'], 8);
            }
            if ($custName === '') {
                $custName = 'Counter Customer';
            }

            $channel = $isOnlineGcash ? 'Online PayMongo GCash' : ($isPosGcash ? 'Counter POS (GCash QR)' : 'GCash');

            if (in_array($status, ['delivered', 'completed'], true)) {
                $releasedEarnings += $gcashAmount;
                $settledRecords[] = [
                    'id'            => (int) $o['id'],
                    'reference'     => (string) ($o['order_number'] ?? ('#' . $o['id'])),
                    'type'          => 'order',
                    'type_label'    => 'Product Order',
                    'customer_name' => $custName,
                    'channel'       => $channel,
                    'amount'        => $gcashAmount,
                    'status'        => 'completed',
                    'status_label'  => 'Completed & Released',
                    'completed_at'  => $o['completed_at'] ?? $o['placed_at'] ?? date('Y-m-d H:i:s'),
                ];
            } else {
                $escrowHolding += $gcashAmount;
                $escrowRecords[] = [
                    'id'            => (int) $o['id'],
                    'reference'     => (string) ($o['order_number'] ?? ('#' . $o['id'])),
                    'type'          => 'order',
                    'type_label'    => 'Product Order',
                    'customer_name' => $custName,
                    'channel'       => $channel,
                    'amount'        => $gcashAmount,
                    'status'        => $status,
                    'status_label'  => 'Held in Escrow (' . humanize_status($status) . ')',
                    'placed_at'     => $o['placed_at'] ?? date('Y-m-d H:i:s'),
                ];
            }
        }

        // 2. Process Printing Requests
        foreach ($printingRequests as $pr) {
            $status = strtolower(trim((string) ($pr['status'] ?? '')));
            if ($status === 'cancelled') {
                continue;
            }

            // Check verified GCash records from pre-fetched payments map
            $verifiedPrGcash = (float) ($prPaymentsMap[(int) $pr['id']] ?? 0.0);

            $prGcashAmount = 0.0;
            if ($verifiedPrGcash > 0) {
                $prGcashAmount = $verifiedPrGcash;
            } elseif ((float) ($pr['down_payment'] ?? 0) > 0) {
                // Online down payment through marketplace checkout is via PayMongo GCash
                $prGcashAmount = (float) $pr['down_payment'];
            }

            // If not paid via GCash, skip
            if ($prGcashAmount <= 0) {
                continue;
            }

            $custName = trim(($pr['first_name'] ?? '') . ' ' . ($pr['last_name'] ?? ''));
            if ($custName === '') {
                $custName = 'Online Customer';
            }

            $fileName = !empty($pr['file_name']) ? $pr['file_name'] : 'Document.pdf';

            if ($status === 'completed') {
                $releasedEarnings += $prGcashAmount;
                $settledRecords[] = [
                    'id'            => (int) $pr['id'],
                    'reference'     => (string) ($pr['request_number'] ?? ('PR-' . $pr['id'])),
                    'type'          => 'printing',
                    'type_label'    => 'Printing Request',
                    'customer_name' => $custName,
                    'file_name'     => $fileName,
                    'channel'       => 'GCash (Printing)',
                    'amount'        => $prGcashAmount,
                    'status'        => 'completed',
                    'status_label'  => 'Completed & Released',
                    'completed_at'  => $pr['completed_at'] ?? $pr['created_at'] ?? date('Y-m-d H:i:s'),
                ];
            } else {
                $escrowHolding += $prGcashAmount;
                $escrowRecords[] = [
                    'id'            => (int) $pr['id'],
                    'reference'     => (string) ($pr['request_number'] ?? ('PR-' . $pr['id'])),
                    'type'          => 'printing',
                    'type_label'    => 'Printing Request',
                    'customer_name' => $custName,
                    'file_name'     => $fileName,
                    'channel'       => 'GCash (Printing Escrow)',
                    'amount'        => $prGcashAmount,
                    'status'        => $status,
                    'status_label'  => 'Held in Escrow (' . humanize_status($status) . ')',
                    'placed_at'     => $pr['created_at'] ?? date('Y-m-d H:i:s'),
                ];
            }
        }

        // Sort records by timestamp descending
        usort($settledRecords, static function ($a, $b) {
            return strtotime($b['completed_at']) <=> strtotime($a['completed_at']);
        });
        usort($escrowRecords, static function ($a, $b) {
            return strtotime($b['placed_at']) <=> strtotime($a['placed_at']);
        });

        // Deduct active payout requests (exclude rejected, failed, or cancelled)
        $payouts = $db->table('payout_requests')
            ->where('shop_id', $shopId)
            ->whereNotIn('status', ['rejected', 'failed', 'cancelled'])
            ->selectSum('amount')
            ->get()->getRowArray();
        $payoutDeductions = (float) ($payouts['amount'] ?? 0);

        $availableBalance = max(0.0, round($releasedEarnings - $payoutDeductions, 2));

        return [
            'available_balance' => $availableBalance,
            'escrow_holding'    => round($escrowHolding, 2),
            'released_earnings' => round($releasedEarnings, 2),
            'total_deductions'  => $payoutDeductions,
            'settled_records'   => $settledRecords,
            'escrow_records'    => $escrowRecords,
        ];
    }

    public function analytics()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $shop   = $res['shop'];

        $orderModel = new OrderModel();

        $range = (string) $this->request->getGet('range');
        $range = in_array($range, ['7', '30', 'year'], true) ? $range : '30';

        // ---- Summary ----
        $totalRevenue = $orderModel->getRevenueForShop($shopId);
        $validCount   = $orderModel->getOrderCountForShop($shopId, ['pending', 'processing', 'shipped', 'ready_for_pickup', 'delivered', 'completed']);
        $aov          = $validCount > 0 ? $totalRevenue / $validCount : 0.0;

        // ---- Sales by category ----
        $categorySales = $orderModel->getCategorySales($shopId);
        $catTotal      = array_sum(array_column($categorySales, 'revenue'));
        foreach ($categorySales as &$c) {
            $c['pct'] = $catTotal > 0 ? round(((float) $c['revenue'] / $catTotal) * 100, 1) : 0.0;
        }
        unset($c);
        $topCategory = $categorySales[0]['category'] ?? '—';

        // ---- Order status distribution ----
        $statusDist = $orderModel->getOrderStatusDistribution($shopId);
        $statusTotal = array_sum(array_column($statusDist, 'count'));
        foreach ($statusDist as &$s) {
            $s['pct'] = $statusTotal > 0 ? round(((int) $s['count'] / $statusTotal) * 100, 1) : 0.0;
        }
        unset($s);

        // ---- Top products ----
        $topProducts = $orderModel->getTopProductsForShop($shopId, 5);

        // ---- Revenue over time (current + previous window) ----
        $chart = $orderModel->getSalesChartData($shopId, $range, true);
        $chartMax = max(array_merge($chart['values'], $chart['previous_values'] ?? []));

        return view('tenant/analytics', [
            'shop'                => $shop,
            'range'               => $range,
            'total_revenue'       => $totalRevenue,
            'total_orders'        => $orderModel->getOrderCountForShop($shopId),
            'avg_order_value'     => $aov,
            'avg_order_base'      => $validCount,
            'top_category'        => $topCategory,
            'category_sales'      => $categorySales,
            'status_dist'         => $statusDist,
            'status_total'        => $statusTotal,
            'top_products'        => $topProducts,
            'chart_labels'        => $chart['labels'],
            'chart_values'        => $chart['values'],
            'chart_previous'      => $chart['previous_values'] ?? [],
            'chart_previous_total'=> $chart['previous_total'] ?? 0.0,
            'chart_total'         => array_sum($chart['values']),
            'chart_max'           => $chartMax,
            'activeNav'           => 'analytics',
            'title'               => 'Analytics Dashboard',
        ]);
    }

    public function settings()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = $res['shopId'];
        $shop   = $res['shop'];

        return view('tenant/settings', [
            'shop'           => $shop,
            'businessHours'  => (new ShopBusinessHourModel())->getForShop($shopId),
            'notifPrefs'     => (new ShopNotificationPreferenceModel())->getForShop($shopId),
            'activeNav'      => 'settings',
            'title'          => 'Settings',
        ]);
    }

    public function archive()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $shop   = $res['shop'];

        // Automatically archive completed items older than 3 days
        (new ArchivedItemModel())->autoArchiveCompletedItems($shopId, 3);

        $db = \Config\Database::connect();
        $totalCount    = $db->table('archived_items')->where('shop_id', $shopId)->countAllResults();
        $ordersCount   = $db->table('archived_items')->where('shop_id', $shopId)->where('item_type', 'order')->countAllResults();
        $invCount      = $db->table('archived_items')->where('shop_id', $shopId)->where('item_type', 'inventory')->countAllResults();
        $printingCount = $db->table('archived_items')->where('shop_id', $shopId)->where('item_type', 'printing_request')->countAllResults();

        $type   = trim((string) $this->request->getGet('type'));
        $search = trim((string) $this->request->getGet('q'));
        $perPage = (int) ($this->request->getGet('per_page') ?: 10);
        if (!in_array($perPage, [5, 10, 15, 20], true)) {
            $perPage = 10;
        }
        $page   = max(1, (int) $this->request->getGet('page_archive'));

        $result = (new ArchivedItemModel())->getArchivedForShopPaginated($shopId, $perPage, $page, 'archive', $type, $search);
        $items  = $result['items'];

        // Enrich items with metadata
        foreach ($items as &$it) {
            $itemType = $it['item_type'] ?? '';
            $itemId   = (int) ($it['item_id'] ?? 0);

            $it['details'] = [];
            if ($itemType === 'inventory') {
                $prod = $db->table('products p')
                    ->select('p.id, p.name, p.price, p.stock_quantity, p.sku, c.name AS category_name, pi.image_url')
                    ->join('categories c', 'c.id = p.category_id', 'left')
                    ->join('product_images pi', 'pi.product_id = p.id AND pi.is_primary = 1', 'left')
                    ->where('p.id', $itemId)
                    ->get()->getRowArray();
                if ($prod) {
                    $it['details'] = [
                        'name'          => $prod['name'],
                        'price'         => (float) $prod['price'],
                        'stock'         => (int) $prod['stock_quantity'],
                        'sku'           => $prod['sku'] ?? '',
                        'category'      => $prod['category_name'] ?? 'General',
                        'image_url'     => $prod['image_url'] ?? null,
                    ];
                }
            } elseif ($itemType === 'order') {
                $ord = $db->table('orders o')
                    ->select('o.id, o.order_number, o.total_amount, o.status, o.payment_status, o.fulfillment_method, u.first_name, u.last_name, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count')
                    ->join('users u', 'u.id = o.customer_id', 'left')
                    ->where('o.id', $itemId)
                    ->get()->getRowArray();
                if ($ord) {
                    $custName = trim(($ord['first_name'] ?? '') . ' ' . ($ord['last_name'] ?? ''));
                    $it['details'] = [
                        'order_number'       => $ord['order_number'] ?? ('#' . $ord['id']),
                        'total_amount'       => (float) $ord['total_amount'],
                        'status'             => $ord['status'],
                        'payment_status'     => $ord['payment_status'],
                        'fulfillment_method' => $ord['fulfillment_method'],
                        'customer_name'      => $custName !== '' ? $custName : 'Customer',
                        'item_count'         => (int) ($ord['item_count'] ?? 1),
                    ];
                }
            } elseif ($itemType === 'printing_request') {
                $pr = $db->table('printing_requests pr')
                    ->select('pr.id, pr.request_number, pr.file_name, pr.total_price, pr.status, pr.page_count, pr.copies, u.first_name, u.last_name')
                    ->join('users u', 'u.id = pr.customer_id', 'left')
                    ->where('pr.id', $itemId)
                    ->get()->getRowArray();
                if ($pr) {
                    $custName = trim(($pr['first_name'] ?? '') . ' ' . ($pr['last_name'] ?? ''));
                    $it['details'] = [
                        'request_number' => $pr['request_number'] ?? ('PR-' . $pr['id']),
                        'file_name'      => $pr['file_name'] ?? 'Document.pdf',
                        'total_price'    => (float) $pr['total_price'],
                        'status'         => $pr['status'],
                        'page_count'     => (int) ($pr['page_count'] ?? 1),
                        'copies'         => (int) ($pr['copies'] ?? 1),
                        'customer_name'  => $custName !== '' ? $custName : 'Customer',
                    ];
                }
            }
        }
        unset($it);

        return view('tenant/archive', [
            'shop'          => $shop,
            'archivedItems' => $items,
            'pager'         => $result['pager'],
            'per_page'      => $perPage,
            'activeNav'     => 'archive',
            'title'         => 'Archive & Recovery',
            'activeType'    => $type,
            'searchQuery'   => $search,
            'kpis'          => [
                'total'     => $totalCount,
                'orders'    => $ordersCount,
                'inventory' => $invCount,
                'printing'  => $printingCount,
            ],
        ]);
    }

    public function updateOrderStatus()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $orderId = (int) $this->request->getPost('order_id');
        $status  = $this->request->getPost('status');

        $allowed = ['pending', 'processing', 'shipped', 'ready_for_pickup', 'delivered', 'completed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return redirect()->back()->with('error', 'Invalid order status.');
        }

        $orderModel = new OrderModel();
        $order      = $orderModel->find($orderId);
        if (!$order || (int) $order['shop_id'] !== $shopId) {
            return redirect()->back()->with('error', 'Order not found.');
        }

        $fulfillmentMethod = $order['fulfillment_method'] ?? 'delivery';

        // Constrain status transitions dynamically based on fulfillment type
        if ($fulfillmentMethod === 'pickup') {
            $allowedForPickup = ['pending', 'processing', 'ready_for_pickup', 'completed', 'cancelled'];
            if (!in_array($status, $allowedForPickup, true)) {
                return redirect()->back()->with('error', 'Invalid status for Store Pick-up order. Store Pick-up orders cannot be set to "' . humanize_status($status) . '".');
            }
        } else {
            // Doorstep Delivery
            $allowedForDelivery = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
            if (!in_array($status, $allowedForDelivery, true)) {
                return redirect()->back()->with('error', 'Invalid status for Doorstep Delivery order. Delivery orders cannot be set to "' . humanize_status($status) . '".');
            }
        }

        $data = ['status' => $status];
        if ($status === 'completed' || $status === 'delivered') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        if ($status === 'cancelled') {
            $data['cancelled_at'] = date('Y-m-d H:i:s');
        }
        $orderModel->update($orderId, $data);

        $shop = (new ShopModel())->find($shopId);

        // Notify customer of order status change (in-app)
        $customerId = (int) ($order['customer_id'] ?? 0);
        if ($customerId > 0) {
            $orderNum = $order['order_number'] ?? ('ORD-' . $orderId);
            $shopName = $shop['shop_name'] ?? 'the shop';

            $firstItem = (new OrderItemModel())->where('order_id', $orderId)->first();
            $itemTitle = !empty($firstItem['product_name']) ? $firstItem['product_name'] : '';
            $orderDesc = $itemTitle !== '' ? "Your order #{$orderNum} ({$itemTitle})" : "Your order #{$orderNum}";

            $statusLabels = [
                'processing'       => ['Order is Processing', "{$orderDesc} is now being processed by {$shopName}."],
                'shipped'          => ['Order is Shipped', "{$orderDesc} has been shipped and is on its way."],
                'ready_for_pickup' => ['Order Ready for Pick-up', "{$orderDesc} is ready for pick-up at {$shopName}."],
                'delivered'        => ['Order Delivered', "{$orderDesc} has been marked as delivered."],
                'completed'        => ['Order Completed', "{$orderDesc} has been completed. Thank you!"],
                'cancelled'        => ['Order Cancelled', "{$orderDesc} has been cancelled."],
            ];

            if (isset($statusLabels[$status])) {
                [$notifTitle, $notifMessage] = $statusLabels[$status];
                (new NotificationModel())->create(
                    $customerId,
                    'order_status',
                    $notifTitle,
                    $notifMessage,
                    '/customer/orders'
                );
            }

            // Task 3: Prompt customer to rate the product on completion/delivery
            if (in_array($status, ['completed', 'delivered'], true)) {
                $ratingMsg = $itemTitle !== ''
                    ? "How was \"{$itemTitle}\" from {$shopName}? Leave a rating to help other shoppers!"
                    : "How was your order from {$shopName}? Leave a rating to help other shoppers!";
                (new NotificationModel())->create(
                    $customerId,
                    'review_prompt',
                    '⭐ Rate Your Purchase',
                    $ratingMsg,
                    '/customer/orders'
                );
            }
        }

        // Notify shop owner of status update (in-app)
        if ($shop && !empty($shop['owner_id']) && in_array($status, ['processing', 'shipped', 'ready_for_pickup'], true)) {
            $ownerLabels = [
                'processing'       => 'Order Processing',
                'shipped'          => 'Order Shipped',
                'ready_for_pickup' => 'Order Ready for Pick-up',
            ];
            $oTitle = $ownerLabels[$status] ?? ucfirst($status);
            (new NotificationModel())->create(
                (int) $shop['owner_id'],
                'order_status',
                "Order #{$orderNum} {$oTitle}",
                "Order #{$orderNum} has been updated to {$oTitle}.",
                '/tenant/orders'
            );
        }

        $deliveryRecord = null;
        if ($status === 'shipped') {
            $deliveryModel = new DeliveryModel();
            $existing = $deliveryModel
                ->where('deliverable_type', 'order')
                ->where('deliverable_id', $orderId)
                ->first();

            if (!$existing) {
                $customer = (new UserModel())->find($order['customer_id']);
                $address  = !empty($customer['address']) ? $customer['address'] : 'Customer Shipping Address';

                // Determine real starting coordinates from the shop
                $startLat = isset($shop['latitude']) && $shop['latitude'] !== null ? (float) $shop['latitude'] : null;
                $startLng = isset($shop['longitude']) && $shop['longitude'] !== null ? (float) $shop['longitude'] : null;

                if (!DeliveryModel::isPolomolokCoordinate($startLat, $startLng)) {
                    // Attempt on-the-fly geocoding for the shop address
                    $shopAddrParts = array_filter([
                        $shop['street'] ?? '',
                        $shop['barangay'] ?? '',
                        $shop['city'] ?? 'Polomolok',
                        $shop['province'] ?? 'South Cotabato',
                    ]);
                    $shopFullAddress = implode(', ', $shopAddrParts);
                    if ($shopFullAddress !== '') {
                        $geo = (new \App\Services\GoogleMapsService())->geocodeAddress($shopFullAddress);
                        if ($geo && DeliveryModel::isPolomolokCoordinate($geo['lat'], $geo['lng'])) {
                            $startLat = $geo['lat'];
                            $startLng = $geo['lng'];
                            (new ShopModel())->update($shopId, [
                                'latitude'    => $startLat,
                                'longitude'   => $startLng,
                                'geocoded_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                }

                // Fall back to Polomolok town center default only as last resort — never Manila
                if (!DeliveryModel::isPolomolokCoordinate($startLat, $startLng)) {
                    $gConfig = config('GoogleMaps');
                    $startLat = $gConfig->defaultLat ?? DeliveryModel::POLOMOLOK_CENTER_LAT;
                    $startLng = $gConfig->defaultLng ?? DeliveryModel::POLOMOLOK_CENTER_LNG;
                }

                $delId = $deliveryModel->insert([
                    'deliverable_type'    => 'order',
                    'deliverable_id'      => $orderId,
                    'tracking_id'         => 'TRK-' . strtoupper(substr(md5($orderId . time()), 0, 8)),
                    'courier_name'        => 'Standard Courier',
                    'destination_address' => $address,
                    'current_lat'         => $startLat,
                    'current_lng'         => $startLng,
                    'location_updated_at' => date('Y-m-d H:i:s'),
                    'status'              => 'shipped',
                    'shipped_at'          => date('Y-m-d H:i:s'),
                    'created_at'          => date('Y-m-d H:i:s'),
                ]);
                $deliveryRecord = $deliveryModel->find($delId);
            } else {
                $deliveryModel->update($existing['id'], [
                    'status'     => 'shipped',
                    'shipped_at' => date('Y-m-d H:i:s'),
                ]);
                $deliveryRecord = $deliveryModel->find($existing['id']);
            }
        }

        // TextBee SMS Dispatch (Fires on 'shipped' for Delivery or 'ready_for_pickup' for Pick-up)
        if (in_array($status, ['shipped', 'ready_for_pickup'], true)) {
            try {
                $textBee = new \App\Services\TextBeeService();
                $textBee->sendOrderNotification($order, $status, $shop, $deliveryRecord);
            } catch (\Throwable $e) {
                log_message('error', '[Tenant::updateOrderStatus] TextBee SMS error: ' . $e->getMessage());
            }
        }

        if ($status === 'shipped') {
            return redirect()->to(base_url('tenant/deliveries'))->with('success', 'Order #' . ($order['order_number'] ?? $orderId) . ' status updated to Shipped and transferred to Delivery module.');
        }

        if ($status === 'ready_for_pickup') {
            return redirect()->back()->with('success', 'Order #' . ($order['order_number'] ?? $orderId) . ' is now marked Ready for Pick-up! Customer has been notified.');
        }

        if ($status === 'processing') {
            return redirect()->back()->with('success', 'Order #' . ($order['order_number'] ?? $orderId) . ' is now Processing. You can now generate and download its QR Code.');
        }

        return redirect()->back()->with('success', 'Order status updated.');
    }

    public function updatePrintingStatus()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId    = (int) $res['shopId'];
        $requestId = (int) $this->request->getPost('request_id');
        $status    = (string) $this->request->getPost('status');

        // Accept both the legacy UI values and the DB enum directly.
        $map = [
            'pending'            => 'new',
            'processing'         => 'in_production',
            'ready'              => 'ready_for_pickup',
            'new'                => 'new',
            'in_production'      => 'in_production',
            'ready_for_pickup'   => 'ready_for_pickup',
            'ready_for_delivery' => 'ready_for_delivery',
            'completed'          => 'completed',
            'cancelled'          => 'cancelled',
        ];
        $target = $map[$status] ?? null;
        $allowed = ['new', 'in_production', 'ready_for_pickup', 'ready_for_delivery', 'completed', 'cancelled'];
        if (!in_array($target, $allowed, true)) {
            return redirect()->back()->with('error', 'Invalid printing status.');
        }

        $prModel = new PrintingRequestModel();
        $row     = $prModel->find($requestId);
        if (!$row || (int) $row['shop_id'] !== $shopId) {
            return redirect()->back()->with('error', 'Printing request not found.');
        }

        $current = (string) $row['status'];
        if (!$this->canTransitionPrint($current, $target)) {
            return redirect()->back()->with('error', 'Invalid status transition from "' . humanize_status($current) . '" to "' . humanize_status($target) . '".');
        }

        $data = ['status' => $target, 'progress_percent' => $this->progressForStatus($target)];
        if ($target === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        $prModel->update($requestId, $data);

        // Sync corresponding delivery record status
        $deliveryModel = new DeliveryModel();
        $isDelivery    = ($row['fulfillment_method'] ?? 'pickup') === 'delivery';
        $delStatus     = match ($target) {
            'ready_for_pickup'   => 'ready_for_pickup',
            'ready_for_delivery' => 'shipped',
            'completed'          => $isDelivery ? 'shipped' : 'ready_for_pickup',
            'cancelled'          => 'cancelled',
            default              => $isDelivery ? 'shipped' : 'ready_for_pickup',
        };
        $delRow = $deliveryModel->where('deliverable_type', 'printing_request')->where('deliverable_id', $requestId)->first();
        if ($delRow && !in_array($delRow['status'], ['delivered', 'returned'], true)) {
            $deliveryModel->update($delRow['id'], ['status' => $delStatus]);
            $delRow['status'] = $delStatus;
        }

        // Send customer in-app notification
        $reqNum = $row['request_number'] ?? ('PR-' . $requestId);
        $fileName = !empty($row['file_name']) ? $row['file_name'] : 'Document.pdf';
        $customerId = (int) ($row['customer_id'] ?? 0);
        if ($customerId > 0) {
            $notifTitle = match ($target) {
                'ready_for_pickup'   => 'Printing Ready for Pick-up',
                'ready_for_delivery' => 'Printing Order Shipped',
                'completed'          => $isDelivery ? 'Printing Shipped for Delivery' : 'Printing Completed & Ready for Pick-up',
                'in_production'      => 'Printing In Production',
                'cancelled'          => 'Printing Request Cancelled',
                default              => 'Printing Request Updated',
            };
            $notifMsg = match ($target) {
                'ready_for_pickup'   => "Your printing request for {$fileName} (#{$reqNum}) is ready for pick-up! Show your QR code at the shop.",
                'ready_for_delivery' => "Your printing request for {$fileName} (#{$reqNum}) has been printed and shipped for delivery!",
                'completed'          => $isDelivery ? "Your printing request for {$fileName} (#{$reqNum}) has been completed and is out for delivery!" : "Your printing request for {$fileName} (#{$reqNum}) is completed! Please bring your Pick-up QR code to collect your order.",
                'in_production'      => "Your printing request for {$fileName} (#{$reqNum}) is now being printed in production.",
                'cancelled'          => "Your printing request for {$fileName} (#{$reqNum}) has been cancelled.",
                default              => "Your printing request for {$fileName} (#{$reqNum}) status has been updated to " . humanize_status($target) . ".",
            };
            (new NotificationModel())->create($customerId, 'printing', $notifTitle, $notifMsg, '/customer/printing');
        }

        // TextBee SMS Dispatch for Printing Requests (ready_for_pickup or ready_for_delivery / completed delivery)
        try {
            $shop = (new ShopModel())->find($shopId);
            $textBee = new \App\Services\TextBeeService();
            $textBee->sendPrintingNotification($row, $target, $shop, $delRow);
        } catch (\Throwable $e) {
            log_message('error', '[Tenant::updatePrintingStatus] TextBee SMS error: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Printing request status updated.');
    }

    /**
     * Printing requests move forward only: new -> in_production ->
     * ready_for_pickup | ready_for_delivery -> completed. Cancellation is
     * allowed from any non-terminal state; completed/cancelled are terminal.
     */
    private function canTransitionPrint(string $current, string $target): bool
    {
        if ($current === $target) {
            return true;
        }

        if (in_array($current, ['completed', 'cancelled'], true)) {
            return false;
        }

        if ($target === 'cancelled') {
            return true;
        }

        $level = [
            'new'                => 0,
            'in_production'      => 1,
            'ready_for_pickup'   => 2,
            'ready_for_delivery' => 2,
            'in_transit'         => 2,
            'completed'          => 3,
        ];
        if (!isset($level[$current]) || !isset($level[$target])) {
            return false;
        }

        $isPeer = in_array($current, ['ready_for_pickup', 'ready_for_delivery', 'in_transit'], true)
            && in_array($target, ['ready_for_pickup', 'ready_for_delivery', 'in_transit'], true);

        return $isPeer || $level[$target] > $level[$current];
    }

    /**
     * Helper to resolve the physical file path for a printing request.
     * Checks WRITEPATH and FCPATH candidate paths and ensures security containment.
     */
    protected function resolvePrintFilePath(array $row): ?string
    {
        $rawUrl = (string) ($row['file_url'] ?? '');
        if (trim($rawUrl) === '') {
            return null;
        }

        if (str_starts_with($rawUrl, 'http://') || str_starts_with($rawUrl, 'https://')) {
            return null;
        }

        $clean = ltrim(str_replace(['\\', '//'], '/', $rawUrl), '/');
        $baseName = basename($clean);

        $candidates = [
            WRITEPATH . 'uploads/printing/' . $baseName,
            WRITEPATH . (str_starts_with($clean, 'writable/') ? substr($clean, strlen('writable/')) : $clean),
            WRITEPATH . $clean,
            WRITEPATH . 'uploads/' . $baseName,
            FCPATH . $clean,
            FCPATH . 'uploads/printing/' . $baseName,
            FCPATH . 'uploads/printing_attachments/' . $baseName,
        ];

        $rootWrite = realpath(WRITEPATH);
        $rootFc    = realpath(FCPATH);

        foreach ($candidates as $cand) {
            $real = realpath($cand);
            if ($real !== false && is_file($real)) {
                if (($rootWrite && str_starts_with($real . DIRECTORY_SEPARATOR, $rootWrite . DIRECTORY_SEPARATOR)) ||
                    ($rootFc && str_starts_with($real . DIRECTORY_SEPARATOR, $rootFc . DIRECTORY_SEPARATOR))) {
                    return $real;
                }
            }
        }

        return null;
    }

    /**
     * Secure file download for a printing request.
     */
    public function downloadPrintFile($requestId)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];

        $prModel = new PrintingRequestModel();
        $row     = $prModel->find((int) $requestId);
        if (!$row || (int) $row['shop_id'] !== $shopId) {
            return redirect()->to(base_url('tenant/printing'))->with('error', 'Printing request not found.');
        }

        $fileUrl = trim((string) ($row['file_url'] ?? ''));
        $name = trim((string) ($row['file_name'] ?? ''));

        if (str_starts_with($fileUrl, 'http://') || str_starts_with($fileUrl, 'https://')) {
            $context = stream_context_create([
                'http' => ['timeout' => 30, 'follow_location' => 1],
                'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $content = @file_get_contents($fileUrl, false, $context);
            if ($content !== false) {
                if ($name === '') {
                    $name = basename(parse_url($fileUrl, PHP_URL_PATH) ?: 'document');
                }
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'pdf'         => 'application/pdf',
                    'doc'         => 'application/msword',
                    'docx'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png'         => 'image/png',
                    'webp'        => 'image/webp',
                    default       => 'application/octet-stream',
                };
                return $this->response
                    ->setContentType($mime)
                    ->setHeader('Content-Disposition', 'attachment; filename="' . str_replace('"', '', $name) . '"')
                    ->setHeader('Content-Length', (string) strlen($content))
                    ->setBody($content);
            }
            return redirect()->to($fileUrl);
        }

        $real = $this->resolvePrintFilePath($row);
        if (!$real) {
            return redirect()->to(base_url('tenant/printing'))->with('error', 'The printing file is missing or not accessible on the server.');
        }

        if ($name === '') {
            $name = basename($real);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? (finfo_file($finfo, $real) ?: 'application/octet-stream') : 'application/octet-stream';
        if ($finfo) {
            finfo_close($finfo);
        }

        return $this->response
            ->setContentType($mime)
            ->setHeader('Content-Disposition', 'attachment; filename="' . str_replace('"', '', $name) . '"')
            ->setHeader('Content-Length', (string) filesize($real))
            ->setBody((string) file_get_contents($real));
    }

    /**
     * Inline document preview for a printing request.
     */
    public function viewPrintFile($requestId)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];

        $prModel = new PrintingRequestModel();
        $row     = $prModel->find((int) $requestId);
        if (!$row || (int) $row['shop_id'] !== $shopId) {
            return redirect()->to(base_url('tenant/printing'))->with('error', 'Printing request not found.');
        }

        $fileUrl = trim((string) ($row['file_url'] ?? ''));
        $name = trim((string) ($row['file_name'] ?? ''));

        if (str_starts_with($fileUrl, 'http://') || str_starts_with($fileUrl, 'https://')) {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext === 'docx' || $ext === 'doc') {
                return redirect()->to($fileUrl);
            }
            $context = stream_context_create([
                'http' => ['timeout' => 30, 'follow_location' => 1],
                'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $content = @file_get_contents($fileUrl, false, $context);
            if ($content !== false) {
                if ($name === '') {
                    $name = basename(parse_url($fileUrl, PHP_URL_PATH) ?: 'document.pdf');
                }
                $mime = match ($ext) {
                    'pdf'         => 'application/pdf',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png'         => 'image/png',
                    'webp'        => 'image/webp',
                    default       => 'application/pdf',
                };
                return $this->response
                    ->setContentType($mime)
                    ->setHeader('Content-Disposition', 'inline; filename="' . str_replace('"', '', $name) . '"')
                    ->setHeader('Content-Length', (string) strlen($content))
                    ->setBody($content);
            }
            return redirect()->to($fileUrl);
        }

        $real = $this->resolvePrintFilePath($row);
        if (!$real) {
            return redirect()->to(base_url('tenant/printing'))->with('error', 'The printing file is missing or not accessible on the server.');
        }

        if ($name === '') {
            $name = basename($real);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? (finfo_file($finfo, $real) ?: 'application/pdf') : 'application/pdf';
        if ($finfo) {
            finfo_close($finfo);
        }

        return $this->response
            ->setContentType($mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . str_replace('"', '', $name) . '"')
            ->setHeader('Content-Length', (string) filesize($real))
            ->setBody((string) file_get_contents($real));
    }

    /**
     * Save printing settings for the current shop (prices, bindings, down payment, paper sizes).
     */
    public function savePrintingSettings()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
            }
            return $res;
        }

        $shopId = (int) $res['shopId'];

        $downPaymentPercent = (float) ($this->request->getPost('down_payment_percent') ?? 50.00);
        $priceStaple        = (float) ($this->request->getPost('price_staple') ?? 10.00);
        $priceSpiral        = (float) ($this->request->getPost('price_spiral') ?? 35.00);
        $priceColor         = (float) ($this->request->getPost('price_color_per_page') ?? 5.00);
        $priceBw            = (float) ($this->request->getPost('price_bw_per_page') ?? 2.00);

        $settingModel = new \App\Models\ShopPrintingSettingModel();
        $settingModel->saveForShop($shopId, [
            'down_payment_percent' => $downPaymentPercent,
            'price_staple'         => $priceStaple,
            'price_spiral'         => $priceSpiral,
            'price_color_per_page' => $priceColor,
            'price_bw_per_page'    => $priceBw,
        ]);

        $sizesInput = (array) $this->request->getPost('paper_sizes');
        $sizeModel  = new \App\Models\ShopPaperSizeSettingModel();
        $sizeModel->saveForShop($shopId, $sizesInput);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'message' => 'Printing settings saved successfully.']);
        }

        return redirect()->back()->with('success', 'Printing settings saved successfully.');
    }

    /**
     * Archive a single completed printing request (logged, not deleted).
     */
    public function archivePrintingRequest($requestId)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];

        $prModel = new PrintingRequestModel();
        $row     = $prModel->find((int) $requestId);
        if (!$row || (int) $row['shop_id'] !== $shopId) {
            return redirect()->back()->with('error', 'Printing request not found.');
        }

        $this->insertArchiveRow($shopId, 'printing_request', (int) $row['id'], (string) $row['request_number']);

        return redirect()->back()->with('success', 'Printing request archived.');
    }

    /**
     * Archive every completed printing request for the shop at once.
     */
    public function archiveAllCompleted()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId    = (int) $res['shopId'];
        $completed = (new PrintingRequestModel())
            ->where('shop_id', $shopId)
            ->where('status', 'completed')
            ->findAll();

        $count = 0;
        foreach ($completed as $c) {
            if ($this->insertArchiveRow($shopId, 'printing_request', (int) $c['id'], (string) $c['request_number'])) {
                $count++;
            }
        }

        return redirect()->back()->with('success', $count . ' completed request(s) archived.');
    }

    /**
     * Archive a single completed or delivered product order (logged, not deleted).
     */
    public function archiveOrder($orderId)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];

        $orderModel = new OrderModel();
        $row        = $orderModel->find((int) $orderId);
        if (!$row || (int) $row['shop_id'] !== $shopId) {
            return redirect()->back()->with('error', 'Order not found.');
        }

        if (!in_array($row['status'], ['completed', 'delivered'], true)) {
            return redirect()->back()->with('error', 'Only completed or delivered orders can be archived.');
        }

        $label = !empty($row['order_number']) ? ('#' . ltrim($row['order_number'], '#')) : ('#ORD-' . $row['id']);
        $this->insertArchiveRow($shopId, 'order', (int) $row['id'], $label);

        return redirect()->back()->with('success', 'Order archived successfully.');
    }

    /**
     * Archive every completed/delivered order for the shop at once.
     */
    public function archiveAllCompletedOrders()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId    = (int) $res['shopId'];
        $completed = (new OrderModel())
            ->where('shop_id', $shopId)
            ->whereIn('status', ['completed', 'delivered'])
            ->findAll();

        $count = 0;
        foreach ($completed as $c) {
            $label = !empty($c['order_number']) ? ('#' . ltrim($c['order_number'], '#')) : ('#ORD-' . $c['id']);
            if ($this->insertArchiveRow($shopId, 'order', (int) $c['id'], $label)) {
                $count++;
            }
        }

        return redirect()->back()->with('success', $count . ' completed order(s) archived.');
    }

    private function insertArchiveRow(int $shopId, string $itemType, int $itemId, string $label): bool
    {
        $exists = (new ArchivedItemModel())
            ->where('shop_id', $shopId)
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->first();

        if ($exists) {
            return false;
        }

        (new ArchivedItemModel())->insert([
            'shop_id'     => $shopId,
            'item_type'   => $itemType,
            'item_id'     => $itemId,
            'item_label'  => $label,
            'archived_by' => (int) session()->get('user_id'),
        ]);

        return true;
    }

    public function updateDeliveryStatus()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId     = (int) $res['shopId'];
        $deliveryId = (int) $this->request->getPost('delivery_id');
        $status     = (string) $this->request->getPost('delivery_status');

        // Accept both the DB enum values directly and the legacy UI aliases.
        $map = [
            'pending'          => 'ready_for_pickup',
            'ready_for_pickup' => 'ready_for_pickup',
            'dispatched'       => 'shipped',
            'shipped'          => 'shipped',
            'in_transit'       => 'in_transit',
            'delivered'        => 'delivered',
            'returned'         => 'returned',
            'cancelled'        => 'cancelled',
        ];
        $dbStatus = $map[$status] ?? null;
        if ($dbStatus === null) {
            return redirect()->back()->with('error', 'Invalid delivery status.');
        }

        $deliveryModel = new DeliveryModel();
        $row           = $deliveryModel->find($deliveryId);
        if (!$row || $deliveryModel->resolveShopId($deliveryId) !== $shopId) {
            return redirect()->back()->with('error', 'Delivery not found.');
        }

        $data = ['status' => $dbStatus];
        if ($dbStatus === 'shipped' && empty($row['shipped_at'])) {
            $data['shipped_at'] = date('Y-m-d H:i:s');
        }
        if ($dbStatus === 'delivered' && empty($row['delivered_at'])) {
            $data['delivered_at'] = date('Y-m-d H:i:s');
        }
        $deliveryModel->update($deliveryId, $data);

        // Synchronize linked order or printing request
        if ($row['deliverable_type'] === 'order') {
            if ($dbStatus === 'delivered') {
                (new OrderModel())->update($row['deliverable_id'], ['status' => 'delivered', 'completed_at' => date('Y-m-d H:i:s')]);
            } elseif ($dbStatus === 'returned') {
                (new OrderModel())->update($row['deliverable_id'], ['status' => 'returned']);
            } elseif ($dbStatus === 'shipped') {
                (new OrderModel())->update($row['deliverable_id'], ['status' => 'shipped']);
            } elseif ($dbStatus === 'in_transit') {
                (new OrderModel())->update($row['deliverable_id'], ['status' => 'in_transit']);
            } elseif ($dbStatus === 'cancelled') {
                (new OrderModel())->update($row['deliverable_id'], ['status' => 'cancelled']);
            }
        } elseif ($row['deliverable_type'] === 'printing_request') {
            if ($dbStatus === 'delivered') {
                (new PrintingRequestModel())->update($row['deliverable_id'], ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);
            } elseif ($dbStatus === 'cancelled') {
                (new PrintingRequestModel())->update($row['deliverable_id'], ['status' => 'cancelled']);
            } elseif ($dbStatus === 'shipped') {
                (new PrintingRequestModel())->update($row['deliverable_id'], ['status' => 'ready_for_delivery']);
            } elseif ($dbStatus === 'in_transit') {
                (new PrintingRequestModel())->update($row['deliverable_id'], ['status' => 'in_transit']);
            }
        }

        return redirect()->back()->with('success', 'Delivery status updated.');
    }

    /**
     * Batch advance all 'shipped' deliveries for this tenant's shop to 'in_transit'.
     */
    public function bulkInTransit()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $db     = \Config\Database::connect();

        $rows = $db->table('deliveries d')
            ->select('d.id, d.deliverable_type, d.deliverable_id')
            ->join('orders o', "o.id = d.deliverable_id AND d.deliverable_type = 'order'", 'left')
            ->join('printing_requests pr', "pr.id = d.deliverable_id AND d.deliverable_type = 'printing_request'", 'left')
            ->where('d.status', 'shipped')
            ->groupStart()
                ->where('o.shop_id', $shopId)
                ->orWhere('pr.shop_id', $shopId)
            ->groupEnd()
            ->get()->getResultArray();

        if (empty($rows)) {
            return redirect()->back()->with('info', 'No shipped packages to update.');
        }

        $delIds   = array_column($rows, 'id');
        $now      = date('Y-m-d H:i:s');

        // 1. Update deliveries to in_transit
        $db->table('deliveries')->whereIn('id', $delIds)->update([
            'status'              => 'in_transit',
            'location_updated_at' => $now,
        ]);

        // 2. Synchronize linked orders and printing requests
        $orderIds = [];
        $prIds    = [];
        foreach ($rows as $r) {
            if ($r['deliverable_type'] === 'order') {
                $orderIds[] = (int) $r['deliverable_id'];
            } elseif ($r['deliverable_type'] === 'printing_request') {
                $prIds[] = (int) $r['deliverable_id'];
            }
        }

        if (!empty($orderIds)) {
            $db->table('orders')->whereIn('id', $orderIds)->update([
                'status' => 'in_transit',
            ]);
        }

        if (!empty($prIds)) {
            $db->table('printing_requests')->whereIn('id', $prIds)->update([
                'status' => 'in_transit',
            ]);
        }

        return redirect()->back()->with('success', count($delIds) . ' shipped package(s) updated to In Transit.');
    }

    public function requestWithdrawal()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $shopModel = new ShopModel();
        $shop = $shopModel->find($shopId);

        // Pre-condition: Verify authoritative GCash info from database
        $rawGcashNum  = preg_replace('/\D/', '', (string) ($shop['gcash_number'] ?? ''));
        $rawGcashName = trim((string) ($shop['gcash_account_name'] ?? ''));

        if ($rawGcashName === '' || mb_strlen($rawGcashName) < 2 || !preg_match('/^09\d{9}$/', $rawGcashNum)) {
            return redirect()->to('/tenant/settings?tab=payment')->with('error', 'Please complete your GCash account information before requesting a withdrawal.');
        }

        $amount = (float) $this->request->getPost('amount');
        if ($amount < 20) {
            return redirect()->back()->with('error', 'Minimum withdrawal amount is ₱20.00.');
        }

        $db = \Config\Database::connect();
        $lockName = 'withdrawal_lock_shop_' . $shopId;
        $lockAcquired = false;

        try {
            $lockRes = $db->query('SELECT GET_LOCK(?, 5) AS lock_acquired', [$lockName])->getRowArray();
            $lockAcquired = ((int) ($lockRes['lock_acquired'] ?? 0)) === 1;

            if (!$lockAcquired) {
                return redirect()->back()->with('error', 'Another withdrawal operation is currently in progress for your shop. Please try again.');
            }

            $db->transBegin();

            // Re-evaluate available balance inside transaction under advisory lock
            $balanceData = $this->getShopEscrowAndBalance($shopId);
            $availableBalance = (float) $balanceData['available_balance'];

            if ($amount > $availableBalance) {
                $db->transRollback();
                return redirect()->back()->with('error', 'Amount exceeds your available balance (₱' . number_format($availableBalance, 2) . '). Funds awaiting delivery are held in escrow.');
            }

            $deductionPercent = (new SiteContentModel())->getPlatformDeductionPercent();
            $fee = round($amount * ($deductionPercent / 100), 2);
            $netAmount = max(0.0, round($amount - $fee, 2));
            $refNumber = 'WD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

            $insertData = [
                'shop_id'                => $shopId,
                'reference_number'       => $refNumber,
                'amount'                 => $amount,
                'destination_method'     => 'gcash',
                'destination_detail'     => $rawGcashNum,          // Immutable recipient number snapshot
                'recipient_account_name' => $rawGcashName,         // Immutable recipient name snapshot
                'recipient_institution'  => 'G-Xchange, Inc.',     // Immutable institution snapshot
                'fee'                    => $fee,
                'deduction_percent'      => $deductionPercent,
                'net_amount'             => $netAmount,
                'status'                 => 'pending',
                'requested_at'           => date('Y-m-d H:i:s'),
            ];

            $db->table('payout_requests')->insert($insertData);
            $payoutId = $db->insertID();

            (new AuditLogModel())->log(
                (int) session()->get('user_id'),
                'tenant',
                'Requested Withdrawal',
                'payout_request',
                'success',
                $payoutId
            );

            (new NotificationModel())->create(
                (int) session()->get('user_id'),
                'payout',
                'Withdrawal Requested',
                "Your GCash withdrawal request #{$refNumber} for ₱" . number_format($amount, 2) . " (Net: ₱" . number_format($netAmount, 2) . ") has been submitted.",
                '/tenant/withdrawals'
            );

            $db->transCommit();
            return redirect()->back()->with('success', 'Withdrawal request submitted successfully.');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Withdrawal submission error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while submitting your withdrawal request. Please try again.');
        } finally {
            if ($lockAcquired) {
                $db->query('SELECT RELEASE_LOCK(?)', [$lockName]);
            }
        }
    }

    public function saveSettings()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = $res['shopId'];
        $section = (string) $this->request->getPost('section');

        if ($section === 'payment') {
            return $this->savePaymentDetails($shopId);
        }

        if ($section === 'hours') {
            return $this->saveBusinessHours($shopId);
        }

        if ($section === 'notifications') {
            return $this->saveNotificationPreferences($shopId);
        }

        if ($section === 'services') {
            return $this->saveServiceModules($shopId);
        }

        return $this->saveShopProfile($shopId);
    }

    private function saveServiceModules(int $shopId)
    {
        $module = $this->request->getPost('module');
        $allowed = ['offers_printing', 'offers_delivery', 'offers_pickup'];
        $shopModel = new ShopModel();

        if ($module && in_array($module, $allowed, true)) {
            $enabled = (int) $this->request->getPost('enabled') ? 1 : 0;
            $shopModel->update($shopId, [$module => $enabled]);
        } else {
            $update = [];
            foreach ($allowed as $field) {
                if ($this->request->getPost($field) !== null) {
                    $update[$field] = $this->request->getPost($field) ? 1 : 0;
                }
            }
            if (!empty($update)) {
                $shopModel->update($shopId, $update);
            }
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'message' => 'Service module updated successfully.']);
        }

        return redirect()->to(base_url('tenant/settings#preferences'))->with('success', 'Service module updated.');
    }

    private function saveShopProfile(int $shopId)
    {
        $shopName = trim((string) $this->request->getPost('shop_name'));
        if ($shopName === '') {
            return redirect()->back()->with('error', 'Shop name is required.');
        }

        $description = trim((string) $this->request->getPost('description'));
        if (mb_strlen($description) > 500) {
            return redirect()->back()->with('error', 'Description must be 500 characters or fewer.');
        }

        $street      = trim((string) $this->request->getPost('street'));
        $barangay    = trim((string) $this->request->getPost('barangay'));
        $addressLine = trim((string) $this->request->getPost('address_line'));

        if ($addressLine === '' || $addressLine === 'Polomolok') {
            $parts = array_filter([$street, $barangay, 'Polomolok', 'South Cotabato']);
            if (!empty($parts)) {
                $addressLine = implode(', ', $parts);
            }
        }

        $lat = $this->request->getPost('latitude');
        $lng = $this->request->getPost('longitude');

        $updateData = [
            'shop_name'    => $shopName,
            'description'  => $description,
            'street'       => $street,
            'barangay'     => $barangay,
            'address_line' => $addressLine,
        ];

        if ($this->request->getPost('offers_printing') !== null) {
            $updateData['offers_printing'] = $this->request->getPost('offers_printing') ? 1 : 0;
        }
        if ($this->request->getPost('offers_delivery') !== null) {
            $updateData['offers_delivery'] = $this->request->getPost('offers_delivery') ? 1 : 0;
        }
        if ($this->request->getPost('offers_pickup') !== null) {
            $updateData['offers_pickup'] = $this->request->getPost('offers_pickup') ? 1 : 0;
        }

        if ($lat !== null && $lng !== null && is_numeric($lat) && is_numeric($lng) && (float) $lat != 0 && (float) $lng != 0) {
            $updateData['latitude']    = (float) $lat;
            $updateData['longitude']   = (float) $lng;
            $updateData['geocoded_at'] = date('Y-m-d H:i:s');
        } else {
            $shopModel = new ShopModel();
            $existingShop = $shopModel->find($shopId);
            $existingStreet = $existingShop['street'] ?? '';
            $existingBarangay = $existingShop['barangay'] ?? '';
            $needsGeocoding = empty($existingShop['latitude']) || $street !== $existingStreet || $barangay !== $existingBarangay;

            if ($needsGeocoding && ($street !== '' || $barangay !== '' || $addressLine !== '')) {
                $mapsService = new \App\Services\GoogleMapsService();
                $geo = $mapsService->geocodeAddress($addressLine);
                if ($geo) {
                    $updateData['latitude']    = $geo['lat'];
                    $updateData['longitude']   = $geo['lng'];
                    $updateData['geocoded_at'] = date('Y-m-d H:i:s');
                }
            }
        }

        (new ShopModel())->update($shopId, $updateData);

        return redirect()->back()->with('success', 'Shop profile saved.');
    }

    private function savePaymentDetails(int $shopId)
    {
        $gcashNumber        = trim((string) $this->request->getPost('gcash_number'));
        $confirmGcashNumber = trim((string) $this->request->getPost('confirm_gcash_number'));
        $gcashName          = trim((string) $this->request->getPost('gcash_account_name'));
        $confirmOwnership   = $this->request->getPost('gcash_confirm_ownership');

        if ($gcashName === '') {
            return $this->settingsResponse(false, 'GCash registered account name is required.');
        }
        if (mb_strlen($gcashName) < 2 || mb_strlen($gcashName) > 100) {
            return $this->settingsResponse(false, 'GCash registered account name must be between 2 and 100 characters.');
        }

        $digits        = preg_replace('/\D/', '', $gcashNumber);
        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        }
        $confirmDigits = preg_replace('/\D/', '', $confirmGcashNumber);
        if (str_starts_with($confirmDigits, '63') && strlen($confirmDigits) === 12) {
            $confirmDigits = '0' . substr($confirmDigits, 2);
        }

        if (!preg_match('/^09\d{9}$/', $digits)) {
            return $this->settingsResponse(false, 'Please enter a valid 11-digit GCash mobile number starting with 09 (e.g. 09171234567).');
        }
        if ($digits !== $confirmDigits) {
            return $this->settingsResponse(false, 'The GCash mobile number and confirmation number do not match.');
        }
        if (empty($confirmOwnership)) {
            return $this->settingsResponse(false, 'Please confirm that the GCash account information provided is correct and belongs to you or your business.');
        }

        (new ShopModel())->update($shopId, [
            'gcash_number'       => $digits,
            'gcash_account_name' => $gcashName,
        ]);

        (new AuditLogModel())->log(
            (int) session()->get('user_id'),
            'tenant',
            'Updated GCash Payment Details',
            'shop',
            'success',
            $shopId
        );

        return $this->settingsResponse(true, 'GCash payment details updated successfully.');
    }

    private function saveBusinessHours(int $shopId)
    {
        $hoursModel = new ShopBusinessHourModel();
        $days       = [];

        for ($day = 0; $day <= 6; $day++) {
            $closed = (int) $this->request->getPost('closed_' . $day);
            $open   = trim((string) $this->request->getPost('open_' . $day));
            $close  = trim((string) $this->request->getPost('close_' . $day));

            if ($closed) {
                $days[$day] = ['open_time' => null, 'close_time' => null, 'is_closed' => 1];
                continue;
            }

            $normalizedOpen  = $this->normalizeTime($open);
            $normalizedClose = $this->normalizeTime($close);

            if ($normalizedOpen === null || $normalizedClose === null) {
                return $this->settingsResponse(false, 'Please enter valid opening and closing times.');
            }
            if ($normalizedClose <= $normalizedOpen) {
                return $this->settingsResponse(false, 'Closing time must be after opening time.');
            }

            $days[$day] = ['open_time' => $normalizedOpen, 'close_time' => $normalizedClose, 'is_closed' => 0];
        }

        $hoursModel->saveForShop($shopId, $days);

        return $this->settingsResponse(true, 'Business hours saved.');
    }

    private function saveNotificationPreferences(int $shopId)
    {
        (new ShopNotificationPreferenceModel())->saveForShop($shopId, [
            'new_orders'     => (int) $this->request->getPost('new_orders'),
            'low_stock'      => (int) $this->request->getPost('low_stock'),
        ]);

        return $this->settingsResponse(true, 'Notification preferences saved.');
    }

    /**
     * Normalize a "H:i" or "H:i:s" time input to "H:i:s" (or null when empty/invalid).
     */
    private function normalizeTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)) {
            return $value . ':00';
        }
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $value)) {
            return $value;
        }

        return null;
    }

    /**
     * Return a JSON response for the auto-save AJAX calls, or a flash-message
     * redirect when the request was a normal form submission.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\ResponseInterface
     */
    private function settingsResponse(bool $ok, string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => $ok, 'message' => $message]);
        }

        return redirect()->back()->with($ok ? 'success' : 'error', $message);
    }

    public function saveShopLogo()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = $res['shopId'];
        $shop   = $res['shop'];

        $file = $this->request->getFile('logo');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Please choose an image file to upload.');
        }
        if ($file->hasMoved()) {
            return redirect()->back()->with('error', 'Logo upload failed. Please try again.');
        }

        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $mime = $file->getMimeType();
        if (!isset($mimeMap[$mime])) {
            return redirect()->back()->with('error', 'Only JPG, PNG, WEBP or GIF images are allowed for the shop logo.');
        }
        if ($file->getSize() > 2097152) {
            return redirect()->back()->with('error', 'Shop logo must be 2 MB or smaller.');
        }

        $cloudinary = new \App\Libraries\CloudinaryService();
        $publicId   = 'logo_' . $shopId . '_' . time() . '_' . bin2hex(random_bytes(4));
        $newLogoUrl = null;

        if ($cloudinary->isConfigured()) {
            try {
                $uploadRes = $cloudinary->uploadImage($file, \App\Libraries\CloudinaryService::FOLDER_SHOP_LOGOS, $publicId);
                if ($uploadRes && !empty($uploadRes['secure_url'])) {
                    $newLogoUrl = $uploadRes['secure_url'];
                }
            } catch (\Throwable $e) {
                log_message('error', '[Tenant::saveShopLogo] Cloudinary upload exception: ' . $e->getMessage());
            }
        }

        // Resilient local storage fallback
        if (!$newLogoUrl) {
            $uploadDir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'shop_logos';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $fileName = $file->getRandomName();
            if ($file->isValid() && !$file->hasMoved()) {
                $file->move($uploadDir, $fileName);
                $newLogoUrl = 'uploads/shop_logos/' . $fileName;
            }
        }

        if (!$newLogoUrl) {
            return redirect()->back()->with('error', 'Failed to save shop logo. Please try again.');
        }

        (new ShopModel())->update($shopId, ['logo_url' => $newLogoUrl]);

        // Safe cleanup: only clean up old logo asset after successful DB update
        if (!empty($shop['logo_url']) && $shop['logo_url'] !== $newLogoUrl) {
            if ($cloudinary->isCloudinaryUrl($shop['logo_url'])) {
                $oldPublicId = $cloudinary->extractPublicId($shop['logo_url']);
                if ($oldPublicId) {
                    $cloudinary->deleteAsset($oldPublicId, 'image');
                }
            } elseif (strpos($shop['logo_url'], 'uploads/shop_logos/') === 0) {
                $oldPath = ROOTPATH . 'public/' . $shop['logo_url'];
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }

        return redirect()->back()->with('success', 'Shop logo updated.');
    }

    public function saveProduct()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $id     = (int) $this->request->getPost('product_id');

        $name         = trim((string) $this->request->getPost('name'));
        $description  = trim((string) $this->request->getPost('description'));
        $price        = (float) $this->request->getPost('price');
        $comparePrice = (float) $this->request->getPost('compare_at_price');
        $stock        = (int) $this->request->getPost('stock_quantity');
        $threshold    = (int) $this->request->getPost('low_stock_threshold');
        $categoryId   = (int) $this->request->getPost('category_id');
        $sku          = trim((string) $this->request->getPost('sku'));
        $shippingFee  = (float) $this->request->getPost('shipping_fee');

        if ($name === '' || $price < 0 || $stock < 0 || $threshold < 0) {
            return redirect()->back()->with('error', 'Please provide a valid product name, non-negative price, stock, and low-stock threshold.');
        }
        if ($categoryId > 0 && !(new CategoryModel())->find($categoryId)) {
            return redirect()->back()->with('error', 'Invalid category selected.');
        }

        $productModel = new ProductModel();

        // Duplicate SKU guard (per shop), skipping the product being edited.
        if ($sku !== '') {
            $existingSku = $productModel
                ->where('shop_id', $shopId)
                ->where('sku', $sku)
                ->where('deleted_at', null)
                ->first();
            if ($existingSku && (int) $existingSku['id'] !== $id) {
                return redirect()->back()->with('error', 'That SKU is already used by another product.');
            }
        }

        $data = [
            'category_id'         => $categoryId > 0 ? $categoryId : null,
            'name'                => $name,
            'description'         => $description,
            'price'               => $price,
            'compare_at_price'    => $comparePrice > 0 ? $comparePrice : null,
            'shipping_fee'        => $shippingFee >= 0 ? $shippingFee : 0,
            'stock_quantity'      => $stock,
            'low_stock_threshold' => $threshold > 0 ? $threshold : 5,
        ];

        $targetProductId = $id;
        if ($id > 0) {
            $existing = $productModel->find($id);
            if (!$existing || (int) $existing['shop_id'] !== $shopId) {
                return redirect()->back()->with('error', 'Product not found.');
            }
            $productModel->update($id, $data);
            $this->maybeNotifyLowStock($shopId, $existing, $stock);
        } else {
            $data['shop_id'] = $shopId;
            $data['sku']     = $sku !== '' ? $sku : $this->generateSku($productModel, $shopId);
            $data['status']  = 'active';
            $data['rating_average'] = 0;
            $data['rating_count']   = 0;

            $productModel->insert($data);
            $targetProductId = (int) $productModel->getInsertID();
        }

        // Multiple images upload & attachment
        $this->handleProductImageUpload($targetProductId);

        // Product variants save/update
        $this->handleProductVariantsSave($targetProductId);

        // Sync embedding for AI search
        $this->syncProductEmbedding($targetProductId);

        $msg = $id > 0 ? 'Product updated.' : 'Product added to inventory.';
        return redirect()->back()->with('success', $msg);
    }

    /**
     * Upload and save product images (supports multiple images via product_images[]).
     */
    private function handleProductImageUpload(int $productId): void
    {
        $imageModel   = new ProductImageModel();
        $cloudinary   = new \App\Libraries\CloudinaryService();

        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        $allowedMimes = array_keys($mimeMap);
        $maxBytes     = 5 * 1024 * 1024; // 5MB

        $existingCount = $imageModel->where('product_id', $productId)->countAllResults();
        $maxSortOrder  = 0;
        if ($existingCount > 0) {
            $latest = $imageModel->where('product_id', $productId)->orderBy('sort_order', 'DESC')->first();
            $maxSortOrder = $latest ? ((int) $latest['sort_order'] + 1) : $existingCount;
        }

        $uploadedAny = false;

        // 1. Multiple files via product_images[]
        $files = $this->request->getFileMultiple('product_images');
        if (!empty($files)) {
            foreach ($files as $file) {
                if (!$file || !$file->isValid() || $file->hasMoved()) {
                    continue;
                }
                $mime = $file->getMimeType();
                if (!isset($mimeMap[$mime]) || $file->getSize() > $maxBytes) {
                    continue;
                }

                $publicId = 'prod_' . $productId . '_' . time() . '_' . bin2hex(random_bytes(4));
                $imageUrl = null;

                if ($cloudinary->isConfigured()) {
                    try {
                        $uploadRes = $cloudinary->uploadImage($file, \App\Libraries\CloudinaryService::FOLDER_PRODUCTS, $publicId);
                        if ($uploadRes && !empty($uploadRes['secure_url'])) {
                            $imageUrl = $uploadRes['secure_url'];
                        }
                    } catch (\Throwable $e) {
                        log_message('error', '[Tenant::handleProductImageUpload] Cloudinary upload exception: ' . $e->getMessage());
                    }
                }

                // Resilient local storage fallback
                if (!$imageUrl) {
                    $uploadDir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'products';
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0775, true);
                    }
                    $fileName = $file->getRandomName();
                    if ($file->isValid() && !$file->hasMoved()) {
                        $file->move($uploadDir, $fileName);
                        $imageUrl = 'uploads/products/' . $fileName;
                    }
                }

                if ($imageUrl) {
                    $isPrimary = ($existingCount === 0) ? 1 : 0;
                    $imageModel->insert([
                        'product_id' => $productId,
                        'image_url'  => $imageUrl,
                        'alt_text'   => '',
                        'is_primary' => $isPrimary,
                        'sort_order' => $maxSortOrder++,
                    ]);
                    $existingCount++;
                    $uploadedAny = true;
                }
            }
        }

        // 2. Single file input fallback (product_image) - only if no multiple files were uploaded
        if (!$uploadedAny) {
            $single = $this->request->getFile('product_image');
            if ($single && $single->isValid() && !$single->hasMoved()) {
                $mime = $single->getMimeType();
                if (isset($mimeMap[$mime]) && $single->getSize() <= $maxBytes) {
                    $publicId = 'prod_' . $productId . '_' . time() . '_' . bin2hex(random_bytes(4));
                    $imageUrl = null;

                    if ($cloudinary->isConfigured()) {
                        try {
                            $uploadRes = $cloudinary->uploadImage($single, \App\Libraries\CloudinaryService::FOLDER_PRODUCTS, $publicId);
                            if ($uploadRes && !empty($uploadRes['secure_url'])) {
                                $imageUrl = $uploadRes['secure_url'];
                            }
                        } catch (\Throwable $e) {
                            log_message('error', '[Tenant::handleProductImageUpload] Cloudinary single upload exception: ' . $e->getMessage());
                        }
                    }

                    // Resilient local storage fallback
                    if (!$imageUrl) {
                        $uploadDir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'products';
                        if (!is_dir($uploadDir)) {
                            @mkdir($uploadDir, 0775, true);
                        }
                        $fileName = $single->getRandomName();
                        if ($single->isValid() && !$single->hasMoved()) {
                            $single->move($uploadDir, $fileName);
                            $imageUrl = 'uploads/products/' . $fileName;
                        }
                    }

                    if ($imageUrl) {
                        $isPrimary = ($existingCount === 0) ? 1 : 0;
                        $imageModel->insert([
                            'product_id' => $productId,
                            'image_url'  => $imageUrl,
                            'alt_text'   => '',
                            'is_primary' => $isPrimary,
                            'sort_order' => $maxSortOrder++,
                        ]);
                    }
                }
            }
        }
}

    /**
     * Save product variants submitted via repeatable form fields.
     * Uses upsert-by-(name, value) to preserve variant IDs across edits.
     */
    private function handleProductVariantsSave(int $productId, ?array $overrideRows = null): void
    {
        if ($overrideRows !== null) {
            $variantNames  = array_column($overrideRows, 'name');
            $variantValues = array_column($overrideRows, 'value');
            $variantStocks = array_column($overrideRows, 'stock');
            $variantPrices = array_column($overrideRows, 'price');
            $variantSkus   = array_column($overrideRows, 'sku');
        } else {
            $req = $this->request ?? service('request');
            $variantNames  = (array) $req->getPost('variant_name');
            $variantValues = (array) $req->getPost('variant_value');
            $variantStocks = (array) $req->getPost('variant_stock');
            $variantPrices = (array) $req->getPost('variant_price');
            $variantSkus   = (array) $req->getPost('variant_sku');
        }

        $variantModel = new ProductVariantModel();
        $existingVariants = $variantModel->where('product_id', $productId)->findAll();

        $existingMap = [];
        foreach ($existingVariants as $ev) {
            $key = mb_strtolower(trim($ev['name'])) . '|||' . mb_strtolower(trim($ev['value']));
            $existingMap[$key] = $ev;
        }

        $count = max(count($variantValues), count($variantNames));
        $keptIds = [];

        if ($count > 0) {
            for ($idx = 0; $idx < $count; $idx++) {
                $val  = isset($variantValues[$idx]) ? trim((string) $variantValues[$idx]) : '';
                $name = isset($variantNames[$idx]) ? trim((string) $variantNames[$idx]) : '';

                // Handle single type input or name+val gracefully
                if ($val === '' && $name !== '' && $name !== 'Type') {
                    $val  = $name;
                    $name = 'Type';
                } elseif ($val !== '' && ($name === '' || $name === 'Type')) {
                    $name = 'Type';
                }

                if ($val === '') {
                    continue;
                }

                $canonicalName = $name !== '' ? $name : 'Type';
                $stock = max(0, (int) ($variantStocks[$idx] ?? 0));
                $price = isset($variantPrices[$idx]) && $variantPrices[$idx] !== '' ? (float) $variantPrices[$idx] : null;
                $sku   = isset($variantSkus[$idx]) ? trim((string) $variantSkus[$idx]) : null;

                $data = [
                    'product_id'     => $productId,
                    'name'           => $canonicalName,
                    'value'          => $val,
                    'sku_suffix'     => $sku !== '' ? $sku : null,
                    'stock_quantity' => $stock,
                    'price_override' => ($price !== null && $price >= 0) ? $price : null,
                ];

                $key = mb_strtolower($canonicalName) . '|||' . mb_strtolower($val);
                if (isset($existingMap[$key])) {
                    $existingId = (int) $existingMap[$key]['id'];
                    $variantModel->update($existingId, $data);
                    $keptIds[] = $existingId;
                } else {
                    $newId = $variantModel->insert($data);
                    if ($newId) {
                        $keptIds[] = (int) $newId;
                        // Avoid duplicates if same name+val appears multiple times in submitted arrays
                        $existingMap[$key] = array_merge($data, ['id' => $newId]);
                    }
                }
            }
        }

        // Delete existing rows that were not retained in this submission
        $existingIds = array_map('intval', array_column($existingVariants, 'id'));
        $toDelete = array_diff($existingIds, $keptIds);
        if (!empty($toDelete)) {
            $variantModel->whereIn('id', $toDelete)->delete();
        }
    }

    /**
     * Delete an uploaded product image (CSRF-protected, tenant-scoped).
     */
    public function deleteProductImage($imageId)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId     = (int) $res['shopId'];
        $imageId    = (int) $imageId;
        $imageModel = new ProductImageModel();

        $image = $imageModel
            ->select('product_images.*, products.shop_id')
            ->join('products', 'products.id = product_images.product_id')
            ->where('product_images.id', $imageId)
            ->first();

        if (!$image || (int) $image['shop_id'] !== $shopId) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'Image not found or permission denied.']);
        }

        $productId  = (int) $image['product_id'];
        $wasPrimary = (int) $image['is_primary'] === 1;

        // Delete asset from Cloudinary or local physical file
        $cloudinary = new \App\Libraries\CloudinaryService();
        if ($cloudinary->isCloudinaryUrl($image['image_url'])) {
            $publicId = $cloudinary->extractPublicId($image['image_url']);
            if ($publicId) {
                $cloudinary->deleteAsset($publicId, 'image');
            }
        } elseif (str_starts_with($image['image_url'], 'uploads/')) {
            $filePath = ROOTPATH . 'public/' . $image['image_url'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        // Delete database record
        $imageModel->delete($imageId);

        // If primary image was deleted, promote the next lowest sort_order image
        if ($wasPrimary) {
            $next = $imageModel->where('product_id', $productId)->orderBy('sort_order', 'ASC')->first();
            if ($next) {
                $imageModel->update($next['id'], ['is_primary' => 1]);
            }
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Image deleted successfully.']);
    }

    /**
     * Refresh the AI search embedding for a product after a save.
     *
     * Best effort by design: the embedding only changes when the searchable
     * text (name / description / category) changes, and a Cohere outage must
     * never block or fail a seller's product save. Anything missed here is
     * picked up by `php spark products:embed`.
     */
    private function syncProductEmbedding(int $productId): void
    {
        if ($productId <= 0) {
            return;
        }

        try {
            $service = service('semanticSearch');
            if ($service->isEnabled()) {
                $service->syncProduct($productId);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Product embedding sync failed for product #' . $productId . ': ' . $e->getMessage());
        }
    }

    private function generateSku(ProductModel $productModel, int $shopId): string
    {
        do {
            $sku = 'SKU-' . strtoupper(substr(uniqid(), -6));
        } while ($productModel->where('shop_id', $shopId)->where('sku', $sku)->first());

        return $sku;
    }

    /**
     * Adjust a product's stock level by a delta or to an absolute value.
     * AJAX JSON endpoint used by the restock controls.
     */
    public function adjustStock()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId    = (int) $res['shopId'];
        $productId = (int) $this->request->getPost('product_id');
        $delta     = (int) $this->request->getPost('delta');
        $setTo     = (int) $this->request->getPost('set_to');

        $productModel = new ProductModel();
        $product      = $productId > 0 ? $productModel->find($productId) : null;
        if (!$product || (int) $product['shop_id'] !== $shopId) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Product not found.']);
        }

        $newStock = (int) $product['stock_quantity'];
        if ($setTo > 0) {
            $newStock = $setTo;
        } elseif ($delta !== 0) {
            $newStock += $delta;
        }
        if ($newStock < 0) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'error' => 'Stock cannot go below zero.']);
        }

        $productModel->update($productId, ['stock_quantity' => $newStock]);

        $this->maybeNotifyLowStock($shopId, $product, $newStock);

        return $this->response->setJSON([
            'success'        => true,
            'product_id'     => $productId,
            'stock_quantity' => $newStock,
            'stock_status'   => $this->stockStatusFor($newStock, (int) $product['low_stock_threshold']),
        ]);
    }

    /**
     * Archive a product: hide it from the storefront and log it in the
     * archive table instead of hard-deleting (protects order history).
     */
    public function archiveProduct($productId)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];

        $productModel = new ProductModel();
        $product      = $productModel->find((int) $productId);
        if (!$product || (int) $product['shop_id'] !== $shopId) {
            return redirect()->back()->with('error', 'Product not found.');
        }
        if ($product['status'] === 'archived') {
            return redirect()->back()->with('error', 'Product is already archived.');
        }

        $productModel->update((int) $productId, [
            'status'     => 'archived',
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insertArchiveRow($shopId, 'inventory', (int) $productId, (string) $product['name']);

        return redirect()->back()->with('success', 'Product archived. You can restore it from the Archive page.');
    }

    /**
     * Bulk archive selected products owned by the shop.
     */
    public function bulkArchiveProducts()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId     = (int) $res['shopId'];
        $productIds = $this->request->getPost('product_ids');

        if (empty($productIds) || !is_array($productIds)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'error' => 'No products selected.']);
            }
            return redirect()->back()->with('error', 'No products selected.');
        }

        $productModel = new ProductModel();
        $archivedCount = 0;

        foreach ($productIds as $id) {
            $pid = (int) $id;
            if ($pid <= 0) continue;
            $product = $productModel->find($pid);
            if ($product && (int) $product['shop_id'] === $shopId && $product['status'] !== 'archived') {
                $productModel->update($pid, [
                    'status'     => 'archived',
                    'deleted_at' => date('Y-m-d H:i:s'),
                ]);
                $this->insertArchiveRow($shopId, 'inventory', $pid, (string) $product['name']);
                $archivedCount++;
            }
        }

        $msg = "Successfully archived {$archivedCount} product(s).";
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'archived_count' => $archivedCount, 'message' => $msg]);
        }
        return redirect()->back()->with('success', $msg);
    }

    /**
     * Bulk adjust stock for selected products owned by the shop.
     */
    public function bulkAdjustStock()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId     = (int) $res['shopId'];
        $productIds = $this->request->getPost('product_ids');
        $delta      = (int) $this->request->getPost('delta');
        $setTo      = $this->request->getPost('set_to');

        if (empty($productIds) || !is_array($productIds)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'error' => 'No products selected.']);
            }
            return redirect()->back()->with('error', 'No products selected.');
        }

        $productModel = new ProductModel();
        $updatedCount = 0;

        foreach ($productIds as $id) {
            $pid = (int) $id;
            if ($pid <= 0) continue;
            $product = $productModel->find($pid);
            if ($product && (int) $product['shop_id'] === $shopId) {
                $newStock = (int) $product['stock_quantity'];
                if ($setTo !== null && $setTo !== '') {
                    $newStock = max(0, (int) $setTo);
                } elseif ($delta !== 0) {
                    $newStock = max(0, $newStock + $delta);
                }
                $productModel->update($pid, ['stock_quantity' => $newStock]);
                $this->maybeNotifyLowStock($shopId, $product, $newStock);
                $updatedCount++;
            }
        }

        $msg = "Successfully updated stock for {$updatedCount} product(s).";
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'updated_count' => $updatedCount, 'message' => $msg]);
        }
        return redirect()->back()->with('success', $msg);
    }

    private function stockStatusFor(int $stock, int $threshold): string
    {
        if ($stock <= 0) {
            return 'out';
        }
        if ($stock <= $threshold) {
            return 'low';
        }
        return 'in';
    }

    /**
     * Emit a gated low-stock/out-of-stock notification to the shop owner when
     * the shop's "Low Stock Alerts" preference is enabled.
     */
    private function maybeNotifyLowStock(int $shopId, array $product, int $newStock): void
    {
        $threshold = (int) ($product['low_stock_threshold'] ?? 5);
        if ($newStock > $threshold) {
            return;
        }

        $shop = (new ShopModel())->find($shopId);
        if (!$shop || empty($shop['owner_id'])) {
            return;
        }

        $prefs = (new ShopNotificationPreferenceModel())->getForShop($shopId);
        if (empty($prefs['low_stock'])) {
            return;
        }

        $productName = (string) ($product['name'] ?? 'A product');
        if ($newStock <= 0) {
            $title   = 'Product out of stock';
            $message = $productName . ' is now out of stock. Restock it to keep your storefront active.';
        } else {
            $title   = 'Low stock alert';
            $message = $productName . ' is at ' . $newStock . ' unit(s), at or below the threshold of ' . $threshold . '.';
        }

        (new NotificationModel())->create((int) $shop['owner_id'], 'low_stock', $title, $message, '/tenant/inventory');
    }

    public function deleteProduct($productId)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $productModel = new ProductModel();
        $product      = $productModel->find((int) $productId);

        if (!$product || (int) $product['shop_id'] !== (int) $res['shopId']) {
            return redirect()->back()->with('error', 'Product not found.');
        }

        $productModel->delete((int) $productId);

        return redirect()->back()->with('success', 'Product deleted from inventory.');
    }

    public function restoreProduct($archiveId)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
            }
            return $res;
        }

        $shopId       = (int) $res['shopId'];
        $archiveModel = new ArchivedItemModel();
        $item         = $archiveModel->find((int) $archiveId);

        if (!$item || (int) $item['shop_id'] !== $shopId) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Archived item not found.']);
            }
            return redirect()->back()->with('error', 'Archived item not found.');
        }

        if (!empty($item['restored_at'])) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'This item has already been restored.']);
            }
            return redirect()->back()->with('error', 'This item has already been restored.');
        }

        if ($item['item_type'] === 'inventory') {
            $product = (new ProductModel())->find((int) $item['item_id']);
            if (!$product || (int) $product['shop_id'] !== $shopId) {
                if ($this->request->isAJAX()) {
                    return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'The linked product no longer exists in your shop.']);
                }
                return redirect()->back()->with('error', 'The linked product no longer exists in your shop.');
            }

            (new ProductModel())->update((int) $item['item_id'], [
                'status'     => 'active',
                'deleted_at' => null,
            ]);
        }

        $archiveModel->update((int) $archiveId, ['restored_at' => date('Y-m-d H:i:s')]);
        $archiveModel->delete((int) $archiveId);

        (new AuditLogModel())->log(
            (int) (session()->get('user_id') ?? 0) ?: null,
            'tenant',
            'restored_archive_item',
            $item['item_type'],
            'success',
            (int) $item['item_id'],
            $this->request->getIPAddress()
        );

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'message' => 'Item restored successfully.']);
        }
        return redirect()->back()->with('success', 'Item restored successfully.');
    }

    public function archiveItemDetail($archiveId)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId = (int) $res['shopId'];
        $db     = \Config\Database::connect();

        $archive = $db->table('archived_items a')
            ->select('a.*, u.first_name AS archived_by_first, u.last_name AS archived_by_last, u.email AS archived_by_email')
            ->join('users u', 'u.id = a.archived_by', 'left')
            ->where('a.id', (int) $archiveId)
            ->where('a.shop_id', $shopId)
            ->get()->getRowArray();

        if (!$archive) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Archived item not found.']);
        }

        $itemType = $archive['item_type'];
        $itemId   = (int) $archive['item_id'];
        $data     = [
            'archive_id'        => (int) $archive['id'],
            'item_type'         => $itemType,
            'item_id'           => $itemId,
            'item_label'        => $archive['item_label'],
            'archived_at'       => $archive['archived_at'],
            'archived_by_name'  => trim(($archive['archived_by_first'] ?? '') . ' ' . ($archive['archived_by_last'] ?? '')) ?: 'System',
            'archived_by_email' => $archive['archived_by_email'] ?? '',
            'entity'            => null,
        ];

        if ($itemType === 'inventory') {
            $product = $db->table('products p')
                ->select('p.*, c.name AS category_name')
                ->join('categories c', 'c.id = p.category_id', 'left')
                ->where('p.id', $itemId)
                ->get()->getRowArray();
            if ($product) {
                $images = $db->table('product_images')
                    ->where('product_id', $itemId)
                    ->orderBy('is_primary', 'DESC')
                    ->get()->getResultArray();
                $product['images'] = $images;
                $data['entity']    = $product;
            }
        } elseif ($itemType === 'order') {
            $order = $db->table('orders o')
                ->select('o.*, u.first_name, u.last_name, u.email, u.phone AS phone_number')
                ->join('users u', 'u.id = o.customer_id', 'left')
                ->where('o.id', $itemId)
                ->get()->getRowArray();
            if ($order) {
                $items = $db->table('order_items oi')
                    ->select('oi.*, p.name AS product_name, pi.image_url')
                    ->join('products p', 'p.id = oi.product_id', 'left')
                    ->join('product_images pi', 'pi.product_id = p.id AND pi.is_primary = 1', 'left')
                    ->where('oi.order_id', $itemId)
                    ->get()->getResultArray();
                $order['items'] = $items;
                $data['entity'] = $order;
            }
        } elseif ($itemType === 'printing_request') {
            $pr = $db->table('printing_requests pr')
                ->select('pr.*, u.first_name, u.last_name, u.email, u.phone AS phone_number')
                ->join('users u', 'u.id = pr.customer_id', 'left')
                ->where('pr.id', $itemId)
                ->get()->getRowArray();
            if ($pr) {
                $data['entity'] = $pr;
            }
        }

        return $this->response->setJSON(['success' => true, 'data' => $data]);
    }

    public function bulkRestore()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
            }
            return $res;
        }

        $shopId     = (int) $res['shopId'];
        $archiveIds = $this->request->getPost('archive_ids');
        if (empty($archiveIds) || !is_array($archiveIds)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'error' => 'No items selected.']);
            }
            return redirect()->back()->with('error', 'No items selected.');
        }

        $archiveModel  = new ArchivedItemModel();
        $productModel  = new ProductModel();
        $auditModel    = new AuditLogModel();
        $restoredCount = 0;

        foreach ($archiveIds as $id) {
            $aid  = (int) $id;
            $item = $archiveModel->where('id', $aid)->where('shop_id', $shopId)->first();
            if (!$item) {
                continue;
            }

            if ($item['item_type'] === 'inventory') {
                $productModel->update((int) $item['item_id'], [
                    'status'     => 'active',
                    'deleted_at' => null,
                ]);
            }

            $archiveModel->delete($aid);
            $restoredCount++;
        }

        $auditModel->log(
            (int) (session()->get('user_id') ?? 0) ?: null,
            'tenant',
            'bulk_restored_archive_items',
            'archive',
            'success',
            $restoredCount,
            $this->request->getIPAddress()
        );

        $msg = "{$restoredCount} item(s) restored successfully.";
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'message' => $msg, 'count' => $restoredCount]);
        }
        return redirect()->back()->with('success', $msg);
    }

    public function permanentDelete($archiveId)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
            }
            return $res;
        }

        $shopId       = (int) $res['shopId'];
        $archiveModel = new ArchivedItemModel();
        $item         = $archiveModel->where('id', (int) $archiveId)->where('shop_id', $shopId)->first();

        if (!$item) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Archived item not found.']);
            }
            return redirect()->back()->with('error', 'Archived item not found.');
        }

        $archiveModel->delete((int) $archiveId);

        (new AuditLogModel())->log(
            (int) (session()->get('user_id') ?? 0) ?: null,
            'tenant',
            'permanent_deleted_archive_item',
            $item['item_type'],
            'success',
            (int) $item['item_id'],
            $this->request->getIPAddress()
        );

        $msg = 'Item permanently removed from archive.';
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'message' => $msg]);
        }
        return redirect()->back()->with('success', $msg);
    }

    public function bulkPermanentDelete()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
            }
            return $res;
        }

        $shopId     = (int) $res['shopId'];
        $archiveIds = $this->request->getPost('archive_ids');
        if (empty($archiveIds) || !is_array($archiveIds)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'error' => 'No items selected.']);
            }
            return redirect()->back()->with('error', 'No items selected.');
        }

        $archiveModel = new ArchivedItemModel();
        $deletedCount = 0;

        foreach ($archiveIds as $id) {
            $aid  = (int) $id;
            $item = $archiveModel->where('id', $aid)->where('shop_id', $shopId)->first();
            if ($item) {
                $archiveModel->delete($aid);
                $deletedCount++;
            }
        }

        (new AuditLogModel())->log(
            (int) (session()->get('user_id') ?? 0) ?: null,
            'tenant',
            'bulk_permanent_deleted_archive_items',
            'archive',
            'success',
            $deletedCount,
            $this->request->getIPAddress()
        );

        $msg = "{$deletedCount} item(s) permanently removed from archive.";
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'message' => $msg, 'count' => $deletedCount]);
        }
        return redirect()->back()->with('success', $msg);
    }

    public function exportArchiveCsv()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $db     = \Config\Database::connect();

        $rows = $db->table('archived_items a')
            ->select('a.id, a.item_type, a.item_id, a.item_label, a.archived_at, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = a.archived_by', 'left')
            ->where('a.shop_id', $shopId)
            ->orderBy('a.archived_at', 'DESC')
            ->get()->getResultArray();

        $filename = 'archive_records_' . date('Y-m-d') . '.csv';

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['Archive ID', 'Item Type', 'Reference / Label', 'Original Item ID', 'Archived Date', 'Archived By Name', 'Archived By Email']);

        foreach ($rows as $r) {
            $archiverName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: 'System';
            fputcsv($output, [
                $r['id'],
                ucfirst(str_replace('_', ' ', $r['item_type'])),
                $r['item_label'],
                $r['item_id'],
                $r['archived_at'],
                $archiverName,
                $r['email'] ?? '',
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csvContent);
    }

    private function progressForStatus(string $status): int
    {
        switch ($status) {
            case 'in_production':
                return 40;
            case 'ready_for_pickup':
            case 'ready_for_delivery':
            case 'in_transit':
                return 80;
            case 'completed':
                return 100;
            default:
                return 10;
        }
    }

    /**
     * AJAX endpoint to search active in-stock products for the POS store pickup flow.
     * Strictly tenant-scoped to the authenticated merchant's shop.
     */
    public function posSearchProducts()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId     = (int) $res['shopId'];
        $query      = trim((string) $this->request->getGet('q'));
        $categoryId = (int) $this->request->getGet('category_id');

        $db      = \Config\Database::connect();
        $builder = $db->table('products p')
            ->select('p.id, p.name, p.price, p.stock_quantity, p.sku, p.category_id, pi.image_url')
            ->join('product_images pi', 'pi.product_id = p.id AND pi.is_primary = 1', 'left')
            ->where('p.shop_id', $shopId)
            ->where('p.status', 'active')
            ->where('p.deleted_at IS NULL')
            ->where('p.stock_quantity >', 0);

        if ($categoryId > 0) {
            $builder->where('p.category_id', $categoryId);
        }

        if ($query !== '') {
            $builder->groupStart()
                ->like('p.name', $query)
                ->orLike('p.sku', $query)
            ->groupEnd();
        }

        $products = $builder->limit(20)->get()->getResultArray();

        return $this->response->setJSON([
            'success'  => true,
            'products' => array_map(static fn ($p) => [
                'id'             => (int) $p['id'],
                'name'           => $p['name'],
                'price'          => (float) $p['price'],
                'stock_quantity' => (int) $p['stock_quantity'],
                'sku'            => $p['sku'] ?? '',
                'image_url'      => !empty($p['image_url']) ? base_url($p['image_url']) : null,
            ], $products),
        ]);
    }

    /**
     * Securely verify a customer's scanned store pick-up QR code or order code.
     * Enforces strict tenant shop scoping, Store Pick-up fulfillment checks,
     * status eligibility, and dispatches customer arrival notification.
     */
    public function posVerifyQr()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized.']);
        }

        $shopId = (int) $res['shopId'];
        $shop   = $res['shop'];

        $rawCode = trim((string) ($this->request->getPost('qr_code') ?? $this->request->getPost('order_number') ?? ''));
        if ($rawCode === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'No QR code or order number provided.',
                'message' => 'No QR code or order number provided.',
            ]);
        }

        $deliveryModel = new DeliveryModel();
        $tokens = $deliveryModel->extractLookupTokens($rawCode);
        if (empty($tokens)) {
            $tokens[] = $rawCode;
        }

        $orderModel = new OrderModel();
        // Match exact order_number, prefixed 'ORD-', or numeric ID across all extracted tokens
        $order = null;
        foreach ($tokens as $t) {
            $qb = $orderModel
                ->select('orders.*, u.first_name, u.last_name, u.phone as customer_phone')
                ->join('users u', 'u.id = orders.customer_id', 'left')
                ->groupStart()
                    ->where('orders.order_number', $t)
                    ->orWhere('orders.order_number', 'ORD-' . $t)
                    ->orWhere('orders.order_number', '#' . $t);
            if (is_numeric($t)) {
                $qb->orWhere('orders.id', (int) $t);
            }
            $qb->groupEnd();
            $order = $qb->first();
            if ($order) {
                break;
            }
        }

        // If not found in orders, check printing requests
        if (!$order) {
            $prModel = new \App\Models\PrintingRequestModel();
            $pr = null;
            foreach ($tokens as $t) {
                $qb = $prModel
                    ->groupStart()
                        ->where('request_number', $t)
                        ->orWhere('request_number', 'PR-' . $t)
                        ->orWhere('request_number', '#' . $t);
                if (is_numeric($t)) {
                    $qb->orWhere('id', (int) $t);
                }
                $qb->groupEnd();
                $pr = $qb->first();
                if ($pr) {
                    break;
                }
            }

            if ($pr) {
                // Tenant ownership check
                if ((int) $pr['shop_id'] !== $shopId) {
                    return $this->response->setStatusCode(403)->setJSON([
                        'success' => false,
                        'error'   => "Request #{$pr['request_number']} belongs to another shop, not {$shop['shop_name']}.",
                        'message' => "Request #{$pr['request_number']} belongs to another shop, not {$shop['shop_name']}.",
                    ]);
                }

                // Fulfillment check (Store Pick-up)
                if (($pr['fulfillment_method'] ?? '') !== 'pickup') {
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error'   => "Wrong fulfillment type: Printing Request #{$pr['request_number']} was placed for Doorstep Delivery. Please use the Deliveries scanner to process deliveries.",
                        'message' => "Wrong fulfillment type: Printing Request #{$pr['request_number']} was placed for Doorstep Delivery. Please use the Deliveries scanner to process deliveries.",
                    ]);
                }

                // Status check: reject pending/in_production/new requests
                if (in_array($pr['status'], ['new', 'in_production', 'pending'], true)) {
                    $statusName = strtoupper(humanize_status($pr['status']));
                    $errMsg = "Printing Request #{$pr['request_number']} is currently {$statusName}. Hindi pa ito maaaring i-scan o i-claim dahil ginagawa pa ito. Paki-mark muna bilang Ready for Pick-up bago i-scan sa POS.";
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error'   => $errMsg,
                        'message' => $errMsg,
                    ]);
                }

                if ($pr['status'] === 'cancelled' || $pr['status'] === 'returned') {
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error'   => "Printing Request #{$pr['request_number']} has already been " . ($pr['status'] === 'returned' ? 'returned' : 'cancelled') . ".",
                        'message' => "Printing Request #{$pr['request_number']} has already been " . ($pr['status'] === 'returned' ? 'returned' : 'cancelled') . ".",
                    ]);
                }

                if (in_array($pr['status'], ['completed', 'delivered'], true)) {
                    $prUser = (new \App\Models\UserModel())->find($pr['customer_id'] ?? 0);
                    $prCustomerName = $prUser ? trim(($prUser['first_name'] ?? '') . ' ' . ($prUser['last_name'] ?? '')) : 'Online Customer';
                    return $this->response->setJSON([
                        'success'        => true,
                        'is_printing'    => true,
                        'already_done'   => true,
                        'message'        => "Printing Request #{$pr['request_number']} has already been completed / released.",
                        'request_id'     => (int) $pr['id'],
                        'request_number' => $pr['request_number'],
                        'redirect_url'   => site_url('tenant/pos?printing_id=' . $pr['id']),
                        'order'          => [
                            'id'             => (int) $pr['id'],
                            'order_number'   => $pr['request_number'],
                            'customer_name'  => $prCustomerName,
                            'customer_phone' => $prUser['phone'] ?? '',
                            'status'         => $pr['status'],
                            'payment_method' => 'ONLINE / COUNTER',
                            'subtotal'       => (float) ($pr['total_price'] ?? 0),
                            'total_amount'   => (float) ($pr['total_price'] ?? 0),
                            'placed_at'      => !empty($pr['created_at']) ? date('M d, Y h:i A', strtotime($pr['created_at'])) : date('M d, Y h:i A'),
                            'completed_at'   => !empty($pr['completed_at']) ? date('M d, Y h:i A', strtotime($pr['completed_at'])) : date('M d, Y h:i A'),
                            'receipt_url'    => site_url('tenant/printing/receipt/' . $pr['id']),
                        ],
                        'items'          => [
                            [
                                'id'             => (int) $pr['id'],
                                'product_id'     => 0,
                                'variant_id'     => 0,
                                'variant_label'  => '',
                                'name'           => 'Printing: ' . ($pr['file_name'] ?? 'Document.pdf'),
                                'price'          => (float) ($pr['total_price'] ?? 0),
                                'quantity'       => 1,
                                'line_total'     => (float) ($pr['total_price'] ?? 0),
                                'stock_quantity' => 999,
                                'image_url'      => null,
                            ]
                        ],
                    ]);
                }

                // Dispatch arrival notification to customer
                $customerId = (int) ($pr['customer_id'] ?? 0);
                if ($customerId > 0) {
                    $shopName = $shop['shop_name'] ?? 'the shop';
                    (new NotificationModel())->create(
                        $customerId,
                        'printing',
                        'Store Pick-up In Progress',
                        "Your printing request #{$pr['request_number']} is being processed at {$shopName}.",
                        '/customer/printing'
                    );
                }

                return $this->response->setJSON([
                    'success'        => true,
                    'is_printing'    => true,
                    'message'        => "Printing Request #{$pr['request_number']} successfully verified.",
                    'request_id'     => (int) $pr['id'],
                    'request_number' => $pr['request_number'],
                    'customer_id'    => (int) ($pr['customer_id'] ?? 0),
                    'redirect_url'   => site_url('tenant/pos?printing_id=' . $pr['id']),
                ]);
            }

            $firstToken = $tokens[0] ?? $rawCode;
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => "Order or request #{$firstToken} was not found in the system.",
                'message' => "Order or request #{$firstToken} was not found in the system.",
            ]);
        }

        // Tenant ownership check (Reject cross-shop access)
        if ((int) $order['shop_id'] !== $shopId) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error'   => "Order #{$order['order_number']} belongs to another shop, not {$shop['shop_name']}.",
                'message' => "Order #{$order['order_number']} belongs to another shop, not {$shop['shop_name']}.",
            ]);
        }

        // Fulfillment check (Store Pick-up)
        if (($order['fulfillment_method'] ?? '') !== 'pickup') {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => "Wrong fulfillment type: Order #{$order['order_number']} was placed for Doorstep Delivery. Please use the Deliveries scanner to process deliveries.",
                'message' => "Wrong fulfillment type: Order #{$order['order_number']} was placed for Doorstep Delivery. Please use the Deliveries scanner to process deliveries.",
            ]);
        }

        // Order eligibility check: reject pending and processing orders (must be marked ready_for_pickup first)
        if (in_array($order['status'], ['pending', 'processing', 'in_progress'], true)) {
            $statusName = strtoupper(humanize_status($order['status']));
            $errMsg = "Order #{$order['order_number']} is currently {$statusName}. Hindi pa ito maaaring i-scan o i-claim dahil inihahanda pa ito. Paki-mark muna bilang Ready for Pick-up bago i-scan sa POS.";
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => $errMsg,
                'message' => $errMsg,
            ]);
        }

        if ($order['status'] === 'returned') {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => "Order #{$order['order_number']} has already been marked as RETURNED.",
                'message' => "Order #{$order['order_number']} has already been marked as RETURNED.",
            ]);
        }

        if (in_array($order['status'], ['completed', 'delivered'], true)) {
            $orderItemModel = new \App\Models\OrderItemModel();
            $rawItems = $orderItemModel
                ->select('order_items.*, p.stock_quantity as current_stock, (SELECT image_url FROM product_images WHERE product_id = order_items.product_id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as gallery_image')
                ->join('products p', 'p.id = order_items.product_id', 'left')
                ->where('order_items.order_id', $order['id'])
                ->findAll();

            $customerName = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
            if ($customerName === '' || $customerName === 'Walk-in Customer') {
                $customerName = 'Counter Customer';
            }

            return $this->response->setJSON([
                'success'        => true,
                'already_done'   => true,
                'message'        => "Order #{$order['order_number']} has already been completed / released.",
                'order_id'       => (int) $order['id'],
                'order_number'   => $order['order_number'],
                'customer_id'    => (int) ($order['customer_id'] ?? 0),
                'redirect_url'   => site_url('tenant/pos?order_id=' . $order['id']),
                'order'          => [
                    'id'             => (int) $order['id'],
                    'order_number'   => $order['order_number'],
                    'customer_name'  => $customerName,
                    'customer_phone' => trim((string) ($order['customer_phone'] ?? '')),
                    'status'         => $order['status'],
                    'payment_method' => strtoupper($order['pos_payment_method'] ?: ($order['payment_method'] ?: 'CASH')),
                    'subtotal'       => (float) $order['subtotal'],
                    'total_amount'   => (float) $order['total_amount'],
                    'placed_at'      => !empty($order['placed_at']) ? date('M d, Y h:i A', strtotime($order['placed_at'])) : date('M d, Y h:i A'),
                    'completed_at'   => !empty($order['completed_at']) ? date('M d, Y h:i A', strtotime($order['completed_at'])) : (!empty($order['placed_at']) ? date('M d, Y h:i A', strtotime($order['placed_at'])) : date('M d, Y h:i A')),
                    'receipt_url'    => site_url('tenant/orders/receipt/' . $order['order_number']),
                ],
                'items'          => array_map(function($it) {
                    return [
                        'id'             => (int) $it['id'],
                        'product_id'     => (int) $it['product_id'],
                        'variant_id'     => (int) ($it['variant_id'] ?? 0),
                        'variant_label'  => $it['variant_label'] ?? '',
                        'name'           => $it['product_name'],
                        'price'          => (float) $it['unit_price'],
                        'quantity'       => (int) $it['quantity'],
                        'line_total'     => (float) $it['line_total'],
                        'stock_quantity' => (int) ($it['current_stock'] ?? 99),
                        'image_url'      => !empty($it['gallery_image']) ? product_image_url($it['gallery_image']) : null,
                    ];
                }, $rawItems),
            ]);
        }

        if ($order['status'] === 'cancelled') {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => "Order #{$order['order_number']} has been cancelled.",
                'message' => "Order #{$order['order_number']} has been cancelled.",
            ]);
        }

        // Dispatch arrival notification to customer's account
        $customerId = (int) ($order['customer_id'] ?? 0);
        if ($customerId > 0) {
            $shopName = $shop['shop_name'] ?? 'the shop';
            (new NotificationModel())->create(
                $customerId,
                'order_status',
                'Store Pick-up In Progress',
                "Your Store Pick-up order #{$order['order_number']} is being processed at {$shopName}.",
                '/customer/orders'
            );
        }

        return $this->response->setJSON([
            'success'      => true,
            'message'      => "Order #{$order['order_number']} successfully verified.",
            'order_id'     => (int) $order['id'],
            'order_number' => $order['order_number'],
            'customer_id'  => (int) $order['customer_id'],
            'redirect_url' => site_url('tenant/pos?order_id=' . $order['id']),
        ]);
    }

    /**
     * Mark a completed order or item as Returned from POS.
     * Restores product and variant inventory stock quantities,
     * synchronizes delivery status, and notifies customer.
     */
    public function posReturnOrder()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized.']);
        }

        $shopId = (int) $res['shopId'];
        $shop   = $res['shop'];

        $orderId    = (int) $this->request->getPost('order_id');
        $printingId = (int) $this->request->getPost('printing_id');

        $db = \Config\Database::connect();

        if ($orderId > 0) {
            $orderModel = new OrderModel();
            $order = $orderModel->where('id', $orderId)->where('shop_id', $shopId)->first();

            if (!$order) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success'   => false,
                    'error'     => 'Order not found or does not belong to your shop.',
                    'csrf_hash' => csrf_hash(),
                ]);
            }

            if ($order['status'] === 'returned') {
                return $this->response->setStatusCode(400)->setJSON([
                    'success'   => false,
                    'error'     => "Order #{$order['order_number']} has already been marked as Returned.",
                    'csrf_hash' => csrf_hash(),
                ]);
            }

            $db->transStart();

            $now = date('Y-m-d H:i:s');
            // 1. Update order status to returned
            $orderModel->update($orderId, [
                'status'        => 'returned',
                'cancelled_at'  => $now,
                'cancel_reason' => 'Returned via POS Counter',
            ]);

            // 2. Restore inventory stock for all products and variants in this order
            $orderItemModel = new OrderItemModel();
            $orderItems = $orderItemModel->where('order_id', $orderId)->findAll();
            $restoredCount = 0;

            foreach ($orderItems as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $pId = (int) ($item['product_id'] ?? 0);
                $vId = (int) ($item['variant_id'] ?? 0);

                if ($pId > 0 && $qty > 0) {
                    $db->table('products')
                        ->where('id', $pId)
                        ->where('shop_id', $shopId)
                        ->set('stock_quantity', 'stock_quantity + ' . $qty, false)
                        ->update();

                    if ($vId > 0) {
                        $db->table('product_variants')
                            ->where('id', $vId)
                            ->set('stock_quantity', 'stock_quantity + ' . $qty, false)
                            ->update();
                    }
                    $restoredCount += $qty;
                }
            }

            // 3. Update any linked delivery
            $deliveryModel = new DeliveryModel();
            $delivery = $deliveryModel
                ->where('deliverable_type', 'order')
                ->where('deliverable_id', $orderId)
                ->first();

            if ($delivery && $delivery['status'] !== 'returned') {
                $deliveryModel->update($delivery['id'], [
                    'status' => 'returned',
                ]);
            }

            // 4. Notify customer if valid registered user
            $customerId = (int) ($order['customer_id'] ?? 0);
            if ($customerId > 0) {
                $shopName = $shop['shop_name'] ?? 'the shop';
                (new NotificationModel())->create(
                    $customerId,
                    'order_status',
                    'Order Marked as Returned',
                    "Your order #{$order['order_number']} has been processed as Returned at {$shopName}.",
                    '/customer/orders'
                );
            }

            // 5. Audit Log
            try {
                (new AuditLogModel())->log(
                    (int) session()->get('user_id'),
                    'tenant',
                    'order_returned',
                    'order',
                    'success',
                    $orderId,
                    $this->request->getIPAddress()
                );
            } catch (\Throwable $t) {}

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success'   => false,
                    'error'     => 'Database error while processing return. Please try again.',
                    'csrf_hash' => csrf_hash(),
                ]);
            }

            return $this->response->setJSON([
                'success'        => true,
                'message'        => "Order #{$order['order_number']} successfully marked as RETURNED. {$restoredCount} items restored to inventory.",
                'order_number'   => $order['order_number'],
                'restored_items' => $restoredCount,
                'csrf_hash'      => csrf_hash(),
            ]);
        }

        if ($printingId > 0) {
            $prModel = new PrintingRequestModel();
            $pr = $prModel->where('id', $printingId)->where('shop_id', $shopId)->first();

            if (!$pr) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success'   => false,
                    'error'     => 'Printing request not found or does not belong to your shop.',
                    'csrf_hash' => csrf_hash(),
                ]);
            }

            $db->transStart();
            $prModel->update($printingId, [
                'status' => 'cancelled',
            ]);

            $deliveryModel = new DeliveryModel();
            $delivery = $deliveryModel
                ->where('deliverable_type', 'printing_request')
                ->where('deliverable_id', $printingId)
                ->first();

            if ($delivery && $delivery['status'] !== 'returned') {
                $deliveryModel->update($delivery['id'], [
                    'status' => 'returned',
                ]);
            }

            $customerId = (int) ($pr['customer_id'] ?? 0);
            if ($customerId > 0) {
                $shopName = $shop['shop_name'] ?? 'the shop';
                (new NotificationModel())->create(
                    $customerId,
                    'printing',
                    'Printing Request Returned',
                    "Your printing request #{$pr['request_number']} has been processed as Returned at {$shopName}.",
                    '/customer/printing'
                );
            }

            $db->transComplete();

            return $this->response->setJSON([
                'success'      => true,
                'message'      => "Printing Request #{$pr['request_number']} successfully processed as returned.",
                'csrf_hash'    => csrf_hash(),
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success'   => false,
            'error'     => 'No valid order or printing request specified.',
            'csrf_hash' => csrf_hash(),
        ]);
    }

    /**
     * Complete a Store Pick-up order with optional additional in-store items via POS.
     * Strictly tenant-scoped, atomic transaction, with stock validation and audit logging.
     */
    public function posCompletePickup()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId = (int) $res['shopId'];
        $userId = (int) session()->get('user_id');

        $orderId    = (int) $this->request->getPost('order_id');
        $printingId = (int) $this->request->getPost('printing_id');
        $rawItems   = $this->request->getPost('items');
        $items      = is_array($rawItems) ? $rawItems : (json_decode((string)$rawItems, true) ?: []);
        $counterMethod = strtolower(trim((string) $this->request->getPost('counter_payment_method') ?? 'cash'));
        if (!in_array($counterMethod, ['cash', 'gcash', 'card', 'none'], true)) {
            $counterMethod = 'cash';
        }

        // =========================================================================
        // BRANCH C: COMBINED PRODUCT ORDER + PRINTING REQUEST PICK-UP
        // =========================================================================
        if ($orderId > 0 && $printingId > 0) {
            $orderModel = new OrderModel();
            $order = $orderModel->find($orderId);

            if (!$order || (int) $order['shop_id'] !== $shopId) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Order not found for your shop.']);
            }
            if (($order['fulfillment_method'] ?? '') !== 'pickup') {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'POS completion is only permitted for Store Pick-up orders.']);
            }
            if ($order['status'] !== 'ready_for_pickup') {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Order #' . $order['order_number'] . ' is ' . humanize_status($order['status']) . '. Only Ready for Pick-up orders can be completed in POS.']);
            }

            $prModel = new PrintingRequestModel();
            $pr = $prModel->find($printingId);

            if (!$pr || (int) $pr['shop_id'] !== $shopId) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Printing request not found for your shop.']);
            }
            if (($pr['fulfillment_method'] ?? '') !== 'pickup') {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'POS completion is only permitted for Store Pick-up printing requests.']);
            }
            if (!in_array($pr['status'], ['ready_for_pickup', 'ready'], true)) {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Printing request #' . $pr['request_number'] . ' is ' . humanize_status($pr['status']) . '. Only Ready for Pick-up requests can be completed in POS.']);
            }

            $db = \Config\Database::connect();
            $db->transStart();

            $productModel       = new ProductModel();
            $orderItemModel     = new OrderItemModel();
            $additionalSubtotal = 0.00;
            $addedItemSummaries = [];

            // 1. Validate and process in-store add-on items (linked to order)
            foreach ($items as $it) {
                $productId = (int) ($it['product_id'] ?? 0);
                $quantity  = max(1, (int) ($it['quantity'] ?? 1));
                if ($productId <= 0) continue;

                // Lock row FOR UPDATE to prevent race condition / negative stock
                $product = $db->query(
                    'SELECT * FROM products WHERE id = ? AND shop_id = ? AND deleted_at IS NULL FOR UPDATE',
                    [$productId, $shopId]
                )->getRowArray();

                if (!$product) {
                    $db->transRollback();
                    return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => "Product #{$productId} is not available in your shop."]);
                }

                if ((int) $product['stock_quantity'] < $quantity) {
                    $db->transRollback();
                    return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => "Insufficient stock for '{$product['name']}'. Available: {$product['stock_quantity']}, Requested: {$quantity}."]);
                }

                $variantId = (int) ($it['variant_id'] ?? 0);
                if ($variantId > 0) {
                    // Lock row FOR UPDATE to prevent race condition / negative stock
                    $variant = $db->query(
                        'SELECT * FROM product_variants WHERE id = ? AND product_id = ? FOR UPDATE',
                        [$variantId, $productId]
                    )->getRowArray();

                    if (!$variant) {
                        $db->transRollback();
                        return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => "Selected variant for '{$product['name']}' is not available in your shop."]);
                    }

                    if ((int) $variant['stock_quantity'] < $quantity) {
                        $db->transRollback();
                        return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => "Insufficient stock for '{$product['name']}'. Available: {$variant['stock_quantity']}, Requested: {$quantity}."]);
                    }

                    $db->table('product_variants')->where('id', $variantId)->update([
                        'stock_quantity' => max(0, (int) $variant['stock_quantity'] - $quantity),
                    ]);
                }

                $unitPrice = (float) $product['price'];
                $lineTotal = round($unitPrice * $quantity, 2);
                $additionalSubtotal += $lineTotal;

                $newStock = (int) $product['stock_quantity'] - $quantity;
                $productModel->update($productId, ['stock_quantity' => $newStock]);
                $this->maybeNotifyLowStock($shopId, $product, $newStock);

                $orderItemModel->insert([
                    'order_id'        => $orderId,
                    'product_id'      => $productId,
                    'variant_id'      => $variantId > 0 ? $variantId : null,
                    'variant_label'   => !empty($it['variant_label']) ? $it['variant_label'] : null,
                    'product_name'    => $product['name'],
                    'quantity'        => $quantity,
                    'unit_price'      => $unitPrice,
                    'line_total'      => $lineTotal,
                    'is_pos_addition' => 1,
                ]);

                $addedItemSummaries[] = "{$quantity}x {$product['name']}";
            }

            // 2. Finalize product order
            $onlineOrderPaid = ($order['payment_status'] ?? '') === 'paid';
            $orderDue = !$onlineOrderPaid ? (float) $order['total_amount'] : 0.0;
            $finalOrderTotal = round((float) $order['total_amount'] + $additionalSubtotal, 2);

            $orderUpdate = [
                'pos_additional_amount' => $additionalSubtotal,
                'pos_payment_method'    => ($additionalSubtotal > 0 || !$onlineOrderPaid) ? $counterMethod : 'none',
                'pos_payment_status'    => ($additionalSubtotal > 0 || !$onlineOrderPaid) ? 'paid' : 'not_applicable',
                'total_amount'          => $finalOrderTotal,
                'status'                => 'completed',
                'completed_at'          => date('Y-m-d H:i:s'),
            ];
            if (!$onlineOrderPaid) {
                $orderUpdate['payment_status'] = 'paid';
            }
            $orderModel->update($orderId, $orderUpdate);

            $orderCounterOwed = round($orderDue + $additionalSubtotal, 2);
            if ($orderCounterOwed > 0) {
                (new PaymentModel())->insert([
                    'payable_type'     => 'order',
                    'payable_id'       => $orderId,
                    'method'           => $counterMethod,
                    'amount'           => $orderCounterOwed,
                    'reference_number' => null,
                    'proof_image_url'  => null,
                    'status'           => 'verified',
                    'processed_at'     => date('Y-m-d H:i:s'),
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
            }

            // 3. Finalize printing request
            $verifiedPrPayments = (new PaymentModel())
                ->where('payable_type', 'printing_request')
                ->where('payable_id', $printingId)
                ->where('status', 'verified')
                ->findAll();

            $prOnlinePaid = 0.0;
            foreach ($verifiedPrPayments as $pm) {
                $prOnlinePaid += (float) $pm['amount'];
            }
            if ($prOnlinePaid <= 0 && (float) ($pr['down_payment'] ?? 0) > 0) {
                $prOnlinePaid = (float) $pr['down_payment'];
            }
            $prTotal = (float) $pr['total_price'];
            $prRemainingBalance = max(0.0, round($prTotal - $prOnlinePaid, 2));

            $prModel->update($printingId, [
                'status'           => 'completed',
                'progress_percent' => 100,
                'completed_at'     => date('Y-m-d H:i:s'),
            ]);

            if ($prRemainingBalance > 0) {
                (new PaymentModel())->insert([
                    'payable_type'     => 'printing_request',
                    'payable_id'       => $printingId,
                    'method'           => $counterMethod,
                    'amount'           => $prRemainingBalance,
                    'reference_number' => null,
                    'proof_image_url'  => null,
                    'status'           => 'verified',
                    'processed_at'     => date('Y-m-d H:i:s'),
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
            }

            // 4. Sync linked delivery records if any
            $deliveryModel = new DeliveryModel();
            $delOrder = $deliveryModel->where('deliverable_type', 'order')->where('deliverable_id', $orderId)->first();
            if ($delOrder && !in_array($delOrder['status'], ['delivered', 'returned'], true)) {
                $deliveryModel->update($delOrder['id'], ['status' => 'delivered', 'delivered_at' => date('Y-m-d H:i:s')]);
            }
            $delPr = $deliveryModel->where('deliverable_type', 'printing_request')->where('deliverable_id', $printingId)->first();
            if ($delPr && !in_array($delPr['status'], ['delivered', 'returned'], true)) {
                $deliveryModel->update($delPr['id'], ['status' => 'delivered', 'delivered_at' => date('Y-m-d H:i:s')]);
            }

            // 5. Audit logs for both
            $auditLogModel = new \App\Models\AuditLogModel();
            $auditLogModel->log($userId, 'tenant', 'pos_pickup_complete', 'order', 'success', $orderId, $this->request->getIPAddress());
            $auditLogModel->log($userId, 'tenant', 'pos_pickup_complete', 'printing_request', 'success', $printingId, $this->request->getIPAddress());

            // 6. Notifications for both
            $notifModel = new NotificationModel();
            $shop = (new ShopModel())->find($shopId);
            $shopName = $shop['shop_name'] ?? 'the shop';
            $custOrder = (int) ($order['customer_id'] ?? 0);
            if ($custOrder > 0) {
                $notifModel->create(
                    $custOrder,
                    'order_status',
                    'Pick-up Order Completed',
                    "Your Store Pick-up order #{$order['order_number']} has been completed at {$shopName}.",
                    '/customer/orders'
                );
            }
            $custPr = (int) ($pr['customer_id'] ?? 0);
            if ($custPr > 0) {
                $fileName = !empty($pr['file_name']) ? $pr['file_name'] : 'Document.pdf';
                $notifModel->create(
                    $custPr,
                    'printing',
                    'Pick-up Printing Completed',
                    "Your printing request for {$fileName} (#{$pr['request_number']}) has been completed and collected at {$shopName}.",
                    '/customer/printing'
                );
            }

            $db->transComplete();
            if ($db->transStatus() === false) {
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => 'A database error occurred while completing the combined pick-up.']);
            }

            $combinedCounterDue = round($orderCounterOwed + $prRemainingBalance, 2);

            $custRow = !empty($order['customer_id']) ? (new UserModel())->find((int) $order['customer_id']) : null;
            $custNameCombined = $custRow ? trim(($custRow['first_name'] ?? '') . ' ' . ($custRow['last_name'] ?? '')) : 'Counter Customer';

            return $this->response->setJSON([
                'success'             => true,
                'type'                => 'combined',
                'is_combined'         => true,
                'message'             => "Combined Pick-up (Order #{$order['order_number']} & Printing #{$pr['request_number']}) completed successfully!",
                'order_id'            => (int) $order['id'],
                'order_number'        => $order['order_number'],
                'customer_name'       => $custNameCombined,
                'printing_id'         => (int) $pr['id'],
                'printing_number'     => $pr['request_number'],
                'additional_subtotal' => $additionalSubtotal,
                'order_due'           => $orderDue,
                'printing_balance'    => $prRemainingBalance,
                'final_total'         => $combinedCounterDue,
                'total_collected'     => $combinedCounterDue,
            ]);
        }

        // =========================================================================
        // BRANCH A: PRINTING REQUEST STORE PICK-UP ONLY
        // =========================================================================
        if ($printingId > 0) {
            $prModel = new PrintingRequestModel();
            $pr = $prModel->find($printingId);

            if (!$pr || (int) $pr['shop_id'] !== $shopId) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Printing request not found for your shop.']);
            }

            if (($pr['fulfillment_method'] ?? '') !== 'pickup') {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'POS completion is only permitted for Store Pick-up printing requests.']);
            }

            if (!in_array($pr['status'], ['ready_for_pickup', 'ready'], true)) {
                $msg = in_array($pr['status'], ['completed', 'delivered'], true)
                    ? 'This printing request is already completed.'
                    : 'This printing request is ' . humanize_status($pr['status']) . '. Only Ready for Pick-up requests can be completed in POS.';
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => $msg]);
            }

            $db = \Config\Database::connect();
            $db->transStart();

            $productModel       = new ProductModel();
            $additionalSubtotal = 0.00;
            $addedItemSummaries = [];

            // Validate and process in-store items (if any added at counter)
            foreach ($items as $it) {
                $productId = (int) ($it['product_id'] ?? 0);
                $quantity  = max(1, (int) ($it['quantity'] ?? 1));

                if ($productId <= 0) continue;

                // Lock row FOR UPDATE to prevent race condition / negative stock
                $product = $db->query(
                    'SELECT * FROM products WHERE id = ? AND shop_id = ? AND deleted_at IS NULL FOR UPDATE',
                    [$productId, $shopId]
                )->getRowArray();

                if (!$product) {
                    $db->transRollback();
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error'   => "Product #{$productId} is not available in your shop.",
                    ]);
                }

                if ((int) $product['stock_quantity'] < $quantity) {
                    $db->transRollback();
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error'   => "Insufficient stock for '{$product['name']}'. Available: {$product['stock_quantity']}, Requested: {$quantity}.",
                    ]);
                }

                $variantId = (int) ($it['variant_id'] ?? 0);
                if ($variantId > 0) {
                    // Lock row FOR UPDATE to prevent race condition / negative stock
                    $variant = $db->query(
                        'SELECT * FROM product_variants WHERE id = ? AND product_id = ? FOR UPDATE',
                        [$variantId, $productId]
                    )->getRowArray();

                    if (!$variant) {
                        $db->transRollback();
                        return $this->response->setStatusCode(400)->setJSON([
                            'success' => false,
                            'error'   => "Selected variant for '{$product['name']}' is not available in your shop.",
                        ]);
                    }

                    if ((int) $variant['stock_quantity'] < $quantity) {
                        $db->transRollback();
                        return $this->response->setStatusCode(400)->setJSON([
                            'success' => false,
                            'error'   => "Insufficient stock for '{$product['name']}'. Available: {$variant['stock_quantity']}, Requested: {$quantity}.",
                        ]);
                    }

                    $db->table('product_variants')->where('id', $variantId)->update([
                        'stock_quantity' => max(0, (int) $variant['stock_quantity'] - $quantity),
                    ]);
                }

                $unitPrice = (float) $product['price'];
                $lineTotal = round($unitPrice * $quantity, 2);
                $additionalSubtotal += $lineTotal;

                // Deduct stock safely
                $newStock = (int) $product['stock_quantity'] - $quantity;
                $productModel->update($productId, ['stock_quantity' => $newStock]);

                // Low stock check
                $this->maybeNotifyLowStock($shopId, $product, $newStock);

                $addedItemSummaries[] = "{$quantity}x {$product['name']}";
            }

            // Calculate verified payments already paid online
            $verifiedPayments = (new PaymentModel())
                ->where('payable_type', 'printing_request')
                ->where('payable_id', $printingId)
                ->where('status', 'verified')
                ->findAll();

            $onlinePaid = 0.0;
            foreach ($verifiedPayments as $pm) {
                $onlinePaid += (float) $pm['amount'];
            }
            if ($onlinePaid <= 0 && (float) ($pr['down_payment'] ?? 0) > 0) {
                $onlinePaid = (float) $pr['down_payment'];
            }

            $totalPrintPrice   = (float) $pr['total_price'];
            $remainingBalance  = max(0.0, round($totalPrintPrice - $onlinePaid, 2));
            $counterAmountDue  = round($remainingBalance + $additionalSubtotal, 2);

            // Transition printing request to completed
            $prModel->update($printingId, [
                'status'           => 'completed',
                'progress_percent' => 100,
                'completed_at'     => date('Y-m-d H:i:s'),
            ]);

            // Sync corresponding delivery record if any exists
            $deliveryModel = new DeliveryModel();
            $delRow = $deliveryModel->where('deliverable_type', 'printing_request')->where('deliverable_id', $printingId)->first();
            if ($delRow && !in_array($delRow['status'], ['delivered', 'returned'], true)) {
                $deliveryModel->update($delRow['id'], ['status' => 'ready_for_pickup']);
            }

            // Record payment for counter settlement (remaining balance and/or in-store items)
            if ($counterAmountDue > 0) {
                $paymentModel = new PaymentModel();
                $paymentModel->insert([
                    'payable_type'     => 'printing_request',
                    'payable_id'       => $printingId,
                    'method'           => $counterMethod,
                    'amount'           => $counterAmountDue,
                    'reference_number' => null,
                    'proof_image_url'  => null,
                    'status'           => 'verified',
                    'processed_at'     => date('Y-m-d H:i:s'),
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
            }

            // Audit log
            (new \App\Models\AuditLogModel())->log(
                $userId,
                'tenant',
                'pos_pickup_complete',
                'printing_request',
                'success',
                $printingId,
                $this->request->getIPAddress()
            );

            // Customer notification
            $customerId = (int) ($pr['customer_id'] ?? 0);
            if ($customerId > 0) {
                $shop = (new ShopModel())->find($shopId);
                $shopName = $shop['shop_name'] ?? 'the shop';
                $fileName = !empty($pr['file_name']) ? $pr['file_name'] : 'Document.pdf';

                if ($additionalSubtotal > 0) {
                    $itemsStr = !empty($addedItemSummaries) ? implode(', ', $addedItemSummaries) : 'in-store items';
                    (new NotificationModel())->create(
                        $customerId,
                        'printing',
                        'Additional Items Purchased',
                        "₱" . number_format($additionalSubtotal, 2) . " in additional items ({$itemsStr}) were purchased with Printing Request #{$pr['request_number']} at {$shopName}.",
                        '/customer/printing'
                    );
                }

                (new NotificationModel())->create(
                    $customerId,
                    'printing',
                    'Pick-up Printing Completed',
                    "Your printing request for {$fileName} (#{$pr['request_number']}) has been completed and collected at {$shopName}.",
                    '/customer/printing'
                );
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'error'   => 'A database error occurred while completing the printing pick-up.',
                ]);
            }

            $custRowPrint = !empty($pr['customer_id']) ? (new UserModel())->find((int) $pr['customer_id']) : null;
            $custNamePrint = $custRowPrint ? trim(($custRowPrint['first_name'] ?? '') . ' ' . ($custRowPrint['last_name'] ?? '')) : 'Counter Customer';

            return $this->response->setJSON([
                'success'             => true,
                'is_printing'         => true,
                'message'             => 'Printing pick-up completed successfully.',
                'order_id'            => $pr['request_number'],
                'order_number'        => $pr['request_number'],
                'customer_name'       => $custNamePrint,
                'additional_subtotal' => $additionalSubtotal,
                'remaining_balance'   => $remainingBalance,
                'final_total'         => $counterAmountDue,
            ]);
        }

        // =========================================================================
        // BRANCH B: PRODUCT ORDER STORE PICK-UP
        // =========================================================================
        $orderModel = new OrderModel();
        $order = $orderModel->find($orderId);

        if (!$order || (int) $order['shop_id'] !== $shopId) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Order not found for your shop.']);
        }

        if (($order['fulfillment_method'] ?? '') !== 'pickup') {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'POS completion is only permitted for Store Pick-up orders.']);
        }

        if ($order['status'] !== 'ready_for_pickup') {
            $msg = in_array($order['status'], ['completed', 'delivered'], true)
                ? 'This order is already ' . $order['status'] . '.'
                : 'Order #' . $order['order_number'] . ' is ' . humanize_status($order['status']) . '. Only Ready for Pick-up orders can be completed in POS.';
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => $msg]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $productModel   = new ProductModel();
        $orderItemModel = new OrderItemModel();

        $additionalSubtotal = 0.00;
        $addedItemSummaries = [];

        // Validate and process in-store items
        foreach ($items as $it) {
            $productId = (int) ($it['product_id'] ?? 0);
            $quantity  = max(1, (int) ($it['quantity'] ?? 1));

            if ($productId <= 0) continue;

            // Lock row FOR UPDATE to prevent race condition / negative stock
            $product = $db->query(
                'SELECT * FROM products WHERE id = ? AND shop_id = ? AND deleted_at IS NULL FOR UPDATE',
                [$productId, $shopId]
            )->getRowArray();

            if (!$product) {
                $db->transRollback();
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error'   => "Product #{$productId} is not available in your shop.",
                ]);
            }

            if ((int) $product['stock_quantity'] < $quantity) {
                $db->transRollback();
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error'   => "Insufficient stock for '{$product['name']}'. Available: {$product['stock_quantity']}, Requested: {$quantity}.",
                ]);
            }

            $variantId = (int) ($it['variant_id'] ?? 0);
            if ($variantId > 0) {
                // Lock row FOR UPDATE to prevent race condition / negative stock
                $variant = $db->query(
                    'SELECT * FROM product_variants WHERE id = ? AND product_id = ? FOR UPDATE',
                    [$variantId, $productId]
                )->getRowArray();

                if (!$variant) {
                    $db->transRollback();
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error'   => "Selected variant for '{$product['name']}' is not available in your shop.",
                    ]);
                }

                if ((int) $variant['stock_quantity'] < $quantity) {
                    $db->transRollback();
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error'   => "Insufficient stock for '{$product['name']}'. Available: {$variant['stock_quantity']}, Requested: {$quantity}.",
                    ]);
                }

                $db->table('product_variants')->where('id', $variantId)->update([
                    'stock_quantity' => max(0, (int) $variant['stock_quantity'] - $quantity),
                ]);
            }

            $unitPrice = (float) $product['price'];
            $lineTotal = round($unitPrice * $quantity, 2);
            $additionalSubtotal += $lineTotal;

            // Deduct stock safely
            $newStock = (int) $product['stock_quantity'] - $quantity;
            $productModel->update($productId, ['stock_quantity' => $newStock]);

            // Low stock check
            $this->maybeNotifyLowStock($shopId, $product, $newStock);

            // Insert as POS addition
            $orderItemModel->insert([
                'order_id'        => $orderId,
                'product_id'      => $productId,
                'variant_id'      => $variantId > 0 ? $variantId : null,
                'variant_label'   => !empty($it['variant_label']) ? $it['variant_label'] : null,
                'product_name'    => $product['name'],
                'quantity'        => $quantity,
                'unit_price'      => $unitPrice,
                'line_total'      => $lineTotal,
                'is_pos_addition' => 1,
            ]);

            $addedItemSummaries[] = "{$quantity}x {$product['name']}";
        }

        // Finalize order
        $onlinePaid = ($order['payment_status'] ?? '') === 'paid';
        $finalTotal = round((float) $order['total_amount'] + $additionalSubtotal, 2);

        $orderUpdate = [
            'pos_additional_amount' => $additionalSubtotal,
            'pos_payment_method'    => $additionalSubtotal > 0 ? $counterMethod : 'none',
            'pos_payment_status'    => $additionalSubtotal > 0 ? 'paid' : 'not_applicable',
            'total_amount'          => $finalTotal,
            'status'                => 'completed',
            'completed_at'          => date('Y-m-d H:i:s'),
        ];

        // If online order was unpaid/COD, customer settled total at counter
        if (!$onlinePaid) {
            $orderUpdate['payment_status'] = 'paid';
        }

        $orderModel->update($orderId, $orderUpdate);

        // Record payment for POS addition if applicable
        if ($additionalSubtotal > 0) {
            $paymentModel = new PaymentModel();
            $paymentModel->insert([
                'payable_type'   => 'order',
                'payable_id'     => $orderId,
                'method'         => $counterMethod,
                'amount'         => $additionalSubtotal,
                'reference_number' => null,
                'proof_image_url' => null,
                'status'         => 'verified',
                'processed_at'   => date('Y-m-d H:i:s'),
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        }

        // Record in audit log
        (new \App\Models\AuditLogModel())->log(
            $userId,
            'tenant',
            'pos_pickup_complete',
            'order',
            'success',
            $orderId,
            $this->request->getIPAddress()
        );

        // Notify customer
        $customerId = (int) ($order['customer_id'] ?? 0);
        if ($customerId > 0) {
            $shop = (new ShopModel())->find($shopId);
            $shopName = $shop['shop_name'] ?? 'the shop';
            $currentTime = date('M d, Y h:i A');

            if ($additionalSubtotal > 0) {
                $itemsStr = !empty($addedItemSummaries) ? implode(', ', $addedItemSummaries) : 'in-store items';
                (new NotificationModel())->create(
                    $customerId,
                    'order_status',
                    'Additional Items Added',
                    "₱" . number_format($additionalSubtotal, 2) . " in additional items ({$itemsStr}) were added to Order #{$order['order_number']} at {$shopName} on {$currentTime}.",
                    '/customer/orders'
                );
            }

            (new NotificationModel())->create(
                $customerId,
                'order_status',
                'Pick-up Order Completed',
                "Your Store Pick-up order #{$order['order_number']} has been completed at {$shopName}.",
                '/customer/orders'
            );
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error'   => 'A database error occurred while completing the pick-up.',
            ]);
        }

        $custRowOrder = !empty($order['customer_id']) ? (new UserModel())->find((int) $order['customer_id']) : null;
        $custNamePickup = $custRowOrder ? trim(($custRowOrder['first_name'] ?? '') . ' ' . ($custRowOrder['last_name'] ?? '')) : 'Counter Customer';

        return $this->response->setJSON([
            'success'             => true,
            'message'             => 'Store pick-up completed successfully.',
            'order_id'            => (int) $orderId,
            'order_number'        => $order['order_number'],
            'customer_name'       => $custNamePickup,
            'additional_subtotal' => $additionalSubtotal,
            'final_total'         => $finalTotal,
        ]);
    }

    /**
     * Complete an instant walk-in counter sale via POS (no prior website order).
     * Strictly tenant-scoped, atomic transaction with row locking and audit logging.
     */
    public function posCompleteWalkin()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $shopId = (int) $res['shopId'];
        $userId = (int) session()->get('user_id');

        $rawItems = $this->request->getPost('items');
        $items = is_array($rawItems) ? $rawItems : (json_decode((string)$rawItems, true) ?: []);
        $counterMethod = strtolower(trim((string) $this->request->getPost('counter_payment_method') ?? 'cash'));
        if (!in_array($counterMethod, ['cash', 'gcash', 'card'], true)) {
            $counterMethod = 'cash';
        }

        $customerNameNote = trim((string) $this->request->getPost('customer_name') ?? '');

        if (empty($items)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'Please add at least one product for this walk-in sale.',
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $productModel   = new ProductModel();
        $orderModel     = new OrderModel();
        $orderItemModel = new OrderItemModel();
        $userModel      = new UserModel();

        // Ensure a Walk-in Customer user record exists to satisfy foreign key constraints
        $walkinUser = $userModel->where('email', 'walkin@blax.local')->first();
        if (!$walkinUser) {
            $walkinCustomerId = $userModel->insert([
                'role'          => 'customer',
                'first_name'    => 'Walk-in',
                'last_name'     => 'Customer',
                'email'         => 'walkin@blax.local',
                'phone'         => 'N/A',
                'password_hash' => password_hash('walkin_' . bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
                'status'        => 'active',
            ]);
        } else {
            $walkinCustomerId = (int) $walkinUser['id'];
        }

        $totalAmount = 0.00;
        $validatedItems = [];

        // Validate products and check stock availability with pessimistic row locking
        foreach ($items as $it) {
            $productId = (int) ($it['product_id'] ?? 0);
            $quantity  = max(1, (int) ($it['quantity'] ?? 1));

            if ($productId <= 0) continue;

            // Lock row FOR UPDATE to prevent race condition / negative stock
            $product = $db->query(
                'SELECT * FROM products WHERE id = ? AND shop_id = ? AND deleted_at IS NULL FOR UPDATE',
                [$productId, $shopId]
            )->getRowArray();

            if (!$product) {
                $db->transRollback();
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error'   => "Product #{$productId} is not available in your shop catalog.",
                ]);
            }

            if ((int) $product['stock_quantity'] < $quantity) {
                $db->transRollback();
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error'   => "Insufficient stock for '{$product['name']}'. Available: {$product['stock_quantity']}, Requested: {$quantity}.",
                ]);
            }

            $variantId = (int) ($it['variant_id'] ?? 0);
            if ($variantId > 0) {
                // Lock row FOR UPDATE to prevent race condition / negative stock
                $variant = $db->query(
                    'SELECT * FROM product_variants WHERE id = ? AND product_id = ? FOR UPDATE',
                    [$variantId, $productId]
                )->getRowArray();

                if (!$variant) {
                    $db->transRollback();
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error'   => "Selected variant for '{$product['name']}' is not available in your shop catalog.",
                    ]);
                }

                if ((int) $variant['stock_quantity'] < $quantity) {
                    $db->transRollback();
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error'   => "Insufficient stock for '{$product['name']}'. Available: {$variant['stock_quantity']}, Requested: {$quantity}.",
                    ]);
                }

                $db->table('product_variants')->where('id', $variantId)->update([
                    'stock_quantity' => max(0, (int) $variant['stock_quantity'] - $quantity),
                ]);
            }

            $unitPrice = (float) $product['price'];
            $lineTotal = round($unitPrice * $quantity, 2);
            $totalAmount += $lineTotal;

            // Deduct inventory
            $newStock = (int) $product['stock_quantity'] - $quantity;
            $productModel->update($productId, ['stock_quantity' => $newStock]);

            // Check low stock threshold
            $this->maybeNotifyLowStock($shopId, $product, $newStock);

            $validatedItems[] = [
                'product_id'    => $productId,
                'variant_id'    => $variantId > 0 ? $variantId : null,
                'variant_label' => !empty($it['variant_label']) ? $it['variant_label'] : null,
                'product_name'  => $product['name'],
                'quantity'      => $quantity,
                'unit_price'    => $unitPrice,
                'line_total'    => $lineTotal,
            ];
        }

        if (empty($validatedItems)) {
            $db->transRollback();
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'No valid products selected for this walk-in sale.',
            ]);
        }

        $orderNumber = 'ORD-POS-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $enumPaymentMethod = match ($counterMethod) {
            'card'  => 'card',
            'gcash' => 'gcash',
            default => 'cod',
        };

        $now = date('Y-m-d H:i:s');

        // Create completed order
        $orderId = $orderModel->insert([
            'order_number'          => $orderNumber,
            'customer_id'           => $walkinCustomerId,
            'shop_id'               => $shopId,
            'shipping_address_id'   => null,
            'fulfillment_method'    => 'pickup',
            'payment_method'        => $enumPaymentMethod,
            'subtotal'              => $totalAmount,
            'shipping_fee'          => 0.00,
            'tax_amount'            => 0.00,
            'total_amount'          => $totalAmount,
            'pos_additional_amount' => $totalAmount,
            'pos_payment_method'    => $counterMethod,
            'pos_payment_status'    => 'paid',
            'status'                => 'completed',
            'payment_status'        => 'paid',
            'placed_at'             => $now,
            'completed_at'          => $now,
            'cancel_reason'         => $customerNameNote !== '' ? ('Walk-in: ' . $customerNameNote) : null,
        ]);

        // Insert items
        foreach ($validatedItems as $vIt) {
            $orderItemModel->insert([
                'order_id'      => $orderId,
                'product_id'    => $vIt['product_id'],
                'variant_id'    => $vIt['variant_id'] ?? null,
                'variant_label' => $vIt['variant_label'] ?? null,
                'product_name'  => $vIt['product_name'],
                'quantity'      => $vIt['quantity'],
                'unit_price'    => $vIt['unit_price'],
                'line_total'    => $vIt['line_total'],
            ]);
        }

        // Record payment for walk-in sale
        $paymentModel = new PaymentModel();
        $paymentModel->insert([
            'payable_type'   => 'order',
            'payable_id'     => $orderId,
            'method'         => $counterMethod,
            'amount'         => $totalAmount,
            'reference_number' => null,
            'proof_image_url' => null,
            'status'         => 'verified',
            'processed_at'   => $now,
            'created_at'     => $now,
        ]);

        // Audit log
        $auditLogModel = new AuditLogModel();
        $auditLogModel->insert([
            'actor_id'    => $userId,
            'actor_role'  => 'shop_owner',
            'action'      => 'pos_walkin_sale',
            'target_type' => 'order',
            'target_id'   => $orderId,
            'status'      => 'success',
            'ip_address'  => $this->request->getIPAddress(),
            'created_at'  => $now,
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error'   => 'Database error while finalizing walk-in POS sale.',
            ]);
        }

        return $this->response->setJSON([
            'success'       => true,
            'message'       => "Walk-in sale completed successfully! Order #{$orderNumber}",
            'order_id'      => $orderId,
            'order_number'  => $orderNumber,
            'total_amount'  => $totalAmount,
            'customer_name' => $customerNameNote !== '' ? $customerNameNote : 'Counter Customer',
        ]);
    }

    /**
     * Display the POS page for walk-in sales and store-pickup additions.
     * Strictly tenant-scoped.
     */
    public function pos()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $shop   = $res['shop'];

        // Check if we're in store-pickup mode (order_id provided)
        $orderId = $this->request->getGet('order_id');
        $order   = null;
        $orderItems = [];
        $orderModel = new OrderModel();

        if ($orderId) {
            $order = $orderModel
                ->select('orders.*, u.first_name, u.last_name, u.phone as customer_phone, u.email as customer_email, u.profile_image_url')
                ->join('users u', 'u.id = orders.customer_id', 'left')
                ->where('orders.id', $orderId)
                ->where('orders.shop_id', $shopId)
                ->first();

            if (!$order) {
                return redirect()->to('tenant/pos')->with('error', 'Invalid order for your shop.');
            }
            if (($order['fulfillment_method'] ?? '') !== 'pickup') {
                return redirect()->to('tenant/pos')->with('error', 'POS additions are only permitted for Store Pick-up orders.');
            }
            if (!in_array($order['status'], ['ready_for_pickup', 'completed'], true)) {
                return redirect()->to('tenant/pos')->with('error', 'Order is ' . humanize_status($order['status']) . '. Only Ready for Pick-up orders can be processed in POS.');
            }
            $orderItems = (new OrderItemModel())
                ->select('order_items.*, (SELECT image_url FROM product_images WHERE product_id = order_items.product_id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as gallery_image')
                ->where('order_items.order_id', $order['id'])
                ->findAll();
        }

        // Check if we're in printing pick-up mode (printing_id provided)
        $printingId = $this->request->getGet('printing_id');
        $printingRequest = null;
        $printingPayments = [];
        $printingOnlinePaid = 0.0;
        $printingRemainingBalance = 0.0;

        if ($printingId) {
            $prModel = new PrintingRequestModel();
            $printingRequest = $prModel
                ->select('printing_requests.*, u.first_name, u.last_name, u.phone, u.email')
                ->join('users u', 'u.id = printing_requests.customer_id', 'left')
                ->where('printing_requests.id', $printingId)
                ->where('printing_requests.shop_id', $shopId)
                ->first();

            if (!$printingRequest) {
                return redirect()->to('tenant/pos')->with('error', 'Printing request not found for your shop.');
            }
            if (($printingRequest['fulfillment_method'] ?? '') !== 'pickup') {
                return redirect()->to('tenant/pos')->with('error', 'POS is only permitted for Store Pick-up printing requests.');
            }
            if (!in_array($printingRequest['status'], ['ready_for_pickup', 'ready', 'completed'], true)) {
                return redirect()->to('tenant/pos')->with('error', 'Printing request is ' . humanize_status($printingRequest['status']) . '. Only Ready for Pick-up requests can be processed in POS.');
            }

            $printingPayments = (new PaymentModel())
                ->where('payable_type', 'printing_request')
                ->where('payable_id', $printingRequest['id'])
                ->where('status', 'verified')
                ->findAll();

            foreach ($printingPayments as $pm) {
                $printingOnlinePaid += (float) $pm['amount'];
            }
            if ($printingOnlinePaid <= 0 && (float) ($printingRequest['down_payment'] ?? 0) > 0) {
                $printingOnlinePaid = (float) $printingRequest['down_payment'];
            }
            $totalPrice = (float) $printingRequest['total_price'];
            $printingRemainingBalance = max(0.0, round($totalPrice - $printingOnlinePaid, 2));
        }

        // Available store-pickup product orders for quick selection (ONLY ready_for_pickup)
        $pickupOrders = $orderModel
            ->select('orders.*, u.first_name, u.last_name, u.phone, (SELECT GROUP_CONCAT(CONCAT(quantity, "x ", product_name) SEPARATOR ", ") FROM order_items WHERE order_id = orders.id) as items_summary')
            ->join('users u', 'u.id = orders.customer_id', 'left')
            ->where('orders.shop_id', $shopId)
            ->where('orders.fulfillment_method', 'pickup')
            ->where('orders.status', 'ready_for_pickup')
            ->orderBy('orders.placed_at', 'DESC')
            ->findAll();

        // Available store-pickup printing requests for quick selection (ONLY ready_for_pickup)
        $printingPickupRequests = (new PrintingRequestModel())
            ->select('printing_requests.*, u.first_name, u.last_name, u.phone, u.email')
            ->join('users u', 'u.id = printing_requests.customer_id', 'left')
            ->where('printing_requests.shop_id', $shopId)
            ->where('printing_requests.fulfillment_method', 'pickup')
            ->groupStart()
                ->where('printing_requests.status', 'ready_for_pickup')
                ->orWhere('printing_requests.status', 'ready')
            ->groupEnd()
            ->orderBy('printing_requests.created_at', 'DESC')
            ->findAll();

        $categories = (new CategoryModel())->getAllCached();

        // Cross-reference customers who have both ready-for-pickup orders and printing requests
        $customerHasPrintingMap = [];
        foreach ($printingPickupRequests as $prItem) {
            $cId = (int) ($prItem['customer_id'] ?? 0);
            if ($cId > 0) {
                $customerHasPrintingMap[$cId][] = $prItem;
            }
        }

        $customerHasOrderMap = [];
        foreach ($pickupOrders as $poItem) {
            $cId = (int) ($poItem['customer_id'] ?? 0);
            if ($cId > 0) {
                $customerHasOrderMap[$cId][] = $poItem;
            }
        }

        $pageTitle = 'Walk-in POS';
        if (!empty($order) && !empty($printingRequest)) {
            $pageTitle = 'Combined Pick-up POS';
        } elseif (!empty($printingRequest)) {
            $pageTitle = 'Printing Pick-up POS';
        } elseif (!empty($order)) {
            $pageTitle = 'Store Pick-up POS';
        }

        service('renderer')->setData([
            'shop'                   => $shop,
            'order'                  => $order,
            'printingRequest'        => $printingRequest,
            'pickupOrders'           => $pickupOrders,
            'printingPickupRequests' => $printingPickupRequests,
            'fullscreenLayout'       => true,
            'customerHasPrintingMap' => $customerHasPrintingMap,
            'customerHasOrderMap'    => $customerHasOrderMap,
        ]);

        return view('tenant/pos', [
            'shop'                     => $shop,
            'order'                    => $order,
            'orderItems'               => $orderItems,
            'pickupOrders'             => $pickupOrders,
            'printingRequest'          => $printingRequest,
            'printingPayments'         => $printingPayments,
            'printingOnlinePaid'       => $printingOnlinePaid,
            'printingRemainingBalance' => $printingRemainingBalance,
            'printingPickupRequests'   => $printingPickupRequests,
            'customerHasPrintingMap'   => $customerHasPrintingMap,
            'customerHasOrderMap'      => $customerHasOrderMap,
            'fullscreenLayout'         => true,
            'categories'               => $categories,
            'activeNav'                => 'pos',
            'title'                    => $pageTitle,
        ]);
    }

    /**
     * JSON endpoint for order detail modal.
     */
    public function orderDetailJson(int $orderId)
    {
        return $this->orderItems($orderId);
    }

    /**
     * Submit customer compliance report from tenant dashboard.
     */
    public function reportCustomer()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $customerId  = (int) $this->request->getPost('customer_id');
        $issueType   = trim((string) $this->request->getPost('issue_type'));
        $description = trim((string) $this->request->getPost('description'));

        if ($customerId <= 0 || empty($issueType) || empty($description)) {
            return redirect()->back()->with('error', 'Please select an issue type and provide a description.');
        }

        $customer = (new UserModel())->find($customerId);
        if (!$customer) {
            return redirect()->back()->with('error', 'Customer account not found.');
        }

        $reportNumber = 'REP-' . rand(1000, 9999);
        $complianceModel = new ComplianceModel();
        $complianceModel->insert([
            'report_number'    => $reportNumber,
            'reporter_id'      => (int) session()->get('user_id'),
            'reported_shop_id' => null,
            'reported_user_id' => $customerId,
            'issue_type'       => $issueType,
            'description'      => $description,
            'status'           => 'pending',
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', "Report #{$reportNumber} submitted to compliance team for review.");
    }

    /**
     * Dedicated delivery mapping and details page for a specific shipment.
     */
    public function deliveryDetail(int $deliveryId)
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = (int) $res['shopId'];
        $shop   = $res['shop'];

        $deliveryModel = new DeliveryModel();
        $delivery = $deliveryModel->find($deliveryId);
        if (!$delivery) {
            return redirect()->to('/tenant/delivery')->with('error', 'Delivery record not found.');
        }

        // Verify ownership
        $ownerShopId = $deliveryModel->resolveShopId($deliveryId);
        if ($ownerShopId !== $shopId) {
            return redirect()->to('/tenant/delivery')->with('error', 'Unauthorized access to this delivery record.');
        }

        // Activate live tracking broadcast when tenant views the live route
        if (in_array($delivery['status'], ['shipped', 'in_transit'], true)) {
            $now = date('Y-m-d H:i:s');
            $startLat = !empty($delivery['current_lat']) && (float) $delivery['current_lat'] != 0
                ? (float) $delivery['current_lat']
                : (!empty($shop['latitude']) && (float) $shop['latitude'] != 0 ? (float) $shop['latitude'] : DeliveryModel::POLOMOLOK_CENTER_LAT);
            $startLng = !empty($delivery['current_lng']) && (float) $delivery['current_lng'] != 0
                ? (float) $delivery['current_lng']
                : (!empty($shop['longitude']) && (float) $shop['longitude'] != 0 ? (float) $shop['longitude'] : DeliveryModel::POLOMOLOK_CENTER_LNG);

            $deliveryModel->update($deliveryId, [
                'current_lat'         => $startLat,
                'current_lng'         => $startLng,
                'location_updated_at' => $now,
            ]);
            $delivery['current_lat'] = $startLat;
            $delivery['current_lng'] = $startLng;
            $delivery['location_updated_at'] = $now;
        }

        // Fetch customer and deliverable details
        $customer = null;
        $order = null;
        $orderItems = [];
        $printingRequest = null;

        if ($delivery['deliverable_type'] === 'order') {
            $order = (new OrderModel())->find($delivery['deliverable_id']);
            if ($order && !empty($order['customer_id'])) {
                $customer = (new UserModel())->find($order['customer_id']);
            }
            if ($order) {
                $orderItems = (new OrderItemModel())
                    ->select('order_items.*, (SELECT image_url FROM product_images WHERE product_id = order_items.product_id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as gallery_image, (SELECT image_url FROM product_images WHERE product_id = order_items.product_id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as product_image')
                    ->where('order_items.order_id', $order['id'])
                    ->findAll();
            }
        } elseif ($delivery['deliverable_type'] === 'printing_request') {
            $printingRequest = (new PrintingRequestModel())->find($delivery['deliverable_id']);
            if ($printingRequest && !empty($printingRequest['customer_id'])) {
                $customer = (new UserModel())->find($printingRequest['customer_id']);
            }
        }

        // Coordinates check & fallback
        $destLat = (float) ($delivery['destination_latitude'] ?? 0);
        $destLng = (float) ($delivery['destination_longitude'] ?? 0);

        if ($destLat === 0.0 || $destLng === 0.0) {
            if ($order && !empty($order['shipping_address_id'])) {
                $shippingAddr = (new \App\Models\ShippingAddressModel())->find($order['shipping_address_id']);
                if ($shippingAddr && !empty($shippingAddr['latitude']) && !empty($shippingAddr['longitude'])) {
                    $destLat = (float) $shippingAddr['latitude'];
                    $destLng = (float) $shippingAddr['longitude'];
                }
            }
        }

        if (($destLat === 0.0 || $destLng === 0.0) && !empty($delivery['destination_address'])) {
            $mapsService = new \App\Services\GoogleMapsService();
            $geo = $mapsService->geocodeAddress($delivery['destination_address']);
            if ($geo) {
                $destLat = $geo['lat'];
                $destLng = $geo['lng'];
            }
        }

        if ($destLat === 0.0 || $destLng === 0.0) {
            $destLat = 6.2136;
            $destLng = 125.0661;
        }

        $shopLat = !empty($shop['latitude']) && (float) $shop['latitude'] != 0 ? (float) $shop['latitude'] : 6.2136;
        $shopLng = !empty($shop['longitude']) && (float) $shop['longitude'] != 0 ? (float) $shop['longitude'] : 125.0661;

        service('renderer')->setData(['shop' => $shop]);

        return view('tenant/delivery_detail', [
            'shop'            => $shop,
            'delivery'        => $delivery,
            'customer'        => $customer,
            'order'           => $order,
            'orderItems'      => $orderItems,
            'printingRequest' => $printingRequest,
            'shopLat'         => $shopLat,
            'shopLng'         => $shopLng,
            'destLat'         => $destLat,
            'destLng'         => $destLng,
            'activeNav'       => 'delivery',
            'title'           => 'Delivery #' . ($delivery['tracking_id'] ?? $deliveryId),
        ]);
    }

    /**
     * Stop active delivery GPS broadcast when tenant exits live route page.
     * Route: POST /tenant/deliveries/stop-broadcast
     */
    public function stopDeliveryBroadcast()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $shopId = (int) $res['shopId'];
        $deliveryId = (int) $this->request->getPost('delivery_id');

        if ($deliveryId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing delivery_id']);
        }

        $deliveryModel = new DeliveryModel();
        $delivery = $deliveryModel->find($deliveryId);

        if (!$delivery) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Delivery not found']);
        }

        $actualShopId = $deliveryModel->resolveShopId($deliveryId) ?? (int) ($delivery['shop_id'] ?? 0);
        if ($actualShopId > 0 && $actualShopId !== $shopId && session()->get('user_role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        // Deactivate active broadcast session by setting location_updated_at to NULL
        $deliveryModel->update($deliveryId, [
            'location_updated_at' => null,
        ]);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Broadcast stopped']);
    }
}



