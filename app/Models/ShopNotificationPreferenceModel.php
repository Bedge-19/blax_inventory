<?php

namespace App\Models;

use CodeIgniter\Model;

class ShopNotificationPreferenceModel extends Model
{
    protected $table            = 'shop_notification_preferences';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'shop_id',
        'new_orders',
        'low_stock',
        'weekly_summary',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * The shop's notification preferences, defaulting to all enabled when
     * no row exists yet.
     *
     * @return array<string, mixed>
     */
    public function getForShop(int $shopId): array
    {
        $row = $this->where('shop_id', $shopId)->first();

        return $row ?: [
            'shop_id'       => $shopId,
            'new_orders'    => 1,
            'low_stock'     => 1,
        ];
    }

    /**
     * Upsert the shop's notification preferences.
     *
     * @param array<string, mixed> $prefs
     */
    public function saveForShop(int $shopId, array $prefs): void
    {
        $data = [
            'new_orders'     => !empty($prefs['new_orders']) ? 1 : 0,
            'low_stock'      => !empty($prefs['low_stock']) ? 1 : 0,
        ];

        $existing = $this->where('shop_id', $shopId)->first();
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert(array_merge(['shop_id' => $shopId], $data));
        }
    }
}