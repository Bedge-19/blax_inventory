<?php

namespace App\Models;

use CodeIgniter\Model;

class SiteContentModel extends Model
{
    protected $table            = 'site_contents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'page',
        'content_key',
        'label',
        'content_type',
        'text_value',
        'image_url',
        'sort_order',
        'updated_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByPage(string $page): array
    {
        return $this->where('page', $page)->orderBy('sort_order', 'ASC')->orderBy('content_key', 'ASC')->findAll();
    }

    public function getContentMap(string $page): array
    {
        $rows = $this->getByPage($page);
        $map = [];
        foreach ($rows as $r) {
            $map[$r['content_key']] = $r;
        }
        return $map;
    }

    public function getAllGrouped(): array
    {
        $rows = $this->orderBy('page', 'ASC')->orderBy('sort_order', 'ASC')->findAll();
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['page']][] = $r;
        }
        return $grouped;
    }

    /**
     * Request-level memoized cache.
     * @var array<string, array>
     */
    private static array $requestCache = [];

    /**
     * Clear site content caches (both in-memory and local disk cache).
     */
    public static function clearCache(): void
    {
        self::$requestCache = [];
        try {
            cache()->delete('blax_site_contents_home_map');
            cache()->delete('blax_site_contents_all_key_map');
            cache()->delete('blax_platform_deduction_percent');
        } catch (\Throwable $e) {
            // Ignore cache engine failure
        }
    }

    /**
     * Fetch homepage content map cleanly combining home_banners,
     * announcement_bar, and footer_info, ensuring home_banners entries
     * take precedence and avoid collisions with other pages (e.g. printing_services, categories).
     *
     * Cached in local /tmp cache for 600s + request memoization.
     *
     * @return array<string, array>
     */
    public function getHomeContentMap(): array
    {
        if (isset(self::$requestCache['home_map'])) {
            return self::$requestCache['home_map'];
        }

        try {
            $cached = cache('blax_site_contents_home_map');
            if (is_array($cached)) {
                self::$requestCache['home_map'] = $cached;
                return $cached;
            }
        } catch (\Throwable $e) {}

        // First load global components (announcement_bar, footer_info)
        $globalRows = $this->whereIn('page', ['announcement_bar', 'footer_info'])
                           ->orderBy('sort_order', 'ASC')
                           ->findAll();
        $map = [];
        foreach ($globalRows as $r) {
            $map[$r['content_key']] = $r;
        }

        // Then overlay home_banners so homepage-specific keys are never overwritten
        $homeRows = $this->where('page', 'home_banners')
                         ->orderBy('sort_order', 'ASC')
                         ->findAll();
        foreach ($homeRows as $r) {
            $map[$r['content_key']] = $r;
        }

        try {
            cache()->save('blax_site_contents_home_map', $map, 600);
        } catch (\Throwable $e) {}

        self::$requestCache['home_map'] = $map;
        return $map;
    }

    /**
     * Fetch all site content entries indexed by content_key.
     * home_banners takes precedence over generic/other page keys.
     *
     * Cached in local /tmp cache for 600s + request memoization.
     *
     * @return array<string, array>
     */
    public function getAllKeyMap(): array
    {
        if (isset(self::$requestCache['all_key_map'])) {
            return self::$requestCache['all_key_map'];
        }

        try {
            $cached = cache('blax_site_contents_all_key_map');
            if (is_array($cached)) {
                self::$requestCache['all_key_map'] = $cached;
                return $cached;
            }
        } catch (\Throwable $e) {}

        $rows = $this->orderBy('sort_order', 'ASC')->findAll();
        $map = [];
        foreach ($rows as $r) {
            if ($r['page'] !== 'home_banners') {
                $map[$r['content_key']] = $r;
            }
        }
        foreach ($rows as $r) {
            if ($r['page'] === 'home_banners') {
                $map[$r['content_key']] = $r;
            }
        }

        try {
            cache()->save('blax_site_contents_all_key_map', $map, 600);
        } catch (\Throwable $e) {}

        self::$requestCache['all_key_map'] = $map;
        return $map;
    }

    public function getPlatformDeductionPercent(): float
    {
        if (isset(self::$requestCache['deduction_percent'])) {
            return self::$requestCache['deduction_percent'];
        }

        try {
            $cached = cache('blax_platform_deduction_percent');
            if (is_numeric($cached)) {
                $val = (float) $cached;
                self::$requestCache['deduction_percent'] = $val;
                return $val;
            }
        } catch (\Throwable $e) {}

        $row = $this->where('page', 'platform')->where('content_key', 'withdrawal_deduction_percent')->first();
        $rate = 3.00;
        if ($row && is_numeric($row['text_value'])) {
            $rate = (float) $row['text_value'];
        }

        try {
            cache()->save('blax_platform_deduction_percent', $rate, 600);
        } catch (\Throwable $e) {}

        self::$requestCache['deduction_percent'] = $rate;
        return $rate;
    }

    public function setPlatformDeductionPercent(float $percent): bool
    {
        self::clearCache();
        $percent = round(max(0, min(50, $percent)), 2);
        $row = $this->where('page', 'platform')->where('content_key', 'withdrawal_deduction_percent')->first();
        if ($row) {
            return (bool) $this->update($row['id'], [
                'text_value' => number_format($percent, 2, '.', ''),
            ]);
        }
        return (bool) $this->insert([
            'page'         => 'platform',
            'content_key'  => 'withdrawal_deduction_percent',
            'label'        => 'Withdrawal Deduction Percentage',
            'content_type' => 'text',
            'text_value'   => number_format($percent, 2, '.', ''),
            'sort_order'   => 1,
        ]);
    }
}
