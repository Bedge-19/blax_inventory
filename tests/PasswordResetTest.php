<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\PasswordResetTokenModel;

class PasswordResetTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    public function testForgotPasswordPageRenders()
    {
        $result = $this->get('forgot-password');
        $result->assertOK();
        $this->assertStringContainsString('Forgot Password', $result->getBody());
        $this->assertStringContainsString('name="email"', $result->getBody());
    }

    public function testInvalidResetTokenRedirects()
    {
        $result = $this->get('reset-password/non-existent-token-12345');
        $result->assertRedirectTo('/forgot-password');
    }
}
