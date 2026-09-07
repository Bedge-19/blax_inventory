<?php

namespace App\Services;

use App\Models\ReviewModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\ShopModel;
use RuntimeException;

/**
 * Business logic for product and shop ratings/reviews.
 *
 * - Verified purchase: only orders with status 'delivered' or 'completed'
 * - One rating per user per product (via unique constraint)
 * - One rating per user per shop (via unique constraint)
 * - Recalculates average rating and count on upsert/delete
 */
class ReviewService
{
    private ReviewModel $reviews;
    private OrderModel $orders;
    private ProductModel $products;
    private ShopModel $shops;

    /** Order statuses that qualify as "verified purchase" (strict: delivered/completed only) */
    private const VERIFIED_STATUSES = ['delivered', 'completed'];

    public function __construct(
        ?ReviewModel $reviews = null,
        ?OrderModel $orders = null,
        ?ProductModel $products = null,
        ?ShopModel $shops = null
    ) {
        $this->reviews  = $reviews ?? new ReviewModel();
        $this->orders   = $orders ?? new OrderModel();
        $this->products = $products ?? new ProductModel();
        $this->shops    = $shops ?? new ShopModel();
    }

    /**
     * Check if a user has a verified purchase of a product from a shop.
     *
     * @return bool True if user has an order with the product where status is 'delivered' or 'completed'
     */
    public function isVerifiedBuyer(int $userId, int $productId, int $shopId): bool
    {
        $row = $this->orders
            ->select('orders.id')
            ->join('order_items', 'order_items.order_id = orders.id', 'inner')
            ->where('orders.customer_id', $userId)
            ->where('order_items.product_id', $productId)
            ->where('orders.shop_id', $shopId)
            ->whereIn('orders.status', self::VERIFIED_STATUSES)
            ->first();

        return $row !== null;
    }

    /**
     * Check if a user has a verified purchase from a shop (any product).
     *
     * @return bool True if user has any order from the shop with status 'delivered' or 'completed'
     */
    public function hasVerifiedShopPurchase(int $userId, int $shopId): bool
    {
        $row = $this->orders
            ->where('customer_id', $userId)
            ->where('shop_id', $shopId)
            ->whereIn('status', self::VERIFIED_STATUSES)
            ->first();

        return $row !== null;
    }

