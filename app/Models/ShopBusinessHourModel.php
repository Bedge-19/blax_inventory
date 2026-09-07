<?php

namespace App\Models;

use CodeIgniter\Model;

class ShopBusinessHourModel extends Model
{
    protected $table            = 'shop_business_hours';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'shop_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_closed',
    ];

    protected $useTimestamps = false;

    /**
     * All saved hours for a shop, keyed by day_of_week (0 = Sunday ... 6 = Saturday).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getForShop(int $shopId): array
    {
        $rows = $this->where('shop_id', $shopId)->orderBy('day_of_week', 'ASC')->findAll();

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[(int) $row['day_of_week']] = $row;
        }

        return $byDay;
    }

    /**
     * A shop's week schedule formatted for public display, with consecutive
     * days that share the same schedule collapsed into a single range
     * (e.g. "Monday - Friday"). Weeks start on Monday. Returns an empty array
     * when the shop has no usable hours saved.
     *
     * @return array<int, array{label: string, is_closed: bool, open: ?string, close: ?string}>
     */
    public function getGroupedForShop(int $shopId): array
    {
        $byDay = $this->getForShop($shopId);
        if ($byDay === []) {
            return [];
        }

        $names = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            0 => 'Sunday',
        ];

        $groups = [];
        foreach ($names as $day => $name) {
            if (!isset($byDay[$day])) {
                continue;
            }

            $row    = $byDay[$day];
            $closed = !empty($row['is_closed']);
            $open   = $closed ? null : $this->formatTime($row['open_time'] ?? null);
            $close  = $closed ? null : $this->formatTime($row['close_time'] ?? null);

            // An open day without both times is incomplete; nothing to show.
            if (!$closed && ($open === null || $close === null)) {
                continue;
            }

            $last = $groups === [] ? null : array_key_last($groups);
            if (
                $last !== null
                && $groups[$last]['is_closed'] === $closed
                && $groups[$last]['open'] === $open
                && $groups[$last]['close'] === $close
                && $groups[$last]['last_day'] === $day - 1
            ) {
                $groups[$last]['label']    = $groups[$last]['first_name'] . ' - ' . $name;
                $groups[$last]['last_day'] = $day;
                continue;
            }

            $groups[] = [
                'label'      => $name,
                'first_name' => $name,
                'last_day'   => $day,
                'is_closed'  => $closed,
                'open'       => $open,
                'close'      => $close,
            ];
        }

        return array_values(array_map(static function (array $group): array {
            unset($group['first_name'], $group['last_day']);

            return $group;
        }, $groups));
    }

    /**
     * "18:00:00" => "6:00 PM". Null when the value is missing or unparsable.
     */
    private function formatTime(?string $time): ?string
    {
        $time = trim((string) $time);
        if ($time === '') {
            return null;
        }

        $parsed = date_create_from_format('H:i:s', strlen($time) === 5 ? $time . ':00' : $time);

        return $parsed ? $parsed->format('g:i A') : null;
    }

    /**
     * Replace a shop's full week schedule. $days is keyed by day_of_week with
     * open_time/close_time (nullable) and is_closed per entry.
     *
     * @param array<int, array{open_time: ?string, close_time: ?string, is_closed: int}> $days
     */
    public function saveForShop(int $shopId, array $days): void
    {
        $this->where('shop_id', $shopId)->delete();

        foreach ($days as $day => $schedule) {
            $this->insert([
                'shop_id'     => $shopId,
                'day_of_week' => $day,
                'open_time'   => $schedule['open_time'],
                'close_time'  => $schedule['close_time'],
                'is_closed'   => !empty($schedule['is_closed']) ? 1 : 0,
            ]);
        }
    }
}