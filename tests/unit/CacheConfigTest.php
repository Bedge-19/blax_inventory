<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for Config\Cache — handler selection based on environment.
 */
class CacheConfigTest extends CIUnitTestCase
{
    /**
     * When VERCEL=1, handler should be 'database'.
     */
    public function testDatabaseHandlerOnVercel()
    {
        putenv('VERCEL=1');
        putenv('CACHE_HANDLER');

        try {
            $config = new \Config\Cache();
            $this->assertEquals('database', $config->handler,
                'Cache handler must be "database" on Vercel to survive across serverless instances');
        } finally {
            putenv('VERCEL');
        }
    }

    /**
     * Without VERCEL env, handler should be 'file' (local dev default).
     */
    public function testFileHandlerInLocalDev()
    {
        putenv('VERCEL');
        putenv('CACHE_HANDLER');

        try {
            $config = new \Config\Cache();
            $this->assertEquals('file', $config->handler,
                'Cache handler should be "file" in local development');
        } finally {
            // Nothing to restore
        }
    }

    /**
     * CACHE_HANDLER env var overrides everything.
     */
    public function testCacheHandlerEnvVarOverridesDefault()
    {
        putenv('CACHE_HANDLER=redis');

        try {
            $config = new \Config\Cache();
            $this->assertEquals('redis', $config->handler,
                'CACHE_HANDLER env var should override the default handler');
        } finally {
            putenv('CACHE_HANDLER');
        }
    }

    /**
     * The 'database' handler is registered in validHandlers.
     */
    public function testDatabaseHandlerIsRegistered()
    {
        $config = new \Config\Cache();
        $this->assertArrayHasKey('database', $config->validHandlers,
            'Database cache handler must be registered in validHandlers');
        $this->assertEquals(
            \App\Libraries\Cache\DatabaseCacheHandler::class,
            $config->validHandlers['database']
        );
    }
}