    /**
     * Get an existing review by the user for a product or shop.
     *
     * @return array|null
     */
    public function getUserReview(int $userId, ?int $productId = null, ?int $shopId = null): ?array
    {
        $builder = $this->reviews->where('user_id', $userId);

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
     * Save (insert or update) a product review.
     *
     * @throws RuntimeException if validation fails or purchase not verified
     *
     * @return array{id:int, rating:int, comment:string|null, created_at:string, updated_at:string|null}
     */
    public function saveProductReview(int $userId, int $productId, int $shopId, int $rating, ?string $comment): array
    {
        if ($rating < 1 || $rating > 5) {
            throw new RuntimeException('Rating must be between 1 and 5.');
        }

        // Verify the product exists and belongs to the shop
        $product = $this->products->where('id', $productId)->where('shop_id', $shopId)->first();
        if (!$product) {
            throw new RuntimeException('Product not found in this shop.');
        }

        // Verify purchase
        if (!$this->isVerifiedBuyer($this->getCurrentUserId(), $productId, $shopId)) {
            throw new RuntimeException('You can only review products you have received (order status: delivered or completed).');
        }

        $comment = $comment !== null ? trim($comment) : null;
        if ($comment !== '' && $comment !== null && mb_strlen($comment) > 2000) {
            throw new RuntimeException('Review text is too long (max 2000 characters).');
        }

        $now = date('Y-m-d H:i:s');

        $data = [
            'user_id'    => $this->getCurrentUserId(),
            'product_id' => $productId,
            'shop_id'    => $shopId,
            'rating'     => $rating,
            'comment'    => $comment,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $review = $this->getUserReview($this->getCurrentUserId(), $productId, null);
            if ($review) {
                // Update existing
                $this->reviews->update($review['id'], $data);
                $reviewId = $review['id'];
            } else {
                // Insert new
                $data['user_id']    = $this->getCurrentUserId();
                $data['product_id'] = $productId;
                $data['shop_id']    = $shopId;
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->reviews->insert($data);
                $reviewId = $this->reviews->getInsertID();
            }
        } catch (\Throwable $e) {
            // Duplicate key violation means race condition; fetch and update
            $existing = $this->getUserReview($this->getCurrentUserId(), $productId);
            if ($existing) {
                $this->reviews->update($existing['id'], $data);
                $reviewId = $existing['id'];
            } else {
                throw new RuntimeException('Failed to save review: ' . $e->getMessage());
            }
        }

        // Recalculate product rating
        $this->recalculateProductRating($productId);

        return $this->reviews->find($reviewId);
    }

    /**
     * Save (insert or update) a shop review.
     *
     * @return array{id:int, rating:int, comment:string|null, created_at:string, updated_at:string|null}
     */
    public function saveShopReview(int $userId, int $shopId, int $rating, ?string $comment): array
    {
        if ($rating < 1 || $rating > 5) {
            throw new RuntimeException('Rating must be between 1 and 5.');
        }

        // Verify shop exists
        $shop = $this->shops->find($shopId);
        if (!$shop) {
            throw new RuntimeException('Shop not found.');
        }

        // Verify purchase from this shop
        if (!$this->hasVerifiedShopPurchase($this->getCurrentUserId(), $shopId)) {
            throw new RuntimeException('You can only review shops you have purchased from (order status: delivered or completed).');
        }

        $comment = $comment !== null ? trim($comment) : null;
        if ($comment !== '' && $comment !== null && mb_strlen($comment) > 2000) {
            throw new RuntimeException('Review text is too long (max 2000 characters).');
        }

        $data = [
            'user_id'  => $this->getCurrentUserId(),
            'shop_id'  => $shopId,
            'rating'   => $rating,
            'comment'  => $comment,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $review = $this->getUserReview($this->getCurrentUserId(), null, $shopId);
            if ($review) {
                $this->reviews->update($review['id'], $data);
                $reviewId = $review['id'];
            } else {
                $data['user_id']    = $this->getCurrentUserId();
                $data['shop_id']    = $shopId;
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->reviews->insert($data);
                $reviewId = $this->reviews->getInsertID();
            }
        } catch (\Throwable $e) {
            $existing = $this->getUserReview($this->getCurrentUserId(), null, $shopId);
            if ($existing) {
                $this->reviews->update($existing['id'], $data);
                $reviewId = $existing['id'];
            } else {
                throw new RuntimeException('Failed to save review: ' . $e->getMessage());
            }
        }

        $this->recalculateShopRating($shopId);

        return $this->reviews->find($reviewId);
    }

    /**
     * Get reviews for a product (with user info).
     *
     * @return list<array>
     */
    public function getProductReviews(int $productId, int $limit = 10, int $offset = 0): array
    {
        return $this->reviews
            ->select('reviews.*, users.first_name, users.last_name')
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
        return (int) $this->reviews->where('product_id', $productId)->countAllResults();
    }

    /**
     * Get reviews for a shop (with user info).
     *
     * @return list<array>
     */
    public function getShopReviews(int $shopId, int $limit = 10, int $offset = 0): array
    {
        return $this->reviews
            ->select('reviews.*, users.first_name, users.last_name')
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
        return (int) $this->reviews->where('shop_id', $shopId)->countAllResults();
    }

    /**
     * Recalculate product average rating and count.
     */
    public function recalculateProductRating(int $productId): void
    {
        $row = $this->reviews
            ->select('AVG(rating) as avg_rating, COUNT(*) as cnt')
            ->where('product_id', $productId)
            ->first();

        $avg   = $row && $row['avg_rating'] !== null ? round((float) $row['avg_rating'], 2) : 0.00;
        $count = $row ? (int) $row['cnt'] : 0;

        $this->products->update($productId, [
            'rating_average' => $avg,
            'rating_count'   => $count,
        ]);
    }

    /**
     * Recalculate shop average rating and count.
     */
    public function recalculateShopRating(int $shopId): void
    {
        $row = $this->reviews
            ->select('AVG(rating) as avg_rating, COUNT(*) as cnt')
            ->where('shop_id', $shopId)
            ->first();

        $avg   = $row && $row['avg_rating'] !== null ? round((float) $row['avg_rating'], 2) : 0.00;
        $count = $row ? (int) $row['cnt'] : 0;

        $this->shops->update($shopId, [
            'rating_average' => $avg,
            'rating_count'   => $count,
        ]);
    }

    /**
     * Get the current logged-in user's ID.
     * Uses session like the existing controllers do.
     */
    private function getCurrentUserId(): int
    {
        $session = session();
        $userId  = $session->get('user_id');
        if (!$userId) {
            throw new RuntimeException('You must be logged in to submit a review.');
        }
        return (int) $userId;
    }
}