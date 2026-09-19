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
     * Record an audit log entry.
     *
     * @param int|null    $actorId
     * @param string      $actorRole   Allowed ENUM: 'admin','tenant','customer','system'
     * @param string      $action      Max 150 chars
     * @param string|null $targetType  Max 60 chars
     * @param string      $status      'success' or 'failed'
     * @param int|null    $targetId
     * @param string|null $ipAddress
     * @return int|bool Insert ID on success, false on failure
     */
    public function log(
        ?int $actorId,
        string $actorRole,
        string $action,
        ?string $targetType = null,
        string $status = 'success',
        ?int $targetId = null,
        ?string $ipAddress = null
    ) {
        $roleMap = [
            'shop_owner' => 'tenant',
            'seller'     => 'tenant',
            'merchant'   => 'tenant',
            'superadmin' => 'admin',
            'staff'      => 'tenant',
            'user'       => 'customer',
        ];

        $role = strtolower(trim($actorRole));
        if (isset($roleMap[$role])) {
            $role = $roleMap[$role];
        }
        if (!in_array($role, ['admin', 'tenant', 'customer', 'system'], true)) {
            $role = 'system';
        }

        $validActorId = ($actorId !== null && $actorId > 0) ? $actorId : null;
        if ($validActorId !== null) {
            $userExists = $this->db->table('users')->where('id', $validActorId)->countAllResults();
            if ($userExists === 0) {
                $validActorId = null;
            }
        }

        $validTargetId = ($targetId !== null && (int)$targetId > 0) ? (int)$targetId : null;

        if ($ipAddress === null) {
            try {
                $ipAddress = service('request')->getIPAddress();
            } catch (\Throwable $e) {
                $ipAddress = null;
            }
        }

        $data = [
            'actor_id'    => $validActorId,
            'actor_role'  => $role,
            'action'      => mb_substr(trim($action), 0, 150),
            'target_type' => ($targetType !== null && trim($targetType) !== '') ? mb_substr(trim($targetType), 0, 60) : null,
            'target_id'   => $validTargetId,
            'status'      => strtolower(trim($status)) === 'failed' ? 'failed' : 'success',
            'ip_address'  => $ipAddress ? mb_substr((string)$ipAddress, 0, 45) : null,
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        return $this->insert($data);
    }

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
        string $group = 'audit_log',
        ?string $dateRange = null,
        ?string $ip = null
    ): array {
        $builder = $this->builder()
            ->select('audit_logs.*, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = audit_logs.actor_id', 'left')
            ->orderBy('audit_logs.created_at', 'DESC');

        if ($search !== null && $search !== '') {
            $builder->groupStart()
                ->like('audit_logs.action', $search)
                ->orLike('audit_logs.target_type', $search)
                ->orLike('audit_logs.target_id', $search)
                ->orLike('audit_logs.ip_address', $search)
                ->orLike('u.first_name', $search)
                ->orLike('u.last_name', $search)
                ->orLike('u.email', $search)
                ->groupEnd();
        }

        if ($ip !== null && $ip !== '') {
            $builder->where('audit_logs.ip_address', $ip);
        }

        if ($role !== null && in_array($role, ['admin', 'tenant', 'customer', 'system'], true)) {
            $builder->where('audit_logs.actor_role', $role);
        }

        if ($status !== null && in_array($status, ['success', 'failed'], true)) {
            $builder->where('audit_logs.status', $status);
        }

        if ($dateRange === 'today') {
            $builder->where('audit_logs.created_at >=', date('Y-m-d 00:00:00'));
        } elseif ($dateRange === '7d') {
            $builder->where('audit_logs.created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')));
        } elseif ($dateRange === '30d') {
            $builder->where('audit_logs.created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')));
        }

        $logs = $this->paginate($perPage, $group, $page);

        return [
            'logs'  => $logs ?: [],
            'pager' => $this->pager,
        ];
    }
}
