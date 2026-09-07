<?php

namespace App\Models;

use CodeIgniter\Model;

class FavoriteShopModel extends Model
{
    protected $table            = 'favorite_shops';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'shop_id',
        'created_at',
    ];

    public function getUserFavoriteShops(int $userId)
    {
        return $this->db->table('favorite_shops fs')
            ->select('fs.*, s.shop_name, s.slug, s.logo_url, s.rating_average, s.rating_count, s.description')
            ->join('shops s', 's.id = fs.shop_id', 'inner')
            ->where('fs.user_id', $userId)
            ->where('s.status', 'active')
            ->get()->getResultArray();
    }
}
