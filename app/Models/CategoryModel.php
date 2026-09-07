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
     * Trending categories ranked by the number of products they contain,
     * capped at $limit. Returns real categories with a derived product_count.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTrendingCategories(int $limit = 3)
    {
        return $this->db->table('categories c')
            ->select('c.*, COUNT(p.id) AS product_count')
            ->join('products p', 'p.category_id = c.id', 'left')
            ->groupBy('c.id')
            ->orderBy('product_count', 'DESC')
            ->orderBy('c.sort_order', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
}
