<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cloudinary extends BaseConfig
{
    /**
     * Complete Cloudinary URL (optional):
     * cloudinary://API_KEY:API_SECRET@CLOUD_NAME
     */
    public string $cloudinaryUrl = '';

    /**
     * Individual credentials read strictly from environment variables.
     * Defaults to safe values; real credentials are never committed to source code.
     */
    public string $cloudName = '';
    public string $apiKey    = '';
    public string $apiSecret = '';

    /**
     * Folder prefix in Cloudinary
     */
    public string $rootFolder = 'blax';

    /**
     * Whether local disk storage (public/uploads/...) is permitted as a fallback
     * when Cloudinary is unconfigured or encounters an error.
     * Defaults to true in development; can be toggled via ALLOW_LOCAL_FALLBACK env var.
     */
    public bool $allowLocalFallback = true;

    public function __construct()
    {
        parent::__construct();

        $this->cloudinaryUrl = (string) (getenv('CLOUDINARY_URL')
            ?: (env('CLOUDINARY_URL') ?: ($_ENV['CLOUDINARY_URL'] ?? ($_SERVER['CLOUDINARY_URL'] ?? $this->cloudinaryUrl))));
        $this->cloudName     = (string) (getenv('CLOUDINARY_CLOUD_NAME')
            ?: (env('CLOUDINARY_CLOUD_NAME') ?: ($_ENV['CLOUDINARY_CLOUD_NAME'] ?? ($_SERVER['CLOUDINARY_CLOUD_NAME'] ?? $this->cloudName))));
        $this->apiKey        = (string) (getenv('CLOUDINARY_API_KEY')
            ?: (env('CLOUDINARY_API_KEY') ?: ($_ENV['CLOUDINARY_API_KEY'] ?? ($_SERVER['CLOUDINARY_API_KEY'] ?? $this->apiKey))));
        $this->apiSecret     = (string) (getenv('CLOUDINARY_API_SECRET')
            ?: (env('CLOUDINARY_API_SECRET') ?: ($_ENV['CLOUDINARY_API_SECRET'] ?? ($_SERVER['CLOUDINARY_API_SECRET'] ?? $this->apiSecret))));

        // Parse credentials from CLOUDINARY_URL if provided
        if (!empty($this->cloudinaryUrl)) {
            $parsed = parse_url(trim($this->cloudinaryUrl));
            if ($parsed !== false) {
                if (empty($this->apiKey) && !empty($parsed['user'])) {
                    $this->apiKey = urldecode($parsed['user']);
                }
                if (empty($this->apiSecret) && !empty($parsed['pass'])) {
                    $this->apiSecret = urldecode($parsed['pass']);
                }
                if (empty($this->cloudName) && !empty($parsed['host'])) {
                    $this->cloudName = $parsed['host'];
                }
            }
        }

        $fallbackEnv = getenv('ALLOW_LOCAL_FALLBACK') ?: (env('ALLOW_LOCAL_FALLBACK') ?? ($_ENV['ALLOW_LOCAL_FALLBACK'] ?? null));
        if ($fallbackEnv !== null) {
            $this->allowLocalFallback = filter_var($fallbackEnv, FILTER_VALIDATE_BOOLEAN);
        } else {
            $this->allowLocalFallback = true;
        }
    }
}
