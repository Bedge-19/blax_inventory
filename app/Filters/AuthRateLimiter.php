<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthRateLimiter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Rate limit POST attempts to authentication actions
        if (strtolower($request->getMethod()) !== 'post') {
            return;
        }

        $throttler = service('throttler');
        $ip = $request->getIPAddress() ?: '127.0.0.1';
        $key = 'auth_throttle_' . md5($ip);

        // 10 attempts per 300 seconds (5 minutes)
        if ($throttler->check($key, 10, 300) === false) {
            return service('response')
                ->setStatusCode(429)
                ->setHeader('Retry-After', '300')
                ->setBody('Too many attempts. Please try again in 5 minutes.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
