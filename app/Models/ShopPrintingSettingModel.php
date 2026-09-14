<?php

namespace App\Models;

use CodeIgniter\Model;

class ShopPrintingSettingModel extends Model
{
    protected $table            = 'shop_printing_settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'shop_id',
        'down_payment_percent',
        'price_staple',
        'price_spiral',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Default printing settings for a shop.
     */
    public const DEFAULTS = [
        'down_payment_percent' => 50.00,
        'price_staple'         => 10.00,
        'price_spiral'         => 35.00,
    ];

    /**
     * Retrieve settings for a shop with automatic default fallback.
     */
    public function getForShop(int $shopId): array
    {
        $setting = $this->where('shop_id', $shopId)->first();

        if (!$setting) {
            return array_merge(['shop_id' => $shopId, 'id' => null], self::DEFAULTS);
        }

        return [
            'id'                   => (int) $setting['id'],
            'shop_id'              => (int) $setting['shop_id'],
            'down_payment_percent' => (float) ($setting['down_payment_percent'] ?? self::DEFAULTS['down_payment_percent']),
            'price_staple'         => (float) ($setting['price_staple'] ?? self::DEFAULTS['price_staple']),
            'price_spiral'         => (float) ($setting['price_spiral'] ?? self::DEFAULTS['price_spiral']),
        ];
    }

    /**
     * Save settings for a shop using upsert logic.
     */
    public function saveForShop(int $shopId, array $data): bool
    {
        $existing = $this->where('shop_id', $shopId)->first();

        $saveData = [
            'shop_id'              => $shopId,
            'down_payment_percent' => isset($data['down_payment_percent']) ? max(0, min(100, (float) $data['down_payment_percent'])) : self::DEFAULTS['down_payment_percent'],
            'price_staple'         => isset($data['price_staple']) ? max(0, (float) $data['price_staple']) : self::DEFAULTS['price_staple'],
            'price_spiral'         => isset($data['price_spiral']) ? max(0, (float) $data['price_spiral']) : self::DEFAULTS['price_spiral'],
        ];

        if ($existing) {
            return (bool) $this->update($existing['id'], $saveData);
        }

        return (bool) $this->insert($saveData);
    }
}
