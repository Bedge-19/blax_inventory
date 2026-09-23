<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleAccessFilter implements FilterInterface
{
    /**
     * Enforce strict role-based access control across route groups.
     *
     * @param array|null $arguments [0] Expected role: 'tenant' (or 'shop_owner'), 'admin', or 'customer'
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // 1. Authentication check
        if (!$session->get('isLoggedIn')) {
            if ($request->isAJAX()) {
                return service('response')->setStatusCode(401)->setJSON([
                    'success'  => false,
                    'error'    => 'Authentication required.',
                    'redirect' => base_url('login'),
                ]);
            }

            session()->setFlashdata('error', 'Please log in to continue.');
            return redirect()->to('/login');
        }

        $userRole = (string) ($session->get('user_role') ?? '');
        $expectedRole = !empty($arguments[0]) ? strtolower((string) $arguments[0]) : '';

        // Normalize roles ('shop_owner' and 'tenant' are synonymous)
        $normalizedUserRole = ($userRole === 'shop_owner' || $userRole === 'tenant') ? 'tenant' : $userRole;
        $normalizedExpectedRole = ($expectedRole === 'shop_owner' || $expectedRole === 'tenant') ? 'tenant' : $expectedRole;

        // 2. Strict role matching
        if ($normalizedExpectedRole !== '' && $normalizedUserRole !== $normalizedExpectedRole) {
            $errorMsg = match ($normalizedExpectedRole) {
                'tenant'   => 'Access forbidden. Tenant access required.',
                'admin'    => 'Access forbidden. Admin privileges required.',
                'customer' => 'Access forbidden. Customer account required.',
                default    => 'Access forbidden. Required role: ' . $expectedRole,
            };

            if ($request->isAJAX()) {
                return service('response')->setStatusCode(403)->setJSON([
                    'success'  => false,
                    'error'    => $errorMsg,
                    'redirect' => '/',
                ]);
            }

            session()->setFlashdata('error', 'Access denied. You do not have permission to access that area.');
            return redirect()->to('/');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }

    /**
     * Map a user role to its isolated home dashboard.
     */
    private function getDashboardForRole(string $role): string
    {
        return match ($role) {
            'admin'                   => '/admin/dashboard',
            'shop_owner', 'tenant'    => '/tenant/dashboard',
            default                   => '/',
        };
    }
}
