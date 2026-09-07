<?php
// Full approval flow test: temp user -> shop -> approve -> verify email
// Cleanup files left behind after run.
define('CI_ENVIRONMENT', 'development');

require __DIR__ . '/vendor/autoload.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
\CodeIgniter\Boot::bootSpark($paths);

$userModel = new \App\Models\UserModel();
$shopModel = new \App\Models\ShopModel();

$testEmail = 'test_tenant_temp@blax.test';
$testPassHash = password_hash('TestPass123!', PASSWORD_BCRYPT);

// 1. Cleanup any previous test data
$userModel->where('email', $testEmail)->delete();

echo "Creating test user (shop_owner role)...\n";
$userId = $userModel->insert([
    'role' => 'shop_owner',
    'first_name' => 'Test',
    'last_name' => 'Tenant',
    'email' => $testEmail,
    'password_hash' => $testPassHash,
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s'),
]);
echo "User ID: $userId\n";

echo "Creating test shop (pending status)...\n";
$shopId = $shopModel->insert([
    'owner_id' => $userId,
    'shop_name' => 'Test Tenant Shop',
    'description' => 'Temporary test shop for approval',
    'status' => 'pending',
    'offers_printing' => 0,
    'created_at' => date('Y-m-d H:i:s'),
]);
echo "Shop ID: $shopId\n";

// Verify shop is pending
$shop = $shopModel->find($shopId);
echo "Shop status: " . ($shop['status'] ?? 'unknown') . "\n";

// 2. Simulate admin approval flow (calling controller method directly)
echo "\nRunning admin approval process...\n";
$admin = new \App\Controllers\Admin();
$admin->approveTenant();

// 3. Check results
$shop = $shopModel->find($shopId);
$user = $userModel->find($userId);
echo "After approval - Shop status: " . ($shop['status'] ?? 'unknown') . "\n";
echo "After approval - User status: " . ($user['status'] ?? 'unknown') . "\n";

// 4. Try to approve again (should fail - already active)
echo "\nAttempting double-approval (should block)...\n";
try {
    $admin->approveTenant();
} catch (\Throwable $e) {
    // continues to redirect with error
}
$shopAfter = $shopModel->find($shopId);
echo "Double-approval status: " . ($shopAfter['status'] ?? 'unknown') . "\n";

// 5. Check log for success
$logFile = WRITEPATH . 'logs/log-' . date('Y-m-d') . '.log';
$log = file_get_contents($logFile);
if (strpos($log, 'verification email sent') !== false || strpos($log, 'sendTenantVerified') !== false) {
    echo "SUCCESS: Email dispatch logged.\n";
}
if (strpos($log, 'verification email failed') !== false) {
    echo "WARNING: Email failed in logs.\n";
}

// 6. Cleanup
echo "\nCleaning up test records...\n";
$shopModel->delete($shopId);
$userModel->delete($userId);
echo "Done. Deleted test user $userId and shop $shopId.\n";