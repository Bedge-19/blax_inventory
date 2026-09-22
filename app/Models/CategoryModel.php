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
        return $this->db->table('categories c')
            ->select('c.id, c.parent_id, c.name, c.slug, c.image_url, c.sort_order, c.view_count, COUNT(p.id) AS product_count')
            ->join('products p', 'p.category_id = c.id', 'left')
            ->groupBy('c.id, c.parent_id, c.name, c.slug, c.image_url, c.sort_order, c.view_count')
            ->orderBy('c.view_count', 'DESC')
            ->orderBy('product_count', 'DESC')
            ->orderBy('c.sort_order', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
}
