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
            ->select('ci.*, p.name as product_name, p.price, p.shop_id, p.stock_quantity, s.shop_name, pi.image_url')
            ->join('products p', 'p.id = ci.product_id', 'left')
            ->join('shops s', 's.id = p.shop_id', 'left')
            ->join('product_images pi', 'pi.product_id = p.id AND pi.is_primary = 1', 'left')
            ->where('ci.cart_id', $cartId)
            ->get()->getResultArray();
    }
}
