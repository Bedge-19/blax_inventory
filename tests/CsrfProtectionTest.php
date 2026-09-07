<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class CsrfProtectionTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testCsrfBlocksPostWithoutToken()
    {
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->post('cart/add', ['product_id' => 1, 'quantity' => 1]);
    }

    public function testCsrfExemptsPaymongoWebhook()
    {
        // payment/webhook should not throw SecurityException even without CSRF token
        $result = $this->withBody('{"test":1}')->post('payment/webhook');
        // Webhook signature validator should run and return 400, not CSRF exception
        $result->assertStatus(400);
        $this->assertStringContainsString('Missing signature header', $result->getBody());
    }
}
