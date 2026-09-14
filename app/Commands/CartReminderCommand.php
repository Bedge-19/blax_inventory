<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\NotificationModel;

class CartReminderCommand extends BaseCommand
{
    protected $group       = 'Cart';
    protected $name        = 'cart:process-expiry';
    protected $description = 'Purge expired cart items (> 3 days) and dispatch 8-hour reminders with item details.';

    public function run(array $params)
    {
        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();

        // 1. Purge expired cart items (expires_at <= NOW())
        $expiredItems = $db->table('cart_items')
            ->where('expires_at IS NOT NULL')
            ->where('expires_at <=', $now)
            ->get()->getResultArray();

        $purgedCount = 0;
        if (!empty($expiredItems)) {
            $expiredIds = array_column($expiredItems, 'id');
            $db->table('cart_items')->whereIn('id', $expiredIds)->delete();
            $purgedCount = count($expiredIds);
            CLI::write("Purged {$purgedCount} expired cart item(s).", 'green');
        } else {
            CLI::write("No expired cart items found.", 'white');
        }

        // 2. Dispatch 8-Hour Reminders
        // Eligible items: expires_at > NOW(), not yet expired, reminder cadence every 8 hours
        $eligibleItems = $db->table('cart_items ci')
            ->select('ci.id, ci.cart_id, ci.expires_at, ci.reminder_count, ci.created_at, ci.last_reminder_at, c.user_id, p.name as product_name')
            ->join('carts c', 'c.id = ci.cart_id', 'inner')
            ->join('products p', 'p.id = ci.product_id', 'left')
            ->where('ci.expires_at IS NOT NULL')
            ->where('ci.expires_at >', $now)
            ->groupStart()
                ->where('ci.last_reminder_at IS NULL')
                ->orWhere("ci.last_reminder_at <=", date('Y-m-d H:i:s', strtotime('-8 hours')))
            ->groupEnd()
            ->orderBy('ci.expires_at', 'ASC')
            ->get()->getResultArray();

        if (empty($eligibleItems)) {
            CLI::write("No cart reminder notifications due at this time.", 'white');
            return EXIT_SUCCESS;
        }

        // Group eligible items by user_id
        $itemsByUser = [];
        foreach ($eligibleItems as $item) {
            $userId = (int) ($item['user_id'] ?? 0);
            if ($userId > 0) {
                $itemsByUser[$userId][] = $item;
            }
        }

        $notifModel = new NotificationModel();
        $notifiedCount = 0;

        foreach ($itemsByUser as $userId => $userItems) {
            $itemIds = array_column($userItems, 'id');
            $firstItem = $userItems[0];
            $productTitle = !empty($firstItem['product_name']) ? $firstItem['product_name'] : 'produkto';

            if (count($userItems) > 1) {
                $productTitle .= ' (at ' . (count($userItems) - 1) . ' pang item)';
            }

            // Calculate remaining duration
            $diffSeconds = max(0, strtotime($firstItem['expires_at']) - time());
            $diffHours   = max(1, (int) round($diffSeconds / 3600));

            if ($diffHours >= 24) {
                $days = (int) ceil($diffHours / 24);
                $timeStr = $days . ' ' . ($days === 1 ? 'araw' : 'araw');
            } else {
                $timeStr = $diffHours . ' ' . ($diffHours === 1 ? 'oras' : 'oras');
            }

            $notifTitle = 'May item ka pa sa iyong cart!';
            $notifMsg   = "Ang {$productTitle} sa iyong cart ay mawawala sa loob ng {$timeStr}. Gusto mo na ba itong i-checkout ngayon?";

            $notifModel->create(
                $userId,
                'cart_reminder',
                $notifTitle,
                $notifMsg,
                '/cart'
            );

            // Update reminder tracking
            $db->table('cart_items')
                ->whereIn('id', $itemIds)
                ->set('reminder_count', 'reminder_count + 1', false)
                ->set('last_reminder_at', $now)
                ->update();

            $notifiedCount++;
        }

        CLI::write("Dispatched cart reminders to {$notifiedCount} customer(s).", 'green');

        return EXIT_SUCCESS;
    }
}
