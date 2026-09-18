<?php

namespace App\Models;

use CodeIgniter\Model;

class ComplianceModel extends Model
{
    protected $table            = 'compliance_reports';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'report_number',
        'reporter_id',
        'reported_shop_id',
        'reported_user_id',
        'issue_type',
        'description',
        'status',
        'resolved_at',
        'created_at',
    ];

    /**
     * Paginated compliance reports for Admin management.
     *
     * @return array{reports: array, pager: \CodeIgniter\Pager\Pager|null}
     */
    public function getCompliancePaginated(
        ?string $search = null,
        ?string $status = null,
        int $perPage = 15,
        int $page = 1,
        string $group = 'compliance'
    ): array {
        $this->builder()
            ->select("compliance_reports.*, compliance_reports.reported_shop_id as shop_id, compliance_reports.issue_type as issue, compliance_reports.status as compliance_status, s.shop_name, (SELECT COUNT(*) FROM compliance_reports c2 WHERE c2.reported_shop_id = compliance_reports.reported_shop_id AND c2.status != 'resolved') as flag_count, u.first_name as reporter_first, u.last_name as reporter_last, ru.first_name as reported_customer_first, ru.last_name as reported_customer_last")
            ->join('shops s', 's.id = compliance_reports.reported_shop_id', 'left')
            ->join('users u', 'u.id = compliance_reports.reporter_id', 'left')
            ->join('users ru', 'ru.id = compliance_reports.reported_user_id', 'left')
            ->orderBy('compliance_reports.created_at', 'DESC');

        if ($search !== null && $search !== '') {
            $this->builder()->groupStart()
                ->like('compliance_reports.report_number', $search)
                ->orLike('compliance_reports.issue_type', $search)
                ->orLike('s.shop_name', $search)
                ->orLike('ru.first_name', $search)
                ->orLike('ru.last_name', $search)
                ->groupEnd();
        }

        if ($status !== null && in_array($status, ['pending', 'under_review', 'flagged', 'resolved'], true)) {
            $this->builder()->where('compliance_reports.status', $status);
        }

        $reports = $this->paginate($perPage, $group, $page);

        return [
            'reports' => $reports ?: [],
            'pager'   => $this->pager,
        ];
    }
}
