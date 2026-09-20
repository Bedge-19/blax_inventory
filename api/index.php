<?php

/**
 * Vercel serverless bridge for CodeIgniter 4.
 *
 * Vercel's filesystem is READ-ONLY except for /tmp, which is writable but
 * wiped whenever the container cold-starts. This file:
 *   1. Points FCPATH at the real public/ folder (so base_url(), assets, etc.
 *      still resolve the same way they would on a normal server).
 *   2. Makes sure /tmp/blax/{cache,logs,session,uploads} exist before
 *      CodeIgniter boots, since app/Config/Paths.php redirects the writable
 *      directory there in production.
 *   3. Boots CodeIgniter exactly like public/index.php does.
 */

$minPhpVersion = '8.1';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo 'PHP ' . $minPhpVersion . '+ required. Current: ' . PHP_VERSION;
    exit(1);
}

// Pre-create the writable subfolders on every cold start.
$tmpBase = '/tmp/blax';
foreach (['cache', 'logs', 'session', 'uploads', 'uploads/business_permits'] as $sub) {
    $dir = $tmpBase . '/' . $sub;
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

// FCPATH must point at the folder that contains the front controller's
// "view" of the app — we reuse public/ so relative asset paths etc. behave
// exactly like the normal public/index.php entrypoint.
define('FCPATH', __DIR__ . '/../public/');

require __DIR__ . '/../app/Config/Paths.php';

$paths = new Config\Paths();

require $paths->systemDirectory . '/Boot.php';

exit(CodeIgniter\Boot::bootWeb($paths));
