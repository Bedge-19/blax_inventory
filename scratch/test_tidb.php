<?php
$m = mysqli_init();
$m->ssl_set(NULL, NULL, NULL, NULL, NULL);
$res = @$m->real_connect(
    'gateway01.ap-southeast-1.prod.aws.tidbcloud.com',
    '2xsV5M53UudMfnZ.root',
    'y3b0jACs14yRTEy7',
    'blax_marketplace',
    4000,
    NULL,
    MYSQLI_CLIENT_SSL
);

if (!$res) {
    echo "Connection failed: " . mysqli_connect_error() . "\n";
    exit(1);
}

echo "Successfully connected to TiDB Cloud!\n";
$tables = $m->query("SHOW TABLES");
$tableNames = [];
while ($row = $tables->fetch_array()) {
    $tableNames[] = $row[0];
}
echo "Found " . count($tableNames) . " tables in blax_marketplace:\n";
echo implode(", ", $tableNames) . "\n";
