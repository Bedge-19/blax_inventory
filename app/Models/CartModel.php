<?php

namespace App\Models;

use CodeIgniter\Model;

class CartModel extends Model
{
    protected $table            = 'carts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'created_at',
        'updated_at',
    ];

    public function getOrCreateCart(int $userId)
    {
        $cart = $this->where('user_id', $userId)->first();
        if (!$cart) {
            $id = $this->insert(['user_id' => $userId]);
            $cart = $this->find($id);
        }
        return $cart;
    }
}
