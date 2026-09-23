<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminAuth implements FilterInterface
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
        if ($userRole !== 'admin') {
            $dest = '/';
            if ($request->isAJAX()) {
                return service('response')->setStatusCode(403)->setJSON([
                    'success'  => false,
                    'error'    => 'Access forbidden. Admin privileges required.',
                    'redirect' => $dest,
                ]);
            }

            session()->setFlashdata('error', 'Access denied. Admin privileges required.');
            return redirect()->to($dest);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
