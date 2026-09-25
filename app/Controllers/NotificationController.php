<?php

namespace App\Controllers;

use App\Models\NotificationModel;

class NotificationController extends BaseController
{
    /**
     * Mark a notification as read and redirect safely to its destination.
     */
    public function click(int $id)
    {
        $session = session();
        if (!$session->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $userId = (int) $session->get('user_id');
        $userRole = (string) $session->get('user_role');
        $notifModel = new NotificationModel();
        $notification = $notifModel->find($id);
        if (!$notification) {
            return redirect()->to($userRole === 'admin' ? '/admin/dashboard' : (($userRole === 'shop_owner' || $userRole === 'tenant') ? '/tenant/dashboard' : '/'));
        }

        // Allow owner or admin to view/mark notification as read
        if ((int) $notification['user_id'] === $userId || $userRole === 'admin') {
            $notifModel->markRead($id, (int) $notification['user_id']);
        }

        // Resolve safe redirection target
        $targetUrl = $this->resolveSafeRedirectUrl($notification, $userRole);

        return redirect()->to($targetUrl);
    }

    /**
     * Mark single notification as read via AJAX.
     */
    public function markRead(int $id)
    {
        $session = session();
        if (!$session->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        $userId = (int) $session->get('user_id');
        $userRole = (string) $session->get('user_role');
        $notifModel = new NotificationModel();
        $notification = $notifModel->find($id);

        if (!$notification) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Notification not found']);
        }

        if ((int) $notification['user_id'] !== $userId && $userRole !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'Forbidden']);
        }

        $notifModel->markRead($id, (int) $notification['user_id']);

        return $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->setJSON(['success' => true]);
    }

    /**
     * Mark all notifications as read for current user.
     */
    public function markAllRead()
    {
        $session = session();
        if (!$session->get('isLoggedIn')) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false]);
            }
            return redirect()->to('/login');
        }

        $userId = (int) $session->get('user_id');
        (new NotificationModel())->markAllRead($userId);

        if ($this->request->isAJAX()) {
            return $this->response
                ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
                ->setJSON(['success' => true, 'unread_count' => 0]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Strictly validate or map target URL to prevent open redirects and enforce role isolation.
     * Guarantees:
     * - Tenant / shop_owner always lands on /tenant/*
     * - Admin always lands on /admin/*
     * - Customer always lands on /customer/* or public marketplace routes (never /tenant/* or /admin/*)
     */
    private function resolveSafeRedirectUrl(array $notification, string $userRole): string
    {
        $actionUrl = $notification['action_url'] ?? '';
        $cleanUrl = '';
        if (is_string($actionUrl) && $actionUrl !== '') {
            $trimmed = trim($actionUrl);
            if (str_starts_with($trimmed, '/') && !str_starts_with($trimmed, '//') && !str_contains($trimmed, ':')) {
                $cleanUrl = $trimmed;
            }
        }

        $type = (string) ($notification['type'] ?? '');

        // 1. Tenant / Shop Owner: Must strictly stay within /tenant/*
        if ($userRole === 'shop_owner' || $userRole === 'tenant') {
            if ($cleanUrl !== '' && str_starts_with($cleanUrl, '/tenant/')) {
                return $cleanUrl;
            }
            if ($cleanUrl !== '' && str_starts_with($cleanUrl, '/customer/orders')) {
                return '/tenant/orders';
            }
            if ($cleanUrl !== '' && str_starts_with($cleanUrl, '/customer/printing')) {
                return '/tenant/printing';
            }

            return match ($type) {
                'order', 'order_status', 'delivery', 'new_order', 'order_cancelled' => '/tenant/orders',
                'printing', 'new_printing_request'                                 => '/tenant/printing',
                'low_stock'                                                        => '/tenant/inventory',
                'payout', 'withdrawal'                                             => '/tenant/withdrawals',
                default                                                            => '/tenant/dashboard',
            };
        }

        // 2. Admin: Must strictly stay within /admin/*
        if ($userRole === 'admin') {
            if ($cleanUrl !== '' && str_starts_with($cleanUrl, '/admin/')) {
                return $cleanUrl;
            }

            return match ($type) {
                'customer_registration'                   => '/admin/customers',
                'merchant_verification', 'shop_status'   => '/admin/tenants',
                'compliance'                              => '/admin/compliance',
                'payout', 'withdrawal', 'payment'         => '/admin/payments',
                default                                   => '/admin/dashboard',
            };
        }

        // 3. Customer: Must strictly stay within /customer/* or public pages (/cart, /)
        if ($cleanUrl !== '' && (str_starts_with($cleanUrl, '/customer/') || $cleanUrl === '/cart' || $cleanUrl === '/')) {
            return $cleanUrl;
        }

        return match ($type) {
            'order', 'order_status', 'delivery', 'review_prompt' => '/customer/orders',
            'printing', 'new_printing_request'                   => '/customer/printing',
            'cart_reminder'                                      => '/cart',
            'account_status'                                     => '/customer/profile',
            default                                              => '/',
        };
    }
}
