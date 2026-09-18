<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Customer / Public Routes
$routes->get('/', 'Home::index');
$routes->post('ai/query', 'Home::aiQuery');

// Auth Routes
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::login', ['filter' => 'authThrottle']);
$routes->get('forgot-password', 'Auth::forgotPassword');
$routes->post('forgot-password', 'Auth::forgotPassword', ['filter' => 'authThrottle']);
$routes->post('reset-password-direct', 'Auth::directResetPassword', ['filter' => 'authThrottle']);
$routes->get('reset-password/(:any)', 'Auth::resetPassword/$1');
$routes->post('reset-password/(:any)', 'Auth::resetPassword/$1', ['filter' => 'authThrottle']);
$routes->get('signup', 'Auth::signup');
$routes->post('signup', 'Auth::signup', ['filter' => 'authThrottle']);
$routes->get('signup/merchant', 'Auth::registerShop');
$routes->post('signup/merchant', 'Auth::registerShop', ['filter' => 'authThrottle']);
$routes->get('merchant-signup', 'Auth::registerShop');
$routes->post('merchant-signup', 'Auth::registerShop', ['filter' => 'authThrottle']);
$routes->get('logout', 'Auth::logout');

// Notification Routes
$routes->get('notifications/click/(:num)', 'NotificationController::click/$1');
$routes->post('notifications/mark-read/(:num)', 'NotificationController::markRead/$1');
$routes->post('notifications/mark-all-read', 'NotificationController::markAllRead');

// Customer Pages
$routes->get('categories', 'Customer::categories');
$routes->get('category/(:segment)', 'Customer::category/$1');
$routes->get('shops', 'Customer::shops');
$routes->get('shop/(:segment)', 'Customer::shop/$1');
$routes->get('product/(:num)', 'Customer::product/$1');
$routes->get('printing-services', 'Customer::printingServices');
$routes->post('printing/request', 'Customer::submitPrintingRequest', ['filter' => 'actionThrottle']);
$routes->post('printing/count-pages', 'Customer::countPrintingPages');
$routes->post('printing/cancel', 'Customer::cancelPrintingRequest');
$routes->get('printing/callback', 'Customer::printingPaymentCallback');

// Cart & Checkout
$routes->get('cart', 'Cart::index');
$routes->post('cart/add', 'Cart::add');
$routes->post('cart/update', 'Cart::updateQuantity');
$routes->post('cart/remove-selected', 'Cart::removeSelected');
$routes->post('cart/checkout', 'Cart::checkout', ['filter' => 'actionThrottle']);
$routes->get('cart/payment/callback', 'Cart::paymentCallback');
$routes->post('payment/webhook', 'Cart::paymongoWebhook');
$routes->get('cart/remove/(:num)', 'Cart::remove/$1');
$routes->get('buy-now', 'Checkout::direct');
$routes->post('buy-now/place', 'Checkout::placeOrder', ['filter' => 'actionThrottle']);

// Dedicated Search Page
$routes->get('search', 'Customer::search');

// Customer Dashboard / Account
$routes->group('customer', ['filter' => 'customerAuth'], function ($routes) {
    $routes->get('orders', 'Customer::orders');
    $routes->get('orders/track/(:any)', 'Customer::trackOrder/$1');
    $routes->post('orders/(:num)/cancel', 'Customer::cancelOrder/$1');
    $routes->post('orders/cancel', 'Customer::cancelOrder');
    $routes->get('printing', 'Customer::printingRequests');
    $routes->get('favorites', 'Customer::favorites');
    $routes->get('addresses', 'Customer::addresses');
    $routes->get('profile', 'Customer::profile');
    $routes->post('profile/update', 'Customer::updateProfile');
    $routes->post('addresses/save', 'Customer::saveAddress');
    $routes->post('addresses/delete', 'Customer::deleteAddress');
    $routes->post('addresses/set-default', 'Customer::setDefaultAddress');
    $routes->post('favorites/remove', 'Customer::unfavoriteShop');
    $routes->post('favorites/add', 'Customer::favoriteShop');
    $routes->post('shop/report', 'Customer::reportShop', ['filter' => 'actionThrottle']);
});

// Rating & Review Routes
$routes->group('reviews', ['filter' => 'customerAuth'], function ($routes) {
    $routes->post('product/save', 'Customer::saveProductReview');
    $routes->post('shop/save', 'Customer::saveShopReview');
});

// AI Assistant AJAX Endpoint
$routes->post('ai-assistant/chat', 'AiAssistant::chat', ['filter' => 'actionThrottle']);

