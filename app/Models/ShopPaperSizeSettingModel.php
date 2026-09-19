<?php

namespace App\Models;

use CodeIgniter\Model;

class ShopPaperSizeSettingModel extends Model
{
    protected $table            = 'shop_printing_paper_sizes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'shop_id',
        'paper_size',
        'is_enabled',
    ];
    protected $useTimestamps    = false;

    /**
     * All 10 supported paper sizes with human labels.
     */
    public const SUPPORTED_SIZES = [
        'letter' => ['label' => 'Letter (8.5" x 11")'],
        'legal'  => ['label' => 'Legal (8.5" x 14")'],
        'a4'     => ['label' => 'A4 (8.27" x 11.69")'],
        'a3'     => ['label' => 'A3 (11.7" x 16.5")'],
        'a5'     => ['label' => 'A5 (5.83" x 8.27")'],
        'b4'     => ['label' => 'B4 (9.84" x 13.9")'],
        'b5'     => ['label' => 'B5 (6.93" x 9.84")'],
        'a2'     => ['label' => 'A2 (16.5" x 23.4")'],
        'a1'     => ['label' => 'A1 (23.4" x 33.1")'],
        'a0'     => ['label' => 'A0 (33.1" x 46.8")'],
    ];

    /**
     * Retrieve paper size settings for a shop covering all 10 sizes.
     * Falls back to defaults (enabled) if not specifically stored yet.
     */
    public function getForShop(int $shopId): array
    {
        $records = $this->where('shop_id', $shopId)->findAll();
        $indexed = [];
        foreach ($records as $r) {
            $indexed[strtolower($r['paper_size'])] = $r;
        }

        $result = [];
        foreach (self::SUPPORTED_SIZES as $sizeKey => $defaults) {
            if (isset($indexed[$sizeKey])) {
                $row = $indexed[$sizeKey];
                $result[$sizeKey] = [
                    'id'          => (int) $row['id'],
                    'shop_id'     => $shopId,
                    'paper_size'  => $sizeKey,
                    'label'       => $defaults['label'],
                    'is_enabled'  => (int) ($row['is_enabled'] ?? 1),
                ];
            } else {
                $result[$sizeKey] = [
                    'id'          => null,
                    'shop_id'     => $shopId,
                    'paper_size'  => $sizeKey,
                    'label'       => $defaults['label'],
                    'is_enabled'  => 1,
                ];
            }
        }

        return $result;
    }

    /**
     * Save/upsert paper sizes for a shop.
     * $sizesData is keyed by paper_size (e.g. ['letter' => ['is_enabled' => 1]])
     */
    public function saveForShop(int $shopId, array $sizesData): bool
    {
        $existing = $this->where('shop_id', $shopId)->findAll();
        $existingMap = [];
        foreach ($existing as $ex) {
            $existingMap[strtolower($ex['paper_size'])] = $ex;
        }

        foreach (self::SUPPORTED_SIZES as $sizeKey => $defaults) {
            $item = $sizesData[$sizeKey] ?? [];
            $isEnabled = isset($item['is_enabled']) ? (int) $item['is_enabled'] : 1;

            $data = [
                'shop_id'    => $shopId,
                'paper_size' => $sizeKey,
                'is_enabled' => $isEnabled,
            ];

            if (isset($existingMap[$sizeKey])) {
                $this->update($existingMap[$sizeKey]['id'], $data);
            } else {
                $this->insert($data);
            }
        }

        return true;
    }
}
