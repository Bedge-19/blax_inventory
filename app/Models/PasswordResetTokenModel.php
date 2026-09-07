<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetTokenModel extends Model
{
    protected $table            = 'password_reset_tokens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
        'created_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function findValidToken(string $tokenHash): ?array
    {
        return $this->where('token_hash', $tokenHash)
            ->where('used_at IS NULL')
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->first();
    }
}
