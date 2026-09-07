<?php

namespace App\Libraries;

use CodeIgniter\HTTP\ContentSecurityPolicy;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Custom ContentSecurityPolicy handler that respects $autoNonce = false.
 *
 * When autoNonce is false (the default for projects using runtime JIT style injectors
 * such as Tailwind Play CDN), any nonce added to styleSrc or scriptSrc by framework
 * internals (like Kint) is omitted from the final HTTP header so that browsers do
 * not ignore 'unsafe-inline' per the W3C CSP Level 2/3 specification.
 */
class AppContentSecurityPolicy extends ContentSecurityPolicy
{
    public function finalize(ResponseInterface $response)
    {
        if ($this->autoNonce === false) {
            if (is_array($this->styleSrc)) {
                $this->styleSrc = array_filter(
                    $this->styleSrc,
                    fn ($src) => !is_string($src) || !str_starts_with($src, 'nonce-')
                );
            }
            if (is_array($this->scriptSrc)) {
                $this->scriptSrc = array_filter(
                    $this->scriptSrc,
                    fn ($src) => !is_string($src) || !str_starts_with($src, 'nonce-')
                );
            }
        }

        parent::finalize($response);
    }
}
