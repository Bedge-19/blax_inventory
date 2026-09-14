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
}
