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

class Tenant extends BaseController
{
    /**
     * The only order statuses that exist in the orders.status enum.
     */
    private const ORDER_STATUSES = [
        'pending'         => 'Pending',
        'processing'      => 'Processing',
        'shipped'         => 'Shipped',
        'ready_for_pickup' => 'Ready for Pickup',
        'delivered'       => 'Delivered',
        'completed'       => 'Completed',
        'cancelled'       => 'Cancelled',
    ];

    private function getShopOrRedirect()
    {
        $session = session();
        if (!$session->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $userRole = $session->get('user_role');
        if ($userRole !== 'shop_owner' && $userRole !== 'admin') {
            return redirect()->to('/');
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

        // ---- KPI values (tenant-scoped, no invented formulas) ----
        $totalRevenue  = $orderModel->getRevenueForShop($shopId);
        $totalSales    = $orderModel->getOrderCountForShop($shopId);
        $pendingOrders = $orderModel->getOrderCountForShop($shopId, ['pending', 'processing']);
        $printingCount = $prModel->countByShop($shopId);

        // ---- Period deltas: last 30 days vs the previous 30 days ----
        // Boundaries are anchored to the DB server's local calendar date
        // so windows always align with the stored placed_at timestamps.
        $today       = $orderModel->getLocalToday();
        $windowStart = date('Y-m-d 00:00:00', strtotime($today . ' -30 days'));
        $prevStart   = date('Y-m-d 00:00:00', strtotime($today . ' -60 days'));

        $revCurrent   = $orderModel->getRevenueForShop($shopId, $windowStart);
        $revPrevious  = $orderModel->getRevenueForShop($shopId, $prevStart, $windowStart);

        $salesCurrent   = $orderModel->getOrderCountForShop($shopId, [], $windowStart);
        $salesPrevious  = $orderModel->getOrderCountForShop($shopId, [], $prevStart, $windowStart);

        $pendingCurrent   = $orderModel->getOrderCountForShop($shopId, ['pending', 'processing'], $windowStart);
        $pendingPrevious  = $orderModel->getOrderCountForShop($shopId, ['pending', 'processing'], $prevStart, $windowStart);

        $printCurrent  = $prModel->countByShop($shopId, $windowStart);
        $printPrevious = $prModel->countByShop($shopId, $prevStart, $windowStart);

        // ---- Chart data (default: last 7 days) ----
        $chart = $orderModel->getSalesChartData($shopId, '7');
        $chartMax = max($chart['values']);

        // ---- Lists ----
        $recentOrders   = array_slice($orderModel->getOrdersByShop($shopId), 0, 8);
        $recentRequests = array_slice($prModel->getRequestsByShop($shopId), 0, 8);
        $lowStockItems  = $productModel->getLowStockProducts($shopId);
        $lowStockCount  = $productModel->countLowStockProducts($shopId);

        // ---- Header notifications ----
        $userId        = (int) session()->get('user_id');
        $notifications = $notifModel->getRecent($userId);
        $unreadCount   = $notifModel->getUnreadCount($userId);

        return view('tenant/dashboard', [
            'shop'           => $shop,
            'products'       => $productModel->where('shop_id', $shopId)->where('deleted_at', null)->findAll(),
            'orders'         => $orderModel->getOrdersByShop($shopId),
            'printReqs'      => $prModel->getRequestsByShop($shopId),
            // KPI values
            'total_revenue'  => $totalRevenue,
            'total_sales'    => $totalSales,
            'pending_orders' => $pendingOrders,
            'printing_count' => $printingCount,
            // KPI deltas (neutral when there is no prior-period data)
            'revenue_delta'  => $this->deltaPercent($revCurrent, $revPrevious),
            'sales_delta'    => $this->deltaPercent($salesCurrent, $salesPrevious),
            'pending_delta'  => $this->deltaPercent($pendingCurrent, $pendingPrevious),
            'printing_delta' => $this->deltaPercent($printCurrent, $printPrevious),
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
            'all_categories'  => (new CategoryModel())->orderBy('sort_order', 'ASC')->findAll(),
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

        $orderModel = new OrderModel();

        $search   = trim((string) $this->request->getGet('q'));
        $status   = (string) $this->request->getGet('status');
        $status   = in_array($status, array_keys(self::ORDER_STATUSES), true) ? $status : '';
        $dateFrom = $this->isDate((string) $this->request->getGet('from')) ? (string) $this->request->getGet('from') : '';
        $dateTo   = $this->isDate((string) $this->request->getGet('to')) ? (string) $this->request->getGet('to') : '';
        $page     = max(1, (int) $this->request->getGet('page_orders'));

        $result = $orderModel->getOrdersByShopPaginated(
            $shopId,
            $search !== '' ? $search : null,
            $status !== '' ? $status : null,
            $dateFrom !== '' ? $dateFrom : null,
            $dateTo !== '' ? $dateTo : null,
            10,
            $page,
            'orders'
        );

        return view('tenant/orders', [
            'shop'          => $shop,
            'orders'        => $result['orders'],
            'pager'         => $result['pager'],
            'summary'       => $orderModel->getOrdersSummary($shopId),
            'filters'       => ['q' => $search, 'status' => $status, 'from' => $dateFrom, 'to' => $dateTo],
            'statusOptions' => [
                'pending'          => 'Pending',
                'processing'       => 'Processing',
                'ready_for_pickup' => 'Ready for Pickup',
                'shipped'          => 'Shipped',
                'delivered'        => 'Delivered',
                'completed'        => 'Completed',
                'cancelled'        => 'Cancelled',
            ],
            'activeNav'     => 'orders',
            'title'         => 'Orders Management',
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

        $items    = (new OrderItemModel())->where('order_id', $orderId)->findAll();
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
                'order_number'       => $order['order_number'],
                'status'             => humanize_status($order['status']),
                'raw_status'          => $order['status'],
                'payment_status'      => $order['payment_status'] ?? 'unpaid',
                'placed_at'          => date('M d, Y h:i A', strtotime($order['placed_at'])),
                'payment_method'     => strtoupper($order['payment_method'] ?? 'cod'),
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
            'items' => array_map(static fn ($i) => [
                'product_name' => $i['product_name'],
                'quantity'     => (int) $i['quantity'],
                'unit_price'   => (float) $i['unit_price'],
                'line_total'   => (float) $i['line_total'],
            ], $items),
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

        // Recent requests (all statuses), with independent paginator group.
        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');
        $status = in_array($status, ['new', 'in_production', 'ready_for_pickup', 'ready_for_delivery', 'completed', 'cancelled'], true) ? $status : '';
        $page   = max(1, (int) $this->request->getGet('page_recent'));

        $recent = (new PrintingRequestModel())->getRequestsByShopPaginated(
            $shopId,
            $search !== '' ? $search : null,
            $status !== '' ? $status : null,
            10,
            $page,
            'recent'
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

        $allReqIds = array_merge(
            array_column($recent['requests'], 'id'),
            array_column($completed['requests'], 'id')
        );
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

        return view('tenant/printing', [
            'shop'             => $shop,
            'requests'         => $recent['requests'],
            'pager'            => $recent['pager'],
            'summary'          => (new PrintingRequestModel())->getPrintSummary($shopId),
            'queue'            => (new PrintingRequestModel())->getProductionQueue($shopId),
            'completed'        => $completed['requests'],
            'completed_pager'  => $completed['pager'],
            'filters'          => ['q' => $search, 'status' => $status, 'cq' => $cSearch],
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
        $status = in_array($status, ['ready_for_pickup', 'shipped', 'in_transit', 'delivered', 'cancelled'], true) ? $status : '';
        $page   = max(1, (int) $this->request->getGet('page_deliveries'));

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
                'ready_for_pickup' => 'Ready for Pickup',
                'shipped'          => 'Shipped',
                'in_transit'       => 'In Transit',
                'delivered'        => 'Delivered',
                'returned'         => 'Returned',
                'cancelled'        => 'Cancelled',
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

        // Fulfillment check: Reject Store Pick-up orders in Delivery scanner
        if (($row['fulfillment_method'] ?? '') === 'pickup') {
            $ref = $row['ref_number'] ?: $row['tracking_id'];
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => "Wrong fulfillment type: Order #{$ref} was placed for Store Pick-up. Please process it at the counter via POS.",
                'message' => "Wrong fulfillment type: Order #{$ref} was placed for Store Pick-up. Please process it at the counter via POS.",
            ]);
        }

        $currentStatus = $row['status'] ?? 'ready_for_pickup';
        $newStatus     = null;
        $statusMsg     = '';
        $actionType    = '';

        if ($currentStatus === 'cancelled') {
            $ref = $row['ref_number'] ?: $row['tracking_id'];
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => "Order #{$ref} has been cancelled.",
                'message' => "Order #{$ref} has been cancelled.",
            ]);
        }

        // Status transition on QR Scan:
        // 1. If currently shipped / in_transit / ready_for_pickup / processing / pending -> automatically mark as 'delivered'!
        // 2. If already delivered -> inform user that it is verified as delivered
        if (in_array($currentStatus, ['shipped', 'in_transit', 'ready_for_pickup', 'processing', 'pending'], true)) {
            $newStatus = 'delivered';
            $now = date('Y-m-d H:i:s');
            
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

            $currentStatus = 'delivered';
            $row['delivered_at'] = $now;
            $actionType = 'delivered';
            $statusMsg = 'Order #' . ($row['ref_number'] ?: $row['tracking_id']) . ' has been verified and marked as DELIVERED!';
        } elseif ($currentStatus === 'delivered') {
            $actionType = 'already_delivered';
            $statusMsg = 'Order #' . ($row['ref_number'] ?: $row['tracking_id']) . ' is already verified as DELIVERED.';
        } elseif ($currentStatus === 'returned') {
            $actionType = 'already_returned';
            $statusMsg = 'Order/Request #' . ($row['ref_number'] ?: $row['tracking_id']) . ' is currently marked as RETURNED.';
        }

        // Send customer notification on QR status update
        $customerId = (int) ($row['customer_id'] ?? 0);
        if ($customerId > 0 && $newStatus !== null) {
            $reqRef = $row['ref_number'] ?: $row['tracking_id'];
            if ($newStatus === 'delivered') {
                (new NotificationModel())->create(
                    $customerId,
                    'delivery',
                    'Order Delivered / Picked Up',
                    'Your order/request #' . $reqRef . ' has been successfully marked as delivered / picked up!'
                );
            } elseif ($newStatus === 'returned') {
                (new NotificationModel())->create(
                    $customerId,
                    'delivery',
                    'Order Status Returned',
                    'Your order/request #' . $reqRef . ' status has been updated to returned.'
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
                'deliverable_type' => $row['deliverable_type'],
                'courier_name'     => $row['courier_name'] ?? '',
                'destination'      => $row['destination_address'] ?? '',
                'status'           => humanize_status($currentStatus),
                'status_key'       => $currentStatus,
                'customer'         => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                'created_at'       => date('M d, Y h:i A', strtotime($row['created_at'])),
                'shipped_at'       => !empty($row['shipped_at']) ? date('M d, Y h:i A', strtotime($row['shipped_at'])) : null,
                'delivered_at'     => !empty($row['delivered_at']) ? date('M d, Y h:i A', strtotime($row['delivered_at'])) : null,
            ],
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

        $page = max(1, (int) $this->request->getGet('page_withdrawals'));

        $payoutModel = new PayoutModel();
        $result      = $payoutModel->getWithdrawalsByShopPaginated($shopId, 15, $page, 'withdrawals');
        $payouts     = $result['withdrawals'];

        $balanceData = $this->getShopEscrowAndBalance($shopId);

        return view('tenant/withdrawals', [
            'shop'              => $shop,
            'withdrawals'       => $payouts,
            'payouts'           => $payouts,
            'pager'             => $result['pager'],
            'available_balance' => $balanceData['available_balance'],
            'escrow_holding'    => $balanceData['escrow_holding'],
            'released_earnings' => $balanceData['released_earnings'],
        ]);
    }

    /**
     * Compute shop owner's available balance and escrow holdings based on fulfillment.
     * Available Balance increases ONLY AFTER the order/printing status changes to Delivered or Completed.
     * Formula: Shop Balance = Current Balance + (Item Subtotal - Platform Fees).
     */
    public function getShopEscrowAndBalance(int $shopId): array
    {
        $orderModel = new OrderModel();
        $db         = \Config\Database::connect();

        $orders = $orderModel->getOrdersByShop($shopId);
        $printingRequests = $db->table('printing_requests')->where('shop_id', $shopId)->get()->getResultArray();

        $releasedEarnings = 0.0;
        $escrowHolding    = 0.0;

        foreach ($orders as $o) {
            $isPaid = ($o['payment_status'] ?? '') === 'paid';
            $isDelivered = in_array($o['status'] ?? '', ['delivered', 'completed'], true);
            $subtotal = (float) ($o['subtotal'] ?? ($o['total_amount'] ?? 0));
            // Platform fee formula: Item Subtotal - Platform Fees (0% fee currently for marketplace items)
            $netEarnings = $subtotal;

            if ($isDelivered) {
                // If delivered/completed, funds are released to available balance
                if ($isPaid || in_array($o['payment_method'] ?? '', ['cod', 'pickup'], true)) {
                    $releasedEarnings += $netEarnings;
                }
            } elseif ($isPaid) {
                // Paid via PayMongo GCash but not yet delivered -> held in system escrow
                $escrowHolding += $netEarnings;
            }
        }

        foreach ($printingRequests as $pr) {
            $status = strtolower((string) ($pr['status'] ?? ''));
            $isCompleted = ($status === 'completed');
            $downPayment = (float) ($pr['down_payment'] ?? 0);
            $totalPrice  = (float) ($pr['total_price'] ?? 0);

            if ($isCompleted) {
                $releasedEarnings += ($totalPrice > 0 ? $totalPrice : $downPayment);
            } elseif (stripos($status, 'paid') !== false || $downPayment > 0) {
                $escrowHolding += $downPayment;
            }
        }

        $payouts = $db->table('payout_requests')
            ->where('shop_id', $shopId)
            ->where('status !=', 'rejected')
            ->selectSum('amount')
            ->get()->getRowArray();
        $payoutDeductions = (float) ($payouts['amount'] ?? 0);

        $availableBalance = max(0.0, round($releasedEarnings - $payoutDeductions, 2));

        return [
            'available_balance' => $availableBalance,
            'escrow_holding'    => round($escrowHolding, 2),
            'released_earnings' => round($releasedEarnings, 2),
            'total_deductions'  => $payoutDeductions,
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

        $shopId = $res['shopId'];
        $shop   = $res['shop'];

        $page   = max(1, (int) $this->request->getGet('page_archive'));
        $result = (new ArchivedItemModel())->getArchivedForShopPaginated($shopId, 10, $page, 'archive');

        return view('tenant/archive', [
            'shop'          => $shop,
            'archivedItems' => $result['items'],
            'pager'         => $result['pager'],
            'activeNav'     => 'archive',
            'title'         => 'Archive & Recovery',
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

                $delId = $deliveryModel->insert([
                    'deliverable_type'    => 'order',
                    'deliverable_id'      => $orderId,
                    'tracking_id'          => 'TRK-' . strtoupper(substr(md5($orderId . time()), 0, 8)),
                    'courier_name'        => 'Standard Courier',
                    'destination_address' => $address,
                    'current_lat'         => 14.5995,
                    'current_lng'         => 120.9842,
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
            'completed'          => 3,
        ];
        if (!isset($level[$current]) || !isset($level[$target])) {
            return false;
        }

        $isPeer = ($current === 'ready_for_pickup' && $target === 'ready_for_delivery')
            || ($current === 'ready_for_delivery' && $target === 'ready_for_pickup');

        return $isPeer || $level[$target] > $level[$current];
    }

    /**
     * Secure file download for a printing request. The stored file_url is
     * resolved against WRITEPATH and containment-checked so arbitrary
     * filesystem paths can never be served.
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

        $rel  = ltrim((string) $row['file_url'], '/');
        $path = str_starts_with($rel, 'writable/')
            ? WRITEPATH . substr($rel, strlen('writable/'))
            : WRITEPATH . $rel;

        $real = realpath($path);
        $root = realpath(WRITEPATH);
        if ($real === false || $root === false || !str_starts_with($real . DIRECTORY_SEPARATOR, $root . DIRECTORY_SEPARATOR)) {
            return redirect()->to(base_url('tenant/printing'))->with('error', 'Printing file is not available.');
        }
        if (!is_file($real)) {
            return redirect()->to(base_url('tenant/printing'))->with('error', 'The printing file is missing on the server.');
        }

        $name = trim((string) $row['file_name']);
        if ($name === '') {
            $name = basename($real);
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . str_replace('"', '', $name) . '"')
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

        $settingModel = new \App\Models\ShopPrintingSettingModel();
        $settingModel->saveForShop($shopId, [
            'down_payment_percent' => $downPaymentPercent,
            'price_staple'         => $priceStaple,
            'price_spiral'         => $priceSpiral,
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

        // Synchronize linked order
        if ($row['deliverable_type'] === 'order') {
            if ($dbStatus === 'delivered') {
                (new OrderModel())->update($row['deliverable_id'], ['status' => 'delivered', 'completed_at' => date('Y-m-d H:i:s')]);
            } elseif ($dbStatus === 'returned') {
                (new OrderModel())->update($row['deliverable_id'], ['status' => 'returned']);
            } elseif ($dbStatus === 'shipped') {
                (new OrderModel())->update($row['deliverable_id'], ['status' => 'shipped']);
            } elseif ($dbStatus === 'cancelled') {
                (new OrderModel())->update($row['deliverable_id'], ['status' => 'cancelled']);
            }
        }

        return redirect()->back()->with('success', 'Delivery status updated.');
    }

    public function requestWithdrawal()
    {
        $res = $this->getShopOrRedirect();
        if ($res instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $res;
        }

        $shopId = $res['shopId'];

        $amount         = (float) $this->request->getPost('amount');
        $method         = $this->request->getPost('method');
        $accountDetails = trim($this->request->getPost('account_details') ?? '');

        if ($amount <= 0 || empty($accountDetails)) {
            return redirect()->back()->with('error', 'Please enter a valid amount and account details.');
        }

        $balanceData = $this->getShopEscrowAndBalance($shopId);
        $availableBalance = $balanceData['available_balance'];

        if ($amount > $availableBalance) {
            return redirect()->back()->with('error', 'Amount exceeds your available balance (₱' . number_format($availableBalance, 2) . '). Funds awaiting delivery are held in escrow.');
        }

        // GCash only — ignore any bank/maya input, enforce gcash + 3% fee
        $destMethod = 'gcash';
        $cleaned = preg_replace('/\D/', '', $accountDetails);
        if (!preg_match('/^09\d{9}$/', $cleaned)) {
            return redirect()->back()->with('error', 'Please enter a valid GCash number (09XXXXXXXXX). Only GCash withdrawals are supported.');
        }
        $accountDetails = $cleaned;
        $fee = round($amount * 0.03, 2);

        $db->table('payout_requests')->insert([
            'shop_id'            => $shopId,
            'reference_number'   => 'WD-' . date('Ymd') . '-' . rand(1000, 9999),
            'amount'             => $amount,
            'destination_method' => $destMethod,
            'destination_detail' => $accountDetails,
            'fee'                => $fee,
            'status'             => 'pending',
            'requested_at'       => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Withdrawal request submitted.');
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

        return $this->saveShopProfile($shopId);
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

        (new ShopModel())->update($shopId, [
            'shop_name'       => $shopName,
            'description'     => $description,
            'address_line'    => trim((string) $this->request->getPost('address_line')),
            'offers_printing' => $this->request->getPost('offers_printing') ? 1 : 0,
        ]);

        return redirect()->back()->with('success', 'Shop profile saved.');
    }

    private function savePaymentDetails(int $shopId)
    {
        $gcashNumber = trim((string) $this->request->getPost('gcash_number'));
        $gcashName   = trim((string) $this->request->getPost('gcash_account_name'));

        if ($gcashNumber !== '') {
            $digits = preg_replace('/\D/', '', $gcashNumber);
            if (!preg_match('/^09\d{9}$/', $digits)) {
                return $this->settingsResponse(false, 'Please enter a valid GCash number (e.g. 0917 123 4567).');
            }
        }
        if (mb_strlen($gcashName) > 100) {
            return $this->settingsResponse(false, 'Account name must be 100 characters or fewer.');
        }

        (new ShopModel())->update($shopId, [
            'gcash_number'       => $gcashNumber,
            'gcash_account_name' => $gcashName,
        ]);

        return $this->settingsResponse(true, 'Payment details updated.');
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

        $uploadPath = ROOTPATH . 'public/uploads/shop_logos';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $fileName = 'logo_' . $shopId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $mimeMap[$mime];
        $file->move($uploadPath, $fileName);

        if (!empty($shop['logo_url']) && strpos($shop['logo_url'], 'uploads/shop_logos/') === 0) {
            $oldPath = ROOTPATH . 'public/' . $shop['logo_url'];
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        (new ShopModel())->update($shopId, ['logo_url' => 'uploads/shop_logos/' . $fileName]);

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
        $uploadPath   = ROOTPATH . 'public/uploads/product_images/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

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

                $fileName = 'prod_' . $productId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $mimeMap[$mime];
                $file->move($uploadPath, $fileName);

                $isPrimary = ($existingCount === 0) ? 1 : 0;
                $imageModel->insert([
                    'product_id' => $productId,
                    'image_url'  => 'uploads/product_images/' . $fileName,
                    'alt_text'   => '',
                    'is_primary' => $isPrimary,
                    'sort_order' => $maxSortOrder++,
                ]);
                $existingCount++;
            }
        }

        // 2. Single file input fallback (product_image)
        $single = $this->request->getFile('product_image');
        if ($single && $single->isValid() && !$single->hasMoved()) {
            $mime = $single->getMimeType();
            if (isset($mimeMap[$mime]) && $single->getSize() <= $maxBytes) {
                $fileName = 'prod_' . $productId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $mimeMap[$mime];
                $single->move($uploadPath, $fileName);

                $isPrimary = ($existingCount === 0) ? 1 : 0;
                $imageModel->insert([
                    'product_id' => $productId,
                    'image_url'  => 'uploads/product_images/' . $fileName,
                    'alt_text'   => '',
                    'is_primary' => $isPrimary,
                    'sort_order' => $maxSortOrder++,
                ]);
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

        // Delete local physical file
        $filePath = ROOTPATH . 'public/' . $image['image_url'];
        if (str_starts_with($image['image_url'], 'uploads/') && file_exists($filePath)) {
            @unlink($filePath);
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

        (new NotificationModel())->create((int) $shop['owner_id'], 'low_stock', $title, $message);
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
            return $res;
        }

        $archiveModel = new ArchivedItemModel();
        $item         = $archiveModel->find((int) $archiveId);

        if (!$item || (int) $item['shop_id'] !== (int) $res['shopId']) {
            return redirect()->back()->with('error', 'Archived item not found.');
        }

        // A record already restored (or in the middle of being restored) is
        // not eligible for another restore.
        if (!empty($item['restored_at'])) {
            return redirect()->back()->with('error', 'This item has already been restored.');
        }

        if ($item['item_type'] === 'inventory') {
            $product = (new ProductModel())->find((int) $item['item_id']);
            if (!$product || (int) $product['shop_id'] !== (int) $res['shopId']) {
                return redirect()->back()->with('error', 'The linked product no longer exists in your shop.');
            }

            (new ProductModel())->update((int) $item['item_id'], [
                'status'     => 'active',
                'deleted_at' => null,
            ]);
        }

        $archiveModel->update((int) $archiveId, ['restored_at' => date('Y-m-d H:i:s')]);
        $archiveModel->delete((int) $archiveId);

        return redirect()->back()->with('success', 'Item restored successfully.');
    }

    private function progressForStatus(string $status): int
    {
        switch ($status) {
            case 'in_production':
                return 40;
            case 'ready_for_pickup':
            case 'ready_for_delivery':
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

        $search = trim(str_replace(['#'], '', $rawCode));
        if (str_starts_with($search, '{') && str_ends_with($search, '}')) {
            $decoded = json_decode($search, true);
            if (is_array($decoded)) {
                $search = trim((string) ($decoded['order_no'] ?? $decoded['order_number'] ?? $decoded['id'] ?? $search));
            }
        }

        // Clean and extract order number: matches 'ORD-XXXXX' or raw order code
        $orderNumber = $search;
        if (preg_match('/(ORD-[A-Za-z0-9_-]+)/i', $search, $matches)) {
            $orderNumber = strtoupper($matches[1]);
        } elseif (preg_match('#/order/([A-Za-z0-9_-]+)#', $search, $matches)) {
            $orderNumber = strtoupper($matches[1]);
        } else {
            $orderNumber = strtoupper(trim(str_replace(['#', ' '], '', $search)));
        }

        $orderModel = new OrderModel();
        // Match exact order_number, prefixed 'ORD-', or numeric ID
        $order = $orderModel
            ->groupStart()
                ->where('order_number', $orderNumber)
                ->orWhere('order_number', 'ORD-' . $orderNumber)
                ->orWhere('id', is_numeric($orderNumber) ? (int) $orderNumber : 0)
            ->groupEnd()
            ->first();

        if (!$order) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => "Order #{$orderNumber} was not found in the system.",
                'message' => "Order #{$orderNumber} was not found in the system.",
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

        // Order eligibility check: reject pending orders (must be accepted/processing first)
        if ($order['status'] === 'pending') {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => "Order #{$order['order_number']} is still pending. Please accept and process the order before using POS.",
                'message' => "Order #{$order['order_number']} is still pending. Please accept and process the order before using POS.",
            ]);
        }

        if (in_array($order['status'], ['completed', 'delivered'], true)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => "Order #{$order['order_number']} has already been completed / released.",
                'message' => "Order #{$order['order_number']} has already been completed / released.",
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

        $orderId = (int) $this->request->getPost('order_id');
        $rawItems = $this->request->getPost('items');
        $items = is_array($rawItems) ? $rawItems : (json_decode((string)$rawItems, true) ?: []);
        $counterMethod = strtolower(trim((string) $this->request->getPost('counter_payment_method') ?? 'cash'));
        if (!in_array($counterMethod, ['cash', 'gcash', 'card', 'none'], true)) {
            $counterMethod = 'cash';
        }

        $orderModel = new OrderModel();
        $order = $orderModel->find($orderId);

        if (!$order || (int) $order['shop_id'] !== $shopId) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Order not found for your shop.']);
        }

        if (($order['fulfillment_method'] ?? '') !== 'pickup') {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'POS completion is only permitted for Store Pick-up orders.']);
        }

        if (in_array($order['status'], ['pending', 'completed', 'delivered', 'cancelled'], true)) {
            $msg = $order['status'] === 'pending'
                ? 'This order is still pending. Please accept and process the order before completing pick-up.'
                : 'This order is already ' . $order['status'] . '.';
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
            $product = $db->table('products')
                ->where('id', $productId)
                ->where('shop_id', $shopId)
                ->where('deleted_at IS NULL')
                ->get()->getRowArray();

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
        (new \App\Models\AuditLogModel())->insert([
            'actor_id'    => $userId,
            'actor_role'  => 'shop_owner',
            'action'      => 'pos_pickup_complete',
            'target_type' => 'order',
            'target_id'   => $orderId,
            'status'      => 'success',
            'ip_address'  => $this->request->getIPAddress(),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

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

        return $this->response->setJSON([
            'success'             => true,
            'message'             => 'Store pick-up completed successfully.',
            'order_number'        => $order['order_number'],
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

            $product = $db->table('products')
                ->where('id', $productId)
                ->where('shop_id', $shopId)
                ->where('deleted_at IS NULL')
                ->get()->getRowArray();

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

            $unitPrice = (float) $product['price'];
            $lineTotal = round($unitPrice * $quantity, 2);
            $totalAmount += $lineTotal;

            // Deduct inventory
            $newStock = (int) $product['stock_quantity'] - $quantity;
            $productModel->update($productId, ['stock_quantity' => $newStock]);

            // Check low stock threshold
            $this->maybeNotifyLowStock($shopId, $product, $newStock);

            $validatedItems[] = [
                'product_id'   => $productId,
                'product_name' => $product['name'],
                'quantity'     => $quantity,
                'unit_price'   => $unitPrice,
                'line_total'   => $lineTotal,
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
                'order_id'        => $orderId,
                'product_id'      => $vIt['product_id'],
                'product_name'    => $vIt['product_name'],
                'quantity'        => $vIt['quantity'],
                'unit_price'      => $vIt['unit_price'],
                'line_total'      => $vIt['line_total'],
                'is_pos_addition' => 1,
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
            'success'      => true,
            'message'      => "Walk-in sale completed successfully! Order #{$orderNumber}",
            'order_id'     => $orderId,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount,
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
            $order = $orderModel->find($orderId);
            if (!$order || (int) $order['shop_id'] !== $shopId) {
                return redirect()->back()->with('error', 'Invalid order for your shop.');
            }
            if (($order['fulfillment_method'] ?? '') !== 'pickup') {
                return redirect()->back()->with('error', 'POS additions are only permitted for Store Pick-up orders.');
            }
            if ($order['status'] === 'pending') {
                $orderModel->update((int) $order['id'], ['status' => 'processing']);
                $order['status'] = 'processing';
            }
            $orderItems = (new OrderItemModel())->where('order_id', $order['id'])->findAll();
        }

        // Available store-pickup orders for quick selection (excluding completed, delivered, cancelled)
        $pickupOrders = $orderModel
            ->where('shop_id', $shopId)
            ->where('fulfillment_method', 'pickup')
            ->whereNotIn('status', ['completed', 'delivered', 'cancelled'])
            ->orderBy('placed_at', 'DESC')
            ->findAll();

        $categories = (new CategoryModel())->orderBy('sort_order', 'ASC')->findAll();

        service('renderer')->setData([
            'shop'          => $shop,
            'order'         => $order,
        ]);

        return view('tenant/pos', [
            'shop'          => $shop,
            'order'         => $order,
            'orderItems'    => $orderItems,
            'pickupOrders'  => $pickupOrders,
            'categories'    => $categories,
            'activeNav'     => 'pos',
            'title'         => !empty($order) ? 'Store Pick-up POS' : 'Walk-in POS',
        ]);
    }
}

