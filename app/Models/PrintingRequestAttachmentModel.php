<?php

namespace App\Models;

use CodeIgniter\Model;

class PrintingRequestAttachmentModel extends Model
{
    protected $table            = 'printing_request_attachments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'printing_request_id',
        'image_url',
        'created_at',
    ];
    protected $useTimestamps    = false;

    /**
     * Get attachments for a printing request.
     */
    public function getForRequest(int $printingRequestId): array
    {
        return $this->where('printing_request_id', $printingRequestId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
