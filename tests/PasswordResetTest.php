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

    public function testDirectResetPasswordRouteIsRemoved()
    {
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->withSession([])->post('reset-password-direct', [
            csrf_token()       => csrf_hash(),
            'email'            => 'someuser@example.com',
            'password'         => 'NewPassword123!',
            'confirm_password' => 'NewPassword123!',
        ]);
    }

    public function testTokenBasedPasswordResetFlow()
    {
        $userModel = new UserModel();
        $tokenModel = new PasswordResetTokenModel();
        $testEmail = 'test_token_reset_' . uniqid() . '@example.com';
        $userId = $userModel->insert([
            'role'          => 'customer',
            'first_name'    => 'Reset',
            'last_name'     => 'Tester',
            'email'         => $testEmail,
            'phone'         => '09123456789',
            'password_hash' => password_hash('OldPassword123!', PASSWORD_DEFAULT),
            'status'        => 'active',
        ]);

        // 1. Request password reset via forgot-password
        $result = $this->withSession([])->post('forgot-password', [
            csrf_token() => csrf_hash(),
            'email'      => $testEmail,
        ]);
        $result->assertRedirectTo('/forgot-password');
        $result->assertSessionHas('success');

        // Verify token record in database
        $tokenRecord = $tokenModel->where('user_id', $userId)->orderBy('id', 'DESC')->first();
        $this->assertNotNull($tokenRecord);
        $this->assertNull($tokenRecord['used_at']);

        // 2. Perform reset using raw token matching the hash
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $tokenModel->update($tokenRecord['id'], ['token_hash' => $tokenHash]);

        $resetResult = $this->withSession([])->post('reset-password/' . $rawToken, [
            csrf_token()       => csrf_hash(),
            'password'         => 'NewPassword123!',
            'confirm_password' => 'NewPassword123!',
        ]);

        $resetResult->assertRedirectTo('/login');
        $resetResult->assertSessionHas('success');

        // Verify user password changed and token marked used
        $updatedUser = $userModel->find($userId);
        $this->assertTrue(password_verify('NewPassword123!', $updatedUser['password_hash']));

        $updatedToken = $tokenModel->find($tokenRecord['id']);
        $this->assertNotNull($updatedToken['used_at']);

        // Clean up
        $tokenModel->where('user_id', $userId)->delete();
        $userModel->delete($userId, true);
    }
}

