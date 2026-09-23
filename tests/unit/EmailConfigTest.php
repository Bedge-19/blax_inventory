<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for Config\Email — Vercel env-var loading.
 */
class EmailConfigTest extends CIUnitTestCase
{
    /**
     * Verify EMAIL_* env vars override the default empty values.
     */
    public function testVercelStyleEnvVarsOverrideDefaults()
    {
        putenv('EMAIL_FROMNAME=Test Sender');
        putenv('EMAIL_FROMEMAIL=test@example.com');
        putenv('EMAIL_PROTOCOL=smtp');
        putenv('EMAIL_SMTPHOST=smtp.example.com');
        putenv('EMAIL_SMTPUSER=smtp_user');
        putenv('EMAIL_SMTPPASS=smtp_pass');
        putenv('EMAIL_SMTPPORT=465');
        putenv('EMAIL_SMTPCRYPTO=ssl');

        try {
            $config = new \Config\Email();

            $this->assertEquals('Test Sender', $config->fromName);
            $this->assertEquals('test@example.com', $config->fromEmail);
            $this->assertEquals('smtp', $config->protocol);
            $this->assertEquals('smtp.example.com', $config->SMTPHost);
            $this->assertEquals('smtp_user', $config->SMTPUser);
            $this->assertEquals('smtp_pass', $config->SMTPPass);
            $this->assertEquals(465, $config->SMTPPort);
            $this->assertEquals('ssl', $config->SMTPCrypto);
        } finally {
            putenv('EMAIL_FROMNAME');
            putenv('EMAIL_FROMEMAIL');
            putenv('EMAIL_PROTOCOL');
            putenv('EMAIL_SMTPHOST');
            putenv('EMAIL_SMTPUSER');
            putenv('EMAIL_SMTPPASS');
            putenv('EMAIL_SMTPPORT');
            putenv('EMAIL_SMTPCRYPTO');
        }
    }

    /**
     * Verify underscore-variant env var names also work.
     */
    public function testUnderscoreVariantEnvVarsWork()
    {
        putenv('EMAIL_FROM_NAME=Alt Sender');
        putenv('EMAIL_FROM_EMAIL=alt@example.com');
        putenv('EMAIL_SMTP_HOST=smtp.alt.com');
        putenv('EMAIL_SMTP_USER=alt_user');
        putenv('EMAIL_SMTP_PASS=alt_pass');
        putenv('EMAIL_SMTP_PORT=2525');
        putenv('EMAIL_SMTP_CRYPTO=tls');

        try {
            $config = new \Config\Email();

            $this->assertEquals('Alt Sender', $config->fromName);
            $this->assertEquals('alt@example.com', $config->fromEmail);
            $this->assertEquals('smtp.alt.com', $config->SMTPHost);
            $this->assertEquals('alt_user', $config->SMTPUser);
            $this->assertEquals('alt_pass', $config->SMTPPass);
            $this->assertEquals(2525, $config->SMTPPort);
            $this->assertEquals('tls', $config->SMTPCrypto);
        } finally {
            putenv('EMAIL_FROM_NAME');
            putenv('EMAIL_FROM_EMAIL');
            putenv('EMAIL_SMTP_HOST');
            putenv('EMAIL_SMTP_USER');
            putenv('EMAIL_SMTP_PASS');
            putenv('EMAIL_SMTP_PORT');
            putenv('EMAIL_SMTP_CRYPTO');
        }
    }

    /**
     * Without env vars, defaults should remain (empty strings or CI defaults).
     */
    public function testDefaultValuesWithoutEnvVars()
    {
        // The .env file may set values, so we only verify no crash
        $config = new \Config\Email();

        $this->assertIsString($config->fromName);
        $this->assertIsString($config->fromEmail);
        $this->assertIsString($config->SMTPHost);
        $this->assertIsInt($config->SMTPPort);
    }
}
