<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class TenantAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if (!$session->get('isLoggedIn')) {
            if ($request->isAJAX()) {
                return service('response')->setStatusCode(401)->setJSON([
                    'success'  => false,
                    'error'    => 'Authentication required.',
                    'redirect' => base_url('login'),
                ]);
            }
            return redirect()->to('/login');
        }

        $userRole = (string) ($session->get('user_role') ?? '');
        if ($userRole !== 'shop_owner' && $userRole !== 'tenant') {
            if ($request->isAJAX()) {
                return service('response')->setStatusCode(403)->setJSON([
                    'success'  => false,
                    'error'    => 'Access forbidden. Tenant access required.',
                    'redirect' => $userRole === 'admin' ? '/admin/dashboard' : '/',
                ]);
            }

            session()->setFlashdata('error', 'Access denied. Tenant access required.');
            return redirect()->to($userRole === 'admin' ? '/admin/dashboard' : '/');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
