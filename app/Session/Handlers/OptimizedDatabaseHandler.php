<?php

declare(strict_types=1);

namespace App\Session\Handlers;

use CodeIgniter\Session\Handlers\DatabaseHandler;
use ReturnTypeWillChange;

/**
 * High-performance database session handler optimized for serverless environments
 * (Vercel) connecting to cloud databases (TiDB Cloud / MySQL).
 *
 * Performance optimizations:
 * 1. Zero lock overhead: Strips blocking GET_LOCK() / RELEASE_LOCK() network roundtrips.
 *    MySQL/TiDB row operations on PRIMARY KEY (id) are inherently atomic.
 * 2. Dirty Checking: Skips UPDATE queries on read-only requests when session data is unchanged.
 * 3. Guest Protection: Avoids inserting empty session records for anonymous guests.
 * 4. Lazy Timestamp Refresh: Only touches the timestamp column if older than 300 seconds.
 */
class OptimizedDatabaseHandler extends DatabaseHandler
{
    /**
     * Timestamp when the session row was read or last updated in this request.
     */
    protected int $lastTimestamp = 0;

    /**
     * Tracks whether the session was authenticated when read, to detect state changes.
     */
    protected bool $wasAuthenticated = false;

    /**
     * Disable blocking user-level locks to save 2 network roundtrips per HTTP request.
     */
    protected function lockSession(string $sessionID): bool
    {
        $this->lock = true;
        return true;
    }

    /**
     * Release lock no-op.
     */
    protected function releaseLock(): bool
    {
        $this->lock = false;
        return true;
    }

    /**
     * Reads session data from the database.
     *
     * @param string $id
     * @return false|string
     */
    #[ReturnTypeWillChange]
    public function read($id)
    {
        if (! isset($this->sessionID)) {
            $this->sessionID = $id;
        }

        $builder = $this->db->table($this->table)
            ->select('data, UNIX_TIMESTAMP(timestamp) as ts')
            ->where('id', $this->idPrefix . $id);

        if ($this->matchIP) {
            $builder->where('ip_address', $this->ipAddress);
        }

        $result = $builder->get()->getRow();

        if ($result === null) {
            $this->rowExists        = false;
            $this->fingerprint      = md5('');
            $this->lastTimestamp    = time();
            $this->wasAuthenticated = false;
            return '';
        }

        $this->lastTimestamp = isset($result->ts) ? (int) $result->ts : time();
        $data = is_bool($result->data) ? '' : $this->decodeData($result->data);

        $this->fingerprint      = md5($data);
        $this->rowExists        = true;
        $this->wasAuthenticated = ($data !== '' && (str_contains($data, 'isLoggedIn') || str_contains($data, 'user_id')));

        return $data;
    }

    /**
     * Writes session data to storage with intelligent dirty-checking.
     * Session writes on auth state changes (login, signup, logout) are unconditional.
     *
     * @param string $id
     * @param string $data
     */
    public function write($id, $data): bool
    {
        if ($this->sessionID !== $id) {
            $this->rowExists        = false;
            $this->sessionID        = $id;
            $this->fingerprint      = md5('');
            $this->wasAuthenticated = false;
        }

        $isUnchanged = ($this->fingerprint === md5($data));
        $now = time();

        $isAuthenticated   = ($data !== '' && (str_contains($data, 'isLoggedIn') || str_contains($data, 'user_id')));
        $isAuthStateChange = ($isAuthenticated !== $this->wasAuthenticated);

        // Optimization: Do NOT write empty sessions to DB for anonymous visitors
        // Never skip if session is authenticated
        if (! $this->rowExists && empty($data) && ! $isAuthenticated) {
            return true;
        }

        // Optimization: Skip database write on read-only requests if data is unchanged
        // and timestamp was refreshed recently (within 5 minutes).
        // NEVER skip write on login/signup authentication state changes or when an
        // authenticated session lacks a confirmed row in DB.
        if (! $isAuthStateChange && $this->rowExists && $isUnchanged && ($now - $this->lastTimestamp) < 300) {
            return true;
        }

        $fullId = $this->idPrefix . $id;
        $preparedData = $this->prepareData($data);
        $escapedTable = $this->db->protectIdentifiers($this->table, true);

        try {
            $sql = "INSERT INTO {$escapedTable} (`id`, `ip_address`, `timestamp`, `data`)
                    VALUES (?, ?, NOW(), ?)
                    ON DUPLICATE KEY UPDATE `ip_address` = VALUES(`ip_address`), `timestamp` = NOW(), `data` = VALUES(`data`)";

            $this->db->query($sql, [$fullId, $this->ipAddress, $preparedData]);

            $this->fingerprint      = md5($data);
            $this->rowExists        = true;
            $this->lastTimestamp    = $now;
            $this->wasAuthenticated = $isAuthenticated;

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'Session write failed: ' . $e->getMessage());
            return $this->fail();
        }
    }

    /**
     * Closes the session without lock queries.
     */
    public function close(): bool
    {
        $this->lock = false;
        return true;
    }

    /**
     * Destroys session row.
     */
    public function destroy($id): bool
    {
        $builder = $this->db->table($this->table)->where('id', $this->idPrefix . $id);

        if ($this->matchIP) {
            $builder->where('ip_address', $this->ipAddress);
        }

        if (! $builder->delete()) {
            return $this->fail();
        }

        $this->rowExists        = false;
        $this->fingerprint      = md5('');
        $this->wasAuthenticated = false;

        return true;
    }
}
