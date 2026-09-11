<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'shop_id',
        'category_id',
        'sku',
        'name',
        'description',
        'price',
        'compare_at_price',
        'stock_quantity',
        'low_stock_threshold',
        'status',
        'rating_average',
        'rating_count',
        'is_bestseller',
        'warranty_period',
        'return_policy_days',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getProductsWithDetails($shopId = null, $categoryId = null, $search = null)
    {
        $builder = $this->db->table('products p')
            ->select('p.*, s.shop_name, s.slug as shop_slug, c.name as category_name, pi.image_url')
            ->join('shops s', 's.id = p.shop_id', 'left')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('product_images pi', 'pi.product_id = p.id AND pi.is_primary = 1', 'left')
            ->where('p.deleted_at', null);

        if ($shopId) {
            $builder->where('p.shop_id', $shopId);
        }
        if ($categoryId) {
            $builder->where('p.category_id', $categoryId);
        }
        if ($search) {
            $builder->groupStart()
                ->like('p.name', $search)
                ->orLike('p.description', $search)
                ->groupEnd();
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Paginated global catalog listing across all active products,
     * preserving search + category filters across pages.
     *
     * @return array{products: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getGlobalProductsPaginated(?int $categoryId = null, ?string $search = null, int $perPage = 20, int $page = 1)
    {
        $this->builder()
            ->select('products.*, s.shop_name, s.slug as shop_slug, c.name as category_name, pi.image_url')
            ->join('shops s', 's.id = products.shop_id', 'left')
            ->join('categories c', 'c.id = products.category_id', 'left')
            ->join('product_images pi', 'pi.product_id = products.id AND pi.is_primary = 1', 'left')
            ->where('products.deleted_at', null)
            ->orderBy('products.id', 'ASC');

        if ($categoryId) {
            $this->builder()->where('products.category_id', $categoryId);
        }
        if ($search) {
            $this->builder()
                ->groupStart()
                ->like('products.name', $search)
                ->orLike('products.description', $search)
                ->groupEnd();
        }

        $products = $this->paginate($perPage, 'default', $page);

        return [
            'products' => $products ?: [],
            'pager'    => $this->pager,
        ];
    }

    /**
     * Paginated product listing for storefronts, with optional search.
     * Preserves the same joins/filters as getProductsWithDetails().
     *
     * @return array{products: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getProductsWithDetailsPaginated(int $shopId, ?string $search = null, int $perPage = 6, int $page = 1)
    {
        $this->builder()
            ->select('products.*, s.shop_name, s.slug as shop_slug, c.name as category_name, pi.image_url')
            ->join('shops s', 's.id = products.shop_id', 'left')
            ->join('categories c', 'c.id = products.category_id', 'left')
            ->join('product_images pi', 'pi.product_id = products.id AND pi.is_primary = 1', 'left')
            ->where('products.deleted_at', null)
            ->where('products.shop_id', $shopId)
            ->orderBy('products.id', 'ASC');

        if ($search) {
            $this->builder()
                ->groupStart()
                ->like('products.name', $search)
                ->orLike('products.description', $search)
                ->groupEnd();
        }

        $products = $this->paginate($perPage, 'default', $page);

        return [
            'products' => $products ?: [],
            'pager'    => $this->pager,
        ];
    }

    /**
     * Active products whose current stock is at or below their own
     * low-stock threshold, for a single shop (tenant-scoped).
     */
    public function getLowStockProducts(int $shopId, int $limit = 8): array
    {
        return $this->db->table('products p')
            ->select('p.id, p.sku, p.name, p.price, p.stock_quantity, p.low_stock_threshold, pi.image_url')
            ->join('product_images pi', 'pi.product_id = p.id AND pi.is_primary = 1', 'left')
            ->where('p.shop_id', $shopId)
            ->where('p.deleted_at', null)
            ->where('p.status', 'active')
            ->where('p.stock_quantity <= p.low_stock_threshold', null, false)
            ->orderBy('p.stock_quantity', 'ASC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /**
     * Count of active low-stock products for a single shop.
     */
    public function countLowStockProducts(int $shopId): int
    {
        return (int) $this->db->table('products')
            ->where('shop_id', $shopId)
            ->where('deleted_at', null)
            ->where('status', 'active')
            ->where('stock_quantity <= low_stock_threshold', null, false)
            ->countAllResults();
    }

    /**
     * Paginated inventory listing for one shop with search, category,
     * derived stock-status, SKU and bestseller filters.
     *
     * @return array{products: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getInventoryPaginated(
        int $shopId,
        ?string $search = null,
        ?int $categoryId = null,
        ?string $stockStatus = null,
        ?string $sku = null,
        ?bool $bestseller = null,
        int $perPage = 10,
        int $page = 1,
        string $group = 'default'
    ) {
        $this->builder()
            ->select('products.*, c.name as category_name, pi.image_url')
            ->join('categories c', 'c.id = products.category_id', 'left')
            ->join('product_images pi', 'pi.product_id = products.id AND pi.is_primary = 1', 'left')
            ->where('products.shop_id', $shopId)
            ->where('products.deleted_at', null)
            ->whereIn('products.status', ['active', 'draft'])
            ->orderBy('products.name', 'ASC');

        if ($categoryId) {
            $this->builder()->where('products.category_id', $categoryId);
        }
        if ($search !== null && $search !== '') {
            $this->builder()
                ->groupStart()
                ->like('products.name', $search)
                ->orLike('products.sku', $search)
                ->orLike('products.description', $search)
                ->groupEnd();
        }
        if ($sku !== null && $sku !== '') {
            $this->builder()->like('products.sku', $sku);
        }
        if ($bestseller === true) {
            $this->builder()->where('products.is_bestseller', 1);
        }
        if ($stockStatus === 'low') {
            $this->builder()
                ->where('products.stock_quantity >', 0)
                ->where('products.stock_quantity <= products.low_stock_threshold', null, false);
        } elseif ($stockStatus === 'out') {
            $this->builder()->where('products.stock_quantity', 0);
        } elseif ($stockStatus === 'in') {
            $this->builder()->where('products.stock_quantity > products.low_stock_threshold', null, false);
        }

        $products = $this->paginate($perPage, $group, $page);

        return [
            'products' => $products ?: [],
            'pager'    => $this->pager,
        ];
    }

    /**
     * Inventory summary metrics for a shop, computed from the active
     * (non-archived) product set.
     *
     * @return array{total_sku: int, low_stock: int, out_of_stock: int, inventory_value: float}
     */
    public function getInventorySummary(int $shopId): array
    {
        $row = $this->db->table('products')
            ->select("
                COUNT(*) AS total_sku,
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) AS out_of_stock,
                SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) AS low_stock,
                SUM(stock_quantity * price) AS inventory_value
            ")
            ->where('shop_id', $shopId)
            ->where('deleted_at', null)
            ->whereIn('status', ['active', 'draft'])
            ->get()->getRowArray();

        return [
            'total_sku'       => (int) ($row['total_sku'] ?? 0),
            'low_stock'       => (int) ($row['low_stock'] ?? 0),
            'out_of_stock'    => (int) ($row['out_of_stock'] ?? 0),
            'inventory_value' => (float) ($row['inventory_value'] ?? 0),
        ];
    }

    /**
     * Categories that actually have products in this shop, used for the
     * inventory category filter.
     */
    public function getShopCategories(int $shopId): array
    {
        return $this->db->table('categories c')
            ->select('c.id, c.name, c.slug')
            ->join('products p', 'p.category_id = c.id', 'inner')
            ->where('p.shop_id', $shopId)
            ->where('p.deleted_at', null)
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Source rows used to build AI embedding documents. Only columns that
     * already exist are selected.
     *
     * @param list<int> $productIds Empty = every non-deleted product
     *
     * @return list<array<string, mixed>>
     */
    public function getEmbeddingSources(array $productIds = [], int $limit = 0, int $offset = 0): array
    {
        $builder = $this->db->table('products p')
            ->select('p.id, p.name, p.description, p.sku, p.warranty_period, p.updated_at,
                      c.name as category_name, c.slug as category_slug, s.shop_name')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('shops s', 's.id = p.shop_id', 'left')
            ->where('p.deleted_at', null)
            ->orderBy('p.id', 'ASC');

        if ($productIds !== []) {
            $builder->whereIn('p.id', array_map('intval', $productIds));
        }
        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Product ids matched by the pre-existing keyword rules. Used to blend
     * literal matches into the semantic ranking so exact searches never regress.
     *
     * @return list<int>
     */
    public function getMatchingIdsByKeyword(string $search, ?int $categoryId = null, int $limit = 200): array
    {
        if (trim($search) === '') {
            return [];
        }

        $builder = $this->db->table('products')
            ->select('products.id')
            ->where('products.deleted_at', null)
            ->groupStart()
            ->like('products.name', $search)
            ->orLike('products.description', $search)
            ->groupEnd()
            ->orderBy('products.id', 'ASC')
            ->limit($limit);

        if ($categoryId) {
            $builder->where('products.category_id', $categoryId);
        }

        return array_map(
            static fn ($row) => (int) $row['id'],
            $builder->get()->getResultArray()
        );
    }

    /**
     * Paginated catalog listing restricted to a relevance-ordered id set.
     *
     * Mirrors getGlobalProductsPaginated() exactly - same joins, same
     * deterministic filters, same pager - and only changes the ordering so the
     * AI relevance sequence is preserved across pages.
     *
     * @param list<int> $orderedIds
     *
     * @return array{products: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getGlobalProductsByRelevance(array $orderedIds, ?int $categoryId = null, int $perPage = 20, int $page = 1)
    {
        $orderedIds = array_values(array_unique(array_map('intval', $orderedIds)));

        if ($orderedIds === []) {
            return ['products' => [], 'pager' => null];
        }

        $this->builder()
            ->select('products.*, s.shop_name, s.slug as shop_slug, c.name as category_name, pi.image_url')
            ->join('shops s', 's.id = products.shop_id', 'left')
            ->join('categories c', 'c.id = products.category_id', 'left')
            ->join('product_images pi', 'pi.product_id = products.id AND pi.is_primary = 1', 'left')
            ->where('products.deleted_at', null)
            ->whereIn('products.id', $orderedIds)
            ->orderBy('FIELD(products.id, ' . implode(',', $orderedIds) . ')', '', false);

        if ($categoryId) {
            $this->builder()->where('products.category_id', $categoryId);
        }

        $products = $this->paginate($perPage, 'default', $page);

        return [
            'products' => $products ?: [],
            'pager'    => $this->pager,
        ];
    }

    /**
     * Product cards for the AI assistant chat.
     *
     * Deliberately selects an explicit whitelist instead of products.* so the
     * chat JSON never leaks internal columns (sku, shop_id, low_stock_threshold,
     * deleted_at, ...) to the browser.
     *
     * Ordering is deterministic and decided here, not by the AI:
     *   - in-stock items always before out-of-stock ones
     *   - then price ascending / descending when the customer signalled a
     *     preference, otherwise AI relevance order
     *
     * @param list<int> $orderedIds Relevance-ordered ids from semantic search
     *
     * @return list<array{id:int, name:string, price:float, image_url:string|null, stock_quantity:int, shop_name:string|null}>
     */
    public function getChatProductCards(array $orderedIds, string $pricePref = 'none', int $limit = 4): array
    {
        $orderedIds = array_values(array_unique(array_filter(array_map('intval', $orderedIds))));

        if ($orderedIds === []) {
            return [];
        }

        $builder = $this->db->table('products p')
            ->select('p.id, p.name, p.price, p.stock_quantity, s.shop_name, pi.image_url')
            ->join('shops s', 's.id = p.shop_id', 'left')
            ->join('product_images pi', 'pi.product_id = p.id AND pi.is_primary = 1', 'left')
            ->where('p.deleted_at', null)
            ->whereIn('p.id', $orderedIds);

        // Availability first - a shopper should not be offered stock we lack.
        $builder->orderBy('(p.stock_quantity > 0) DESC', '', false);

        if ($pricePref === 'cheap') {
            $builder->orderBy('p.price', 'ASC');
        } elseif ($pricePref === 'premium') {
            $builder->orderBy('p.price', 'DESC');
        } else {
            $builder->orderBy('FIELD(p.id, ' . implode(',', $orderedIds) . ')', '', false);
        }

        $rows = $builder->limit(max(1, $limit))->get()->getResultArray();

        return array_map(static fn ($r) => [
            'id'             => (int) $r['id'],
            'name'           => (string) $r['name'],
            'price'          => (float) $r['price'],
            'stock_quantity' => (int) $r['stock_quantity'],
            'shop_name'      => $r['shop_name'] !== null ? (string) $r['shop_name'] : null,
        ], $rows);
    }
}
