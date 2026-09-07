<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'actor_id',
        'actor_role',
        'action',
        'target_type',
        'target_id',
        'status',
        'ip_address',
        'created_at',
    ];
}
