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
        // Environment variable resolution for Railway, Vercel, and local
        // -----------------------------------------------------------
        // Supports Railway native MySQL variables (MYSQLHOST, MYSQLPORT, etc.),
        // Railway MySQL URLs (MYSQL_URL), CI4 dotted keys (database.default.*),
        // and uppercase/Vercel keys (DATABASE_DEFAULT_*).
        // -----------------------------------------------------------

        // 1. Connection URL support (Railway MySQL_URL / DATABASE_URL)
        $dbUrl = $this->firstEnv(['MYSQL_URL', 'DATABASE_URL', 'JAWSDB_URL', 'CLEARDB_DATABASE_URL'], '');
        if ($dbUrl !== '') {
            $parsed = parse_url($dbUrl);
            if ($parsed !== false) {
                if (!empty($parsed['host'])) {
                    $this->default['hostname'] = $parsed['host'];
                }
                if (!empty($parsed['user'])) {
                    $this->default['username'] = urldecode($parsed['user']);
                }
                if (isset($parsed['pass'])) {
                    $this->default['password'] = urldecode($parsed['pass']);
                }
                if (!empty($parsed['port'])) {
                    $this->default['port'] = (int) $parsed['port'];
                }
                if (!empty($parsed['path'])) {
                    $this->default['database'] = ltrim($parsed['path'], '/');
                }
            }
        }

        // 2. Individual parameter overrides
        $this->default['hostname'] = $this->firstEnv(
            ['MYSQLHOST', 'MYSQL_HOST', 'DATABASE_DEFAULT_HOSTNAME', 'database.default.hostname', 'database_default_hostname', 'DB_HOST', 'DB_HOSTNAME'],
            $this->default['hostname'] ?: 'localhost'
        );

        $this->default['username'] = $this->firstEnv(
            ['MYSQLUSER', 'MYSQL_USER', 'DATABASE_DEFAULT_USERNAME', 'database.default.username', 'database_default_username', 'DB_USER', 'DB_USERNAME'],
            $this->default['username'] ?: ''
        );

        $this->default['password'] = $this->firstEnv(
            ['MYSQLPASSWORD', 'MYSQL_PASSWORD', 'DATABASE_DEFAULT_PASSWORD', 'database.default.password', 'database_default_password', 'DB_PASS', 'DB_PASSWORD'],
            $this->default['password'] ?: ''
        );

        $this->default['database'] = $this->firstEnv(
            ['MYSQLDATABASE', 'MYSQL_DATABASE', 'DATABASE_DEFAULT_DATABASE', 'database.default.database', 'database_default_database', 'DB_DATABASE', 'DB_NAME'],
            $this->default['database'] ?: 'blax_marketplace'
        );

        $driver = $this->firstEnv(
            ['DATABASE_DEFAULT_DBDRIVER', 'database.default.DBDriver', 'database_default_dbdriver', 'DB_DRIVER'],
            ''
        );
        if ($driver !== '') {
            $this->default['DBDriver'] = $driver;
        }

        $prefix = $this->firstEnv(
            ['DATABASE_DEFAULT_DBPREFIX', 'database.default.DBPrefix', 'database_default_dbprefix', 'DB_PREFIX'],
            null
        );
        if ($prefix !== null) {
            $this->default['DBPrefix'] = $prefix;
        }

        $charset = $this->firstEnv(
            ['DATABASE_DEFAULT_CHARSET', 'database.default.charset', 'database_default_charset', 'DB_CHARSET'],
            ''
        );
        if ($charset !== '') {
            $this->default['charset'] = $charset;
        }

        $collat = $this->firstEnv(
            ['DATABASE_DEFAULT_DBCOLLAT', 'database.default.DBCollat', 'database_default_dbcollat', 'DB_COLLATION'],
            ''
        );
        if ($collat !== '') {
            $this->default['DBCollat'] = $collat;
        }

        // Port: auto-detect TiDB Cloud (port 4000) vs standard MySQL (3306)
        $envPort = $this->firstEnv(
            ['MYSQLPORT', 'MYSQL_PORT', 'DATABASE_DEFAULT_PORT', 'database.default.port', 'database_default_port', 'DB_PORT'],
            ''
        );
        if ($envPort !== '') {
            $this->default['port'] = (int) $envPort;
        } elseif (str_contains((string) $this->default['hostname'], 'tidbcloud.com')) {
            $this->default['port'] = 4000;
        }

        // Enable compression for TiDB Cloud or any Vercel deployment
        if (str_contains((string) $this->default['hostname'], 'tidbcloud.com') || $this->readEnv('VERCEL') === '1') {
            $this->default['compress'] = true;
        }

        // -----------------------------------------------------------
        // Production safety: disable DBDebug so DB errors become
        // logged/handled failures instead of raw exceptions dumped
        // to visitors. pConnect remains false (correct for serverless).
        // -----------------------------------------------------------
        $ciEnv = $this->firstEnv(['CI_ENVIRONMENT'], ENVIRONMENT);
        if ($ciEnv === 'production' || $this->readEnv('VERCEL') === '1') {
            $this->default['DBDebug'] = false;
        }
    }

    /**
     * Read an environment variable safely across CodeIgniter's env(),
     * $_ENV, $_SERVER, and getenv().
     */
    private function readEnv(string $key): ?string
    {
        if (function_exists('env')) {
            $val = env($key);
            if ($val !== null && $val !== '') {
                return (string) $val;
            }
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string) $_ENV[$key];
        }

        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return (string) $_SERVER[$key];
        }

        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return (string) $val;
        }

        return null;
    }

    /**
     * Return the first non-empty environment variable from the list of keys,
     * or $fallback if none are set.
     */
    private function firstEnv(array $keys, ?string $fallback = null): string
    {
        foreach ($keys as $key) {
            $val = $this->readEnv($key);
            if ($val !== null && $val !== '') {
                return $val;
            }
        }

        return $fallback ?? '';
    }
}
