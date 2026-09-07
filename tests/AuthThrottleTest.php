<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class AuthThrottleTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    public function testAuthThrottlePermitsNormalRequest()
    {
        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        $result = $this->withHeaders(['X-CSRF-TOKEN' => $hash])
            ->post('login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

        // Should return a redirect to login with error, not a 429
        $this->assertNotEquals(429, $result->response()->getStatusCode());
    }

    public function testAuthThrottleBlocksAfterExcessiveAttempts()
    {
        $throttler = service('throttler');
        $ip = service('request')->getIPAddress() ?: '127.0.0.1';
        $key = 'auth_throttle_' . md5($ip);

        // Exhaust the 10 tokens
        for ($i = 0; $i < 10; $i++) {
            $throttler->check($key, 10, 300);
        }

        $hash = csrf_hash();
        $_COOKIE['csrf_cookie_name'] = $hash;

        // Next attempt from same IP should get 429
        $result = $this->withHeaders(['X-CSRF-TOKEN' => $hash])
            ->post('login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

        $this->assertEquals(429, $result->response()->getStatusCode());
    }
}
