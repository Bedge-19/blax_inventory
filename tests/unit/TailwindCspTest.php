<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for Content Security Policy & Tailwind CSS bundling hygiene.
 */
class TailwindCspTest extends CIUnitTestCase
{
    /**
     * Verify cdn.tailwindcss.com is not allowed in scriptSrc.
     */
    public function testTailwindCdnRemovedFromCspScriptSrc()
    {
        $csp = new \Config\ContentSecurityPolicy();
        $scriptSrc = is_array($csp->scriptSrc) ? $csp->scriptSrc : [$csp->scriptSrc];

        $this->assertNotContains(
            'https://cdn.tailwindcss.com',
            $scriptSrc,
            'Tailwind Play CDN must be removed from ContentSecurityPolicy scriptSrc'
        );
    }

    /**
     * Verify head component does not load cdn.tailwindcss.com script.
     */
    public function testHeadComponentDoesNotContainTailwindCdn()
    {
        $headFile = APPPATH . 'Views/components/head.php';
        $this->assertFileExists($headFile);

        $content = file_get_contents($headFile);
        $this->assertStringNotContainsString(
            'cdn.tailwindcss.com',
            $content,
            'head.php must link to local tailwind.css instead of cdn.tailwindcss.com'
        );
    }

    /**
     * Verify head component links local tailwind.css and blax-ui.js.
     */
    public function testHeadComponentLinksLocalAssets()
    {
        $headFile = APPPATH . 'Views/components/head.php';
        $content = file_get_contents($headFile);

        $this->assertStringContainsString(
            'css/tailwind.css',
            $content,
            'head.php must link to local css/tailwind.css'
        );

        $this->assertStringContainsString(
            'js/blax-ui.js',
            $content,
            'head.php must include js/blax-ui.js'
        );
    }
}
