<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Stores the default settings for the ContentSecurityPolicy, if you
 * choose to use it. The values here will be read in and set as defaults
 * for the site. If needed, they can be overridden on a page-by-page basis.
 *
 * Suggested reference for explanations:
 *
 * @see https://www.html5rocks.com/en/tutorials/security/content-security-policy/
 */
class ContentSecurityPolicy extends BaseConfig
{
    // -------------------------------------------------------------------------
    // Broadbrush CSP management
    // -------------------------------------------------------------------------

    /**
     * Default CSP report context
     */
    public bool $reportOnly = false;

    /**
     * Specifies a URL where a browser will send reports
     * when a content security policy is violated.
     */
    public ?string $reportURI = null;

    /**
     * Instructs user agents to rewrite URL schemes, changing
     * HTTP to HTTPS. This directive is for websites with
     * large numbers of old URLs that need to be rewritten.
     */
    public bool $upgradeInsecureRequests = false;

    // -------------------------------------------------------------------------
    // Sources allowed
    // NOTE: once you set a policy to 'none', it cannot be further restricted
    // -------------------------------------------------------------------------

    /**
     * Will default to self if not overridden
     *
     * @var list<string>|string|null
     */
    public $defaultSrc = 'self';

    public $scriptSrc = [
        'self',
        'https://cdn.tailwindcss.com',
        'https://unpkg.com',
        'https://cdnjs.cloudflare.com',
        'https://cdn.jsdelivr.net',
        'https://maps.googleapis.com',
        'https://*.googleapis.com',
        'https://*.gstatic.com',
        'https://*.google.com',
        'unsafe-inline',
        'unsafe-eval',
    ];

    public $styleSrc = [
        'self',
        'https://fonts.googleapis.com',
        'https://maps.googleapis.com',
        'https://*.googleapis.com',
        'unsafe-inline',
    ];

    public $imageSrc = [
        'self',
        'data:',
        'https:',
        'blob:',
        'https://maps.gstatic.com',
        'https://maps.googleapis.com',
        'https://*.googleapis.com',
        'https://*.ggpht.com',
        'https://*.googleusercontent.com',
    ];

    public $baseURI = 'self';

    public $childSrc = [
        'self',
        'https://checkout.paymongo.com',
        'https://maps.googleapis.com',
        'https://*.google.com',
        'blob:',
    ];

    public $connectSrc = [
        'self',
        'https://api.paymongo.com',
        'https://checkout.paymongo.com',
        'https://unpkg.com',
        'https://cdnjs.cloudflare.com',
        'https://maps.googleapis.com',
        'https://places.googleapis.com',
        'https://routes.googleapis.com',
        'https://*.googleapis.com',
        'https://*.gstatic.com',
        'https://*.google.com',
        'blob:',
        'data:',
    ];

    public $fontSrc = [
        'self',
        'https://fonts.gstatic.com',
        'https://fonts.googleapis.com',
        'https://*.gstatic.com',
        'data:',
    ];

    public $formAction = [
        'self',
        'https://checkout.paymongo.com',
    ];

    public $frameAncestors = null;

    public $frameSrc = [
        'self',
        'https://checkout.paymongo.com',
        'https://maps.googleapis.com',
        'https://*.google.com',
    ];

    public $mediaSrc = 'self';

    public $objectSrc = 'none';

    public $manifestSrc = 'self';

    public $pluginTypes = null;

    public $sandbox = null;

    public string $styleNonceTag = '{csp-style-nonce}';

    public string $scriptNonceTag = '{csp-script-nonce}';

    public bool $autoNonce = false;
}
