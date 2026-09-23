<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     *
     * No credentials are hardcoded here. All values are populated at runtime
     * from environment variables in __construct() — either via CI's dotted-key
     * .env loading (local dev) or explicit getenv() (Vercel dashboard).
     *
     * @var array<string, mixed>
     */
    public array $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => '',
        'password'     => '',
        'database'     => '',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_unicode_ci',
        'swapPre'      => '',
        'encrypt'      => ['ssl_verify' => false],
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
        'foundRows'    => false,
        'dateFormat'   => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    /**
     * This database connection is used when running PHPUnit database tests.
     *
     * @var array<string, mixed>
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => 'root',
        'password'    => '',
        'database'    => 'blax_marketplace',
        'DBDriver'    => 'MySQLi',
        'DBPrefix'    => '',
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8',
        'DBCollat'    => '',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
        'dateFormat'  => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    public function __construct()
    {
        // Let CI's parent load dotted-key values from .env (local dev).
        parent::__construct();

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }

        // -----------------------------------------------------------
        // Explicit env-var overrides for Vercel (underscore/caps names)
        // -----------------------------------------------------------
        // Vercel does not allow dots in env var names, so CI's auto-
        // mapping of "database.default.hostname" never fires. We read
        // the Vercel-style names directly and fall back to whatever
        // CI already loaded (which covers the local .env case).
        // -----------------------------------------------------------

        $this->default['hostname'] = $this->firstEnv(
            ['DATABASE_DEFAULT_HOSTNAME', 'DB_HOST', 'DB_HOSTNAME'],
            $this->default['hostname'] ?: 'localhost'
        );

        $this->default['username'] = $this->firstEnv(
            ['DATABASE_DEFAULT_USERNAME', 'DB_USER', 'DB_USERNAME'],
            $this->default['username'] ?: ''
        );

        $this->default['password'] = $this->firstEnv(
            ['DATABASE_DEFAULT_PASSWORD', 'DB_PASS', 'DB_PASSWORD'],
            $this->default['password'] ?: ''
        );

        $this->default['database'] = $this->firstEnv(
            ['DATABASE_DEFAULT_DATABASE', 'DB_DATABASE', 'DB_NAME'],
            $this->default['database'] ?: 'blax_marketplace'
        );

        $driver = $this->firstEnv(['DATABASE_DEFAULT_DBDRIVER', 'DB_DRIVER'], '');
        if ($driver !== '') {
            $this->default['DBDriver'] = $driver;
        }

        $prefix = $this->firstEnv(['DATABASE_DEFAULT_DBPREFIX', 'DB_PREFIX'], null);
        if ($prefix !== null) {
            $this->default['DBPrefix'] = $prefix;
        }

        $charset = $this->firstEnv(['DATABASE_DEFAULT_CHARSET', 'DB_CHARSET'], '');
        if ($charset !== '') {
            $this->default['charset'] = $charset;
        }

        $collat = $this->firstEnv(['DATABASE_DEFAULT_DBCOLLAT', 'DB_COLLATION'], '');
        if ($collat !== '') {
            $this->default['DBCollat'] = $collat;
        }

        // Port: auto-detect TiDB Cloud (port 4000) vs standard MySQL (3306)
        $envPort = $this->firstEnv(['DATABASE_DEFAULT_PORT', 'DB_PORT'], '');
        if ($envPort !== '') {
            $this->default['port'] = (int) $envPort;
        } elseif (str_contains((string) $this->default['hostname'], 'tidbcloud.com')) {
            $this->default['port'] = 4000;
        }

        // Enable compression for TiDB Cloud or any Vercel deployment
        if (str_contains((string) $this->default['hostname'], 'tidbcloud.com') || getenv('VERCEL') === '1') {
            $this->default['compress'] = true;
        }

        // -----------------------------------------------------------
        // Production safety: disable DBDebug so DB errors become
        // logged/handled failures instead of raw exceptions dumped
        // to visitors. pConnect remains false (correct for serverless).
        // NOTE: Connection pooling (e.g. ProxySQL) should be evaluated
        // if TiDB Cloud max-connections becomes a bottleneck.
        // -----------------------------------------------------------
        $ciEnv = getenv('CI_ENVIRONMENT') ?: ENVIRONMENT;
        if ($ciEnv === 'production') {
            $this->default['DBDebug'] = false;
        }
    }

    /**
     * Return the first non-empty getenv() value from the list of keys,
     * or $fallback if none are set.
     */
    private function firstEnv(array $keys, ?string $fallback): string
    {
        foreach ($keys as $key) {
            $val = getenv($key);
            if ($val !== false && $val !== '') {
                return $val;
            }
        }

        return $fallback ?? '';
    }
}
