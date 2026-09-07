<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table            = 'payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'payable_type',
        'payable_id',
        'method',
        'amount',
        'reference_number',
        'proof_image_url',
        'status',
        'processed_at',
        'created_at',
    ];
}
