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
}
