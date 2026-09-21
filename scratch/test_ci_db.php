<?php
define('FCPATH', __DIR__ . '/../public/');
require __DIR__ . '/../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';

// Put environment variables for TiDB
putenv('DB_HOST=gateway01.ap-southeast-1.prod.aws.tidbcloud.com');
putenv('DB_PORT=4000');
putenv('DB_USER=2xsV5M53UudMfnZ.root');
putenv('DB_PASS=y3b0jACs14yRTEy7');
putenv('DB_DATABASE=blax_marketplace');

CodeIgniter\Boot::bootTest($paths);

try {
    $db = \Config\Database::connect();
    $query = $db->query('SELECT COUNT(*) as count FROM users');
    $row = $query->getRow();
    echo "CI4 DB connection successful! Users count: " . $row->count . "\n";
} catch (\Throwable $e) {
    echo "CI4 DB Connection Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
