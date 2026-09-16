<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ActionRateLimiter implements FilterInterface
{
    /**
     * Rate limit intensive or paid third-party operations (AI, Checkout, Printing).
     * Defaults to 30 requests per minute per IP.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtolower($request->getMethod()) !== 'post') {
            return;
        }

        $throttler = service('throttler');
        $ip = $request->getIPAddress() ?: '127.0.0.1';
        $path = trim($request->getUri()->getPath(), '/');
        $key = 'action_throttle_' . md5($ip . '_' . $path);

        // 30 requests per 60 seconds
        if ($throttler->check($key, 30, 60) === false) {
            $isAjax = $request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json');

            if ($isAjax) {
                return service('response')
                    ->setStatusCode(429)
                    ->setHeader('Retry-After', '60')
                    ->setJSON([
                        'status'  => 'error',
                        'success' => false,
                        'message' => 'Too many requests. Please wait a moment before trying again.',
                        'error'   => 'Rate limit exceeded.',
                    ]);
            }

            return service('response')
                ->setStatusCode(429)
                ->setHeader('Retry-After', '60')
                ->setBody('Too many requests. Please wait a minute before trying again.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
