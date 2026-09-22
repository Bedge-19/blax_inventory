<?php

namespace App\Controllers;

use App\Models\ShopModel;
use App\Models\UserModel;
use App\Models\OrderModel;
use App\Models\ComplianceModel;
use App\Models\AuditLogModel;
use App\Models\DeliveryModel;
use App\Models\PayoutModel;
use App\Models\SiteContentModel;
use App\Models\NotificationModel;
use App\Services\PaymongoService;

class Admin extends BaseController
{
    private function checkAdminAuth()
    {
        $session = session();
        if (!$session->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        if ($session->get('user_role') !== 'admin') {
            return redirect()->to('/');
        }
        return true;
    }

    public function dashboard()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $userModel   = new UserModel();
        $payoutModel = new PayoutModel();
        $db = \Config\Database::connect();

        $shops = $db->table('shops s')
            ->select('s.*, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = s.owner_id', 'left')
            ->orderBy('s.created_at', 'DESC')
            ->get()->getResultArray();

        $customers = $userModel->where('role', 'customer')->findAll();
        $pendingPayments = $db->table('payout_requests')->where('status','pending')->where('destination_method','gcash')->countAllResults();

        // Admin revenue only: 3% of completed GCash withdrawals
        $adminRevenue = $payoutModel->getAdminRevenueTotal('completed');
        $adminRevenue30 = $payoutModel->getAdminRevenueChartData('30','completed');

        // Recent shops pagination with selectable per_page (5, 10, 20)
        $perPage = (int) ($this->request->getGet('per_page') ?: 5);
        if (!in_array($perPage, [5, 10, 20], true)) {
            $perPage = 5;
        }
        $page = max(1, (int) ($this->request->getGet('page_recent') ?: $this->request->getGet('page')));
        $totalShops = count($shops);
        $offset = ($page - 1) * $perPage;
        $recentShops = array_slice($shops, $offset, $perPage);

        $pager = service('pager');
        $pager->store('recent', $page, $perPage, $totalShops);

        // Active shops count
        $activeShops = 0;
        $pendingShops = 0;
        foreach ($shops as $s) {
            if (($s['status'] ?? '') === 'active') $activeShops++;
            if (($s['status'] ?? '') === 'pending') $pendingShops++;
        }

        return view('admin/dashboard', [
            'admin_revenue'        => $adminRevenue,
            'admin_revenue_chart_labels' => $adminRevenue30['labels'],
            'admin_revenue_chart_values' => $adminRevenue30['values'],
            'total_shops'          => count($shops),
            'active_shops'         => $activeShops,
            'pending_shops'        => $pendingShops,
            'total_customers'      => count($customers),
            'pending_payments'     => $pendingPayments,
            'recent_shops'         => $recentShops,
            'shops'                => $shops,
            'pager'                => $pager,
            'per_page'             => $perPage,
        ]);
    }

    public function tenants()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $shopModel = new ShopModel();
        $search    = trim((string)$this->request->getGet('q'));
        $status    = trim((string)$this->request->getGet('status'));
        $perPage   = (int) ($this->request->getGet('per_page') ?: 10);
        if (!in_array($perPage, [5, 10, 20], true)) {
            $perPage = 10;
        }
        $page      = max(1, (int) $this->request->getGet('page_tenants'));

        $result = $shopModel->getTenantsPaginated($search, $status, $perPage, $page, 'tenants');
        $shops  = $result['tenants'];

        $db = \Config\Database::connect();
        $activeCount  = $db->table('shops')->where('status','active')->countAllResults();
        $pendingCount = $db->table('shops')->where('status','pending')->countAllResults();

