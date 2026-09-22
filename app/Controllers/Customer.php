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
use App\Models\NotificationModel;
use App\Models\DeliveryModel;
use App\Models\PaymentModel;
use App\Models\PrintingRequestAttachmentModel;
use App\Models\ShopPrintingSettingModel;
use App\Models\ShopPaperSizeSettingModel;

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

        $sort = trim((string) $this->request->getGet('sort'));
        $sort = in_array($sort, ['discovery', 'price_asc', 'price_desc', 'rating', 'newest', 'name_asc', 'name_desc'], true) ? $sort : null;
        $rotationInfo = ProductModel::getRotationInfo(3);

        if (!$category) {
            $result = ['products' => [], 'pager' => null];
        } else {
            try {
                $categoryModel->builder()->where('id', $category['id'])->increment('view_count', 1);
            } catch (\Throwable $e) {
                // Ignore silent counter increment error
            }
            $result = $productModel->getGlobalProductsPaginated((int) $category['id'], null, $perPage, $page, $sort, $rotationInfo['slot_seed']);
        }

        $pager = $result['pager'];
        $total = $pager ? (int) $pager->getTotal() : count($result['products']);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($category && $page > $totalPages) {
            $page = $totalPages;
            $result = $productModel->getGlobalProductsPaginated((int) $category['id'], null, $perPage, $page, $sort, $rotationInfo['slot_seed']);
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
            'sort'           => $sort,
            'rotationInfo'   => $rotationInfo,
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
        $isFavorite = false;
        $session = session();
        if ($session->get('isLoggedIn')) {
            $userId = (int) $session->get('user_id');
            $userShopReview = $reviewModel->getUserReview($userId, null, $shop['id']);
            $isFavorite = (new FavoriteShopModel())->where('user_id', $userId)->where('shop_id', $shop['id'])->first() !== null;
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
            'isFavorite'       => $isFavorite,
            'businessHours'    => $businessHours,
            'paperSizes'       => $paperSizes,
            'printingSettings' => $printingSettings,
        ]);
    }

    /**
     * Dedicated Search Results Page
     */
    public function search()
    {
        $productModel  = new ProductModel();
        $categoryModel = new CategoryModel();

        $search   = trim((string) ($this->request->getGet('q') ?? $this->request->getGet('search') ?? ''));
        $catId    = (int) $this->request->getGet('category_id');
        $sort     = trim((string) $this->request->getGet('sort'));
        $sort     = in_array($sort, ['discovery', 'price_asc', 'price_desc', 'rating', 'newest', 'name_asc', 'name_desc'], true) ? $sort : null;
        $rotationInfo = ProductModel::getRotationInfo(3);

        $page     = max(1, (int) $this->request->getGet('page'));
        $perPage  = 24;

        $result = $productModel->getGlobalProductsPaginated($catId > 0 ? $catId : null, $search !== '' ? $search : null, $perPage, $page, $sort, $rotationInfo['slot_seed']);

        $pager = $result['pager'];
        $total = $pager ? (int) $pager->getTotal() : count($result['products']);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $categories = $categoryModel->orderBy('name', 'ASC')->findAll();

        return view('customer/search_results', [
            'title'         => $search !== '' ? 'Search: ' . esc($search) : 'Search Products',
            'searchQuery'   => $search,
            'categoryId'    => $catId,
            'categories'    => $categories,
            'products'      => $result['products'],
            'pager'         => $result['pager'],
            'totalPages'    => $totalPages,
            'currentPage'   => $page,
            'perPage'       => $perPage,
            'totalProducts' => $total,
            'sort'          => $sort,
            'rotationInfo'  => $rotationInfo,
        ]);
    }

    public function product($id)
    {
        $productModel = new ProductModel();
        $product      = $productModel->getProductWithDetails((int) $id);

        if (!$product) {
            return redirect()->to('/');
        }

        if (!empty($product['category_id'])) {
            try {
                (new CategoryModel())->builder()->where('id', (int) $product['category_id'])->increment('view_count', 1);
            } catch (\Throwable $e) {
                // Ignore silent counter increment error
            }
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

        $orderIds = array_column($orders, 'id');
        $itemsByOrder = [];
        $productIds = [];

        if (!empty($orderIds)) {
            $allItems = $orderItemModel->whereIn('order_id', $orderIds)->findAll();
            foreach ($allItems as $item) {
                $itemsByOrder[(int) $item['order_id']][] = $item;
                $productIds[(int) $item['product_id']] = true;
            }
        }

        foreach ($orders as &$order) {
            $order['items'] = $itemsByOrder[(int) $order['id']] ?? [];
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

        // Filter out completed, delivered, and cancelled orders older than 2 days
        $cutoff2Days = date('Y-m-d H:i:s', time() - 2 * 86400);
        $orders = array_values(array_filter($orders, static function ($o) use ($cutoff2Days) {
            $status = $o['status'] ?? 'pending';
            if (in_array($status, ['completed', 'delivered', 'cancelled'], true)) {
                $checkDate = $o['completed_at'] ?? $o['cancelled_at'] ?? $o['updated_at'] ?? $o['placed_at'] ?? $o['created_at'] ?? null;
                if ($checkDate && $checkDate < $cutoff2Days) {
                    return false;
                }
            }
            return true;
        }));

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
     * AJAX endpoint for customer live position polling.
     */
    public function getDeliveryPosition($orderRef = null)
    {
        $controller = new CustomerOrderController();
        $controller->initController($this->request, $this->response, $this->logger);
        return $controller->getDeliveryPosition($orderRef);
    }

    /**
     * Dedicated live tracking page for Doorstep Delivery printing requests.
     */
    public function trackPrintingRequest($ref = null)
    {
        $controller = new CustomerOrderController();
        $controller->initController($this->request, $this->response, $this->logger);
        return $controller->trackPrintingRequest($ref);
    }

    /**
     * AJAX endpoint for customer live position polling on printing requests.
     */
    public function getPrintingDeliveryPosition($ref = null)
    {
        $controller = new CustomerOrderController();
        $controller->initController($this->request, $this->response, $this->logger);
        return $controller->getPrintingDeliveryPosition($ref);
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

        // Filter out cancelled printing requests older than 5 days
        $cutoff5Days = date('Y-m-d H:i:s', time() - 5 * 86400);
        $requests = array_values(array_filter($requests, static function ($r) use ($cutoff5Days) {
            $status = $r['status'] ?? 'new';
            if ($status === 'cancelled') {
                $checkDate = $r['updated_at'] ?? $r['created_at'] ?? null;
                if ($checkDate && $checkDate < $cutoff5Days) {
                    return false;
                }
            }
            return true;
        }));

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
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Please log in to save addresses.']);
            }
            return redirect()->to('/login');
        }

        $addressId = (int) $this->request->getPost('address_id');

        $polomolokBarangays = [
            'Bentung', 'Cannery Site', 'Crossing Palkan', 'Glamang', 'Kinilis',
            'Klinan 6', 'Koronadal Proper', 'Lam-Caliaf', 'Landan', 'Lumakil',
            'Magsaysay', 'Maligo', 'Pagalungan', 'Palkan', 'Poblacion', 'Polo',
            'Pula Bato', 'Rubber', 'Silway 7', 'Silway 8', 'Sulit', 'Sumbakil',
            'Upper Klinan'
        ];
        $tupiBarangays = [
            'Acmonan', 'Bololmala', 'Bunao', 'Cebuano', 'Crossing Rubber', 'Dajay',
            'Kablon', 'Kalkam', 'Linan', 'Lunen', 'Miaso', 'Palian',
            'Poblacion', 'Polonoling', 'Simbo', 'Tubeng'
        ];
        $validBarangays = array_unique(array_merge($polomolokBarangays, $tupiBarangays));

        $barangay   = trim((string) ($this->request->getPost('barangay') ?: $this->request->getPost('address_line2')));
        $postedCity = trim((string) $this->request->getPost('city'));

        $isTupi = (stripos($postedCity, 'tupi') !== false) || in_array($barangay, $tupiBarangays, true);
        $city = $isTupi ? 'Tupi' : 'Polomolok';
        $postalCode = $isTupi ? '9505' : '9504';

        // Scoped strictly to Polomolok and Tupi, South Cotabato
        $data = [
            'label'          => trim((string) $this->request->getPost('label')) ?: 'Home',
            'recipient_name' => trim((string) $this->request->getPost('recipient_name')),
            'phone'          => trim((string) $this->request->getPost('phone')),
            'address_line1'  => trim((string) $this->request->getPost('address_line1')),
            'address_line2'  => $barangay,
            'city'           => $city,
            'province'       => 'South Cotabato',
            'postal_code'    => $postalCode,
            'country'        => 'Philippines',
        ];

        $errors = [];
        if ($data['recipient_name'] === '') {
            $errors[] = 'Recipient name is required.';
        }
        if ($data['phone'] === '') {
            $errors[] = 'Phone number is required.';
        }
        if ($data['address_line1'] === '') {
            $errors[] = 'Address Line 1 (Street / House / Purok) is required.';
        }
        if ($barangay === '' || !in_array($barangay, $validBarangays, true)) {
            $errors[] = 'Please select a valid official barangay of Polomolok or Tupi.';
        }

        if ($errors) {
            $msg = implode(' ', $errors);
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'error' => $msg]);
            }
            session()->setFlashdata('error', $msg);
            return redirect()->back()->withInput();
        }

        $lat     = $this->request->getPost('latitude');
        $lng     = $this->request->getPost('longitude');
        $placeId = trim((string) $this->request->getPost('place_id'));

        if ($lat !== null && $lng !== null && is_numeric($lat) && is_numeric($lng) && (float) $lat != 0 && (float) $lng != 0 && \App\Models\DeliveryModel::isPolomolokCoordinate((float) $lat, (float) $lng)) {
            $data['latitude']    = (float) $lat;
            $data['longitude']   = (float) $lng;
            $data['place_id']    = $placeId ?: null;
            $data['geocoded_at'] = date('Y-m-d H:i:s');
        } else {
            $centroid = \App\Models\DeliveryModel::getBarangayCoordinate($barangay, $city);
            if ($centroid) {
                $data['latitude']    = $centroid['lat'];
                $data['longitude']   = $centroid['lng'];
                $data['geocoded_at'] = date('Y-m-d H:i:s');
            } else {
                $mapsService = new \App\Services\GoogleMapsService();
                $fullAddr = $data['address_line1'] . ', ' . $barangay . ', ' . $city . ', South Cotabato, Philippines';
                $geo = $mapsService->geocodeAddress($fullAddr);
                if ($geo) {
                    $data['latitude']    = $geo['lat'];
                    $data['longitude']   = $geo['lng'];
                    $data['place_id']    = $geo['place_id'] ?? null;
                    $data['geocoded_at'] = date('Y-m-d H:i:s');
                }
            }
        }

        $addressModel = new ShippingAddressModel();

        if ($addressId > 0) {
            $row = $addressModel->find($addressId);
            if (!$row || (int) $row['user_id'] !== (int) $userId) {
                if ($this->request->isAJAX()) {
                    return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Address not found.']);
                }
                session()->setFlashdata('error', 'Address not found.');
                return redirect()->back();
            }
            $addressModel->update($addressId, $data);
            $msg = 'Shipping address updated successfully.';
            if ($this->request->isAJAX()) {
                $updated = $addressModel->find($addressId);
                return $this->response->setJSON(['success' => true, 'message' => $msg, 'address' => $updated]);
            }
            session()->setFlashdata('success', $msg);
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
        $newId = $addressModel->insert($data);

        $msg = 'New shipping address added successfully.';
        if ($this->request->isAJAX()) {
            $saved = $addressModel->find($newId);
            return $this->response->setJSON(['success' => true, 'message' => $msg, 'address' => $saved]);
        }
        session()->setFlashdata('success', $msg);
        return redirect()->back();
    }

    public function deleteAddress()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
            }
            return redirect()->to('/login');
        }

        $addressId     = (int) $this->request->getPost('address_id');
        $addressModel  = new ShippingAddressModel();
        $row           = $addressModel->find($addressId);

        if (!$row || (int) $row['user_id'] !== (int) $userId) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Address not found.']);
            }
            session()->setFlashdata('error', 'Address not found.');
            return redirect()->back();
        }

        $addressModel->delete($addressId);
        $msg = 'Address deleted successfully.';
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'message' => $msg]);
        }
        session()->setFlashdata('success', $msg);
        return redirect()->back();
    }

    public function setDefaultAddress()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
            }
            return redirect()->to('/login');
        }

        $addressId    = (int) $this->request->getPost('address_id');
        $addressModel = new ShippingAddressModel();
        $row          = $addressModel->find($addressId);

        if (!$row || (int) $row['user_id'] !== (int) $userId) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Address not found.']);
            }
            session()->setFlashdata('error', 'Address not found.');
            return redirect()->back();
        }

        $addressModel->where('user_id', $userId)->update(null, ['is_default' => 0]);
        $addressModel->update($addressId, ['is_default' => 1]);

        $msg = 'Default delivery address updated.';
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'message' => $msg]);
        }
        session()->setFlashdata('success', $msg);
        return redirect()->back();
    }

    /**
     * Customer real-time notifications & order status polling endpoint.
     * Route: GET /customer/realtime/check
     */
    public function realtimeCheck()
    {
        $session = session();
        $userId  = (int) $session->get('user_id');

        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $notifModel = new \App\Models\NotificationModel();
        $unreadCount = $notifModel->getUnreadCount($userId);

        $lastNotifId = (int) $this->request->getGet('last_notif_id');
        $notifBuilder = (new \App\Models\NotificationModel())->where('user_id', $userId);
        if ($lastNotifId > 0) {
            $notifBuilder->where('id >', $lastNotifId);
        }
        $newNotifs = $notifBuilder->orderBy('id', 'DESC')->limit(5)->findAll();

        $maxNotifId = $lastNotifId;
        foreach ($newNotifs as $n) {
            if ((int) $n['id'] > $maxNotifId) {
                $maxNotifId = (int) $n['id'];
            }
        }

        // Active orders status snapshot
        $activeOrders = (new \App\Models\OrderModel())
            ->select('id, order_number, status, placed_at')
            ->where('customer_id', $userId)
            ->whereIn('status', ['pending', 'processing', 'shipped', 'in_transit', 'ready_for_pickup'])
            ->orderBy('placed_at', 'DESC')
            ->findAll();

        return $this->response->setJSON([
            'success'       => true,
            'unread_count'  => $unreadCount,
            'max_notif_id'  => $maxNotifId,
            'new_notifs'    => $newNotifs,
            'has_new'       => !empty($newNotifs),
            'active_orders' => $activeOrders,
            'timestamp'     => date('Y-m-d H:i:s'),
        ]);
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

    public function favoriteShop()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Please sign in to follow this store.');
        }

        $shopId = (int) $this->request->getPost('shop_id');
        if ($shopId <= 0) {
            session()->setFlashdata('error', 'Invalid shop.');
            return redirect()->back();
        }

        $favModel = new FavoriteShopModel();
        $existing = $favModel->where('user_id', $userId)->where('shop_id', $shopId)->first();
        if (!$existing) {
            $favModel->insert([
                'user_id'    => $userId,
                'shop_id'    => $shopId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            session()->setFlashdata('success', 'Store added to your favorite shops.');
        } else {
            session()->setFlashdata('info', 'You are already following this store.');
        }

        return redirect()->back();
    }

    public function reportShop()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Please log in to submit a report.');
        }

        $shopId      = (int) $this->request->getPost('shop_id');
        $issueType   = trim((string) $this->request->getPost('issue_type'));
        $description = trim((string) $this->request->getPost('description'));

        $validReasons = [
            'Counterfeit/Fake Product',
            'Item Not as Described',
            'Harassment/Abusive Behavior',
            'Scam/Fraud',
            'Other',
        ];

        if ($shopId <= 0 || !in_array($issueType, $validReasons, true)) {
            session()->setFlashdata('error', 'Please select a valid reason for reporting this shop.');
            return redirect()->back();
        }

        $shopModel = new ShopModel();
        $shop      = $shopModel->find($shopId);
        if (!$shop) {
            session()->setFlashdata('error', 'Shop not found.');
            return redirect()->back();
        }

        $complianceModel = new \App\Models\ComplianceModel();
        $reportNumber    = 'CR-' . date('Ymd') . '-' . strtoupper(substr(uniqid('', false), -4));

        $complianceModel->insert([
            'report_number'    => $reportNumber,
            'reporter_id'      => $userId,
            'reported_shop_id' => $shopId,
            'reported_user_id' => $shop['owner_id'] ?? null,
            'issue_type'       => $issueType,
            'description'      => $description !== '' ? $description : 'Reported by customer for: ' . $issueType,
            'status'           => 'pending',
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        session()->setFlashdata('success', 'Your report has been submitted for review. Thank you for helping keep Blax safe.');
        return redirect()->back();
    }

    public function saveProductReview()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Please sign in.']);
            }
            return redirect()->to('/login');
        }

        $productId = (int) $this->request->getPost('product_id');
        $orderId   = (int) $this->request->getPost('order_id');
        $rating    = max(1, min(5, (int) ($this->request->getPost('rating') ?? 5)));
        $comment   = trim((string) $this->request->getPost('comment'));

        if ($productId <= 0) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid product.']);
            }
            return redirect()->back()->with('error', 'Invalid product.');
        }

        $reviewModel = new ReviewModel();
        $existing = $reviewModel->where('user_id', $userId)->where('product_id', $productId)->first();

        if ($existing) {
            $reviewModel->update($existing['id'], [
                'rating'     => $rating,
                'comment'    => $comment,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $reviewModel->insert([
                'user_id'    => $userId,
                'product_id' => $productId,
                'order_id'   => $orderId > 0 ? $orderId : null,
                'rating'     => $rating,
                'comment'    => $comment,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Recalculate product rating average and count
        $avg = $reviewModel->where('product_id', $productId)->selectAvg('rating')->first();
        $cnt = $reviewModel->countProductReviews($productId);
        (new ProductModel())->update($productId, [
            'rating_average' => (float) ($avg['rating'] ?? 5.0),
            'rating_count'   => $cnt,
        ]);

        // Task 3: After product review, prompt customer to also rate the shop
        $product = (new ProductModel())->find($productId);
        if ($product && !empty($product['shop_id'])) {
            $existingShopReview = $reviewModel->getUserReview($userId, null, (int) $product['shop_id']);
            if (!$existingShopReview) {
                $shopName = (new ShopModel())->find($product['shop_id'])['shop_name'] ?? 'the shop';
                (new \App\Models\NotificationModel())->create(
                    $userId,
                    'review_prompt',
                    '🏪 Rate the Shop Too!',
                    "You rated a product from {$shopName}. How was your overall experience with this shop?",
                    '/customer/orders'
                );
            }
        }

        if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
            return $this->response->setJSON(['success' => true, 'message' => 'Product review saved successfully!']);
        }

        session()->setFlashdata('success', 'Product review submitted!');
        return redirect()->back();
    }

    public function saveShopReview()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Please sign in.']);
            }
            return redirect()->to('/login');
        }

        $shopId  = (int) $this->request->getPost('shop_id');
        $orderId = (int) $this->request->getPost('order_id');
        $rating  = max(1, min(5, (int) ($this->request->getPost('rating') ?? 5)));
        $comment = trim((string) $this->request->getPost('comment'));

        if ($shopId <= 0) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid shop.']);
            }
            return redirect()->back()->with('error', 'Invalid shop.');
        }

        $reviewModel = new ReviewModel();
        $existing = $reviewModel->where('user_id', $userId)->where('shop_id', $shopId)->first();

        if ($existing) {
            $reviewModel->update($existing['id'], [
                'rating'     => $rating,
                'comment'    => $comment,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $reviewModel->insert([
                'user_id'    => $userId,
                'shop_id'    => $shopId,
                'order_id'   => $orderId > 0 ? $orderId : null,
                'rating'     => $rating,
                'comment'    => $comment,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Recalculate shop rating average and count
        $avg = $reviewModel->where('shop_id', $shopId)->selectAvg('rating')->first();
        $cnt = $reviewModel->countShopReviews($shopId);
        (new ShopModel())->update($shopId, [
            'rating_average' => (float) ($avg['rating'] ?? 5.0),
            'rating_count'   => $cnt,
        ]);

        if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
            return $this->response->setJSON(['success' => true, 'message' => 'Shop review saved successfully!']);
        }

        session()->setFlashdata('success', 'Shop review submitted!');
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
        $cloudinary = new \App\Libraries\CloudinaryService();
        $fileUrl = null;

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
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $maxBytes = 5 * 1024 * 1024; // 5MB

                $refFiles = $this->request->getFileMultiple('reference_photos');
                if (!empty($refFiles)) {
                    foreach ($refFiles as $rf) {
                        if ($rf && $rf->isValid() && !$rf->hasMoved()) {
                            if (in_array($rf->getMimeType(), $allowedMimes, true) && $rf->getSize() <= $maxBytes) {
                                $refPublicId = 'ref_' . time() . '_' . bin2hex(random_bytes(4));
                                $refUpload = $cloudinary->uploadImage($rf, \App\Libraries\CloudinaryService::FOLDER_PRINTING_REFS, $refPublicId);
                                if ($refUpload && !empty($refUpload['secure_url'])) {
                                    $stagedRefPhotos[] = [
                                        'file_name' => $rf->getClientName(),
                                        'file_path' => $refUpload['secure_url'],
                                        'file_size' => $rf->getSize(),
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            $docPublicId = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $uploadRes = $cloudinary->uploadRawFile($file, \App\Libraries\CloudinaryService::FOLDER_PRINTING_DOCS, $docPublicId);
            if ($uploadRes && !empty($uploadRes['secure_url'])) {
                $fileUrl = $uploadRes['secure_url'];
            } else {
                session()->setFlashdata('error', 'Failed to upload document to secure storage. Please try again.');
                return redirect()->back();
            }
        } else {
            // PDF Document
            if ($ext !== 'pdf') {
                session()->setFlashdata('error', 'Only PDF documents are accepted for PDF printing.');
                return redirect()->back();
            }

            $tmpPath = $file->getRealPath() ?: $file->getTempName();
            if (!is_valid_pdf($tmpPath, $file->getClientName())) {
                session()->setFlashdata('error', 'The uploaded file is not a valid PDF document.');
                return redirect()->back();
            }

            $pageCount = count_pdf_pages($tmpPath);
            if ($pageCount <= 0) {
                session()->setFlashdata('error', 'Could not determine the page count of the uploaded PDF.');
                return redirect()->back();
            }

            $docPublicId = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
            $uploadRes = $cloudinary->uploadRawFile($file, \App\Libraries\CloudinaryService::FOLDER_PRINTING_DOCS, $docPublicId);
            if ($uploadRes && !empty($uploadRes['secure_url'])) {
                $fileUrl = $uploadRes['secure_url'];
            } else {
                session()->setFlashdata('error', 'Failed to upload document to secure storage. Please try again.');
                return redirect()->back();
            }
        }

        $allowedPaperSizes = ['letter', 'legal', 'a4', 'a3', 'a5', 'b5', 'b4', 'a2', 'a1', 'a0'];
        $paperSize = strtolower(trim((string) $this->request->getPost('paper_size')));
        if (!in_array($paperSize, $allowedPaperSizes, true)) {
            $paperSize = 'letter';
        }

        $colorMode = strtolower((string) $this->request->getPost('color_mode')) === 'colored' ? 'color' : 'bw';
        $bindingMap = ['stapled' => 'staple', 'spiral' => 'spiral', 'none' => 'none'];
        $binding = $bindingMap[strtolower((string) $this->request->getPost('binding'))] ?? 'none';
        $copies = max(1, min(500, (int) $this->request->getPost('copies')));

        $fulfillment = $this->request->getPost('fulfillment_method') === 'delivery' ? 'delivery' : 'pickup';
        if ($fulfillment === 'delivery' && isset($shop['offers_delivery']) && (int) $shop['offers_delivery'] === 0) {
            session()->setFlashdata('error', 'Kasalukuyang hindi nag-aalok ng Doorstep Delivery service ang tindahang ito.');
            return redirect()->back();
        }
        if ($fulfillment === 'pickup' && isset($shop['offers_pickup']) && (int) $shop['offers_pickup'] === 0) {
            session()->setFlashdata('error', 'Kasalukuyang hindi nag-aalok ng Store Pick-up service ang tindahang ito.');
            return redirect()->back();
        }

        // Fetch dynamic pricing and supported sizes for shop
        $shopPaperSizes = (new \App\Models\ShopPaperSizeSettingModel())->getForShop($shopId);
        $shopSettings   = (new \App\Models\ShopPrintingSettingModel())->getForShop($shopId);

        // Validate paper size is enabled for this shop
        if (isset($shopPaperSizes[$paperSize]) && empty($shopPaperSizes[$paperSize]['is_enabled'])) {
            session()->setFlashdata('error', 'The selected paper size is not currently offered by this shop.');
            return redirect()->back();
        }

        // Global rates per page based on color mode
        $basePerPage = ($colorMode === 'color')
            ? (float) ($shopSettings['price_color_per_page'] ?? 5.00)
            : (float) ($shopSettings['price_bw_per_page'] ?? 2.00);

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

        $rawPayMethod = strtolower(trim((string) $this->request->getPost('payment_method')) ?: 'gcash');
        if ($rawPayMethod === 'gcash') {
            $paymentMethod = 'gcash';
        } else {
            // Offline payment: 'pickup' (Pay at Counter) if Store Pick-up, 'cod' (Cash on Delivery) if Doorstep Delivery
            $paymentMethod = ($fulfillment === 'pickup') ? 'pickup' : 'cod';
        }

        // Non-GCash orders (Store Pick-up pay at counter or Cash on Delivery) insert immediately
        if ($paymentMethod !== 'gcash') {
            $db = \Config\Database::connect();
            $db->transStart();

            $prModel = new PrintingRequestModel();
            $insertData = [
                'request_number'       => $pendingPrinting['request_number'],
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
                'status'               => 'new',
                'progress_percent'     => 0,
            ];

            $prId = $prModel->insert($insertData);

            if (!empty($stagedRefPhotos) && is_array($stagedRefPhotos)) {
                $attachmentModel = new PrintingRequestAttachmentModel();
                foreach ($stagedRefPhotos as $attachment) {
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

            $paymentModel = new PaymentModel();
            $paymentModel->insert([
                'payable_type'     => 'printing_request',
                'payable_id'       => $prId,
                'method'           => $paymentMethod,
                'amount'           => $downPayment,
                'reference_number' => 'OFFLINE-' . bin2hex(random_bytes(8)),
                'proof_image_url'  => null,
                'status'           => 'pending',
                'processed_at'     => null,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);

            if ($fulfillment === 'delivery') {
                $delModel = new DeliveryModel();
                $delModel->insert([
                    'deliverable_type'    => 'printing_request',
                    'deliverable_id'      => $prId,
                    'tracking_id'         => $insertData['request_number'],
                    'courier_name'        => 'Store Courier',
                    'destination_address' => 'Doorstep Delivery',
                    'status'              => 'pending',
                    'created_at'          => date('Y-m-d H:i:s'),
                ]);
            }

            $db->transComplete();

            if ($db->transStatus() !== false) {
                // Trigger shop owner notification
                $shop = (new ShopModel())->find($shopId);
                if ($shop && !empty($shop['owner_id'])) {
                    $customerUser = (new UserModel())->find($userId);
                    $cName = $customerUser ? trim(($customerUser['first_name'] ?? '') . ' ' . ($customerUser['last_name'] ?? '')) : 'A customer';
                    if ($cName === '') $cName = 'A customer';
                    $prTime = date('M d, Y h:i A');
                    $fileName = $file->getClientName();
                    (new NotificationModel())->create(
                        (int) $shop['owner_id'],
                        'new_printing_request',
                        "New Printing Request for {$fileName} (#{$insertData['request_number']})",
                        "Received printing request for {$fileName} (#{$insertData['request_number']}) from {$cName} at {$prTime}.",
                        '/tenant/printing'
                    );
                }

                $msg = ($fulfillment === 'pickup' || $paymentMethod === 'pickup')
                    ? 'Your printing request has been submitted! You can pay down payment or balance at the shop counter upon pick-up.'
                    : 'Your printing request has been submitted! Cash on Delivery will be collected upon doorstep delivery.';
                return redirect()->to('/customer/printing')->with('success', $msg);
            }

            return redirect()->back()->with('error', 'Failed to submit printing request. Please try again.');
        }

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
        if (!$pending && $userId) {
            $pending = session()->get('pending_printing_' . $userId);
        }

        // Session recovery: If PHP session expired during external GCash checkout, recover user
        if (!$userId) {
            $recoveredUserId = null;
            if (!empty($pending['customer_id'])) {
                $recoveredUserId = (int) $pending['customer_id'];
            } elseif ($sessionId !== '') {
                $paymongo = service('paymongoService');
                $check = $paymongo->getCheckoutSession($sessionId);
                if (!empty($check['session']['attributes']['metadata']['user_id'])) {
                    $recoveredUserId = (int) $check['session']['attributes']['metadata']['user_id'];
                }
            }

            if ($recoveredUserId) {
                $recoveredUser = (new \App\Models\UserModel())->find($recoveredUserId);
                if ($recoveredUser) {
                    $session->set([
                        'user_id'    => (int) $recoveredUser['id'],
                        'user_name'  => trim(($recoveredUser['first_name'] ?? '') . ' ' . ($recoveredUser['last_name'] ?? '')),
                        'user_email' => $recoveredUser['email'] ?? '',
                        'user_role'  => $recoveredUser['role'] ?? 'customer',
                        'role'       => $recoveredUser['role'] ?? 'customer',
                        'isLoggedIn' => true,
                    ]);
                    $userId = (int) $recoveredUser['id'];
                }
            }
        }

        if (!$userId) {
            return redirect()->to('/login');
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
        // Idempotency: prevent duplicate printing request/payment if callback runs multiple times concurrently
        $alreadyProcessed = $db->query(
            "SELECT * FROM payments WHERE reference_number = ? LIMIT 1 FOR UPDATE",
            [$sessionId]
        )->getRowArray();

        if ($alreadyProcessed) {
            $db->transComplete();
            // Also clean up any lingering cache
            if ($token !== '') {
                session()->remove('pending_printing_token_' . $token);
                cache()->delete('pending_printing_token_' . $token);
            }
            if ($sessionId !== '') {
                cache()->delete('pending_printing_' . $sessionId);
            }
            return redirect()->to('/customer/printing')->with('info', 'Your printing request down payment has already been verified.');
        }

        try {
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
                'status'               => 'new',
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

            $db->transComplete();
        } catch (\Throwable $e) {
            $db->transRollback();
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), '1062')) {
                return redirect()->to('/customer/printing')->with('info', 'Your printing request down payment has already been verified.');
            }
            throw $e;
        }

        // Clean up pending session and explicit cache data
        session()->remove('pending_printing_' . $userId);
        if ($token !== '') {
            session()->remove('pending_printing_token_' . $token);
            cache()->delete('pending_printing_token_' . $token);
        }
        if ($sessionId !== '') {
            cache()->delete('pending_printing_' . $sessionId);
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

    public function cancelPrintingRequest(?int $id = null)
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            if ($this->request->isAJAX() || str_contains($this->request->getHeaderLine('Accept'), 'application/json')) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized.']);
            }
            return redirect()->to('/login');
        }

        $requestId = $id ?? (int) $this->request->getPost('request_id') ?? (int) $this->request->getPost('id');
        if ($requestId <= 0) {
            if ($this->request->isAJAX() || str_contains($this->request->getHeaderLine('Accept'), 'application/json')) {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid printing request.']);
            }
            session()->setFlashdata('error', 'Invalid printing request.');
            return redirect()->back();
        }

        $prModel = new PrintingRequestModel();
        $row     = $prModel->find($requestId);

        if (!$row || (int) $row['customer_id'] !== (int) $userId) {
            if ($this->request->isAJAX() || str_contains($this->request->getHeaderLine('Accept'), 'application/json')) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Printing request not found.']);
            }
            session()->setFlashdata('error', 'Printing request not found.');
            return redirect()->back();
        }

        $status = strtolower((string) $row['status']);
        if (!in_array($status, ['new', 'in_production'], true)) {
            if ($this->request->isAJAX() || str_contains($this->request->getHeaderLine('Accept'), 'application/json')) {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'This printing request cannot be cancelled once packed or completed.']);
            }
            session()->setFlashdata('error', 'This printing request cannot be cancelled once packed or completed.');
            return redirect()->back();
        }

        $prModel->update($requestId, [
            'status'       => 'cancelled',
            'completed_at' => null,
        ]);

        // Sync linked delivery if any
        $deliveryModel = new \App\Models\DeliveryModel();
        $deliveryModel->where('deliverable_type', 'printing_request')
            ->where('deliverable_id', $requestId)
            ->set(['status' => 'cancelled'])
            ->update();

        // Send shop notification
        $shop = (new \App\Models\ShopModel())->find($row['shop_id']);
        if ($shop && !empty($shop['owner_id'])) {
            $customerUser = (new \App\Models\UserModel())->find($userId);
            $cName = $customerUser ? trim(($customerUser['first_name'] ?? '') . ' ' . ($customerUser['last_name'] ?? '')) : 'A customer';
            $reqNum = $row['request_number'] ?? ('PR-' . $requestId);
            (new \App\Models\NotificationModel())->create(
                (int) $shop['owner_id'],
                'printing_cancelled',
                "Printing Request Cancelled (#{$reqNum})",
                "{$cName} has cancelled printing request #{$reqNum}.",
                '/tenant/printing'
            );
        }

        if ($this->request->isAJAX() || str_contains($this->request->getHeaderLine('Accept'), 'application/json')) {
            return $this->response->setJSON(['success' => true, 'message' => 'Printing request cancelled successfully.']);
        }

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

        $cloudinary = new \App\Libraries\CloudinaryService();

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

            $publicId = 'usr_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4));
            $uploadRes = $cloudinary->uploadImage($file, \App\Libraries\CloudinaryService::FOLDER_PROFILES, $publicId);

            if ($uploadRes && !empty($uploadRes['secure_url'])) {
                $updates['profile_image_url'] = $uploadRes['secure_url'];

                // Cleanup previous image
                if (!empty($current['profile_image_url'])) {
                    if ($cloudinary->isCloudinaryUrl($current['profile_image_url'])) {
                        $oldPublicId = $cloudinary->extractPublicId($current['profile_image_url']);
                        if ($oldPublicId) {
                            $cloudinary->deleteAsset($oldPublicId, 'image');
                        }
                    } elseif (strpos($current['profile_image_url'], 'uploads/profiles/') === 0) {
                        $oldPath = ROOTPATH . 'public/' . $current['profile_image_url'];
                        if (is_file($oldPath)) @unlink($oldPath);
                    }
                }
                $session->set('profile_image_url', $updates['profile_image_url']);
            } else {
                session()->setFlashdata('error', 'Failed to upload profile picture. Please try again.');
                return redirect()->back();
            }
        } elseif ($removeImage) {
            if (!empty($current['profile_image_url'])) {
                if ($cloudinary->isCloudinaryUrl($current['profile_image_url'])) {
                    $oldPublicId = $cloudinary->extractPublicId($current['profile_image_url']);
                    if ($oldPublicId) {
                        $cloudinary->deleteAsset($oldPublicId, 'image');
                    }
                } elseif (strpos($current['profile_image_url'], 'uploads/profiles/') === 0) {
                    $oldPath = ROOTPATH . 'public/' . $current['profile_image_url'];
                    if (is_file($oldPath)) @unlink($oldPath);
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
}
