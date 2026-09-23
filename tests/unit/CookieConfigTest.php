<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for Config\Cookie — secure cookie resolution in production and Vercel environments.
 */
class CookieConfigTest extends CIUnitTestCase
{
    public function testDefaultSecureStateInTesting()
    {
        $config = new \Config\Cookie();
        // In local testing without Vercel or production env flags, secure should be false
        $this->assertFalse($config->secure);
    }

    public function testSecureEnabledInProductionEnvironment()
    {
        putenv('CI_ENVIRONMENT=production');

        try {
            $config = new \Config\Cookie();
            $this->assertTrue($config->secure, 'Cookie $secure must be true when CI_ENVIRONMENT=production');
        } finally {
            putenv('CI_ENVIRONMENT');
        }
    }

    public function testSecureEnabledOnVercel()
    {
        putenv('VERCEL=1');

        try {
            $config = new \Config\Cookie();
            $this->assertTrue($config->secure, 'Cookie $secure must be true when deployed on Vercel');
        } finally {
            putenv('VERCEL');
        }
    }

    public function testSecureEnabledUnderHttpsServer()
    {
        $_SERVER['HTTPS'] = 'on';

        try {
            $config = new \Config\Cookie();
            $this->assertTrue($config->secure, 'Cookie $secure must be true when HTTPS=on');
        } finally {
            unset($_SERVER['HTTPS']);
        }
    }

    public function testSecureEnabledUnderForwardedProtoHttps()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        try {
            $config = new \Config\Cookie();
            $this->assertTrue($config->secure, 'Cookie $secure must be true when HTTP_X_FORWARDED_PROTO=https');
        } finally {
            unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        }
    }
}
