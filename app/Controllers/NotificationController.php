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
        if (!$notification || (int) $notification['user_id'] !== $userId) {
            return redirect()->to('/');
        }

        // Mark as read
        $notifModel->markRead($id, $userId);

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
        $notifModel = new NotificationModel();
        $notification = $notifModel->find($id);

        if (!$notification || (int) $notification['user_id'] !== $userId) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Notification not found']);
        }

        $notifModel->markRead($id, $userId);

        return $this->response->setJSON(['success' => true]);
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
            return $this->response->setJSON(['success' => true]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Strictly validate or map target URL to prevent open redirects.
     */
    private function resolveSafeRedirectUrl(array $notification, string $userRole): string
    {
        $actionUrl = $notification['action_url'] ?? '';

        // 1. If relative action_url is provided and strictly formatted
        if (is_string($actionUrl) && $actionUrl !== '') {
            $trimmed = trim($actionUrl);
            if (str_starts_with($trimmed, '/') && !str_starts_with($trimmed, '//') && !str_contains($trimmed, ':')) {
                return $trimmed;
            }
        }

        // 2. Fallback to server-controlled type mapping
        $type = (string) ($notification['type'] ?? '');
        return match ($type) {
            'order', 'order_status', 'delivery' => $userRole === 'shop_owner' ? '/tenant/orders' : '/customer/orders',
            'new_order'                         => '/tenant/orders',
            'printing', 'new_printing_request'  => $userRole === 'shop_owner' ? '/tenant/printing' : '/customer/printing',
            'low_stock'                         => '/tenant/inventory',
            'cart_reminder'                     => '/cart',
            'merchant_verification'             => '/admin/tenants',
            'customer_registration'             => '/admin/customers',
            'compliance'                        => '/admin/compliance',
            default                             => $userRole === 'shop_owner' ? '/tenant/dashboard' : ($userRole === 'admin' ? '/admin/dashboard' : '/'),
        };
    }
}
