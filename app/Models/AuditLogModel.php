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

    /**
     * Paginated audit logs for Admin system audit view.
     *
     * @return array{logs: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getAuditLogsPaginated(
        ?string $search = null,
        ?string $role = null,
        ?string $status = null,
        int $perPage = 25,
        int $page = 1,
        string $group = 'audit_log'
    ): array {
        $this->builder()
            ->select('audit_logs.*, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = audit_logs.actor_id', 'left')
            ->orderBy('audit_logs.created_at', 'DESC');

        if ($search !== null && $search !== '') {
            $this->builder()->groupStart()
                ->like('audit_logs.action', $search)
                ->orLike('audit_logs.target_type', $search)
                ->orLike('u.first_name', $search)
                ->groupEnd();
        }

        if ($role !== null && in_array($role, ['admin', 'tenant', 'customer', 'system'], true)) {
            $this->builder()->where('audit_logs.actor_role', $role);
        }

        if ($status !== null && in_array($status, ['success', 'failed'], true)) {
            $this->builder()->where('audit_logs.status', $status);
        }

        $logs = $this->paginate($perPage, $group, $page);

        return [
            'logs'  => $logs ?: [],
            'pager' => $this->pager,
        ];
    }
}
