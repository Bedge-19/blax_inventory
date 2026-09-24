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

    public function __construct()
    {
        parent::__construct();

        $this->cloudinaryUrl = (string) (env('CLOUDINARY_URL')
            ?: (getenv('CLOUDINARY_URL') ?: ($_ENV['CLOUDINARY_URL'] ?? ($_SERVER['CLOUDINARY_URL'] ?? $this->cloudinaryUrl))));
        $this->cloudName     = (string) (env('CLOUDINARY_CLOUD_NAME')
            ?: (getenv('CLOUDINARY_CLOUD_NAME') ?: ($_ENV['CLOUDINARY_CLOUD_NAME'] ?? ($_SERVER['CLOUDINARY_CLOUD_NAME'] ?? $this->cloudName))));
        $this->apiKey        = (string) (env('CLOUDINARY_API_KEY')
            ?: (getenv('CLOUDINARY_API_KEY') ?: ($_ENV['CLOUDINARY_API_KEY'] ?? ($_SERVER['CLOUDINARY_API_KEY'] ?? $this->apiKey))));
        $this->apiSecret     = (string) (env('CLOUDINARY_API_SECRET')
            ?: (getenv('CLOUDINARY_API_SECRET') ?: ($_ENV['CLOUDINARY_API_SECRET'] ?? ($_SERVER['CLOUDINARY_API_SECRET'] ?? $this->apiSecret))));

        // Parse credentials from CLOUDINARY_URL if provided
        if (!empty($this->cloudinaryUrl) && preg_match('#^cloudinary://([^:]+):([^@]+)@([a-zA-Z0-9_-]+)$#', trim($this->cloudinaryUrl), $matches)) {
            if (empty($this->apiKey)) {
                $this->apiKey = $matches[1];
            }
            if (empty($this->apiSecret)) {
                $this->apiSecret = $matches[2];
            }
            if (empty($this->cloudName)) {
                $this->cloudName = $matches[3];
            }
        }
    }
}
