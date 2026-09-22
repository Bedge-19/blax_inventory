<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table            = 'categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'parent_id',
        'name',
        'slug',
        'image_url',
        'sort_order',
        'view_count',
    ];

    /**
     * Request-level memoized cache.
     * @var array<string, array>
     */
    private static array $requestCache = [];

    /**
     * Clear category cache.
     */
    public static function clearCache(): void
    {
        self::$requestCache = [];
        try {
            cache()->delete('blax_all_categories_sorted');
            cache()->delete('blax_trending_categories_3');
        } catch (\Throwable $e) {}
    }

    /**
     * Retrieve all categories sorted by sort_order with multi-tier caching (600s TTL).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllCached(): array
    {
        if (isset(self::$requestCache['all_categories'])) {
            return self::$requestCache['all_categories'];
        }

        try {
            $cached = cache('blax_all_categories_sorted');
            if (is_array($cached)) {
                self::$requestCache['all_categories'] = $cached;
                return $cached;
            }
        } catch (\Throwable $e) {}

        $categories = $this->orderBy('sort_order', 'ASC')->findAll();

        try {
            cache()->save('blax_all_categories_sorted', $categories, 600);
        } catch (\Throwable $e) {}

        self::$requestCache['all_categories'] = $categories;
        return $categories;
    }

    /**
     * Paginated category listing ordered by sort_order.
     *
     * @return array{categories: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getCategoriesPaginated(int $perPage = 8, int $page = 1)
    {
        $this->builder()->orderBy('sort_order', 'ASC');

        $categories = $this->paginate($perPage, 'default', $page);

        return [
            'categories' => $categories ?: [],
            'pager'      => $this->pager,
        ];
    }

    /**
     * Trending categories ranked by view_count, capped at $limit.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTrendingCategories(int $limit = 3)
    {
        $cacheKey = 'blax_trending_categories_' . $limit;
        if (isset(self::$requestCache[$cacheKey])) {
            return self::$requestCache[$cacheKey];
        }

        try {
            $cached = cache($cacheKey);
            if (is_array($cached)) {
                self::$requestCache[$cacheKey] = $cached;
                return $cached;
            }
        } catch (\Throwable $e) {}

        $result = $this->db->table('categories c')
            ->select('c.id, c.parent_id, c.name, c.slug, c.image_url, c.sort_order, c.view_count, COUNT(p.id) AS product_count')
            ->join('products p', 'p.category_id = c.id', 'left')
            ->groupBy('c.id, c.parent_id, c.name, c.slug, c.image_url, c.sort_order, c.view_count')
            ->orderBy('c.view_count', 'DESC')
            ->orderBy('product_count', 'DESC')
            ->orderBy('c.sort_order', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        try {
            cache()->save($cacheKey, $result, 300);
        } catch (\Throwable $e) {}

        self::$requestCache[$cacheKey] = $result;
        return $result;
    }
}
