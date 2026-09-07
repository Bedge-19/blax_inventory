<?php

namespace App\Models;

use CodeIgniter\Model;

class SiteContentModel extends Model
{
    protected $table            = 'site_contents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'page',
        'content_key',
        'label',
        'content_type',
        'text_value',
        'image_url',
        'sort_order',
        'updated_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByPage(string $page): array
    {
        return $this->where('page', $page)->orderBy('sort_order', 'ASC')->orderBy('content_key', 'ASC')->findAll();
    }

    public function getContentMap(string $page): array
    {
        $rows = $this->getByPage($page);
        $map = [];
        foreach ($rows as $r) {
            $map[$r['content_key']] = $r;
        }
        return $map;
    }

    public function getAllGrouped(): array
    {
        $rows = $this->orderBy('page', 'ASC')->orderBy('sort_order', 'ASC')->findAll();
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['page']][] = $r;
        }
        return $grouped;
    }
}