        return view('admin/tenants', [
            'tenants'      => $shops,
            'shops'        => $shops,
            'pager'        => $result['pager'],
            'per_page'     => $perPage,
            'filters'      => ['q'=>$search,'status'=>$status],
            'active_count' => $activeCount,
            'pending_count'=> $pendingCount,
            'total_count'  => (int) ($result['pager'] ? $result['pager']->getTotal('tenants') : count($shops)),
        ]);
    }

    public function customers()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $userModel = new UserModel();
        $search    = trim((string)$this->request->getGet('q'));
        $status    = trim((string)$this->request->getGet('status'));
        $perPage   = (int) ($this->request->getGet('per_page') ?: 10);
        if (!in_array($perPage, [5, 10, 20], true)) {
            $perPage = 10;
        }
        $page      = max(1, (int) $this->request->getGet('page_customers'));

        $result    = $userModel->getCustomersPaginated($search, $status, $perPage, $page, 'customers');
        $customers = $result['customers'];

        $db = \Config\Database::connect();
        $total = $db->table('users')->where('role','customer')->countAllResults();
        $activeToday = $db->table('users')->where('role','customer')->where('DATE(last_login_at) = CURDATE()', null, false)->countAllResults();
        // fallback if last_login_at null: use created_today
        $newThisWeek = $db->table('users')->where('role','customer')->where('created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))->countAllResults();

        return view('admin/customers', [
            'customers'     => $customers,
            'pager'         => $result['pager'],
            'per_page'      => $perPage,
            'filters'       => ['q'=>$search,'status'=>$status],
            'total_count'   => $total,
            'active_today'  => $activeToday,
            'new_this_week' => $newThisWeek,
        ]);
    }

    public function payments()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $payoutModel = new PayoutModel();
        $search      = trim((string)$this->request->getGet('q'));
        $status      = trim((string)$this->request->getGet('status'));
        $perPage     = (int) ($this->request->getGet('per_page') ?: 10);
        if (!in_array($perPage, [5, 10, 15, 20], true)) {
            $perPage = 10;
        }
        $page        = max(1, (int) $this->request->getGet('page_payments'));

        $result          = $payoutModel->getPaymentsPaginated($search, $status, $perPage, $page, 'payments');
        $paymentRequests = $result['payments'];

        $db = \Config\Database::connect();
        $pendingTotal = $db->table('payout_requests')->where('status','pending')->where('destination_method','gcash')->selectSum('amount','total')->get()->getRow()->total ?? 0;
        $processingTotal = $db->table('payout_requests')->whereIn('status', ['processing', 'transfer_pending'])->where('destination_method','gcash')->selectSum('amount','total')->get()->getRow()->total ?? 0;
        $completedTotal = $db->table('payout_requests')->where('status','completed')->where('destination_method','gcash')->selectSum('amount','total')->get()->getRow()->total ?? 0;
        $pendingCount = $db->table('payout_requests')->where('status','pending')->where('destination_method','gcash')->countAllResults();
        $processingCount = $db->table('payout_requests')->whereIn('status', ['processing', 'transfer_pending'])->where('destination_method','gcash')->countAllResults();

        return view('admin/payments', [
            'payment_requests'  => $paymentRequests,
            'payments'          => $paymentRequests,
            'pager'             => $result['pager'],
            'per_page'          => $perPage,
            'filters'           => ['q'=>$search,'status'=>$status],
            'pending_total'     => (float)$pendingTotal,
            'processing_total'  => (float)$processingTotal,
            'completed_total'   => (float)$completedTotal,
            'pending_count'     => (int)$pendingCount,
            'processing_count'  => (int)$processingCount,
            'deduction_percent' => (new SiteContentModel())->getPlatformDeductionPercent(),
        ]);
    }

    public function compliance()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $complianceModel = new ComplianceModel();
        $search          = trim((string)$this->request->getGet('q'));
        $status          = trim((string)$this->request->getGet('status'));
        $perPage         = (int) ($this->request->getGet('per_page') ?: 10);
        if (!in_array($perPage, [5, 10, 20], true)) {
            $perPage = 10;
        }
        $page            = max(1, (int) $this->request->getGet('page_compliance'));

        $result  = $complianceModel->getCompliancePaginated($search, $status, $perPage, $page, 'compliance');
        $reports = $result['reports'];

        $db = \Config\Database::connect();
        $totalReports = $db->table('compliance_reports')->countAllResults();
        $pendingReviews = $db->table('compliance_reports')->whereIn('status',['pending','under_review','flagged'])->countAllResults();
        $resolvedCases = $db->table('compliance_reports')->where('status','resolved')->countAllResults();

        return view('admin/compliance', [
            'compliance_items' => $reports,
            'reports'          => $reports,
            'pager'            => $result['pager'],
            'per_page'         => $perPage,
            'filters'          => ['q'=>$search,'status'=>$status],
            'total_reports'    => $totalReports,
            'pending_reviews'  => $pendingReviews,
            'resolved_cases'   => $resolvedCases,
        ]);
    }

    public function tracking()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) {
            return $auth;
        }

        $deliveryModel = new DeliveryModel();
        $pins = $deliveryModel->getAllDeliveryPins();

        // Ensure pins only contain valid Polomolok coordinates
        $pins = array_values(array_filter($pins, function ($p) {
            if (!empty($p['current_lat']) && !empty($p['current_lng'])) {
                return DeliveryModel::isPolomolokCoordinate((float) $p['current_lat'], (float) $p['current_lng']);
            }
            return false;
        }));

        $groupedShops = [];
        $activeShipmentCount = 0;
        $inTransitCount = 0;
        $shippedCount = 0;

        foreach ($pins as $pin) {
            $sId = (int) ($pin['shop_id'] ?? 0);
            $sName = $pin['shop_name'] ?? 'Independent Partner';
            $activeShipmentCount++;
            if ($pin['status'] === 'in_transit') {
                $inTransitCount++;
            } else {
                $shippedCount++;
            }

            if (!isset($groupedShops[$sId])) {
                $groupedShops[$sId] = [
                    'shop_id'    => $sId,
                    'shop_name'  => $sName,
                    'shop_logo'  => $pin['shop_logo'] ?? '',
                    'shop_lat'   => $pin['shop_lat'] ?? null,
                    'shop_lng'   => $pin['shop_lng'] ?? null,
                    'count'      => 0,
                    'deliveries' => [],
                ];
            }
            $groupedShops[$sId]['count']++;
            $groupedShops[$sId]['deliveries'][] = $pin;
        }

        $kpis = [
            'total_active' => $activeShipmentCount,
            'in_transit'   => $inTransitCount,
            'shipped'      => $shippedCount,
            'active_shops' => count($groupedShops),
        ];

        return view('admin/tracking', [
            'pins'         => $pins,
            'groupedShops' => array_values($groupedShops),
            'kpis'         => $kpis,
            'title'        => 'Live Fleet Tracking',
        ]);
    }

    /**
     * AJAX endpoint for live fleet map auto-refreshing.
     * Route: GET admin/tracking/pins
     */
    public function trackingPins()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $deliveryModel = new DeliveryModel();
        $pins = $deliveryModel->getAllDeliveryPins();

        $pins = array_values(array_filter($pins, function ($p) {
            if (!empty($p['current_lat']) && !empty($p['current_lng'])) {
                return DeliveryModel::isPolomolokCoordinate((float) $p['current_lat'], (float) $p['current_lng']);
            }
            return false;
        }));

        $groupedShops = [];
        $activeShipmentCount = 0;
        $inTransitCount = 0;
        $shippedCount = 0;

        foreach ($pins as $pin) {
            $sId = (int) ($pin['shop_id'] ?? 0);
            $sName = $pin['shop_name'] ?? 'Independent Partner';
            $activeShipmentCount++;
            if ($pin['status'] === 'in_transit') {
                $inTransitCount++;
            } else {
                $shippedCount++;
            }

            if (!isset($groupedShops[$sId])) {
                $groupedShops[$sId] = [
                    'shop_id'    => $sId,
                    'shop_name'  => $sName,
                    'shop_logo'  => $pin['shop_logo'] ?? '',
                    'shop_lat'   => $pin['shop_lat'] ?? null,
                    'shop_lng'   => $pin['shop_lng'] ?? null,
                    'count'      => 0,
                    'deliveries' => [],
                ];
            }
            $groupedShops[$sId]['count']++;
            $groupedShops[$sId]['deliveries'][] = $pin;
        }

        $kpis = [
            'total_active' => $activeShipmentCount,
            'in_transit'   => $inTransitCount,
            'shipped'      => $shippedCount,
            'active_shops' => count($groupedShops),
        ];

        return $this->response->setJSON([
            'success'      => true,
            'pins'         => $pins,
            'groupedShops' => array_values($groupedShops),
            'kpis'         => $kpis,
            'count'        => count($pins),
            'timestamp'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Real-time polling endpoint for platform-wide orders and printing requests.
     * Route: GET /admin/realtime/check
     */
    public function realtimeCheck()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $lastOrderId = (int) $this->request->getGet('last_order_id');
        $lastPrintingId = (int) $this->request->getGet('last_printing_id');

        $orderModel = new \App\Models\OrderModel();
        $printingModel = new \App\Models\PrintingRequestModel();
        $userModel = new \App\Models\UserModel();
        $shopModel = new \App\Models\ShopModel();

        $newOrders = [];
        if ($lastOrderId > 0) {
            $raw = $orderModel->where('id >', $lastOrderId)
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($raw as $ord) {
                $shop = $shopModel->find($ord['shop_id']);
                $cust = $userModel->find($ord['customer_id']);
                $custName = trim(($cust['first_name'] ?? '') . ' ' . ($cust['last_name'] ?? ''));
                if ($custName === '') $custName = 'Customer';

                $newOrders[] = [
                    'id'                 => (int) $ord['id'],
                    'order_number'       => $ord['order_number'] ?? ('ORD-' . $ord['id']),
                    'shop_name'          => $shop['shop_name'] ?? 'Store Partner',
                    'customer_name'      => $custName,
                    'total_amount'       => (float) ($ord['total_amount'] ?? 0),
                    'total_amount_fmt'   => number_format((float) ($ord['total_amount'] ?? 0), 2),
                    'fulfillment_method' => strtolower($ord['fulfillment_method'] ?? 'delivery'),
                    'status'             => $ord['status'] ?? 'pending',
                    'created_at'         => $ord['created_at'],
                ];
            }
        }

        $newPrinting = [];
        if ($lastPrintingId > 0) {
            $rawPr = $printingModel->where('id >', $lastPrintingId)
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($rawPr as $pr) {
                $shop = $shopModel->find($pr['shop_id']);
                $cust = $userModel->find($pr['customer_id']);
                $custName = trim(($cust['first_name'] ?? '') . ' ' . ($cust['last_name'] ?? ''));
                if ($custName === '') $custName = 'Customer';

                $newPrinting[] = [
                    'id'                 => (int) $pr['id'],
                    'request_number'     => $pr['request_number'] ?? ('PR-' . $pr['id']),
                    'shop_name'          => $shop['shop_name'] ?? 'Store Partner',
                    'customer_name'      => $custName,
                    'total_amount'       => (float) ($pr['total_price'] ?? 0),
                    'total_amount_fmt'   => number_format((float) ($pr['total_price'] ?? 0), 2),
                    'fulfillment_method' => strtolower($pr['fulfillment_method'] ?? 'delivery'),
                    'status'             => $pr['status'] ?? 'new',
                    'created_at'         => $pr['created_at'],
                ];
            }
        }

        $maxOrdRow = $orderModel->selectMax('id')->first();
        $currentMaxOrderId = (int) ($maxOrdRow['id'] ?? 0);

        $maxPrRow = $printingModel->selectMax('id')->first();
        $currentMaxPrintId = (int) ($maxPrRow['id'] ?? 0);

        return $this->response->setJSON([
            'success'          => true,
            'max_order_id'     => $currentMaxOrderId,
            'max_printing_id'  => $currentMaxPrintId,
            'has_new_orders'   => !empty($newOrders),
            'new_orders'       => $newOrders,
            'has_new_printing' => !empty($newPrinting),
            'new_printing'     => $newPrinting,
        ]);
    }

    public function auditLog()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $search    = trim((string) $this->request->getGet('q'));
        $role      = trim((string) $this->request->getGet('role'));
        $status    = trim((string) $this->request->getGet('status'));
        $dateRange = trim((string) $this->request->getGet('range'));
        $ip        = trim((string) $this->request->getGet('ip'));
        $perPage   = (int) ($this->request->getGet('per_page') ?: 10);
        if (!in_array($perPage, [5, 10, 20], true)) {
            $perPage = 10;
        }
        $page      = max(1, (int) $this->request->getGet('page_audit_log'));

        $auditLogModel = new AuditLogModel();
        $result = $auditLogModel->getAuditLogsPaginated($search, $role, $status, $perPage, $page, 'audit_log', $dateRange, $ip);
        $logs   = $result['logs'];

        $db = \Config\Database::connect();
        $total24h = $db->table('audit_logs')->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))->countAllResults();
        $critical = $db->table('audit_logs')->where('status', 'failed')->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))->countAllResults();
        $financialOps = $db->table('audit_logs')
            ->groupStart()
                ->like('action', 'payout')
                ->orLike('action', 'payment')
                ->orLike('target_type', 'payout')
                ->orLike('target_type', 'payment')
            ->groupEnd()
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->countAllResults();
        $activeActors = $db->table('audit_logs')
            ->select('COUNT(DISTINCT actor_id) AS total')
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->where('actor_id IS NOT NULL')
            ->get()->getRowArray()['total'] ?? 0;

        return view('admin/audit_log', [
            'audit_logs'    => $logs,
            'logs'          => $logs,
            'pager'         => $result['pager'],
            'per_page'      => $perPage,
            'filters'       => [
                'q'      => $search,
                'role'   => $role,
                'status' => $status,
                'range'  => $dateRange,
                'ip'     => $ip,
            ],
            'total_24h'     => $total24h,
            'critical'      => $critical,
            'financial_ops' => $financialOps,
            'active_actors' => (int) $activeActors,
            'activeNav'     => 'audit',
            'title'         => 'Audit Log',
        ]);
    }

    public function auditLogDetail($logId)
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $db = \Config\Database::connect();
        $log = $db->table('audit_logs a')
            ->select('a.*, u.first_name, u.last_name, u.email, u.phone AS phone_number, u.role AS user_actual_role')
            ->join('users u', 'u.id = a.actor_id', 'left')
            ->where('a.id', (int) $logId)
            ->get()->getRowArray();

        if (!$log) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Audit log record not found.']);
        }

        $actorName = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: ($log['actor_role'] === 'system' ? 'System Process' : 'Anonymous');

        // Resolve target metadata if available
        $targetInfo = null;
        if (!empty($log['target_type']) && !empty($log['target_id'])) {
            $tt  = strtolower($log['target_type']);
            $tid = (int) $log['target_id'];
            if ($tt === 'shop') {
                $targetInfo = $db->table('shops')->select('id, shop_name, slug, status')->where('id', $tid)->get()->getRowArray();
            } elseif ($tt === 'user' || $tt === 'customer') {
                $targetInfo = $db->table('users')->select('id, first_name, last_name, email, role')->where('id', $tid)->get()->getRowArray();
            } elseif ($tt === 'order') {
                $targetInfo = $db->table('orders')->select('id, order_number, total_amount, status')->where('id', $tid)->get()->getRowArray();
            } elseif ($tt === 'payout') {
                $targetInfo = $db->table('payout_requests')->select('id, amount, status, payout_method')->where('id', $tid)->get()->getRowArray();
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'data'    => [
                'id'          => (int) $log['id'],
                'actor_id'    => $log['actor_id'] ? (int) $log['actor_id'] : null,
                'actor_name'  => $actorName,
                'actor_email' => $log['email'] ?? '',
                'actor_phone' => $log['phone_number'] ?? '',
                'actor_role'  => $log['actor_role'],
                'action'      => $log['action'],
                'target_type' => $log['target_type'] ?? '',
                'target_id'   => $log['target_id'] ? (int) $log['target_id'] : null,
                'target_info' => $targetInfo,
                'status'      => $log['status'],
                'ip_address'  => $log['ip_address'] ?? '127.0.0.1',
                'created_at'  => $log['created_at'],
                'time_ago'    => date('M d, Y h:i:s A', strtotime($log['created_at'])),
            ],
        ]);
    }

    public function exportAuditLog()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $search    = trim((string) $this->request->getGet('q'));
        $role      = trim((string) $this->request->getGet('role'));
        $status    = trim((string) $this->request->getGet('status'));
        $dateRange = trim((string) $this->request->getGet('range'));
        $ip        = trim((string) $this->request->getGet('ip'));

        $db      = \Config\Database::connect();
        $builder = $db->table('audit_logs a')
            ->select('a.*, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = a.actor_id', 'left')
            ->orderBy('a.created_at', 'DESC');

        if ($search !== '') {
            $builder->groupStart()
                ->like('a.action', $search)
                ->orLike('a.target_type', $search)
                ->orLike('a.target_id', $search)
                ->orLike('a.ip_address', $search)
                ->orLike('u.first_name', $search)
                ->orLike('u.last_name', $search)
                ->orLike('u.email', $search)
                ->groupEnd();
        }

        if ($ip !== '') {
            $builder->where('a.ip_address', $ip);
        }

        if ($role !== '' && in_array($role, ['admin', 'tenant', 'customer', 'system'], true)) {
            $builder->where('a.actor_role', $role);
        }

        if ($status !== '' && in_array($status, ['success', 'failed'], true)) {
            $builder->where('a.status', $status);
        }

        if ($dateRange === 'today') {
            $builder->where('a.created_at >=', date('Y-m-d 00:00:00'));
        } elseif ($dateRange === '7d') {
            $builder->where('a.created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')));
        } elseif ($dateRange === '30d') {
            $builder->where('a.created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')));
        }

        $logs = $builder->get()->getResultArray();
        $filename = 'admin_audit_logs_' . date('Y-m-d') . '.csv';

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['Log ID', 'Timestamp', 'Actor Name', 'Actor Email', 'Role', 'Action', 'Target Type', 'Target ID', 'Status', 'IP Address']);

        foreach ($logs as $l) {
            $actorName = trim(($l['first_name'] ?? '') . ' ' . ($l['last_name'] ?? '')) ?: ($l['actor_role'] === 'system' ? 'System' : 'Unknown');
            fputcsv($output, [
                $l['id'],
                $l['created_at'],
                $actorName,
                $l['email'] ?? '',
                strtoupper($l['actor_role']),
                $l['action'],
                $l['target_type'] ?? '',
                $l['target_id'] ?? '',
                strtoupper($l['status']),
                $l['ip_address'] ?? '',
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

    public function analytics()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $payoutModel = new PayoutModel();
        $db = \Config\Database::connect();
        $range = $this->request->getGet('range');
        $range = in_array($range, ['7','30','year'], true) ? $range : '30';

        $adminRevenueTotal = (float) $payoutModel->getAdminRevenueTotal('completed');
        $adminRevenueChart = $payoutModel->getAdminRevenueChartData($range, 'completed');
        $payoutCount = $db->table('payout_requests')->where('status', 'completed')->where('destination_method', 'gcash')->countAllResults();
        $avgFee = $payoutCount > 0 ? $adminRevenueTotal / $payoutCount : 0;

        // Platform GMV (Orders total + Custom Printing jobs)
        $orderGmv = (float) ($db->table('orders')->selectSum('total_amount')->get()->getRow()->total_amount ?? 0);
        $printGmv = 0.0;
        if ($db->tableExists('printing_requests')) {
            $printGmv = (float) ($db->table('printing_requests')->selectSum('total_price')->get()->getRow()->total_price ?? 0);
        }
        $platformGmv = $orderGmv + $printGmv;

        $totalOrders = $db->table('orders')->countAllResults();
        $totalCustomers = $db->table('users')->where('role', 'customer')->countAllResults();

        // Merchant network
        $totalShops = $db->table('shops')->countAllResults();
        $activeShops = $db->table('shops')->where('status', 'active')->countAllResults();
        $printingShops = $db->table('shops')->where('status', 'active')->where('offers_printing', 1)->countAllResults();

        // Fulfillment method breakdown
        $deliveryCount = $db->table('orders')->where('fulfillment_method', 'delivery')->countAllResults();
        $pickupCount = $db->table('orders')->where('fulfillment_method', 'pickup')->countAllResults();
        $fulfillmentTotal = max(1, $deliveryCount + $pickupCount);
        $fulfillmentDist = [
            'delivery_count' => $deliveryCount,
            'delivery_pct'   => round(($deliveryCount / $fulfillmentTotal) * 100),
            'pickup_count'   => $pickupCount,
            'pickup_pct'     => round(($pickupCount / $fulfillmentTotal) * 100),
        ];

        // Payment method breakdown
        $paymentCounts = [
            'gcash'  => $db->table('orders')->where('payment_method', 'gcash')->countAllResults(),
            'online' => $db->table('orders')->whereIn('payment_method', ['online', 'paymongo', 'card'])->countAllResults(),
            'cash'   => $db->table('orders')->whereIn('payment_method', ['cash', 'cod'])->countAllResults(),
        ];
        $totalPaidMethods = max(1, array_sum($paymentCounts));
        $paymentDist = [
            'gcash'  => ['count' => $paymentCounts['gcash'], 'pct' => round(($paymentCounts['gcash'] / $totalPaidMethods) * 100)],
            'online' => ['count' => $paymentCounts['online'], 'pct' => round(($paymentCounts['online'] / $totalPaidMethods) * 100)],
            'cash'   => ['count' => $paymentCounts['cash'], 'pct' => round(($paymentCounts['cash'] / $totalPaidMethods) * 100)],
        ];

        // Top shops by volume & GMV
        $topShops = $db->table('orders o')
            ->select('s.id as shop_id, s.shop_name, s.logo_url, s.plan, s.offers_printing, s.rating_average, COUNT(o.id) as total_orders, COALESCE(SUM(o.total_amount), 0) as total_gmv')
            ->join('shops s', 's.id = o.shop_id', 'left')
            ->groupBy('o.shop_id')
            ->orderBy('total_orders', 'DESC')
            ->limit(6)
            ->get()->getResultArray();

        // Polomolok delivery area distribution
        $areaCounts = [];
        if ($db->tableExists('shipping_addresses')) {
            $addrRows = $db->table('shipping_addresses sa')
                ->select("COALESCE(NULLIF(sa.city, ''), 'Poblacion') as area, COUNT(sa.id) as count")
                ->groupBy('area')
                ->orderBy('count', 'DESC')
                ->limit(5)
                ->get()->getResultArray();
            $areaCounts = $addrRows;
        }

        // Active deliveries
        $deliveryModel = new DeliveryModel();
        $activeDeliveriesCount = $deliveryModel->whereIn('status', ['shipped', 'in_transit'])->countAllResults();

        // Period chart metrics
        $chartValues = array_map('floatval', $adminRevenueChart['values'] ?? []);
        $chartSum = round(array_sum($chartValues), 2);
        $chartAvg = count($chartValues) > 0 ? round($chartSum / count($chartValues), 2) : 0;
        $chartPeak = count($chartValues) > 0 ? max($chartValues) : 0;

        return view('admin/analytics', [
            'admin_revenue'           => $adminRevenueTotal,
            'avg_fee'                 => $avgFee,
            'payout_count'            => $payoutCount,
            'platform_gmv'            => $platformGmv,
            'total_orders'            => $totalOrders,
            'total_shops'             => $totalShops,
            'active_shops'            => $activeShops,
            'printing_shops'          => $printingShops,
            'total_customers'         => $totalCustomers,
            'fulfillment_dist'        => $fulfillmentDist,
            'payment_dist'            => $paymentDist,
            'top_shops'               => $topShops,
            'area_counts'             => $areaCounts,
            'active_deliveries_count' => $activeDeliveriesCount,
            'chart_labels'            => $adminRevenueChart['labels'],
            'chart_values'            => $adminRevenueChart['values'],
            'chart_sum'               => $chartSum,
            'chart_avg'               => $chartAvg,
            'chart_peak'              => $chartPeak,
            'range'                   => $range,
            'title'                   => 'Analytics',
        ]);
    }

    public function analyticsData()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $this->response->setStatusCode(401)->setJSON(['success' => false]);

        $range = (string) $this->request->getGet('range');
        $range = in_array($range, ['7', '30', 'year'], true) ? $range : '30';
        $payoutModel = new PayoutModel();
        $chart = $payoutModel->getAdminRevenueChartData($range, 'completed');
        $values = array_map('floatval', $chart['values'] ?? []);
        $total = round(array_sum($values), 2);
        $count = count($values);
        $avg = $count > 0 ? round($total / $count, 2) : 0;
        $peak = $count > 0 ? max($values) : 0;

        return $this->response->setJSON([
            'success' => true,
            'range'   => $range,
            'labels'  => $chart['labels'],
            'values'  => $chart['values'],
            'total'   => $total,
            'avg'     => $avg,
            'peak'    => $peak,
        ]);
    }

    public function content()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $model = new SiteContentModel();
        $db = \Config\Database::connect();
        if (!$db->tableExists('site_contents')) {
            $db->query("CREATE TABLE IF NOT EXISTS site_contents (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, page VARCHAR(60) NOT NULL, content_key VARCHAR(100) NOT NULL, label VARCHAR(150) NULL, content_type ENUM('text','textarea','image') DEFAULT 'text', text_value TEXT NULL, image_url VARCHAR(500) NULL, sort_order INT DEFAULT 0, updated_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, UNIQUE KEY uq_page_key (page,content_key))");
        }

        // Migrate legacy 'home' records to 'home_banners'
        $legacyRows = $db->table('site_contents')->where('page', 'home')->get()->getResultArray();
        foreach ($legacyRows as $leg) {
            $existsInHomeBanners = $db->table('site_contents')
                ->where('page', 'home_banners')
                ->where('content_key', $leg['content_key'])
                ->countAllResults();
            if ($existsInHomeBanners === 0) {
                $db->table('site_contents')->where('id', $leg['id'])->update(['page' => 'home_banners']);
            } else {
                $db->table('site_contents')->where('id', $leg['id'])->delete();
            }
        }

        // Remove deprecated promotional_blocks if present
        $db->table('site_contents')->where('page', 'promotional_blocks')->delete();

        // Seed default structured entries if missing, ordered top-to-bottom
        $defaults = [
            ['page'=>'home_banners','content_key'=>'hero_badge','label'=>'Hero Badge Text','content_type'=>'text','text_value'=>'Seasonal Event','sort_order'=>1],
            ['page'=>'home_banners','content_key'=>'hero_title','label'=>'Hero Main Headline','content_type'=>'text','text_value'=>'The Ultimate Merchandise & Printing Hub','sort_order'=>2],
            ['page'=>'home_banners','content_key'=>'hero_subtitle','label'=>'Hero Subtitle Description','content_type'=>'textarea','text_value'=>'Discover premium goods, exclusive deals, and top-tier printing services all in one place across Polomolok.','sort_order'=>3],
            ['page'=>'home_banners','content_key'=>'hero_cta_text','label'=>'Hero CTA Button Text','content_type'=>'text','text_value'=>'Explore Marketplace','sort_order'=>4],
            ['page'=>'home_banners','content_key'=>'hero_image','label'=>'Hero Banner Background','content_type'=>'image','image_url'=>'https://images.unsplash.com/photo-1556742049-0a67daf64f42?auto=format&fit=crop&w=1440&q=80','sort_order'=>5],
            ['page'=>'home_banners','content_key'=>'catalog_title','label'=>'Catalog Section Heading','content_type'=>'text','text_value'=>'Global Product Catalog','sort_order'=>6],
            ['page'=>'home_banners','content_key'=>'catalog_subtitle','label'=>'Catalog Section Subtitle','content_type'=>'textarea','text_value'=>'Aggregation of all products currently available across the entire RHK network.','sort_order'=>7],

            ['page'=>'announcement_bar','content_key'=>'announcement_active','label'=>'Announcement Bar Active (1 or 0)','content_type'=>'text','text_value'=>'1','sort_order'=>1],
            ['page'=>'announcement_bar','content_key'=>'announcement_text','label'=>'Top Bar Announcement Text','content_type'=>'text','text_value'=>'🚀 Free doorstep delivery on orders over ₱500 within Polomolok! Use code BLAXSHIP','sort_order'=>2],

            ['page'=>'footer_info','content_key'=>'footer_contact_email','label'=>'Contact Email','content_type'=>'text','text_value'=>'support@blaxinventory.com','sort_order'=>1],
            ['page'=>'footer_info','content_key'=>'footer_phone','label'=>'Customer Hotline','content_type'=>'text','text_value'=>'+63 917 123 4567','sort_order'=>2],
            ['page'=>'footer_info','content_key'=>'footer_address','label'=>'Hub Office Location','content_type'=>'text','text_value'=>'Poblacion, Polomolok, South Cotabato, Philippines 9504','sort_order'=>3],
            ['page'=>'footer_info','content_key'=>'footer_copyright','label'=>'Copyright Notice','content_type'=>'text','text_value'=>'© 2026 Blax Multi-Tenant Inventory & Printing Platform. All rights reserved.','sort_order'=>4],
        ];

        foreach ($defaults as $d) {
            $existing = $db->table('site_contents')->where('page', $d['page'])->where('content_key', $d['content_key'])->get()->getRowArray();
            if (!$existing) {
                $db->table('site_contents')->insert($d);
            } else {
                // Keep sort_order aligned with top-to-bottom layout
                $db->table('site_contents')->where('id', $existing['id'])->update(['sort_order' => $d['sort_order']]);
            }
        }

        $connectedFields = [
            'hero_badge'           => 'Homepage Hero Badge',
            'hero_title'           => 'Homepage Hero Headline',
            'hero_subtitle'        => 'Homepage Hero Subtitle',
            'hero_cta_text'        => 'Homepage Hero CTA Button',
            'hero_image'           => 'Homepage Hero Background',
            'catalog_title'        => 'Homepage Catalog Heading',
            'catalog_subtitle'     => 'Homepage Catalog Subtitle',
            'announcement_text'    => 'Global Announcement Bar',
            'announcement_active'  => 'Global Announcement Toggle',
            'footer_contact_email' => 'Global Footer Contact',
            'footer_phone'         => 'Global Footer Contact',
            'footer_address'       => 'Global Footer Contact',
            'footer_copyright'     => 'Global Footer Copyright',
        ];

        $grouped = $model->getAllGrouped();
        if (empty($grouped)) {
            $grouped = ['home_banners'=>[]];
        }

        return view('admin/content', [
            'grouped'         => $grouped,
            'pages'           => array_keys($grouped),
            'connectedFields' => $connectedFields,
        ]);
    }

    public function saveContent()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $id = (int)$this->request->getPost('id');
        $page = trim((string)$this->request->getPost('page'));
        $key = trim((string)$this->request->getPost('content_key'));
        $textValue = $this->request->getPost('text_value');
        $label = trim((string)$this->request->getPost('label'));

        $model = new SiteContentModel();
        if ($id > 0) {
            $row = $model->find($id);
            if (!$row) return redirect()->back()->with('error','Content not found.');
            $data = [];
            if ($row['content_type'] !== 'image') {
                $data['text_value'] = $textValue;
            }
            if ($label !== '') $data['label'] = $label;
            $data['updated_by'] = (int)session()->get('user_id');
            $model->update($id, $data);
        } else {
            if ($page === '' || $key === '') return redirect()->back()->with('error','Page and key required.');
            $contentType = $this->request->getPost('content_type') === 'image' ? 'image' : ($this->request->getPost('content_type') === 'textarea' ? 'textarea' : 'text');
            $model->insert([
                'page'=>$page,
                'content_key'=>$key,
                'label'=>$label ?: $key,
                'content_type'=>$contentType,
                'text_value'=> $contentType !== 'image' ? $textValue : null,
                'sort_order'=> (int)$this->request->getPost('sort_order'),
                'updated_by'=> (int)session()->get('user_id'),
            ]);
        }
        return redirect()->back()->with('success','Content updated.');
    }

    public function uploadContentImage()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $id = (int)$this->request->getPost('id');
        $model = new SiteContentModel();
        $row = $model->find($id);
        if (!$row) return redirect()->back()->with('error','Content not found.');

        $file = $this->request->getFile('image');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error','Please choose an image.');
        }
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $mime = $file->getMimeType();
        if (!isset($mimeMap[$mime])) {
            return redirect()->back()->with('error','Only JPG, PNG, WEBP, GIF allowed.');
        }
        if ($file->getSize() > 3*1024*1024) {
            return redirect()->back()->with('error','Image must be 3MB or smaller.');
        }
        $uploadPath = ROOTPATH.'public/uploads/cms';
        if (!is_dir($uploadPath)) mkdir($uploadPath,0777,true);
        $fileName = 'cms_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $mimeMap[$mime];
        $file->move($uploadPath,$fileName);
        // Remove old image if local
        if (!empty($row['image_url']) && strpos($row['image_url'],'uploads/cms/')===0) {
            $old=ROOTPATH.'public/'.$row['image_url'];
            if (is_file($old)) @unlink($old);
        }
        $model->update($id, ['image_url'=>'uploads/cms/'.$fileName, 'updated_by'=>(int)session()->get('user_id')]);
        return redirect()->back()->with('success','Image updated.');
    }

    public function toggleTenantStatus()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $tenantId = (int) $this->request->getPost('tenant_id');
        $shopModel = new ShopModel();
        $shop      = $shopModel->find($tenantId);

        if (!$shop) {
            return redirect()->back()->with('error', 'Tenant shop not found.');
        }

        if (in_array(($shop['status'] ?? ''), ['pending', 'rejected'], true)) {
            return redirect()->back()->with('error', 'Pending or rejected tenants must use the verification actions.');
        }

        $newStatus = ($shop['status'] ?? 'active') === 'active' ? 'suspended' : 'active';
        $shopModel->update($tenantId, ['status' => $newStatus]);

        if (!empty($shop['owner_id'])) {
            $title = 'Shop ' . ucfirst($newStatus);
            $msg   = 'Your shop "' . ($shop['shop_name'] ?? 'Shop') . '" status has been changed to ' . $newStatus . ' by the administrator.';
            (new NotificationModel())->create((int) $shop['owner_id'], 'shop_status', $title, $msg, '/tenant/dashboard');
        }

        return redirect()->back()->with('success', 'Tenant status updated to ' . $newStatus . '.');
    }

    public function toggleCustomerStatus()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $customerId = (int) $this->request->getPost('customer_id');
        $userModel  = new UserModel();
        $customer   = $userModel->find($customerId);

        if (!$customer || ($customer['role'] ?? '') !== 'customer') {
            return redirect()->back()->with('error', 'Customer account not found.');
        }

        $currentStatus = $customer['status'] ?? 'active';
        if (!in_array($currentStatus, ['active', 'inactive'], true)) {
            return redirect()->back()->with('error', 'Only active or inactive customer accounts can be updated here.');
        }

        $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
        $userModel->update($customerId, ['status' => $newStatus]);

        $title = 'Account ' . ucfirst($newStatus);
        $msg   = 'Your customer account status has been updated to ' . $newStatus . ' by the administrator.';
        (new NotificationModel())->create($customerId, 'account_status', $title, $msg, '/customer/profile');

        return redirect()->back()->with('success', 'Customer account ' . $newStatus . '.');
    }

    /**
     * Approve a pending tenant and notify the registered owner once.
     */
    public function approveTenant()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $shopId = (int) $this->request->getPost('tenant_id');
        $shopModel = new ShopModel();
        $shop = $shopModel->find($shopId);

        if (!$shop || ($shop['status'] ?? '') !== 'pending') {
            return redirect()->back()->with('error', 'Only pending tenant applications can be approved.');
        }

        $userModel = new UserModel();
        $user = $userModel->find((int) $shop['owner_id']);
        if (!$user || ($user['role'] ?? '') !== 'shop_owner') {
            return redirect()->back()->with('error', 'Tenant owner account not found.');
        }

        $db = \Config\Database::connect();
        $db->transStart();
        $shopUpdate = ['status' => 'active'];
        if ($db->fieldExists('verified_at', 'shops')) {
            $shopUpdate['verified_at'] = date('Y-m-d H:i:s');
        }
        if ($db->fieldExists('rejection_reason', 'shops')) {
            $shopUpdate['rejection_reason'] = null;
        }
        $shopModel->update($shopId, $shopUpdate);
        $userModel->update((int) $user['id'], ['status' => 'active']);
        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('error', 'Tenant approval transaction failed for shop #' . $shopId);
            return redirect()->back()->with('error', 'Tenant approval could not be saved.');
        }

        try {
            service('mailService')->sendTenantVerified($user, array_merge($shop, [
                'status'      => 'active',
                'verified_at' => date('Y-m-d H:i:s'),
            ]));
        } catch (\Throwable $e) {
            log_message('error', 'Tenant #' . $shopId . ' approved but verification email failed: ' . $e->getMessage());

            return redirect()->back()->with('warning', 'Tenant approved, but the verification email could not be sent.');
        }

        return redirect()->back()->with('success', 'Tenant approved and verification email sent.');
    }

    /**
     * Reject a pending tenant application with an admin-provided reason.
     */
    public function rejectTenant()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $shopId = (int) $this->request->getPost('tenant_id');
        $reason = trim((string) $this->request->getPost('rejection_reason'));
        if ($reason === '' || mb_strlen($reason) > 255) {
            return redirect()->back()->with('error', 'A rejection reason is required and must be 255 characters or fewer.');
        }

        $shopModel = new ShopModel();
        $shop = $shopModel->find($shopId);
        if (!$shop || ($shop['status'] ?? '') !== 'pending') {
            return redirect()->back()->with('error', 'Only pending tenant applications can be rejected.');
        }

        $db = \Config\Database::connect();
        $shopUpdate = ['status' => 'rejected'];
        if ($db->fieldExists('rejection_reason', 'shops')) {
            $shopUpdate['rejection_reason'] = $reason;
        }
        $shopModel->update($shopId, $shopUpdate);

        $userModel = new UserModel();
        $owner = !empty($shop['owner_id']) ? $userModel->find((int) $shop['owner_id']) : null;
        if ($owner) {
            (new NotificationModel())->create(
                (int) $owner['id'],
                'application',
                'Tenant Application Rejected',
                'Your application for "' . ($shop['shop_name'] ?? 'Shop') . '" was not approved: ' . $reason
            );

            try {
                service('mailService')->sendTenantRejected($owner, $shop, $reason);
            } catch (\Throwable $e) {
                log_message('error', 'Tenant #' . $shopId . ' rejected but email failed: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Tenant application rejected.');
    }

    /**
     * Stream a business permit inline only to an authenticated administrator.
     * New records use writable storage; legacy records may still point at the
     * original public upload directory.
     */
    public function tenantPermit($shopId)
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $shop = (new ShopModel())->find((int) $shopId);
        if (!$shop || empty($shop['business_permit_url'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Business permit not found.');
        }

        $storedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $shop['business_permit_url']);
        $fileName = basename($storedPath);
        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Business permit not found.');
        }

        if (str_starts_with($storedPath, 'private' . DIRECTORY_SEPARATOR)) {
            $path = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'business_permits' . DIRECTORY_SEPARATOR . $fileName;
        } elseif (str_starts_with($storedPath, 'uploads' . DIRECTORY_SEPARATOR . 'business_permits' . DIRECTORY_SEPARATOR)) {
            // Legacy path compatibility for existing records only.
            $path = ROOTPATH . 'public' . DIRECTORY_SEPARATOR . ltrim($storedPath, DIRECTORY_SEPARATOR);
        } else {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Business permit not found.');
        }

        if (!is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Business permit file not found.');
        }

        return $this->response->download($path, null, true)->inline()->noCache();
    }

    /**
     * Move a pending withdrawal to processing.
     * CRITICAL: This action MUST NOT call PayMongo.
     */
    public function processPayout()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $withdrawalId = (int) ($this->request->getPost('withdrawal_id') ?: $this->request->getPost('payout_id'));
        $payoutModel  = new PayoutModel();
        $payout       = $payoutModel->find($withdrawalId);

        if (!$payout) {
            return redirect()->back()->with('error', 'Payout request not found.');
        }

        if (($payout['status'] ?? '') !== 'pending') {
            return redirect()->back()->with('error', 'Only pending payout requests can be moved to processing.');
        }

        if (!empty($payout['paymongo_transfer_id'])) {
            return redirect()->back()->with('error', 'This withdrawal has already been submitted to PayMongo.');
        }

        $adminId = (int) session()->get('user_id');

        $payoutModel->update($withdrawalId, [
            'status'       => 'processing',
            'processed_at' => date('Y-m-d H:i:s'),
            'processed_by' => $adminId,
        ]);

        (new AuditLogModel())->log(
            $adminId,
            'admin',
            'Moved Withdrawal to Processing',
            'payout_request',
            'success',
            $withdrawalId
        );

        $shop = (new ShopModel())->find((int) ($payout['shop_id'] ?? 0));
        if ($shop && !empty($shop['owner_id'])) {
            (new NotificationModel())->create(
                (int) $shop['owner_id'],
                'payout',
                'Payout In Processing',
                'Your GCash payout request #' . ($payout['reference_number'] ?? $withdrawalId) . ' (₱' . number_format((float) $payout['amount'], 2) . ') has been reviewed and moved to processing.',
                '/tenant/withdrawals'
            );
        }

        return redirect()->back()->with('success', 'Withdrawal moved to Processing. You may now review and send via PayMongo.');
    }

    /**
     * Initiate external GCash transfer via PayMongo Wallet Transfer API.
     * Only PROCESSING withdrawals may be sent.
     */
    public function sendTransfer()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $withdrawalId = (int) ($this->request->getPost('withdrawal_id') ?: $this->request->getPost('payout_id'));
        $db           = \Config\Database::connect();
        $adminId      = (int) session()->get('user_id');

        // Start transaction with row locking
        $db->transBegin();
        $payout = $db->table('payout_requests')
            ->where('id', $withdrawalId)
            ->get()->getRowArray();

        if (!$payout) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Withdrawal request not found.');
        }

        $currentStatus = strtolower((string) ($payout['status'] ?? ''));
        if ($currentStatus !== 'processing') {
            $db->transRollback();
            return redirect()->back()->with('error', 'Only withdrawals in Processing status can be sent to GCash.');
        }

        if (!empty($payout['paymongo_transfer_id']) || $currentStatus === 'transfer_pending' || $currentStatus === 'completed') {
            $db->transRollback();
            return redirect()->back()->with('error', 'Transfer already initiated or completed for this withdrawal.');
        }

        // Authoritative recipient data from immutable snapshot
        $destNumber = preg_replace('/\D/', '', (string) ($payout['destination_detail'] ?? ''));
        $destName   = trim((string) ($payout['recipient_account_name'] ?? ''));

        // Fallback to shop record only if snapshot was missing from legacy row
        if (empty($destNumber) || empty($destName)) {
            $shop = (new ShopModel())->find((int) ($payout['shop_id'] ?? 0));
            if (empty($destNumber)) $destNumber = preg_replace('/\D/', '', (string) ($shop['gcash_number'] ?? ''));
            if (empty($destName)) $destName = trim((string) ($shop['gcash_account_name'] ?? ''));
        }

        if (!preg_match('/^09\d{9}$/', $destNumber) || $destName === '') {
            $db->transRollback();
            return redirect()->back()->with('error', 'Withdrawal lacks complete recipient GCash information.');
        }

        $amount     = (float) ($payout['amount'] ?? 0);
        $rate       = (float) ($payout['deduction_percent'] ?? (new SiteContentModel())->getPlatformDeductionPercent());
        $fee        = (float) (($payout['fee'] ?? 0) > 0 ? $payout['fee'] : round($amount * ($rate / 100), 2));
        $netAmount  = (float) (($payout['net_amount'] ?? 0) > 0 ? $payout['net_amount'] : max(0.0, round($amount - $fee, 2)));

        if ($netAmount <= 0) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Net transfer amount must be greater than zero.');
        }

        $idempotencyKey = (string) (!empty($payout['idempotency_key']) ? $payout['idempotency_key'] : ('WD-TR-' . $withdrawalId . '-' . substr(md5(($payout['reference_number'] ?? '') . $netAmount), 0, 10)));

        // Transition to transfer_pending to prevent duplicate concurrent clicks
        $db->table('payout_requests')->where('id', $withdrawalId)->update([
            'status'                 => 'transfer_pending',
            'transfer_status'        => 'pending',
            'transfer_initiated_at'  => date('Y-m-d H:i:s'),
            'idempotency_key'        => $idempotencyKey,
            'fee'                    => $fee,
            'net_amount'             => $netAmount,
            'recipient_account_name' => $destName,
            'destination_detail'     => $destNumber,
            'recipient_institution'  => $payout['recipient_institution'] ?? 'G-Xchange, Inc.',
        ]);
        $db->transCommit();

        // 2. Call PayMongo Service
        $pmService   = new PaymongoService();
        $transferRes = $pmService->createTransfer([
            'amount'             => $netAmount,
            'destination_number' => $destNumber,
            'destination_name'   => $destName,
            'reference_number'   => (string) ($payout['reference_number'] ?? ('WD ' . $withdrawalId)),
            'description'        => 'GCash payout ' . ($payout['reference_number'] ?? ('WD-' . $withdrawalId)),
            'callback_url'       => base_url('payment/transfer-webhook'),
            'metadata'           => [
                'withdrawal_id'    => (string) $withdrawalId,
                'shop_id'          => (string) ($payout['shop_id'] ?? ''),
                'reference_number' => (string) ($payout['reference_number'] ?? ''),
            ],
        ], $idempotencyKey);

        $shop    = (new ShopModel())->find((int) ($payout['shop_id'] ?? 0));
        $ownerId = (int) ($shop['owner_id'] ?? 0);

        if (!$transferRes['success']) {
            $errorMsg = $transferRes['error'] ?? 'Transfer could not be completed. Please check your PayMongo wallet balance.';

            // Revert status back to processing so admin can retry after resolving issue
            $db->table('payout_requests')->where('id', $withdrawalId)->update([
                'status'          => 'processing',
                'transfer_status' => 'failed',
                'failure_reason'  => mb_substr($errorMsg, 0, 500),
            ]);

            (new AuditLogModel())->log(
                $adminId,
                'admin',
                'PayMongo Transfer Failed',
                'payout_request',
                'failed',
                $withdrawalId
            );

            return redirect()->back()->with('error', $errorMsg);
        }

        $trId     = $transferRes['transfer_id'] ?? '';
        $batchId  = $transferRes['batch_id'] ?? '';
        $trStatus = strtolower((string) ($transferRes['status'] ?? 'pending'));

        if ($trStatus === 'succeeded') {
            $db->table('payout_requests')->where('id', $withdrawalId)->update([
                'status'               => 'completed',
                'transfer_status'      => 'succeeded',
                'completed_at'         => date('Y-m-d H:i:s'),
                'paymongo_transfer_id' => $trId,
                'paymongo_batch_id'    => $batchId,
                'failure_reason'       => null,
            ]);

            (new AuditLogModel())->log(
                $adminId,
                'admin',
                'PayMongo Transfer Succeeded',
                'payout_request',
                'success',
                $withdrawalId
            );

            if ($ownerId > 0) {
                (new NotificationModel())->create(
                    $ownerId,
                    'payout',
                    'Payout Completed',
                    'Your GCash payout #' . ($payout['reference_number'] ?? $withdrawalId) . ' (₱' . number_format($netAmount, 2) . ') has been successfully transferred to your GCash account.',
                    '/tenant/withdrawals'
                );
            }

            return redirect()->back()->with('success', 'Transfer completed successfully via PayMongo. ₱' . number_format($netAmount, 2) . ' sent to GCash.');
        }

        // Standard InstaPay asynchronous transfer (pending)
        $db->table('payout_requests')->where('id', $withdrawalId)->update([
            'status'               => 'transfer_pending',
            'transfer_status'      => 'pending',
            'paymongo_transfer_id' => $trId,
            'paymongo_batch_id'    => $batchId,
        ]);

        (new AuditLogModel())->log(
            $adminId,
            'admin',
            'Initiated PayMongo GCash Transfer',
            'payout_request',
            'success',
            $withdrawalId
        );

        if ($ownerId > 0) {
            (new NotificationModel())->create(
                $ownerId,
                'payout',
                'Payout Transfer Pending',
                'Your GCash payout #' . ($payout['reference_number'] ?? $withdrawalId) . ' (₱' . number_format($netAmount, 2) . ') has been initiated via PayMongo and is processing.',
                '/tenant/withdrawals'
            );
        }

        return redirect()->back()->with('success', 'Transfer initiated via PayMongo (Ref: ' . esc($trId) . '). Waiting for InstaPay settlement.');
    }

    /**
     * Query PayMongo API to refresh transfer status for a transfer_pending withdrawal.
     */
    public function syncTransferStatus()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $withdrawalId = (int) ($this->request->getPost('withdrawal_id') ?: $this->request->getPost('payout_id'));
        $payoutModel  = new PayoutModel();
        $payout       = $payoutModel->find($withdrawalId);

        if (!$payout || empty($payout['paymongo_transfer_id'])) {
            return redirect()->back()->with('error', 'No PayMongo transfer ID found for this withdrawal.');
        }

        $pmService = new PaymongoService();
        $res = $pmService->getTransfer($payout['paymongo_transfer_id']);

        if (!$res['success']) {
            return redirect()->back()->with('error', 'Failed to check status with PayMongo: ' . ($res['error'] ?? 'Unknown error'));
        }

        $status  = strtolower((string) ($res['status'] ?? 'pending'));
        $shop    = (new ShopModel())->find((int) ($payout['shop_id'] ?? 0));
        $ownerId = (int) ($shop['owner_id'] ?? 0);

        if ($status === 'succeeded' && $payout['status'] !== 'completed') {
            $payoutModel->update($withdrawalId, [
                'status'          => 'completed',
                'transfer_status' => 'succeeded',
                'completed_at'    => date('Y-m-d H:i:s'),
                'failure_reason'  => null,
            ]);

            if ($ownerId > 0) {
                (new NotificationModel())->create(
                    $ownerId,
                    'payout',
                    'Payout Completed',
                    'Your GCash payout #' . ($payout['reference_number'] ?? $withdrawalId) . ' has been successfully delivered.',
                    '/tenant/withdrawals'
                );
            }

            return redirect()->back()->with('success', 'PayMongo transfer verified: Succeeded. Withdrawal marked as Completed.');
        }

        if ($status === 'failed' && $payout['status'] !== 'failed') {
            $payoutModel->update($withdrawalId, [
                'status'          => 'failed',
                'transfer_status' => 'failed',
                'failure_reason'  => 'Transfer marked failed by gateway.',
            ]);

            if ($ownerId > 0) {
                (new NotificationModel())->create(
                    $ownerId,
                    'payout',
                    'Payout Failed',
                    'Your GCash payout #' . ($payout['reference_number'] ?? $withdrawalId) . ' failed. Funds have been returned to your available balance.',
                    '/tenant/withdrawals'
                );
            }

            return redirect()->back()->with('error', 'PayMongo transfer returned Failed status. Available balance restored.');
        }

        return redirect()->back()->with('success', 'PayMongo transfer status is currently: ' . strtoupper($status));
    }

    /**
     * Webhook endpoint for PayMongo transfer events (transfer.outward.successful, transfer.outward.failed).
     */
    public function paymongoTransferWebhook()
    {
        $payload = (string) $this->request->getBody();
        $signatureHeader = $this->request->getHeaderLine('Paymongo-Signature');

        if (empty($signatureHeader)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing signature header']);
        }

        $webhookSecret = (string) (env('PAYMONGO_WEBHOOK_SECRET') ?: (getenv('PAYMONGO_WEBHOOK_SECRET') ?: ($_ENV['PAYMONGO_WEBHOOK_SECRET'] ?? '')));
        if (empty($webhookSecret) && (defined('ENVIRONMENT') && ENVIRONMENT === 'testing' || getenv('CI_ENVIRONMENT') === 'testing')) {
            $webhookSecret = 'test_secret_key_123';
        }
        if (empty($webhookSecret)) {
            log_message('error', 'PAYMONGO_WEBHOOK_SECRET is not configured.');
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Webhook secret not configured']);
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $pair) {
            $pairParts = explode('=', trim($pair), 2);
            if (count($pairParts) === 2) {
                $parts[$pairParts[0]] = $pairParts[1];
            }
        }

        if (empty($parts['t'])) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid signature header']);
        }

        $timestamp = $parts['t'];
        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $webhookSecret);

        $signatures = [];
        if (!empty($parts['te'])) $signatures[] = $parts['te'];
        if (!empty($parts['li'])) $signatures[] = $parts['li'];

        $isValid = false;
        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid signature']);
        }

        $data = json_decode($payload, true);
        if (!$data) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid JSON payload']);
        }

        $eventId      = (string) ($data['data']['id'] ?? '');
        $eventType    = (string) ($data['data']['attributes']['type'] ?? '');
        $transferData = $data['data']['attributes']['data'] ?? [];
        $transferId   = (string) ($transferData['id'] ?? '');
        $trStatus     = strtolower((string) ($transferData['attributes']['status'] ?? ($transferData['status'] ?? '')));

        if (empty($transferId) && empty($transferData)) {
            return $this->response->setJSON(['status' => 'ignored', 'reason' => 'no_transfer_data']);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('payout_requests');

        // Look up by transfer ID or reference
        $payout = $builder->where('paymongo_transfer_id', $transferId)->get()->getRowArray();
        if (!$payout && !empty($transferData['attributes']['metadata']['withdrawal_id'])) {
            $payout = $builder->where('id', (int) $transferData['attributes']['metadata']['withdrawal_id'])->get()->getRowArray();
        }

        if (!$payout) {
            return $this->response->setJSON(['status' => 'ignored', 'reason' => 'payout_not_found']);
        }

        // Idempotency check: ignore if already processed this event
        if (!empty($payout['last_webhook_event_id']) && $payout['last_webhook_event_id'] === $eventId) {
            return $this->response->setJSON(['status' => 'already_processed']);
        }

        $shop    = (new ShopModel())->find((int) ($payout['shop_id'] ?? 0));
        $ownerId = (int) ($shop['owner_id'] ?? 0);

        if ($eventType === 'transfer.outward.successful' || $trStatus === 'succeeded') {
            if ($payout['status'] !== 'completed') {
                $builder->where('id', $payout['id'])->update([
                    'status'                => 'completed',
                    'transfer_status'       => 'succeeded',
                    'completed_at'          => date('Y-m-d H:i:s'),
                    'last_webhook_event_id' => $eventId,
                    'failure_reason'        => null,
                ]);

                if ($ownerId > 0) {
                    (new NotificationModel())->create(
                        $ownerId,
                        'payout',
                        'Payout Completed',
                        'Your GCash payout #' . ($payout['reference_number'] ?? $payout['id']) . ' has been completed via PayMongo.',
                        '/tenant/withdrawals'
                    );
                }
            }
        } elseif ($eventType === 'transfer.outward.failed' || $trStatus === 'failed') {
            if ($payout['status'] !== 'failed') {
                $failureReason = $transferData['attributes']['failure_reason'] ?? 'Gateway transfer failed.';
                $builder->where('id', $payout['id'])->update([
                    'status'                => 'failed',
                    'transfer_status'       => 'failed',
                    'failure_reason'        => mb_substr((string) $failureReason, 0, 500),
                    'last_webhook_event_id' => $eventId,
                ]);

                if ($ownerId > 0) {
                    (new NotificationModel())->create(
                        $ownerId,
                        'payout',
                        'Payout Failed',
                        'Your GCash payout #' . ($payout['reference_number'] ?? $payout['id']) . ' could not be processed. Funds were restored to your available balance.',
                        '/tenant/withdrawals'
                    );
                }
            }
        }

        return $this->response->setJSON(['status' => 'success']);
    }

    public function approvePayout()
    {
        return $this->processPayout();
    }

    public function rejectPayout()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $withdrawalId = (int) ($this->request->getPost('withdrawal_id') ?: $this->request->getPost('payout_id'));
        $payoutModel  = new PayoutModel();
        $payout       = $payoutModel->find($withdrawalId);

        if (!$payout) {
            return redirect()->back()->with('error', 'Payout request not found.');
        }

        $reason = trim((string) ($this->request->getPost('rejection_reason') ?: $this->request->getPost('reason') ?: 'Rejected by Admin'));

        $payoutModel->update($withdrawalId, [
            'status'         => 'rejected',
            'failure_reason' => $reason,
        ]);

        (new AuditLogModel())->log(
            (int) session()->get('user_id'),
            'admin',
            'Rejected Withdrawal Request',
            'payout_request',
            'failed',
            $withdrawalId
        );

        $shop = (new ShopModel())->find((int) ($payout['shop_id'] ?? 0));
        if ($shop && !empty($shop['owner_id'])) {
            (new NotificationModel())->create(
                (int) $shop['owner_id'],
                'payout',
                'Payout Rejected',
                'Your payout request #' . ($payout['reference_number'] ?? $withdrawalId) . ' (₱' . number_format((float) $payout['amount'], 2) . ') was rejected.',
                '/tenant/withdrawals'
            );
        }

        return redirect()->back()->with('success', 'Payout rejected. Available balance restored.');
    }

    /**
     * Update payout status with a dropdown selection.
     * Allowed: processing (In Progress), completed, failed (Rejected).
     */
    public function updatePayoutStatus()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $withdrawalId = (int) $this->request->getPost('withdrawal_id');
        $status       = trim((string) $this->request->getPost('status'));

        if ($status === 'processing') {
            return $this->processPayout();
        }

        if ($status === 'failed') {
            return $this->rejectPayout();
        }

        return redirect()->back()->with('error', 'Please use the Process and Send to GCash workflow actions.');
    }

    /**
     * Update the platform withdrawal deduction percentage.
     */
    public function updateDeductionPercent()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $percent = (float) $this->request->getPost('deduction_percent');
        $siteContentModel = new SiteContentModel();
        $siteContentModel->setPlatformDeductionPercent($percent);

        (new AuditLogModel())->log(
            (int) session()->get('user_id'),
            'admin',
            'Updated Platform Deduction Fee',
            "Set platform withdrawal deduction percentage to {$percent}%",
            'success'
        );

        return redirect()->back()->with('success', "Platform withdrawal deduction set to " . number_format($percent, 2) . "%.");
    }

    public function warnCompliance()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $shopId = (int) $this->request->getPost('shop_id');
        $reportId = (int) $this->request->getPost('report_id');
        $warningMsg = trim((string) $this->request->getPost('warning_message'));

        if (empty($warningMsg)) {
            $warningMsg = 'Your shop has received a formal warning regarding compliance and service policy standards. Please review your store operations immediately.';
        }

        $db = \Config\Database::connect();
        if ($reportId > 0) {
            $db->table('compliance_reports')
                ->where('id', $reportId)
                ->set(['status' => 'flagged'])
                ->update();
        } else {
            $db->table('compliance_reports')
                ->where('reported_shop_id', $shopId)
                ->where('status !=', 'resolved')
                ->set(['status' => 'flagged'])
                ->update();
        }

        $shop = (new ShopModel())->find($shopId);
        if ($shop && !empty($shop['owner_id'])) {
            (new NotificationModel())->create(
                (int) $shop['owner_id'],
                'compliance_warning',
                '⚠️ Formal Compliance Warning Issued',
                $warningMsg,
                '/tenant/dashboard'
            );
        }

        return redirect()->back()->with('success', 'Formal compliance warning issued to merchant.');
    }

    public function suspendCompliance()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $shopId = (int) $this->request->getPost('shop_id');
        $reason = trim((string) $this->request->getPost('suspension_reason'));
        if (empty($reason)) {
            $reason = 'Shop suspended due to repeated or severe compliance policy violations.';
        }

        $shopModel = new ShopModel();
        $shop = $shopModel->find($shopId);
        if (!$shop) {
            return redirect()->back()->with('error', 'Shop not found.');
        }

        $shopModel->update($shopId, [
            'status' => 'suspended',
            'is_active' => 0
        ]);

        $db = \Config\Database::connect();
        $db->table('compliance_reports')
            ->where('reported_shop_id', $shopId)
            ->where('status !=', 'resolved')
            ->set(['status' => 'under_review'])
            ->update();

        if (!empty($shop['owner_id'])) {
            (new NotificationModel())->create(
                (int) $shop['owner_id'],
                'compliance_suspension',
                '🚫 Shop Suspended for Compliance Violations',
                'Your shop "' . ($shop['shop_name'] ?? 'Shop') . '" has been deactivated/suspended: ' . $reason,
                '/tenant/dashboard'
            );
        }

        return redirect()->back()->with('success', 'Shop suspended and deactivated successfully.');
    }

    public function resolveCompliance()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $shopId   = (int) $this->request->getPost('shop_id');
        $reportId = (int) $this->request->getPost('report_id');
        $db       = \Config\Database::connect();

        if ($reportId > 0) {
            $db->table('compliance_reports')
                ->where('id', $reportId)
                ->set(['status' => 'resolved', 'resolved_at' => date('Y-m-d H:i:s')])
                ->update();
        }

        if ($shopId > 0) {
            $db->table('compliance_reports')
                ->where('reported_shop_id', $shopId)
                ->where('status !=', 'resolved')
                ->set(['status' => 'resolved', 'resolved_at' => date('Y-m-d H:i:s')])
                ->update();

            $shop = (new ShopModel())->find($shopId);
            if ($shop && !empty($shop['owner_id'])) {
                (new NotificationModel())->create(
                    (int) $shop['owner_id'],
                    'compliance',
                    'Compliance Issues Resolved',
                    'The compliance reports concerning your shop "' . ($shop['shop_name'] ?? 'Shop') . '" have been resolved by administration.',
                    '/tenant/dashboard'
                );
            }
        }

        return redirect()->back()->with('success', 'Compliance issue resolved.');
    }
}
