<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class CspHeaderTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    public function testCspIsEnabledAndConfigured()
    {
        $csp = service('csp');
        $this->assertTrue($csp->enabled());

        $response = service('response');
        $csp->finalize($response);

        $this->assertTrue($response->hasHeader('Content-Security-Policy'));
        $header = $response->getHeaderLine('Content-Security-Policy');
        $this->assertStringContainsString('cdn.tailwindcss.com', $header);
        $this->assertStringContainsString('fonts.googleapis.com', $header);
        $this->assertStringContainsString('fonts.gstatic.com', $header);
        $this->assertStringContainsString('maps.googleapis.com', $header);
        $this->assertStringContainsString('checkout.paymongo.com', $header);
        $this->assertStringContainsString("'unsafe-inline'", $header);
        $this->assertStringNotContainsString('nonce-', $header);
    }
}
