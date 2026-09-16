<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Models\ProductImageModel;
use App\Models\ProductVariantModel;
use App\Models\ShopModel;
use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\PrintingRequestModel;
use App\Models\FavoriteShopModel;
use App\Models\ShippingAddressModel;
use App\Models\UserModel;
use App\Models\SiteContentModel;
use App\Models\ReviewModel;
use App\Models\ShopBusinessHourModel;

class Customer extends BaseController
{
    public function categories()
    {
        $categoryModel = new CategoryModel();

        $page      = max(1, (int) $this->request->getGet('page'));
        $perPage   = 8;

        $trending = $categoryModel->getTrendingCategories(3);

        $result = $categoryModel->getCategoriesPaginated($perPage, $page);

        $pager = $result['pager'];
        $total = $pager ? (int) $pager->getTotal() : count($result['categories']);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $result = $categoryModel->getCategoriesPaginated($perPage, $page);
        }

        return view('customer/categories', [
            'title'           => 'Explore Categories',
            'categories'      => $result['categories'],
            'pager'           => $result['pager'],
            'totalPages'      => $totalPages,
            'currentPage'     => $page,
            'perPage'         => $perPage,
            'totalCategories' => $total,
            'trending'        => $trending,
        ]);
    }

    public function category($slug)
    {
        $categoryModel = new CategoryModel();
        $productModel  = new ProductModel();

        $category = $categoryModel->where('slug', $slug)->first();

        $page     = max(1, (int) $this->request->getGet('page'));
        $perPage  = 32;

        if (!$category) {
            $result = ['products' => [], 'pager' => null];
        } else {
            $result = $productModel->getGlobalProductsPaginated((int) $category['id'], null, $perPage, $page);
        }

        $pager = $result['pager'];
        $total = $pager ? (int) $pager->getTotal() : count($result['products']);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($category && $page > $totalPages) {
            $page = $totalPages;
            $result = $productModel->getGlobalProductsPaginated((int) $category['id'], null, $perPage, $page);
        }

        return view('customer/category_products', [
            'title'          => $category ? $category['name'] : 'Category Products',
            'category'       => $category,
            'products'       => $result['products'],
            'pager'          => $result['pager'],
            'totalPages'     => $totalPages,
            'currentPage'    => $page,
            'perPage'        => $perPage,
            'totalProducts'  => $total,
        ]);
    }

    public function shops()
    {
        $shopModel = new ShopModel();

        $search   = trim((string) $this->request->getGet('q'));
        $category = $this->request->getGet('category');
        $category = in_array($category, ['printing', 'enterprise'], true) ? $category : null;
        $sort     = $this->request->getGet('sort');
        $sort     = in_array($sort, ['top-rated', 'most-recent'], true) ? $sort : 'top-rated';
        $page     = max(1, (int) $this->request->getGet('page'));
        $perPage  = 12;

        $result = $shopModel->getAllShopsPaginated($search !== '' ? $search : null, $category, $sort, $perPage, $page);

        $pager = $result['pager'];
        $total = $pager ? (int) $pager->getTotal() : count($result['shops']);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $result = $shopModel->getAllShopsPaginated($search !== '' ? $search : null, $category, $sort, $perPage, $page);
        }

        return view('customer/shops', [
            'title'       => 'All Shops',
            'shops'       => $result['shops'],
            'pager'       => $result['pager'],
            'totalPages'  => $totalPages,
            'currentPage' => $page,
            'perPage'     => $perPage,
            'totalShops'  => $total,
            'searchQuery' => $search,
            'category'    => $category,
            'sort'        => $sort,
        ]);
    }

    public function shop($slug)
    {
        $shopModel    = new ShopModel();
        $productModel = new ProductModel();

        $shop = $shopModel->where('slug', $slug)->first();
        if (!$shop) {
            return redirect()->to('/shops');
        }

        $search   = trim((string) $this->request->getGet('q'));
        $page     = max(1, (int) $this->request->getGet('page'));
        $perPage  = 6;

        $result = $productModel->getProductsWithDetailsPaginated($shop['id'], $search !== '' ? $search : null, $perPage, $page);

        $pager = $result['pager'];
        $total = $pager ? (int) $pager->getTotal() : count($result['products']);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $result = $productModel->getProductsWithDetailsPaginated($shop['id'], $search !== '' ? $search : null, $perPage, $page);
        }

        // Shop reviews
        $reviewModel = new ReviewModel();
        $shopReviews     = $reviewModel->getShopReviews($shop['id'], 10);
        $shopReviewCount = $reviewModel->countShopReviews($shop['id']);
        $businessHours   = (new ShopBusinessHourModel())->getGroupedForShop((int) $shop['id']);

        $userShopReview = null;
        $session = session();
        if ($session->get('isLoggedIn')) {
            $userId = $session->get('user_id');
            $userShopReview = $reviewModel->getUserReview($userId, null, $shop['id']);
        }

        $paperSizes       = (new \App\Models\ShopPaperSizeSettingModel())->getForShop((int) $shop['id']);
        $printingSettings = (new \App\Models\ShopPrintingSettingModel())->getForShop((int) $shop['id']);

        return view('customer/shop_storefront', [
            'shop'             => $shop,
            'products'         => $result['products'],
            'pager'            => $result['pager'],
            'totalPages'       => $totalPages,
            'currentPage'      => $page,
            'perPage'          => $perPage,
            'totalProducts'    => $total,
            'searchQuery'      => $search,
            'shopReviews'      => $shopReviews,
            'shopReviewCount'  => $shopReviewCount,
            'userShopReview'   => $userShopReview,
            'businessHours'    => $businessHours,
            'paperSizes'       => $paperSizes,
            'printingSettings' => $printingSettings,
        ]);
    }

    public function product($id)
    {
        $productModel = new ProductModel();
        $product      = $productModel->getProductWithDetails((int) $id);

        if (!$product) {
            return redirect()->to('/');
        }

        $relatedProducts = $productModel->getRelatedProducts((int) $product['category_id'], (int) $product['id'], 12);

        $productImageModel = new ProductImageModel();
        $productImages = $productImageModel->where('product_id', $product['id'])
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        $variantModel = new ProductVariantModel();
        $variants = $variantModel->where('product_id', $product['id'])
            ->orderBy('id', 'ASC')
            ->findAll();

        // Reviews
        $reviewModel = new ReviewModel();
        $reviews     = $reviewModel->getProductReviews($id, 10);
        $reviewCount = $reviewModel->countProductReviews($id);

        $userReview = null;
        $session = session();
        if ($session->get('isLoggedIn')) {
            $userId = $session->get('user_id');
            $userReview = $reviewModel->getUserReview($userId, $id);
        }

        return view('customer/product_detail', [
            'product'         => $product,
            'relatedProducts' => $relatedProducts,
            'productImages'   => $productImages,
            'variants'        => $variants,
            'reviews'         => $reviews,
            'reviewCount'     => $reviewCount,
            'userReview'      => $userReview,
        ]);
    }

    public function printingServices()
    {
        $shopModel = new ShopModel();

        $search = trim((string) $this->request->getGet('q'));
        $sort   = $this->request->getGet('sort');
        $sort   = in_array($sort, ['top-rated', 'most-recent'], true) ? $sort : 'top-rated';
        $page   = max(1, (int) $this->request->getGet('page'));
        $perPage = 8;

        $result = $shopModel->getPrintingShopsPaginated($search !== '' ? $search : null, $sort, $perPage, $page);

        $pager = $result['pager'];
        $total = $pager ? (int) $pager->getTotal() : count($result['shops']);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $result = $shopModel->getPrintingShopsPaginated($search !== '' ? $search : null, $sort, $perPage, $page);
        }

        $siteContents = [];
        try {
            $scm = new SiteContentModel();
            $siteContents = $scm->getContentMap('printing_services');
        } catch (\Throwable $e) { $siteContents = []; }

        return view('customer/printing_services', [
            'title'        => 'Printing Services',
            'shops'        => $result['shops'],
            'pager'        => $result['pager'],
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'perPage'      => $perPage,
            'totalShops'   => $total,
            'searchQuery'  => $search,
            'sort'         => $sort,
            'siteContents' => $siteContents,
        ]);
    }

    public function orders()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $orderModel = new OrderModel();
        $orders     = $orderModel->getOrdersByCustomer($userId);

        $orderItemModel    = new OrderItemModel();
        $productImageModel = new ProductImageModel();

        $productIds = [];
        foreach ($orders as &$order) {
            $order['items'] = $orderItemModel->where('order_id', $order['id'])->findAll();
            foreach ($order['items'] as $item) {
                $productIds[(int) $item['product_id']] = true;
            }
        }
        unset($order);

        $productModel = new ProductModel();
        $imageByProduct = [];
        $productNames = [];
        if (!empty($productIds)) {
            $imageRows = $productImageModel->builder()
                ->whereIn('product_id', array_keys($productIds))
                ->orderBy('is_primary', 'DESC')
                ->orderBy('sort_order', 'ASC')
                ->get()->getResultArray();

            foreach ($imageRows as $img) {
                $pid = (int) $img['product_id'];
                if (!isset($imageByProduct[$pid])) {
                    $imageByProduct[$pid] = $img['image_url'];
                }
            }

            $prodRows = $productModel->builder()
                ->select('id, name')
                ->whereIn('id', array_keys($productIds))
                ->get()->getResultArray();
            foreach ($prodRows as $pr) {
                $productNames[(int) $pr['id']] = $pr['name'];
            }
        }

        foreach ($orders as &$order) {
            foreach ($order['items'] as &$item) {
                $pid = (int) ($item['product_id'] ?? 0);
                $item['image_url'] = $imageByProduct[$pid] ?? '';
                if (empty($item['product_name']) && isset($productNames[$pid])) {
                    $item['product_name'] = $productNames[$pid];
                }
            }
            unset($item);
        }
        unset($order);

        return view('customer/orders', [
            'orders' => $orders,
        ]);
    }

    /**
     * Dedicated live tracking page for Doorstep Delivery orders.
     */
    public function trackOrder($orderRef = null)
    {
        $controller = new CustomerOrderController();
        $controller->initController($this->request, $this->response, $this->logger);
        return $controller->trackOrder($orderRef);
    }

    /**
     * Cancel an active order by customer.
     * Validates customer ownership, restricts cancellation to pending/processing only,
     * restores inventory quantities (including variants), and sends notification.
     */
    public function cancelOrder(?int $orderId = null)
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized.']);
            }
            return redirect()->to('/login');
        }

        $orderId = $orderId ?? (int) $this->request->getPost('order_id') ?? (int) $this->request->getPost('id');
        if (!$orderId) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid order ID.']);
            }
            return redirect()->back()->with('error', 'Invalid order ID.');
        }

        $orderModel = new OrderModel();
        $order      = $orderModel->find($orderId);

        if (!$order || (int) $order['customer_id'] !== (int) $userId) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Order not found.']);
            }
            return redirect()->back()->with('error', 'Order not found.');
        }

        $currentStatus = strtolower(trim((string) $order['status']));
        if (!in_array($currentStatus, ['pending', 'processing'], true)) {
            $msg = 'This order is already prepared, shipped, or finalized and can no longer be cancelled.';
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'error' => $msg]);
            }
            return redirect()->back()->with('error', $msg);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Update order status to cancelled
        $now = date('Y-m-d H:i:s');
        $orderModel->update($orderId, [
            'status'        => 'cancelled',
            'cancelled_at'  => $now,
            'cancel_reason' => 'Cancelled by customer',
        ]);

        // 2. Restore inventory stock quantities
        $orderItemModel = new OrderItemModel();
        $items = $orderItemModel->where('order_id', $orderId)->findAll();
        foreach ($items as $item) {
            $qty = (int) ($item['quantity'] ?? 1);
            $pId = (int) ($item['product_id'] ?? 0);
            $vId = (int) ($item['variant_id'] ?? 0);

            if ($pId > 0 && $qty > 0) {
                // Restore product total stock
                $db->table('products')
                    ->where('id', $pId)
                    ->set('stock_quantity', 'stock_quantity + ' . $qty, false)
                    ->update();

                // Restore variant stock if item has variant
                if ($vId > 0) {
                    $db->table('product_variants')
                        ->where('id', $vId)
                        ->set('stock_quantity', 'stock_quantity + ' . $qty, false)
                        ->update();
                }
            }
        }

        // 3. Notify shop owner
        $shop = (new \App\Models\ShopModel())->find($order['shop_id']);
        if ($shop && !empty($shop['owner_id'])) {
            (new \App\Models\NotificationModel())->create(
                (int) $shop['owner_id'],
                'order_cancelled',
                "Order #{$order['order_number']} Cancelled",
                "Customer cancelled Order #{$order['order_number']}.",
                '/tenant/orders'
            );
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => 'Database error while cancelling order.']);
            }
            return redirect()->back()->with('error', 'Database error while cancelling order.');
        }

        if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
            return $this->response->setJSON([
                'success'      => true,
                'message'      => "Order #{$order['order_number']} was cancelled successfully.",
                'order_id'     => $orderId,
                'order_number' => $order['order_number'],
                'status'       => 'cancelled',
                'cancelled_at' => $now,
            ]);
        }

        return redirect()->back()->with('success', "Order #{$order['order_number']} has been cancelled.");
    }

    public function printingRequests()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $prModel  = new PrintingRequestModel();
        $requests = $prModel->getRequestsByCustomer($userId);

        return view('customer/printing_requests', [
            'requests' => $requests,
        ]);
    }

    public function favorites()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $favModel = new FavoriteShopModel();
        $shops    = $favModel->getUserFavoriteShops($userId);

        return view('customer/favorites', [
            'shops' => $shops,
        ]);
    }

    public function addresses()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $addressModel = new ShippingAddressModel();
        $addresses    = $addressModel->where('user_id', $userId)->findAll();

        return view('customer/addresses', [
            'addresses' => $addresses,
        ]);
    }

    public function saveAddress()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $addressId = (int) $this->request->getPost('address_id');

        $validBarangays = [
            'Bentung', 'Cannery Site', 'Crossing Palkan', 'Glamang', 'Kinilis',
            'Klinan 6', 'Koronadal Proper', 'Lam-Caliaf', 'Landan', 'Lumakil',
            'Maligo', 'Palkan', 'Poblacion', 'Polo', 'Pula Bato', 'Rubber',
            'Silway 7', 'Silway 8', 'Sulit', 'Sumbakil', 'Upper Klinan',
            'Pagalungan', 'Magsaysay'
        ];

        $barangay = trim((string) ($this->request->getPost('barangay') ?: $this->request->getPost('address_line2')));

        // Scoped strictly to Polomolok, South Cotabato, 9504, Philippines
        $data = [
            'label'          => trim((string) $this->request->getPost('label')),
            'recipient_name' => trim((string) $this->request->getPost('recipient_name')),
            'phone'          => trim((string) $this->request->getPost('phone')),
            'address_line1'  => trim((string) $this->request->getPost('address_line1')),
            'address_line2'  => $barangay,
            'city'           => 'Polomolok',
            'province'       => 'South Cotabato',
            'postal_code'    => '9504',
            'country'        => 'Philippines',
        ];

        $errors = [];
        if ($data['label'] === '') {
            $errors[] = 'Address label is required.';
        }
        if ($data['recipient_name'] === '') {
            $errors[] = 'Recipient name is required.';
        }
        if ($data['address_line1'] === '') {
            $errors[] = 'Address line 1 (Street / House No.) is required.';
        }
        if ($barangay === '' || !in_array($barangay, $validBarangays, true)) {
            $errors[] = 'Please select a valid official barangay of Polomolok.';
        }

        if ($errors) {
            session()->setFlashdata('error', implode(' ', $errors));
            return redirect()->back();
        }

        $addressModel = new ShippingAddressModel();

        if ($addressId > 0) {
            $row = $addressModel->find($addressId);
            if (!$row || (int) $row['user_id'] !== (int) $userId) {
                session()->setFlashdata('error', 'Address not found.');
                return redirect()->back();
            }
            $addressModel->update($addressId, $data);
            session()->setFlashdata('success', 'Address updated successfully.');
            return redirect()->back();
        }

        $isDefault = $this->request->getPost('is_default') === '1';
        if ($isDefault) {
            $addressModel->where('user_id', $userId)->update(null, ['is_default' => 0]);
        } else {
            $count = $addressModel->where('user_id', $userId)->countAllResults();
            if ($count === 0) {
                $isDefault = true;
            }
        }

        $data['user_id']    = $userId;
        $data['is_default'] = $isDefault ? 1 : 0;
        $addressModel->insert($data);

        session()->setFlashdata('success', 'Address added successfully.');
        return redirect()->back();
    }

    public function deleteAddress()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $addressId     = (int) $this->request->getPost('address_id');
        $addressModel  = new ShippingAddressModel();
        $row           = $addressModel->find($addressId);

        if (!$row || (int) $row['user_id'] !== (int) $userId) {
            session()->setFlashdata('error', 'Address not found.');
            return redirect()->back();
        }

        $addressModel->delete($addressId);
        session()->setFlashdata('success', 'Address deleted successfully.');
        return redirect()->back();
    }

    public function setDefaultAddress()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $addressId    = (int) $this->request->getPost('address_id');
        $addressModel = new ShippingAddressModel();
        $row          = $addressModel->find($addressId);

        if (!$row || (int) $row['user_id'] !== (int) $userId) {
            session()->setFlashdata('error', 'Address not found.');
            return redirect()->back();
        }

        $addressModel->where('user_id', $userId)->update(null, ['is_default' => 0]);
        $addressModel->update($addressId, ['is_default' => 1]);

        session()->setFlashdata('success', 'Default address updated.');
        return redirect()->back();
    }

    public function unfavoriteShop()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $shopId = (int) $this->request->getPost('shop_id');
        if ($shopId <= 0) {
            session()->setFlashdata('error', 'Invalid shop.');
            return redirect()->back();
        }

        $favModel = new FavoriteShopModel();
        $row      = $favModel->where('user_id', $userId)->where('shop_id', $shopId)->first();

        if (!$row) {
            session()->setFlashdata('error', 'Favorite not found.');
            return redirect()->back();
        }

        $favModel->delete($row['id']);
        session()->setFlashdata('success', 'Shop removed from favorites.');
        return redirect()->back();
    }

    public function profile()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $userModel = new UserModel();
        $user      = $userModel->find($userId);

        $orderModel = new OrderModel();
        $orders     = $orderModel->getOrdersByCustomer($userId);

        $prModel = new PrintingRequestModel();
        $requests = $prModel->getRequestsByCustomer($userId);

        $activePrintStatuses = ['new', 'in_production', 'ready_for_pickup', 'ready_for_delivery'];
        $activePrinting = 0;
        foreach ($requests as $req) {
            if (in_array($req['status'] ?? '', $activePrintStatuses, true)) {
                $activePrinting++;
            }
        }

        $recentActivity = [];
        foreach ($orders as $order) {
            $recentActivity[] = [
                'reference' => $order['order_number'] ?? ('ORD-' . $order['id']),
                'service'   => $order['shop_name'] ?? 'Purchase Order',
                'date'      => $order['placed_at'] ?? $order['created_at'] ?? '',
                'status'    => $order['status'] ?? '',
            ];
        }
        foreach ($requests as $req) {
            $recentActivity[] = [
                'reference' => $req['request_number'] ?? ('PR-' . $req['id']),
                'service'   => $req['file_name'] ?? 'Printing Request',
                'date'      => $req['created_at'] ?? '',
                'status'    => $req['status'] ?? '',
            ];
        }
        usort($recentActivity, function ($a, $b) {
            return strtotime($b['date'] ?? '') <=> strtotime($a['date'] ?? '');
        });
        $recentActivity = array_slice($recentActivity, 0, 5);

        return view('customer/profile', [
            'user'            => $user,
            'totalOrders'     => count($orders),
            'activePrinting'  => $activePrinting,
            'recentActivity'  => $recentActivity,
        ]);
    }

    public function submitPrintingRequest()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $shopId = (int) $this->request->getPost('shop_id');

        $shopModel = new ShopModel();
        $shop = $shopId > 0 ? $shopModel->find($shopId) : null;
        if (!$shop || $shop['status'] !== 'active' || empty($shop['offers_printing'])) {
            session()->setFlashdata('error', 'Invalid printing shop selected. Please choose a valid shop.');
            return redirect()->back();
        }

        $file = $this->request->getFile('document');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            session()->setFlashdata('error', 'Please attach a document before submitting your printing request.');
            return redirect()->back();
        }

        $documentType = strtolower(trim((string) $this->request->getPost('document_type'))) === 'docx' ? 'docx' : 'pdf';
        $docChangeType = null;
        $notes = trim((string) $this->request->getPost('notes'));
        $stagedRefPhotos = [];

        $ext = strtolower($file->getClientExtension());
        $uploadPath = WRITEPATH . 'uploads/printing';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        $fileName = $file->getRandomName();

        if ($documentType === 'docx') {
            if (!in_array($ext, ['docx', 'doc'], true)) {
                session()->setFlashdata('error', 'Only Word documents (.doc, .docx) are accepted when Word Document is selected.');
                return redirect()->back();
            }

            $pageCount = max(1, (int) $this->request->getPost('estimated_page_count'));
            $docChangeType = strtolower(trim((string) $this->request->getPost('doc_change_type'))) === 'has_changes' ? 'has_changes' : 'as_is';

            if ($docChangeType === 'has_changes') {
                if ($notes === '') {
                    session()->setFlashdata('error', 'Special instructions are required when requesting document changes or formatting.');
                    return redirect()->back();
                }

                // Handle reference photos upload (max 5MB each, image files only)
                $attachmentsUploadPath = FCPATH . 'uploads/printing_attachments/';
                if (!is_dir($attachmentsUploadPath)) {
                    mkdir($attachmentsUploadPath, 0755, true);
                }

                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $maxBytes = 5 * 1024 * 1024; // 5MB

                $refFiles = $this->request->getFileMultiple('reference_photos');
                if (!empty($refFiles)) {
                    foreach ($refFiles as $rf) {
                        if ($rf && $rf->isValid() && !$rf->hasMoved()) {
                            if (in_array($rf->getMimeType(), $allowedMimes, true) && $rf->getSize() <= $maxBytes) {
                                $refName = 'ref_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $rf->getClientExtension();
                                $rf->move($attachmentsUploadPath, $refName);
                                $stagedRefPhotos[] = [
                                    'file_name' => $rf->getClientName(),
                                    'file_path' => 'uploads/printing_attachments/' . $refName,
                                    'file_size' => $rf->getSize(),
                                ];
                            }
                        }
                    }
                }
            }

            $file->move($uploadPath, $fileName);
            $movedPath = $uploadPath . DIRECTORY_SEPARATOR . $fileName;
        } else {
            // PDF Document
            if ($ext !== 'pdf') {
                session()->setFlashdata('error', 'Only PDF documents are accepted for PDF printing.');
                return redirect()->back();
            }

            $file->move($uploadPath, $fileName);
            $movedPath = $uploadPath . DIRECTORY_SEPARATOR . $fileName;

            if (!is_valid_pdf($movedPath, $file->getClientName())) {
                @unlink($movedPath);
                session()->setFlashdata('error', 'The uploaded file is not a valid PDF document.');
                return redirect()->back();
            }

            $pageCount = count_pdf_pages($movedPath);
            if ($pageCount <= 0) {
                @unlink($movedPath);
                session()->setFlashdata('error', 'Could not determine the page count of the uploaded PDF.');
                return redirect()->back();
            }
        }

        $fileUrl = 'writable/uploads/printing/' . $fileName;

        $allowedPaperSizes = ['letter', 'legal', 'a4', 'a3', 'a5', 'b5', 'b4', 'a2', 'a1', 'a0'];
        $paperSize = strtolower(trim((string) $this->request->getPost('paper_size')));
        if (!in_array($paperSize, $allowedPaperSizes, true)) {
            $paperSize = 'letter';
        }

        $colorMode = strtolower((string) $this->request->getPost('color_mode')) === 'colored' ? 'color' : 'bw';
        $bindingMap = ['stapled' => 'staple', 'spiral' => 'spiral', 'none' => 'none'];
        $binding = $bindingMap[strtolower((string) $this->request->getPost('binding'))] ?? 'none';
        $copies = max(1, (int) $this->request->getPost('copies'));

        $fulfillment = $this->request->getPost('fulfillment_method') === 'delivery' ? 'delivery' : 'pickup';

        // Fetch dynamic pricing for shop
        $shopPaperSizes = (new \App\Models\ShopPaperSizeSettingModel())->getForShop($shopId);
        $shopSettings   = (new \App\Models\ShopPrintingSettingModel())->getForShop($shopId);

        $sizeSetting = $shopPaperSizes[$paperSize] ?? ($shopPaperSizes['letter'] ?? ['price_color' => 5.00, 'price_bw' => 2.00]);
        $basePerPage = ($colorMode === 'color') ? (float) $sizeSetting['price_color'] : (float) $sizeSetting['price_bw'];

        $bindingCost = match ($binding) {
            'staple' => (float) ($shopSettings['price_staple'] ?? 10.00),
            'spiral' => (float) ($shopSettings['price_spiral'] ?? 35.00),
            default  => 0.00,
        };

        $totalPrice = round((($pageCount * $basePerPage) + $bindingCost) * $copies, 2);
        $downPaymentPercent = (float) ($shopSettings['down_payment_percent'] ?? 50.00);
        $downPayment = round($totalPrice * ($downPaymentPercent / 100.00), 2);

        // Prepare pending printing request data (do NOT insert into database yet)
        $pendingPrinting = [
            'request_number'       => 'PR-' . date('Ymd') . '-' . rand(1000, 9999),
            'customer_id'          => $userId,
            'shop_id'              => $shopId,
            'file_name'            => $file->getClientName(),
            'file_url'             => $fileUrl,
            'document_type'        => $documentType,
            'doc_change_type'      => $docChangeType,
            'special_instructions' => $notes !== '' ? $notes : null,
            'paper_size'           => $paperSize,
            'color_mode'           => $colorMode,
            'copies'               => $copies,
            'page_count'           => $pageCount,
            'binding_option'       => $binding,
            'paper_stock'          => trim((string) $this->request->getPost('paper_stock')) ?: 'standard',
            'fulfillment_method'   => $fulfillment,
            'total_price'          => $totalPrice,
            'down_payment'         => $downPayment,
            'progress_percent'     => 0,
            'reference_photos'     => $stagedRefPhotos,
        ];

        // Initiate PayMongo GCash checkout session for down payment
        $token = bin2hex(random_bytes(16));
        $paymongo = service('paymongoService');
        $successUrl = base_url('printing/callback?token=' . $token);
        $cancelUrl  = base_url('printing/callback?cancel=1&token=' . $token);

        $res = $paymongo->createGcashCheckoutSession(
            $downPayment,
            round($downPaymentPercent) . '% Down Payment - ' . $file->getClientName(),
            $successUrl,
            $cancelUrl,
            [
                'type'    => 'printing_down_payment',
                'user_id' => (string)$userId,
                'token'   => $token,
            ]
        );

        if (!empty($res['success']) && !empty($res['checkout_url'])) {
            $pendingPrinting['session_id'] = $res['session_id'];
            $pendingPrinting['token']      = $token;
            session()->set('pending_printing_' . $userId, $pendingPrinting);
            session()->set('pending_printing_token_' . $token, $pendingPrinting);
            cache()->save('pending_printing_' . $res['session_id'], $pendingPrinting, 86400);
            cache()->save('pending_printing_token_' . $token, $pendingPrinting, 86400);

            return redirect()->to($res['checkout_url']);
        }

        return redirect()->back()->with('error', $res['error'] ?? 'Could not initialize GCash payment. Please try again.');
    }

    /**
     * PayMongo Callback verification for Printing Request GCash down payment.
     * Printing Request is ONLY created when payment is server-verified.
     */
    public function printingPaymentCallback()
    {
        $session = session();
        $userId  = $session->get('user_id');
        if (!$userId) {
            return redirect()->to('/login');
        }

        $isCancel = (bool) $this->request->getGet('cancel');
        if ($isCancel) {
            return redirect()->to('/customer/printing')->with('warning', 'GCash down payment checkout was cancelled. Printing request was not created.');
        }

        $sessionId = (string) ($this->request->getGet('session_id') ?? $this->request->getGet('checkout_session_id') ?? '');
        $token     = (string) $this->request->getGet('token');

        $pending = null;
        if ($token !== '') {
            $pending = session()->get('pending_printing_token_' . $token) ?? cache()->get('pending_printing_token_' . $token);
        }
        if (!$pending && $sessionId !== '') {
            $pending = cache()->get('pending_printing_' . $sessionId);
        }
        if (!$pending) {
            $pending = session()->get('pending_printing_' . $userId);
        }

        if (empty($pending)) {
            return redirect()->to('/customer/printing')->with('error', 'Printing checkout session not found or expired.');
        }

        if ($sessionId === '') {
            $sessionId = (string) ($pending['session_id'] ?? '');
        }
        if ($sessionId === '') {
            return redirect()->to('/customer/printing')->with('error', 'Missing PayMongo session ID.');
        }

        $paymongo = service('paymongoService');
        $paid = false; // Never default to true!
        $check = $paymongo->getCheckoutSession($sessionId);
        if (!empty($check['success']) && !empty($check['paid'])) {
            $paid = true;
        }

        if (!$paid) {
            return redirect()->to('/customer/printing')->with('error', '50% Down Payment was not completed or verified. Printing request was not created.');
        }

        // Server-verified successful payment -> NOW insert printing request and payment record under DB transaction
        $db = \Config\Database::connect();
        $db->transStart();

        $paymentModel = new \App\Models\PaymentModel();
        // Idempotency: prevent duplicate printing request/payment if callback runs multiple times
        $alreadyProcessed = $paymentModel->where('reference_number', $sessionId)->first();
        if ($alreadyProcessed) {
            $db->transComplete();
            return redirect()->to('/customer/printing')->with('info', 'Your printing request down payment has already been verified.');
        }

        $prModel = new PrintingRequestModel();
        $insertData = [
            'request_number'       => $pending['request_number'] ?? ('PR-' . date('Ymd') . '-' . rand(1000, 9999)),
            'customer_id'          => $userId,
            'shop_id'              => $pending['shop_id'],
            'file_name'            => $pending['file_name'],
            'file_url'             => $pending['file_url'],
            'document_type'        => $pending['document_type'] ?? 'pdf',
            'doc_change_type'      => $pending['doc_change_type'] ?? 'as_is',
            'special_instructions' => $pending['special_instructions'] ?? null,
            'paper_size'           => $pending['paper_size'],
            'color_mode'           => $pending['color_mode'],
            'copies'               => $pending['copies'],
            'page_count'           => $pending['page_count'],
            'binding_option'       => $pending['binding_option'],
            'paper_stock'          => $pending['paper_stock'],
            'fulfillment_method'   => $fulfillmentMethod = $pending['fulfillment_method'] ?? 'pickup',
            'total_price'          => $pending['total_price'],
            'down_payment'         => $pending['down_payment'],
            'status'               => 'Paid (Down Payment)',
            'progress_percent'     => 0,
        ];

        $prId = $prModel->insert($insertData);

        if (!empty($pending['reference_photos']) && is_array($pending['reference_photos'])) {
            $attachmentModel = new \App\Models\PrintingRequestAttachmentModel();
            foreach ($pending['reference_photos'] as $attachment) {
                $attPath = is_array($attachment) ? ($attachment['file_path'] ?? ($attachment['image_url'] ?? '')) : (string) $attachment;
                if ($attPath !== '') {
                    $attachmentModel->insert([
                        'printing_request_id' => $prId,
                        'image_url'           => $attPath,
                        'created_at'          => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        $paymentModel->insert([
            'payable_type'     => 'printing_request',
            'payable_id'       => $prId,
            'method'           => 'gcash',
            'amount'           => $pending['down_payment'],
            'reference_number' => $sessionId,
            'proof_image_url'  => null,
            'status'           => 'verified',
            'processed_at'     => date('Y-m-d H:i:s'),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        // Clean up pending session data
        session()->remove('pending_printing_' . $userId);
        if ($token !== '') {
            session()->remove('pending_printing_token_' . $token);
        }

        $shop = (new \App\Models\ShopModel())->find($pending['shop_id']);
        if ($shop && !empty($shop['owner_id'])) {
            $customerUser = (new \App\Models\UserModel())->find($userId);
            $cName = $customerUser ? trim(($customerUser['first_name'] ?? '') . ' ' . ($customerUser['last_name'] ?? '')) : 'A customer';
            if ($cName === '') $cName = 'A customer';
            $prTime = date('M d, Y h:i A');
            $fileName = !empty($pending['file_name']) ? $pending['file_name'] : 'Document.pdf';
            (new \App\Models\NotificationModel())->create(
                (int) $shop['owner_id'],
                'new_printing_request',
                "New Printing Request for {$fileName} (#{$pending['request_number']})",
                "Received printing request for {$fileName} (#{$pending['request_number']}) from {$cName} at {$prTime}.",
                '/tenant/printing'
            );
        }

        $db->transComplete();

        if ($db->transStatus() !== false) {
            return redirect()->to('/customer/printing')->with('success', '50% Down Payment verified via PayMongo GCash! Your printing request has been confirmed.');
        }

        return redirect()->to('/customer/printing')->with('error', 'A database error occurred while confirming your printing request.');
    }

    public function countPrintingPages()
    {
        if (!$this->request->isAJAX() && strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405)->setJSON([
                'success' => false,
                'error'   => 'Method not allowed.',
            ]);
        }

        $file = $this->request->getFile('document');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'No PDF file received.',
            ]);
        }

        if (strtolower($file->getClientExtension()) !== 'pdf') {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Only PDF documents are accepted.',
            ]);
        }

        $tmpPath = $file->getTempName();
        if (!is_valid_pdf($tmpPath, $file->getClientName())) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'The uploaded file is not a valid PDF document.',
            ]);
        }

        $pageCount = count_pdf_pages($tmpPath);
        if ($pageCount <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Could not determine the page count of the uploaded PDF.',
            ]);
        }

        return $this->response->setJSON([
            'success'   => true,
            'page_count'=> $pageCount,
            'file_name' => $file->getClientName(),
        ]);
    }

    public function cancelPrintingRequest()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $requestId = (int) $this->request->getPost('request_id');
        if ($requestId <= 0) {
            session()->setFlashdata('error', 'Invalid printing request.');
            return redirect()->back();
        }

        $prModel = new PrintingRequestModel();
        $row     = $prModel->find($requestId);

        if (!$row || (int) $row['customer_id'] !== (int) $userId) {
            session()->setFlashdata('error', 'Printing request not found.');
            return redirect()->back();
        }

        if (strtolower((string) $row['status']) !== 'new') {
            session()->setFlashdata('error', 'This printing request is already approved and cannot be cancelled.');
            return redirect()->back();
        }

        $prModel->update($requestId, ['status' => 'cancelled']);

        session()->setFlashdata('success', 'Printing request cancelled successfully.');
        return redirect()->back();
    }

    public function updateProfile()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $firstName = trim($this->request->getPost('first_name'));
        $lastName  = trim($this->request->getPost('last_name'));
        $email     = trim($this->request->getPost('email'));
        $phone     = trim($this->request->getPost('phone'));

        if ($firstName === '' || $lastName === '') {
            session()->setFlashdata('error', 'First name and last name are required.');
            return redirect()->back();
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            session()->setFlashdata('error', 'A valid email address is required.');
            return redirect()->back();
        }

        $userModel = new UserModel();
        $existing  = $userModel->findByEmail($email);
        if ($existing && (int) $existing['id'] !== (int) $userId) {
            session()->setFlashdata('error', 'That email address is already in use by another account.');
            return redirect()->back();
        }

        $current = $userModel->find($userId);
        $updates = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'phone'      => $phone,
        ];

        $file        = $this->request->getFile('profile_image');
        $removeImage = $this->request->getPost('remove_profile_image') === '1';

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array(strtolower($file->getClientExtension()), $allowed, true)) {
                session()->setFlashdata('error', 'Only JPG, PNG, WEBP or GIF images are allowed for your profile picture.');
                return redirect()->back();
            }
            if ($file->getSize() > 2097152) {
                session()->setFlashdata('error', 'Profile picture must be 2 MB or smaller.');
                return redirect()->back();
            }

            $uploadPath = ROOTPATH . 'public/uploads/profiles';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            $fileName = $file->getRandomName();
            $file->move($uploadPath, $fileName);
            $updates['profile_image_url'] = 'uploads/profiles/' . $fileName;

            if (!empty($current['profile_image_url']) && strpos($current['profile_image_url'], 'uploads/profiles/') === 0) {
                $oldPath = ROOTPATH . 'public/' . $current['profile_image_url'];
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $session->set('profile_image_url', $updates['profile_image_url']);
        } elseif ($removeImage) {
            if (!empty($current['profile_image_url']) && strpos($current['profile_image_url'], 'uploads/profiles/') === 0) {
                $oldPath = ROOTPATH . 'public/' . $current['profile_image_url'];
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $updates['profile_image_url'] = null;
            $session->set('profile_image_url', null);
        }

        $userModel->update($userId, $updates);

        $session->set([
            'user_name'  => $firstName . ' ' . $lastName,
            'user_email' => $email,
        ]);

        session()->setFlashdata('success', 'Profile updated successfully.');
        return redirect()->to('/customer/profile');
    }

    /**
     * Save or update a product review.
     * POST reviews/product/save
     */
    public function saveProductReview()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login')->with('error', 'You must be logged in to submit a review.');
        }

        $productId = (int) $this->request->getPost('product_id');
        $shopId    = (int) $this->request->getPost('shop_id');
        $rating    = (int) $this->request->getPost('rating');
        $comment   = trim((string) $this->request->getPost('comment'));

        if ($productId <= 0 || $shopId <= 0) {
            return redirect()->back()->with('error', 'Invalid product or shop.');
        }
        if ($rating < 1 || $rating > 5) {
            return redirect()->back()->with('error', 'Rating must be between 1 and 5 stars.');
        }
        if ($comment !== '' && mb_strlen($comment) > 2000) {
            return redirect()->back()->with('error', 'Review text is too long (max 2000 characters).');
        }

        try {
            $reviewService = service('reviewService');
            $reviewService->saveProductReview($userId, $productId, $shopId, $rating, $comment);

            session()->setFlashdata('success', 'Your review has been submitted!');
        } catch (\RuntimeException $e) {
            session()->setFlashdata('error', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Failed to save product review: ' . $e->getMessage());
            session()->setFlashdata('error', 'An unexpected error occurred. Please try again.');
        }

        return redirect()->back();
    }

    /**
     * Save or update a shop review.
     * POST reviews/shop/save
     */
    public function saveShopReview()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login')->with('error', 'You must be logged in to submit a review.');
        }

        $shopId  = (int) $this->request->getPost('shop_id');
        $rating  = (int) $this->request->getPost('rating');
        $comment = trim((string) $this->request->getPost('comment'));

        if ($shopId <= 0) {
            return redirect()->back()->with('error', 'Invalid shop.');
        }
        if ($rating < 1 || $rating > 5) {
            return redirect()->back()->with('error', 'Rating must be between 1 and 5 stars.');
        }
        if ($comment !== '' && mb_strlen($comment) > 2000) {
            return redirect()->back()->with('error', 'Review text is too long (max 2000 characters).');
        }

        try {
            $reviewService = service('reviewService');
            $reviewService->saveShopReview($this->getCurrentUserId(), $shopId, $rating, $comment);

            session()->setFlashdata('success', 'Your shop review has been submitted!');
        } catch (\RuntimeException $e) {
            session()->setFlashdata('error', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Failed to save shop review: ' . $e->getMessage());
            session()->setFlashdata('error', 'An unexpected error occurred. Please try again.');
        }

        return redirect()->back();
    }

    /**
     * Helper to get the current user ID, throwing if not logged in.
     */
    private function getCurrentUserId(): int
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            throw new \RuntimeException('You must be logged in to submit a review.');
        }
        return (int) $userId;
    }
}
