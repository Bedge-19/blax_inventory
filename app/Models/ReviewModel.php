<?php

namespace App\Models;

use CodeIgniter\Model;

class ReviewModel extends Model
{
    protected $table            = 'reviews';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'product_id',
        'shop_id',
        'order_id',
        'rating',
        'comment',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get an existing review by user for a product or shop.
     *
     * @return array|null
     */
    public function getUserReview(int $userId, ?int $productId = null, ?int $shopId = null): ?array
    {
        $builder = $this->where('user_id', $userId);

        if ($productId !== null) {
            $builder->where('product_id', $productId);
        } elseif ($shopId !== null) {
            $builder->where('shop_id', $shopId);
        } else {
            return null;
        }

        return $builder->first();
    }

    /**
     * Get reviews for a product with user info.
     *
     * @return list<array>
     */
    public function getProductReviews(int $productId, int $limit = 10, int $offset = 0): array
    {
        return $this->select('reviews.*, users.first_name, users.last_name')
            ->join('users', 'users.id = reviews.user_id', 'left')
            ->where('product_id', $productId)
            ->orderBy('reviews.created_at', 'DESC')
            ->limit($limit, $offset)
            ->findAll();
    }

    /**
     * Count total reviews for a product.
     */
    public function countProductReviews(int $productId): int
    {
        return (int) $this->where('product_id', $productId)->countAllResults();
    }

    /**
     * Get reviews for a shop with user info.
     *
     * @return list<array>
     */
    public function getShopReviews(int $shopId, int $limit = 10, int $offset = 0): array
    {
        return $this->select('reviews.*, users.first_name, users.last_name')
            ->join('users', 'users.id = reviews.user_id', 'left')
            ->where('shop_id', $shopId)
            ->orderBy('reviews.created_at', 'DESC')
            ->limit($limit, $offset)
            ->findAll();
    }

    /**
     * Count total reviews for a shop.
     */
    public function countShopReviews(int $shopId): int
    {
        return (int) $this->where('shop_id', $shopId)->countAllResults();
    }
}