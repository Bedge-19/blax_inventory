<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class PaymongoWebhookTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PAYMONGO_WEBHOOK_SECRET=test_secret_key_123');
        $_ENV['PAYMONGO_WEBHOOK_SECRET'] = 'test_secret_key_123';
    }

    public function testWebhookRejectsMissingSignature()
    {
        $result = $this->withBody('{"test":1}')->post('payment/webhook');
        $result->assertStatus(400);
        $this->assertStringContainsString('Missing signature header', $result->getBody());
    }

    public function testWebhookRejectsForgedSignature()
    {
        $result = $this->withHeaders([
            'Paymongo-Signature' => 't=1234567890,te=invalid_signature_hash',
        ])->withBody('{"test":1}')->post('payment/webhook');

        $result->assertStatus(400);
        $this->assertStringContainsString('Invalid signature', $result->getBody());
    }

    public function testWebhookAcceptsValidSignature()
    {
        $payload = json_encode([
            'data' => [
                'attributes' => [
                    'data' => [
                        'id' => 'cs_test_123',
                        'attributes' => [
                            'status' => 'paid',
                            'metadata' => ['type' => 'unknown'],
                        ],
                    ],
                ],
            ],
        ]);

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, 'test_secret_key_123');

        $result = $this->withHeaders([
            'Paymongo-Signature' => "t={$timestamp},te={$signature}",
        ])->withBody($payload)->post('payment/webhook');

        // It should pass signature check and return 200 JSON
        $result->assertStatus(200);
    }
}
