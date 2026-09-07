<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\NotificationModel;

class CartRemindersCommand extends BaseCommand
{
    protected $group       = 'Cart';
    protected $name        = 'cart:reminders';
    protected $description = 'Purge expired cart items (> 72 hours) and dispatch 8-hour checkout reminders.';

    public function run(array $params)
    {
        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();

        // 1. Purge expired cart items (lifetime > 72 hours)
        // Removes from cart_items only, leaves products table intact.
        $expiredItems = $db->table('cart_items')
            ->where('expires_at IS NOT NULL')
            ->where('expires_at <=', $now)
            ->get()->getResultArray();

        $purgedCount = 0;
        if (!empty($expiredItems)) {
            $expiredIds = array_column($expiredItems, 'id');
            $db->table('cart_items')->whereIn('id', $expiredIds)->delete();
            $purgedCount = count($expiredIds);
            CLI::write("Purged {$purgedCount} expired cart item(s). Products table untouched.", 'green');
        } else {
            CLI::write("No expired cart items found.", 'white');
        }

        // 2. Dispatch 8-Hour Reminders
        // Cadence: 8h, 16h, 24h, 32h, 40h, 48h, 56h, 64h after item addition.
        $eligibleItems = $db->table('cart_items ci')
            ->select('ci.id, ci.cart_id, ci.reminder_count, ci.created_at, ci.last_reminder_at, c.user_id')
            ->join('carts c', 'c.id = ci.cart_id', 'inner')
            ->where('ci.expires_at IS NOT NULL')
            ->where('ci.expires_at >', $now)
            ->where('ci.reminder_count <', 8)
            ->where("TIMESTAMPDIFF(HOUR, ci.created_at, '{$now}') >=", '(ci.reminder_count + 1) * 8', false)
            ->groupStart()
                ->where('ci.last_reminder_at IS NULL')
                ->orWhere("TIMESTAMPDIFF(HOUR, ci.last_reminder_at, '{$now}') >=", 8, false)
            ->groupEnd()
            ->get()->getResultArray();

        if (empty($eligibleItems)) {
            CLI::write("No cart reminder notifications due at this time.", 'white');
            return EXIT_SUCCESS;
        }

        // Group eligible items by user_id to consolidate reminders (one notification per customer)
        $itemsByUser = [];
        foreach ($eligibleItems as $item) {
            $userId = (int) $item['user_id'];
            if ($userId > 0) {
                $itemsByUser[$userId][] = (int) $item['id'];
            }
        }

        $notifModel = new NotificationModel();
        $notifiedCount = 0;

        foreach ($itemsByUser as $userId => $itemIds) {
            $notifModel->create(
                $userId,
                'cart_reminder',
                'Items waiting in your cart',
                'Your cart has items waiting for checkout. Complete your purchase before items expire.',
                '/cart'
            );

            // Increment reminder_count and set last_reminder_at
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
