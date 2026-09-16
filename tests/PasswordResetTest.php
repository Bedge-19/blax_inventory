<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\PasswordResetTokenModel;

class PasswordResetTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '192.168.10.' . rand(1, 250);
        \Config\Services::resetSingle('throttler');
    }

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        \Config\Services::resetSingle('throttler');
        parent::tearDown();
    }

    public function testForgotPasswordPageRenders()
    {
        $result = $this->get('forgot-password');
        $result->assertOK();
        $this->assertStringContainsString('Forgot Password', $result->getBody());
        $this->assertStringContainsString('name="email"', $result->getBody());
    }

    public function testDirectResetPasswordSuccess()
    {
        $userModel = new UserModel();
        $testEmail = 'test_reset_' . uniqid() . '@example.com';
        $userId = $userModel->insert([
            'role'          => 'customer',
            'first_name'    => 'Reset',
            'last_name'     => 'Tester',
            'email'         => $testEmail,
            'phone'         => '09123456789',
            'password_hash' => password_hash('OldPassword123!', PASSWORD_DEFAULT),
            'status'        => 'active',
        ]);

        $result = $this->withSession([])->post('reset-password-direct', [
            csrf_token()       => csrf_hash(),
            'email'            => $testEmail,
            'password'         => 'NewPassword123!',
            'confirm_password' => 'NewPassword123!',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('success');

        $updatedUser = $userModel->find($userId);
        $this->assertTrue(password_verify('NewPassword123!', $updatedUser['password_hash']));

        // Clean up
        $userModel->delete($userId, true);
    }

    public function testDirectResetPasswordValidationErrors()
    {
        // Passwords mismatch
        $result = $this->withSession([])->post('reset-password-direct', [
            csrf_token()       => csrf_hash(),
            'email'            => 'someuser@example.com',
            'password'         => 'NewPassword123!',
            'confirm_password' => 'DifferentPassword123!',
        ]);
        $result->assertRedirectTo('/login');
        $result->assertSessionHas('error');

        // Short password
        $result = $this->withSession([])->post('reset-password-direct', [
            csrf_token()       => csrf_hash(),
            'email'            => 'someuser@example.com',
            'password'         => 'short',
            'confirm_password' => 'short',
        ]);
        $result->assertRedirectTo('/login');
        $result->assertSessionHas('error');
    }
}