// Tenant Routes
$routes->group('tenant', ['filter' => 'tenantAuth'], function ($routes) {
    $routes->get('/', 'Tenant::dashboard');
    $routes->get('dashboard', 'Tenant::dashboard');
    $routes->get('inventory', 'Tenant::inventory');
    $routes->get('orders', 'Tenant::orders');
    $routes->get('orders/export', 'Tenant::ordersExport');
    $routes->get('orders/items/(:num)', 'Tenant::orderItems/$1');
    $routes->get('printing', 'Tenant::printing');
    $routes->get('printing/view/(:num)', 'Tenant::viewPrintFile/$1');
    $routes->get('deliveries', 'Tenant::deliveries');
    $routes->get('delivery', 'Tenant::deliveries');
    $routes->get('deliveries/export', 'Tenant::deliveriesExport');
    $routes->get('withdrawals', 'Tenant::withdrawals');
    $routes->get('analytics', 'Tenant::analytics');
    $routes->get('analytics/data', 'Tenant::analyticsData');
    $routes->get('settings', 'Tenant::settings');
    $routes->get('archive', 'Tenant::archive');
    $routes->get('dashboard/sales', 'Tenant::dashboardSalesData');

    $routes->post('orders/update-status', 'Tenant::updateOrderStatus');
    $routes->post('printing/update-status', 'Tenant::updatePrintingStatus');
    $routes->post('printing/settings/save', 'Tenant::savePrintingSettings');
    $routes->post('deliveries/update-status', 'Tenant::updateDeliveryStatus');
    $routes->post('deliveries/lookup', 'Tenant::deliveryLookup');
    $routes->post('withdrawals/request', 'Tenant::requestWithdrawal');
    $routes->post('settings/save', 'Tenant::saveSettings');
    $routes->post('settings/logo', 'Tenant::saveShopLogo');
    $routes->post('products/save', 'Tenant::saveProduct');
    $routes->post('products/images/delete/(:num)', 'Tenant::deleteProductImage/$1');
    $routes->post('products/delete/(:num)', 'Tenant::deleteProduct/$1');
    $routes->post('products/archive/(:num)', 'Tenant::archiveProduct/$1');
    $routes->post('products/bulk-archive', 'Tenant::bulkArchiveProducts');
    $routes->post('products/adjust-stock', 'Tenant::adjustStock');
    $routes->post('products/bulk-adjust-stock', 'Tenant::bulkAdjustStock');
    $routes->post('printing/archive/(:num)', 'Tenant::archivePrintingRequest/$1');
    $routes->post('printing/archive-all', 'Tenant::archiveAllCompleted');
    $routes->get('printing/download/(:num)', 'Tenant::downloadPrintFile/$1');
    $routes->post('archive/restore/(:num)', 'Tenant::restoreProduct/$1');
    $routes->post('notifications/mark-read', 'Tenant::markNotificationsRead');
    $routes->get('pos/search-products', 'Tenant::posSearchProducts');
    $routes->post('pos/verify-qr', 'Tenant::posVerifyQr');
    $routes->post('pos/complete-pickup', 'Tenant::posCompletePickup');
    $routes->post('pos/complete-walkin', 'Tenant::posCompleteWalkin');
    $routes->get('pos', 'Tenant::pos');
    $routes->get('deliveries/(:num)', 'Tenant::deliveryDetail/$1');
    $routes->get('orders/detail-json/(:num)', 'Tenant::orderDetailJson/$1');
    $routes->post('compliance/report-customer', 'Tenant::reportCustomer');
});

// Admin Routes
$routes->group('admin', ['filter' => 'adminAuth'], function ($routes) {
    $routes->get('/', 'Admin::dashboard');
    $routes->get('dashboard', 'Admin::dashboard');
    $routes->get('tenants', 'Admin::tenants');
    $routes->get('customers', 'Admin::customers');
    $routes->get('payments', 'Admin::payments');
    $routes->get('compliance', 'Admin::compliance');
    $routes->get('tracking', 'Admin::tracking');
    $routes->get('audit-log', 'Admin::auditLog');
    $routes->get('analytics', 'Admin::analytics');
    $routes->get('analytics/data', 'Admin::analyticsData');
    $routes->get('content', 'Admin::content');

    $routes->post('tenants/toggle-status', 'Admin::toggleTenantStatus');
    $routes->post('customers/toggle-status', 'Admin::toggleCustomerStatus');
    $routes->post('tenants/approve', 'Admin::approveTenant');
    $routes->post('tenants/reject', 'Admin::rejectTenant');
    $routes->get('tenants/permit/(:num)', 'Admin::tenantPermit/$1');
    $routes->post('payments/update-status', 'Admin::updatePayoutStatus');
    $routes->post('payments/deduction', 'Admin::updateDeductionPercent');
    $routes->post('compliance/resolve', 'Admin::resolveCompliance');
    $routes->post('compliance/warn', 'Admin::warnCompliance');
    $routes->post('compliance/suspend', 'Admin::suspendCompliance');
    $routes->post('compliance/deactivate', 'Admin::suspendCompliance');
    $routes->post('content/save', 'Admin::saveContent');
    $routes->post('content/upload', 'Admin::uploadContentImage');
});
