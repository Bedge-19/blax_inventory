<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cloudinary extends BaseConfig
{
    /**
     * Complete Cloudinary URL:
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

        $rawUrl = (string) (getenv('CLOUDINARY_URL')
            ?: (env('CLOUDINARY_URL') ?: ($_ENV['CLOUDINARY_URL'] ?? ($_SERVER['CLOUDINARY_URL'] ?? $this->cloudinaryUrl))));
        $rawCloudName = (string) (getenv('CLOUDINARY_CLOUD_NAME')
            ?: (env('CLOUDINARY_CLOUD_NAME') ?: ($_ENV['CLOUDINARY_CLOUD_NAME'] ?? ($_SERVER['CLOUDINARY_CLOUD_NAME'] ?? $this->cloudName))));
        $rawApiKey = (string) (getenv('CLOUDINARY_API_KEY')
            ?: (env('CLOUDINARY_API_KEY') ?: ($_ENV['CLOUDINARY_API_KEY'] ?? ($_SERVER['CLOUDINARY_API_KEY'] ?? $this->apiKey))));
        $rawApiSecret = (string) (getenv('CLOUDINARY_API_SECRET')
            ?: (env('CLOUDINARY_API_SECRET') ?: ($_ENV['CLOUDINARY_API_SECRET'] ?? ($_SERVER['CLOUDINARY_API_SECRET'] ?? $this->apiSecret))));

        // Clean any angle brackets, surrounding quotes, and whitespace
        $this->cloudinaryUrl = $this->sanitizeCredential($rawUrl);
        $this->cloudName     = $this->sanitizeCredential($rawCloudName);
        $this->apiKey        = $this->sanitizeCredential($rawApiKey);
        $this->apiSecret     = $this->sanitizeCredential($rawApiSecret);

        // Parse credentials from CLOUDINARY_URL if individual ones are not fully provided
        if (!empty($this->cloudinaryUrl)) {
            $parsed = parse_url($this->cloudinaryUrl);
            if ($parsed !== false) {
                if (empty($this->apiKey) && !empty($parsed['user'])) {
                    $this->apiKey = $this->sanitizeCredential(urldecode($parsed['user']));
                }
                if (empty($this->apiSecret) && !empty($parsed['pass'])) {
                    $this->apiSecret = $this->sanitizeCredential(urldecode($parsed['pass']));
                }
                if (empty($this->cloudName) && !empty($parsed['host'])) {
                    $this->cloudName = $this->sanitizeCredential($parsed['host']);
                }
            }
        }

        // Auto-synthesize clean CLOUDINARY_URL if separate credentials are provided
        if (!empty($this->cloudName) && !empty($this->apiKey) && !empty($this->apiSecret)) {
            $this->cloudinaryUrl = "cloudinary://{$this->apiKey}:{$this->apiSecret}@{$this->cloudName}";
        }

        $fallbackEnv = getenv('ALLOW_LOCAL_FALLBACK') ?: (env('ALLOW_LOCAL_FALLBACK') ?? ($_ENV['ALLOW_LOCAL_FALLBACK'] ?? null));
        if ($fallbackEnv !== null) {
            $this->allowLocalFallback = filter_var($fallbackEnv, FILTER_VALIDATE_BOOLEAN);
        } else {
            $this->allowLocalFallback = true;
        }
    }

    /**
     * Strip accidental angle brackets <>, quotes, and whitespace from environment strings.
     */
    protected function sanitizeCredential(string $value): string
    {
        $value = trim($value, " \t\n\r\0\x0B\"'<>");
        // Remove inner < and > if copied from documentation templates like <api_key>:<api_secret>
        return str_replace(['<', '>'], '', $value);
    }
}
