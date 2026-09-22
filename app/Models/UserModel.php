<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'role',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password_hash',
        'profile_image_url',
        'status',
        'email_verified_at',
        'last_login_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findByEmail(string $email)
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Paginated customer accounts for Admin management.
     *
     * @return array{customers: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getCustomersPaginated(
        ?string $search = null,
        ?string $status = null,
        int $perPage = 15,
        int $page = 1,
        string $group = 'customers'
    ): array {
        $this->builder()
            ->where('users.role', 'customer')
            ->orderBy('users.created_at', 'DESC');

        if ($search !== null && $search !== '') {
            $this->builder()->groupStart()
                ->like('users.first_name', $search)
                ->orLike('users.last_name', $search)
                ->orLike('users.email', $search)
                ->groupEnd();
        }

        if ($status !== null && in_array($status, ['active', 'suspended', 'pending', 'inactive'], true)) {
            $this->builder()->where('users.status', $status);
        }

        $customers = $this->paginate($perPage, $group, $page);

        return [
            'customers' => $customers ?: [],
            'pager'     => $this->pager,
        ];
    }

    /**
     * Fetch customer cart item count, active orders count, and unread notifications count
     * in a single consolidated SQL query to avoid multiple WAN round trips.
     *
     * @return array{cart_count: int, active_orders_count: int, unread_count: int}
     */
    public function getCustomerHeaderStats(int $userId): array
    {
        if ($userId <= 0) {
            return ['cart_count' => 0, 'active_orders_count' => 0, 'unread_count' => 0];
        }

        $sql = "SELECT 
            (SELECT COUNT(*) FROM cart_items ci INNER JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = ?) AS cart_count,
            (SELECT COUNT(*) FROM orders o WHERE o.customer_id = ? AND o.status IN ('pending', 'processing', 'shipped', 'ready_for_pickup')) AS active_orders_count,
            (SELECT COUNT(*) FROM notifications n WHERE n.user_id = ? AND n.is_read = 0) AS unread_count";

        try {
            $row = $this->db->query($sql, [$userId, $userId, $userId])->getRowArray();
            return [
                'cart_count'          => (int) ($row['cart_count'] ?? 0),
                'active_orders_count' => (int) ($row['active_orders_count'] ?? 0),
                'unread_count'        => (int) ($row['unread_count'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['cart_count' => 0, 'active_orders_count' => 0, 'unread_count' => 0];
        }
    }
}
