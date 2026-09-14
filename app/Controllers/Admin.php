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
        // Recent shops without financials
        $recentShops = array_slice($shops, 0, 5);

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
        ]);
    }

    public function tenants()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $shopModel = new ShopModel();
        $search    = trim((string)$this->request->getGet('q'));
        $status    = trim((string)$this->request->getGet('status'));
        $page      = max(1, (int) $this->request->getGet('page_tenants'));

        $result = $shopModel->getTenantsPaginated($search, $status, 15, $page, 'tenants');
        $shops  = $result['tenants'];

        $db = \Config\Database::connect();
        $activeCount  = $db->table('shops')->where('status','active')->countAllResults();
        $pendingCount = $db->table('shops')->where('status','pending')->countAllResults();

        return view('admin/tenants', [
            'tenants'      => $shops,
            'shops'        => $shops,
            'pager'        => $result['pager'],
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
        $page      = max(1, (int) $this->request->getGet('page_customers'));

        $result    = $userModel->getCustomersPaginated($search, $status, 15, $page, 'customers');
        $customers = $result['customers'];

        $db = \Config\Database::connect();
        $total = $db->table('users')->where('role','customer')->countAllResults();
        $activeToday = $db->table('users')->where('role','customer')->where('DATE(last_login_at) = CURDATE()', null, false)->countAllResults();
        // fallback if last_login_at null: use created_today
        $newThisWeek = $db->table('users')->where('role','customer')->where('created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))->countAllResults();

        return view('admin/customers', [
            'customers'     => $customers,
            'pager'         => $result['pager'],
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
        $page        = max(1, (int) $this->request->getGet('page_payments'));

        $result          = $payoutModel->getPaymentsPaginated($search, $status, 15, $page, 'payments');
        $paymentRequests = $result['payments'];

        $db = \Config\Database::connect();
        $pendingTotal = $db->table('payout_requests')->where('status','pending')->where('destination_method','gcash')->selectSum('amount','total')->get()->getRow()->total ?? 0;
        $completedTotal = $db->table('payout_requests')->where('status','completed')->where('destination_method','gcash')->selectSum('amount','total')->get()->getRow()->total ?? 0;
        $pendingCount = $db->table('payout_requests')->where('status','pending')->where('destination_method','gcash')->countAllResults();

        return view('admin/payments', [
            'payment_requests' => $paymentRequests,
            'payments'         => $paymentRequests,
            'pager'            => $result['pager'],
            'filters'          => ['q'=>$search,'status'=>$status],
            'pending_total'    => (float)$pendingTotal,
            'completed_total'  => (float)$completedTotal,
            'pending_count'    => (int)$pendingCount,
        ]);
    }

    public function compliance()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $complianceModel = new ComplianceModel();
        $search          = trim((string)$this->request->getGet('q'));
        $status          = trim((string)$this->request->getGet('status'));
        $page            = max(1, (int) $this->request->getGet('page_compliance'));

        $result  = $complianceModel->getCompliancePaginated($search, $status, 15, $page, 'compliance');
        $reports = $result['reports'];

        $db = \Config\Database::connect();
        $totalReports = $db->table('compliance_reports')->countAllResults();
        $pendingReviews = $db->table('compliance_reports')->whereIn('status',['pending','under_review','flagged'])->countAllResults();
        $resolvedCases = $db->table('compliance_reports')->where('status','resolved')->countAllResults();

        return view('admin/compliance', [
            'compliance_items' => $reports,
            'reports'          => $reports,
            'pager'            => $result['pager'],
            'filters'          => ['q'=>$search,'status'=>$status],
            'total_reports'    => $totalReports,
            'pending_reviews'  => $pendingReviews,
            'resolved_cases'   => $resolvedCases,
        ]);
    }

    public function tracking()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $search     = trim((string)$this->request->getGet('q'));
        $shopFilter = trim((string)$this->request->getGet('shop'));
        $status     = trim((string)$this->request->getGet('status'));
        $page       = max(1, (int) $this->request->getGet('page_tracking'));

        $deliveryModel = new DeliveryModel();
        $pins = $deliveryModel->getAllDeliveryPins($search, $shopFilter);

        // Polomolok filter: only keep pins with valid Polomolok coordinates or Polomolok destination
        $pins = array_values(array_filter($pins, function($p){
            $hasCoords = isset($p['current_lat']) && isset($p['current_lng']) && $p['current_lat'] !== null && $p['current_lng'] !== null;
            if ($hasCoords) {
                $lat=(float)$p['current_lat']; $lng=(float)$p['current_lng'];
                if (!DeliveryModel::isPolomolokCoordinate($lat,$lng)) return false;
            }
            // destination must mention Polomolok if no coords
            if (!$hasCoords && !empty($p['destination_address']) && stripos($p['destination_address'],'Polomolok')===false) {
                // keep if shop city is Polomolok — check via shops table? For now allow but flag
                // Allow through but map pin will be skipped
            }
            return true;
        }));

        $result     = $deliveryModel->getAdminTrackingPaginated($search, $shopFilter, $status, 20, $page, 'tracking');
        $deliveries = $result['deliveries'];

        // Shops for filter dropdown
        $db = \Config\Database::connect();
        $shops = $db->table('shops')->select('shop_name')->orderBy('shop_name','ASC')->get()->getResultArray();
        $activeCount = $db->table('deliveries')->whereIn('status',['ready_for_pickup','shipped','in_transit'])->countAllResults();

        return view('admin/tracking', [
            'live_deliveries' => $deliveries,
            'deliveries'      => $deliveries,
            'pager'           => $result['pager'],
            'pins'            => $pins,
            'shops'           => $shops,
            'filters'         => ['q'=>$search,'shop'=>$shopFilter,'status'=>$status],
            'active_count'    => $activeCount,
        ]);
    }

    public function auditLog()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $search = trim((string)$this->request->getGet('q'));
        $role   = trim((string)$this->request->getGet('role'));
        $status = trim((string)$this->request->getGet('status'));
        $page   = max(1, (int) $this->request->getGet('page_audit_log'));

        $auditLogModel = new AuditLogModel();
        $result = $auditLogModel->getAuditLogsPaginated($search, $role, $status, 25, $page, 'audit_log');
        $logs   = $result['logs'];

        $db = \Config\Database::connect();
        $total24h = $db->table('audit_logs')->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))->countAllResults();
        $critical = $db->table('audit_logs')->where('status','failed')->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))->countAllResults();
        $newAccounts = $db->table('audit_logs')->where('action','Created Account')->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))->countAllResults();

        return view('admin/audit_log', [
            'audit_logs'   => $logs,
            'logs'         => $logs,
            'pager'        => $result['pager'],
            'filters'      => ['q'=>$search,'role'=>$role,'status'=>$status],
            'total_24h'    => $total24h,
            'critical'     => $critical,
            'new_accounts' => $newAccounts,
        ]);
    }

    public function analytics()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $payoutModel = new PayoutModel();
        $db = \Config\Database::connect();
        $range = $this->request->getGet('range');
        $range = in_array($range, ['7','30','year'], true) ? $range : '30';

        $adminRevenueTotal = $payoutModel->getAdminRevenueTotal('completed');
        $adminRevenueChart = $payoutModel->getAdminRevenueChartData($range,'completed');
        $payoutCount = $db->table('payout_requests')->where('status','completed')->where('destination_method','gcash')->countAllResults();
        $avgFee = $payoutCount > 0 ? $adminRevenueTotal / $payoutCount : 0;
        $totalOrders = $db->table('orders')->countAllResults();
        $activeShops = $db->table('shops')->where('status','active')->countAllResults();
        $totalCustomers = $db->table('users')->where('role','customer')->countAllResults();

        // Top shops by order volume only — no revenue
        $topShops = $db->table('orders o')
            ->select('s.shop_name, COUNT(o.id) as total_orders')
            ->join('shops s', 's.id = o.shop_id', 'left')
            ->groupBy('o.shop_id')
            ->orderBy('total_orders','DESC')
            ->limit(5)
            ->get()->getResultArray();

        return view('admin/analytics', [
            'admin_revenue' => $adminRevenueTotal,
            'avg_fee'       => $avgFee,
            'payout_count'  => $payoutCount,
            'total_orders'  => $totalOrders,
            'active_shops'  => $activeShops,
            'total_customers'=> $totalCustomers,
            'top_shops'     => $topShops,
            'chart_labels'  => $adminRevenueChart['labels'],
            'chart_values'  => $adminRevenueChart['values'],
            'range'         => $range,
        ]);
    }

    public function analyticsData()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $this->response->setStatusCode(401)->setJSON(['success'=>false]);

        $range = (string)$this->request->getGet('range');
        $range = in_array($range, ['7','30','year'], true) ? $range : '30';
        $payoutModel = new PayoutModel();
        $chart = $payoutModel->getAdminRevenueChartData($range,'completed');
        return $this->response->setJSON([
            'success'=>true,
            'range'=>$range,
            'labels'=>$chart['labels'],
            'values'=>$chart['values'],
            'total'=>round(array_sum($chart['values']),2),
        ]);
    }

    public function content()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $model = new SiteContentModel();
        // Ensure table exists — if migration not run, create via db forge fallback
        $db = \Config\Database::connect();
        if (!$db->tableExists('site_contents')) {
            // Auto-create minimal table
            $db->query("CREATE TABLE IF NOT EXISTS site_contents (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, page VARCHAR(60) NOT NULL, content_key VARCHAR(100) NOT NULL, label VARCHAR(150) NULL, content_type ENUM('text','textarea','image') DEFAULT 'text', text_value TEXT NULL, image_url VARCHAR(500) NULL, sort_order INT DEFAULT 0, updated_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, UNIQUE KEY uq_page_key (page,content_key))");
            $db->table('site_contents')->ignore(true)->insertBatch([
                ['page'=>'home','content_key'=>'hero_badge','label'=>'Hero Badge','content_type'=>'text','text_value'=>'Seasonal Event','sort_order'=>1],
                ['page'=>'home','content_key'=>'hero_title','label'=>'Hero Title','content_type'=>'text','text_value'=>'The Ultimate Merchandise Selection','sort_order'=>2],
                ['page'=>'home','content_key'=>'hero_subtitle','label'=>'Hero Subtitle','content_type'=>'textarea','text_value'=>'Discover premium goods, exclusive deals, and top-tier printing services all in one place.','sort_order'=>3],
                ['page'=>'home','content_key'=>'hero_image','label'=>'Hero Image','content_type'=>'image','image_url'=>'https://images.unsplash.com/photo-1556742049-0a67daf64f42?auto=format&fit=crop&w=1440&q=80','sort_order'=>4],
            ]);
        }

        $grouped = $model->getAllGrouped();
        if (empty($grouped)) {
            $grouped = ['home'=>[]];
        }

        return view('admin/content', [
            'grouped' => $grouped,
            'pages'   => array_keys($grouped),
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
        if ($file->hasMoved()) return redirect()->back()->with('error','Upload failed.');
        $allowed=['jpg','jpeg','png','webp','gif'];
        if (!in_array(strtolower($file->getClientExtension()), $allowed, true)) {
            return redirect()->back()->with('error','Only JPG, PNG, WEBP, GIF allowed.');
        }
        if ($file->getSize() > 3*1024*1024) {
            return redirect()->back()->with('error','Image must be 3MB or smaller.');
        }
        $uploadPath = ROOTPATH.'public/uploads/cms';
        if (!is_dir($uploadPath)) mkdir($uploadPath,0777,true);
        $fileName=$file->getRandomName();
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

    public function approvePayout()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $withdrawalId = (int) $this->request->getPost('withdrawal_id');
        $payoutModel  = new PayoutModel();
        $payout       = $payoutModel->find($withdrawalId);

        if (!$payout) {
            return redirect()->back()->with('error', 'Payout request not found.');
        }
        if (($payout['destination_method'] ?? '') !== 'gcash') {
            return redirect()->back()->with('error', 'Only GCash payouts can be approved.');
        }

        // Ensure 3% fee is recorded (if previously 0, compute now)
        $fee = (float)($payout['fee'] ?? 0);
        if ($fee <= 0) {
            $fee = round((float)$payout['amount'] * 0.03, 2);
        }

        $payoutModel->update($withdrawalId, [
            'status'       => 'completed',
            'fee'          => $fee,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'GCash payout approved. 3% admin fee recorded.');
    }

    public function rejectPayout()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $withdrawalId = (int) $this->request->getPost('withdrawal_id');
        $payoutModel  = new PayoutModel();
        $payout       = $payoutModel->find($withdrawalId);

        if (!$payout) {
            return redirect()->back()->with('error', 'Payout request not found.');
        }

        $payoutModel->update($withdrawalId, ['status' => 'failed']);

        return redirect()->back()->with('success', 'Payout rejected.');
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

        $payoutModel = new PayoutModel();
        $payout      = $payoutModel->find($withdrawalId);

        if (!$payout) {
            return redirect()->back()->with('error', 'Payout request not found.');
        }

        $allowedStatuses = ['processing', 'completed', 'failed'];
        if (!in_array($status, $allowedStatuses, true)) {
            return redirect()->back()->with('error', 'Invalid status.');
        }

        $updateData = ['status' => $status];

        if ($status === 'completed') {
            $fee = (float) ($payout['fee'] ?? 0);
            if ($fee <= 0) {
                $fee = round((float) $payout['amount'] * 0.03, 2);
            }
            $updateData['fee'] = $fee;
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }

        $payoutModel->update($withdrawalId, $updateData);

        $statusLabel = match ($status) {
            'processing' => 'In Progress',
            'completed'  => 'Completed',
            'failed'     => 'Rejected',
            default      => $status,
        };

        return redirect()->back()->with('success', "Payout status updated to {$statusLabel}.");
    }

    public function resolveCompliance()
    {
        $auth = $this->checkAdminAuth();
        if ($auth !== true) return $auth;

        $shopId = (int) $this->request->getPost('shop_id');
        $db     = \Config\Database::connect();

        $db->table('compliance_reports')
            ->where('reported_shop_id', $shopId)
            ->where('status !=', 'resolved')
            ->set(['status' => 'resolved', 'resolved_at' => date('Y-m-d H:i:s')])
            ->update();

        return redirect()->back()->with('success', 'Compliance issues resolved.');
    }
}
