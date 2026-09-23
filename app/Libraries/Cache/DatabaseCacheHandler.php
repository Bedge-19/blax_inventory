<?php

namespace App\Libraries\Cache;

use CodeIgniter\Cache\Handlers\BaseHandler;

/**
 * Database-backed cache handler for CodeIgniter 4.
 *
 * Stores cache entries in a `ci_cache` table so they persist across
 * ephemeral serverless instances (e.g. Vercel). This handler is used
 * when CI4's built-in DatabaseHandler is not yet available.
 */
class DatabaseCacheHandler extends BaseHandler
{
    /**
     * @var string The database table name.
     */
    protected string $tableName = 'ci_cache';

    /**
     * @var string The DB group to use.
     */
    protected string $dbGroup = 'default';

    /**
     * @var \CodeIgniter\Database\BaseConnection|null
     */
    protected $db;

    public function __construct()
    {
        // Connection is lazy — we don't connect until first use.
    }

    /**
     * Get (or create) the database connection.
     */
    protected function getDb(): \CodeIgniter\Database\BaseConnection
    {
        if ($this->db === null) {
            $this->db = \Config\Database::connect($this->dbGroup);
        }

        return $this->db;
    }

    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        // Nothing to initialize.
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key)
    {
        try {
            $row = $this->getDb()->table($this->tableName)
                ->where('key', $key)
                ->get()
                ->getRowArray();

            if ($row === null) {
                return null;
            }

            // Check TTL expiration
            $createdAt = (int) $row['created_at'];
            $ttl       = (int) $row['ttl'];

            if ($ttl > 0 && (time() - $createdAt) > $ttl) {
                $this->delete($key);
                return null;
            }

            $value = unserialize($row['value']);

            return $value;
        } catch (\Throwable $e) {
            log_message('error', '[DatabaseCacheHandler] get() failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function save(string $key, $value, int $ttl = 60)
    {
        try {
            $serialized = serialize($value);
            $now        = time();

            $existing = $this->getDb()->table($this->tableName)
                ->where('key', $key)
                ->get()
                ->getRowArray();

            if ($existing !== null) {
                return (bool) $this->getDb()->table($this->tableName)
                    ->where('key', $key)
                    ->update([
                        'value'      => $serialized,
                        'ttl'        => $ttl,
                        'created_at' => $now,
                    ]);
            }

            return (bool) $this->getDb()->table($this->tableName)->insert([
                'key'        => $key,
                'value'      => $serialized,
                'ttl'        => $ttl,
                'created_at' => $now,
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[DatabaseCacheHandler] save() failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key)
    {
        try {
            return (bool) $this->getDb()->table($this->tableName)
                ->where('key', $key)
                ->delete();
        } catch (\Throwable $e) {
            log_message('error', '[DatabaseCacheHandler] delete() failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMatching(string $pattern)
    {
        try {
            $sqlPattern = str_replace('*', '%', $pattern);

            return (bool) $this->getDb()->table($this->tableName)
                ->like('key', $sqlPattern, 'none', null, true)
                ->delete();
        } catch (\Throwable $e) {
            log_message('error', '[DatabaseCacheHandler] deleteMatching() failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function increment(string $key, int $offset = 1)
    {
        $current = $this->get($key);

        if ($current === null) {
            $newVal = $offset;
            $this->save($key, $newVal, 60);
            return $newVal;
        }

        $newVal = (int) $current + $offset;
        $this->save($key, $newVal, 60);

        return $newVal;
    }

    /**
     * {@inheritDoc}
     */
    public function decrement(string $key, int $offset = 1)
    {
        return $this->increment($key, -$offset);
    }

    /**
     * {@inheritDoc}
     */
    public function clean()
    {
        try {
            $this->getDb()->table($this->tableName)->truncate();
            return true;
        } catch (\Throwable $e) {
            log_message('error', '[DatabaseCacheHandler] clean() failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheInfo()
    {
        try {
            return [
                'count' => $this->getDb()->table($this->tableName)->countAllResults(),
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getMetaData(string $key)
    {
        try {
            $row = $this->getDb()->table($this->tableName)
                ->where('key', $key)
                ->get()
                ->getRowArray();

            if ($row === null) {
                return false;
            }

            $createdAt = (int) $row['created_at'];
            $ttl       = (int) $row['ttl'];

            return [
                'expire' => $ttl > 0 ? $createdAt + $ttl : 0,
                'mtime'  => $createdAt,
                'data'   => unserialize($row['value']),
            ];
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isSupported(): bool
    {
        // Always supported — relies on the DB connection which is already configured.
        return true;
    }
}
