<?php

namespace App\Models;

use CodeIgniter\Model;

class ShopModel extends Model
{
    protected $table            = 'shops';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'owner_id',
        'shop_name',
        'slug',
        'description',
        'logo_url',
        'address_line',
        'street',
        'barangay',
        'city',
        'province',
        'business_permit_url',
        'plan',
        'status',
        'rejection_reason',
        'verified_at',
        'offers_printing',
        'rating_average',
        'rating_count',
        'gcash_number',
        'gcash_account_name',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getShopByOwnerId(int $ownerId)
    {
        return $this->where('owner_id', $ownerId)->first();
    }

    /**
     * Resolve the owner's verification state without assuming one owner has
     * only one shop. Existing data permits multiple shops per owner.
     *
     * @return array{state:string, shop:array|null}
     */
    public function getOwnerVerificationState(int $ownerId): array
    {
        $shops = $this->where('owner_id', $ownerId)
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        foreach ($shops as $shop) {
            if (($shop['status'] ?? '') === 'active') {
                return ['state' => 'active', 'shop' => $shop];
            }
        }

        foreach (['pending', 'rejected', 'suspended'] as $state) {
            foreach ($shops as $shop) {
                if (($shop['status'] ?? '') === $state) {
                    return ['state' => $state, 'shop' => $shop];
                }
            }
        }

        return ['state' => 'pending', 'shop' => null];
    }

    /**
     * Highest-rated active shops, sorted by rating (then review count),
     * capped at $limit.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMostRatedShops(int $limit = 5)
    {
        return $this->where('status', 'active')
            ->orderBy('rating_average', 'DESC')
            ->orderBy('rating_count', 'DESC')
            ->findAll($limit);
    }

    /**
     * Paginated listing of all active shops, with optional search and sorting.
     * Category filter uses only real shop fields (offers_printing / plan).
     *
     * @return array{shops: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getAllShopsPaginated(?string $search = null, ?string $category = null, ?string $sort = null, int $perPage = 12, int $page = 1)
    {
        $this->builder()->where('status', 'active');

        if ($category === 'printing') {
            $this->builder()->where('offers_printing', 1);
        } elseif ($category === 'enterprise') {
            $this->builder()->where('plan', 'enterprise');
        }

        if ($search !== null && $search !== '') {
            $this->builder()
                ->groupStart()
                ->like('shop_name', $search)
                ->orLike('description', $search)
                ->groupEnd();
        }

        if ($sort === 'most-recent') {
            $this->builder()->orderBy('created_at', 'DESC');
        } else {
            $this->builder()->orderBy('rating_average', 'DESC')->orderBy('rating_count', 'DESC');
        }

        $shops = $this->paginate($perPage, 'default', $page);

        return [
            'shops' => $shops ?: [],
            'pager' => $this->pager,
        ];
    }

    /**
     * Paginated listing of active shops that offer printing services,
     * with optional search and sorting.
     *
     * @return array{shops: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getPrintingShopsPaginated(?string $search = null, ?string $sort = null, int $perPage = 8, int $page = 1)
    {
        $this->builder()
            ->where('status', 'active')
            ->where('offers_printing', 1);

        if ($search !== null && $search !== '') {
            $this->builder()
                ->groupStart()
                ->like('shop_name', $search)
                ->orLike('description', $search)
                ->groupEnd();
        }

        if ($sort === 'most-recent') {
            $this->builder()->orderBy('created_at', 'DESC');
        } else {
            $this->builder()->orderBy('rating_average', 'DESC')->orderBy('rating_count', 'DESC');
        }

        $shops = $this->paginate($perPage, 'default', $page);

        return [
            'shops' => $shops ?: [],
            'pager' => $this->pager,
        ];
    }

    /**
     * Paginated tenant shops for Admin management.
     *
     * @return array{tenants: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getTenantsPaginated(
        ?string $search = null,
        ?string $status = null,
        int $perPage = 15,
        int $page = 1,
        string $group = 'tenants'
    ): array {
        $this->builder()
            ->select('shops.*, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = shops.owner_id', 'left')
            ->orderBy('shops.created_at', 'DESC');

        if ($search !== null && $search !== '') {
            $this->builder()->groupStart()
                ->like('shops.shop_name', $search)
                ->orLike('u.first_name', $search)
                ->orLike('u.last_name', $search)
                ->orLike('u.email', $search)
                ->groupEnd();
        }

        if ($status !== null && in_array($status, ['active', 'pending', 'suspended', 'rejected'], true)) {
            $this->builder()->where('shops.status', $status);
        }

        $tenants = $this->paginate($perPage, $group, $page);

        return [
            'tenants' => $tenants ?: [],
            'pager'   => $this->pager,
        ];
    }
}
