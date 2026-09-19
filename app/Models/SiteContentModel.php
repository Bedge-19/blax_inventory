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

    /**
     * Fetch all site content entries indexed by content_key.
     *
     * @return array<string, array>
     */
    public function getAllKeyMap(): array
    {
        $rows = $this->orderBy('sort_order', 'ASC')->findAll();
        $map = [];
        foreach ($rows as $r) {
            $map[$r['content_key']] = $r;
        }
        return $map;
    }

    public function getPlatformDeductionPercent(): float
    {
        $row = $this->where('page', 'platform')->where('content_key', 'withdrawal_deduction_percent')->first();
        if ($row && is_numeric($row['text_value'])) {
            return (float) $row['text_value'];
        }
        return 3.00;
    }

    public function setPlatformDeductionPercent(float $percent): bool
    {
        $percent = round(max(0, min(50, $percent)), 2);
        $row = $this->where('page', 'platform')->where('content_key', 'withdrawal_deduction_percent')->first();
        if ($row) {
            return (bool) $this->update($row['id'], [
                'text_value' => number_format($percent, 2, '.', ''),
            ]);
        }
        return (bool) $this->insert([
            'page'         => 'platform',
            'content_key'  => 'withdrawal_deduction_percent',
            'label'        => 'Withdrawal Deduction Percentage',
            'content_type' => 'text',
            'text_value'   => number_format($percent, 2, '.', ''),
            'sort_order'   => 1,
        ]);
    }
}
