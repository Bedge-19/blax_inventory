<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductVariantModel extends Model
{
    protected $table            = 'product_variants';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id',
        'name',
        'value',
        'sku_suffix',
        'stock_quantity',
        'price_override',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Fetch variants for a product ordered by name and value.
     */
    public function getByProductId(int $productId): array
    {
        return $this->where('product_id', $productId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
