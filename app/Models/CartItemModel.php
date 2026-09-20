<?php

namespace App\Models;

use CodeIgniter\Model;

class CartItemModel extends Model
{
    protected $table            = 'cart_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cart_id',
        'product_id',
        'variant_id',
        'variant_label',
        'quantity',
        'unit_price',
        'is_selected',
        'expires_at',
        'last_reminder_at',
        'reminder_count',
        'created_at',
        'updated_at',
    ];

    public function getCartItemsWithProducts(int $cartId)
    {
        return $this->db->table('cart_items ci')
            ->select('ci.*, p.name as product_name, COALESCE(ci.unit_price, p.price) as price, p.shop_id, COALESCE(pv.stock_quantity, p.stock_quantity) as stock_quantity, s.shop_name, s.offers_delivery, s.offers_pickup, pi.image_url')
            ->join('products p', 'p.id = ci.product_id', 'left')
            ->join('shops s', 's.id = p.shop_id', 'left')
            ->join('product_variants pv', 'pv.id = ci.variant_id', 'left')
            ->join('product_images pi', 'pi.product_id = p.id AND pi.is_primary = 1', 'left')
            ->where('ci.cart_id', $cartId)
            ->get()->getResultArray();
    }
}
