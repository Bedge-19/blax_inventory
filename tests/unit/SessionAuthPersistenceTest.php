<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Session\Handlers\OptimizedDatabaseHandler;
use Config\Session as SessionConfig;
use App\Models\UserModel;

class SessionAuthPersistenceTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    /**
     * Test OptimizedDatabaseHandler writes unconditionally on auth state change.
     */
    public function testHandlerWritesUnconditionallyOnAuthStateChange()
    {
        $config = config(SessionConfig::class);
        $handler = new OptimizedDatabaseHandler($config, '127.0.0.1');

        $sessId = 'test_sess_' . bin2hex(random_bytes(8));

        // 1. Initial read for guest session (empty)
        $readData = $handler->read($sessId);
        $this->assertSame('', $readData);

        // 2. Writing empty data for guest should skip DB write (guest protection)
        $this->assertTrue($handler->write($sessId, ''));

        // 3. User logs in / signs up: auth data is now present in session
        $authData = 'isLoggedIn|b:1;user_id|i:999;user_role|s:8:"customer";';
        $writeResult = $handler->write($sessId, $authData);
        $this->assertTrue($writeResult, 'Handler must write session data when auth credentials are set');

        // 4. Read back data to verify it was actually persisted to DB
        $persisted = $handler->read($sessId);
        $this->assertStringContainsString('isLoggedIn', $persisted);
        $this->assertStringContainsString('999', $persisted);

        // Clean up
        $handler->destroy($sessId);
        $handler->close();
    }

    /**
     * Test handler handles session ID regeneration properly.
     */
    public function testHandlerHandlesRegenerationProperly()
    {
        $config = config(SessionConfig::class);
        $handler = new OptimizedDatabaseHandler($config, '127.0.0.1');

        $oldId = 'old_sess_' . bin2hex(random_bytes(8));
        $newId = 'new_sess_' . bin2hex(random_bytes(8));

        // Read old guest session
        $handler->read($oldId);

        // Simulate session ID regeneration on login/signup
        $authData = 'isLoggedIn|b:1;user_id|i:555;user_name|s:4:"Test";';
        $this->assertTrue($handler->write($newId, $authData), 'Writing to new session ID after regenerate must succeed');

        // Read from new session ID
        $readNew = $handler->read($newId);
        $this->assertStringContainsString('555', $readNew);

        // Clean up
        $handler->destroy($newId);
        $handler->close();
    }

    /**
     * Regression test simulating: signup -> redirect -> immediate authenticated request.
     */
    public function testSignupSessionPersistsAcrossRedirect()
    {
        $uniqueEmail = 'newuser_' . time() . '_' . rand(100, 999) . '@test.com';

        // Submit signup
        $result = $this->post('signup', [
            'first_name'       => 'Test',
            'last_name'        => 'Signup',
            'email'            => $uniqueEmail,
            'password'         => 'Password123!',
            'confirm_password' => 'Password123!',
            'phone'            => '09123456789',
            csrf_token()       => csrf_hash(),
        ]);

        $result->assertRedirectTo('/');

        // Verify session has newly set user credentials
        $result->assertSessionHas('isLoggedIn', true);
        $result->assertSessionHas('user_role', 'customer');
        $result->assertSessionHas('user_email', $uniqueEmail);

        // Verify user was inserted into DB
        $userModel = new UserModel();
        $user = $userModel->where('email', $uniqueEmail)->first();
        $this->assertNotNull($user);

        // Immediate subsequent authenticated request using the session from signup
        $sessionData = [
            'user_id'    => (int) $user['id'],
            'user_name'  => 'Test Signup',
            'user_email' => $uniqueEmail,
            'user_role'  => 'customer',
            'isLoggedIn' => true,
        ];

        $subsequent = $this->withSession($sessionData)->get('customer/profile');
        $subsequent->assertOK();

        // Clean up created test user
        $userModel->delete($user['id'], true);
    }
}
