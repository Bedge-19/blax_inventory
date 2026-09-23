<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for Config\Database — credential removal, env-var loading, and production safety.
 */
class DatabaseConfigTest extends CIUnitTestCase
{
    /**
     * Verify no hardcoded TiDB credentials remain in the $default array.
     */
    public function testNoHardcodedCredentialsInDefaultArray()
    {
        // Read the raw source file to catch string literals that CI might still load
        $source = file_get_contents(APPPATH . 'Config/Database.php');

        $this->assertStringNotContainsString('gateway01.ap-southeast-1.prod.aws.tidbcloud.com', $source,
            'TiDB Cloud hostname must not be hardcoded in Database.php');
        $this->assertStringNotContainsString('2xsV5M53UudMfnZ.root', $source,
            'TiDB Cloud username must not be hardcoded in Database.php');
        $this->assertStringNotContainsString('y3b0jACs14yRTEy7', $source,
            'TiDB Cloud password must not be hardcoded in Database.php');
    }

    /**
     * Verify the $default array uses safe placeholder defaults (not production values).
     */
    public function testDefaultArrayHasSafePlaceholderValues()
    {
        $config = new \Config\Database();

        // In test environment without env vars, hostname should be 'localhost' (from .env or fallback)
        $this->assertNotEquals('gateway01.ap-southeast-1.prod.aws.tidbcloud.com', $config->default['hostname']);
        $this->assertNotEquals('2xsV5M53UudMfnZ.root', $config->default['username']);
        $this->assertNotEquals('y3b0jACs14yRTEy7', $config->default['password']);
    }

    /**
     * Verify DATABASE_DEFAULT_* env vars override the defaults.
     */
    public function testVercelStyleEnvVarsOverrideDefaults()
    {
        // Set Vercel-style env vars
        putenv('DATABASE_DEFAULT_HOSTNAME=test-tidb-host.example.com');
        putenv('DATABASE_DEFAULT_USERNAME=test_user');
        putenv('DATABASE_DEFAULT_PASSWORD=test_pass_secret');
        putenv('DATABASE_DEFAULT_DATABASE=test_db_name');
        putenv('DATABASE_DEFAULT_PORT=4000');

        try {
            $config = new \Config\Database();

            $this->assertEquals('test-tidb-host.example.com', $config->default['hostname']);
            $this->assertEquals('test_user', $config->default['username']);
            $this->assertEquals('test_pass_secret', $config->default['password']);
            $this->assertEquals('test_db_name', $config->default['database']);
            $this->assertEquals(4000, $config->default['port']);
        } finally {
            // Clean up
            putenv('DATABASE_DEFAULT_HOSTNAME');
            putenv('DATABASE_DEFAULT_USERNAME');
            putenv('DATABASE_DEFAULT_PASSWORD');
            putenv('DATABASE_DEFAULT_DATABASE');
            putenv('DATABASE_DEFAULT_PORT');
        }
    }

    /**
     * Verify DB_* short-form env vars are also recognized.
     */
    public function testShortFormEnvVarsWork()
    {
        putenv('DB_HOST=short-host.example.com');
        putenv('DB_USER=short_user');
        putenv('DB_PASS=short_pass');
        putenv('DB_DATABASE=short_db');
        putenv('DB_PORT=3307');

        try {
            $config = new \Config\Database();

            $this->assertEquals('short-host.example.com', $config->default['hostname']);
            $this->assertEquals('short_user', $config->default['username']);
            $this->assertEquals('short_pass', $config->default['password']);
            $this->assertEquals('short_db', $config->default['database']);
            $this->assertEquals(3307, $config->default['port']);
        } finally {
            putenv('DB_HOST');
            putenv('DB_USER');
            putenv('DB_PASS');
            putenv('DB_DATABASE');
            putenv('DB_PORT');
        }
    }

    /**
     * Verify DBDebug is false when CI_ENVIRONMENT is production.
     */
    public function testDbDebugDisabledInProduction()
    {
        putenv('CI_ENVIRONMENT=production');

        try {
            $config = new \Config\Database();
            $this->assertFalse($config->default['DBDebug'],
                'DBDebug must be false in production to prevent raw exception dumps');
        } finally {
            putenv('CI_ENVIRONMENT');
        }
    }

    /**
     * Verify DBDebug remains true in development.
     */
    public function testDbDebugEnabledInDevelopment()
    {
        putenv('CI_ENVIRONMENT=development');

        try {
            $config = new \Config\Database();
            $this->assertTrue($config->default['DBDebug'],
                'DBDebug should be true in development for easier debugging');
        } finally {
            putenv('CI_ENVIRONMENT');
        }
    }

    /**
     * Verify TiDB Cloud hostname auto-detects port 4000.
     */
    public function testTidbCloudAutoDetectsPort4000()
    {
        putenv('DATABASE_DEFAULT_HOSTNAME=gateway01.ap-southeast-1.prod.aws.tidbcloud.com');

        try {
            $config = new \Config\Database();
            $this->assertEquals(4000, $config->default['port'],
                'TiDB Cloud hostname should auto-set port to 4000');
            $this->assertTrue($config->default['compress'],
                'TiDB Cloud should enable compression');
        } finally {
            putenv('DATABASE_DEFAULT_HOSTNAME');
        }
    }
}
