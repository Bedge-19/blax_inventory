<?php

declare(strict_types=1);

namespace App\Libraries;

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Cloudinary as CloudinaryConfig;

/**
 * Centralized Cloudinary Storage Service for Blax Marketplace.
 * Provides resilient server-side asset uploading, deletion, and URL resolution.
 */
class CloudinaryService
{
    public const FOLDER_BUSINESS_PERMITS = 'blax/business_permits';
    public const FOLDER_PRINTING_DOCS    = 'blax/printing/documents';
    public const FOLDER_PRINTING_REFS    = 'blax/printing/references';
    public const FOLDER_PROFILES         = 'blax/profiles';
    public const FOLDER_SHOP_LOGOS       = 'blax/shop_logos';
    public const FOLDER_PRODUCTS         = 'blax/products';
    public const FOLDER_CMS              = 'blax/cms';

    /**
     * Standardized responsive image delivery variants.
     * All variants automatically apply optimal WebP/AVIF format (f_auto) and compression (q_auto).
     */
    public const VARIANTS = [
        'avatar'    => 'c_fill,g_face,w_100,h_100,f_auto,q_auto',
        'thumbnail' => 'c_fill,w_160,h_160,f_auto,q_auto',
        'card'      => 'c_limit,w_480,h_480,f_auto,q_auto',
        'detail'    => 'c_limit,w_960,h_960,f_auto,q_auto',
        'logo'      => 'c_limit,w_200,h_200,f_auto,q_auto',
        'banner'    => 'c_limit,w_1440,f_auto,q_auto',
    ];

    protected UploadApi $uploadApi;
    protected AdminApi $adminApi;
    protected CloudinaryConfig $config;
    protected bool $initialized = false;

    public function __construct(?CloudinaryConfig $config = null)
    {
        $this->config = $config ?? config(CloudinaryConfig::class);
        $this->initCloudinary();
    }

    /**
     * Check if Cloudinary credentials are fully configured in the environment.
     */
    public function isConfigured(): bool
    {
        return !empty($this->config->cloudName) && !empty($this->config->apiKey) && !empty($this->config->apiSecret);
    }

    /**
     * Initialize Cloudinary SDK configuration using separate environment variables.
     */
    protected function initCloudinary(): void
    {
        $cldConfig = new Configuration([
            'cloud' => [
                'cloud_name' => $this->config->cloudName ?: 'blax',
                'api_key'    => $this->config->apiKey,
                'api_secret' => $this->config->apiSecret,
            ],
            'url' => [
                'secure' => true,
            ],
        ]);

        $this->uploadApi   = new UploadApi($cldConfig);
        $this->adminApi    = new AdminApi($cldConfig);
        $this->initialized = true;
    }

    /**
     * Upload an image file (JPG, PNG, WEBP, GIF) to Cloudinary.
     * Applies safe maximum dimension bounding (1920x1920) to limit storage usage.
     *
     * @param string|UploadedFile $file
     * @param string $folder
     * @param string|null $publicId
     * @param array $options
     * @return array|null Returns ['secure_url' => ..., 'public_id' => ..., 'format' => ..., 'bytes' => ...] or null on failure
     */
    public function uploadImage($file, string $folder, ?string $publicId = null, array $options = []): ?array
    {
        $defaultOptions = [
            'transformation' => [
                'width'  => 1920,
                'height' => 1920,
                'crop'   => 'limit',
            ],
        ];

        return $this->uploadAsset($file, $folder, 'image', $publicId, array_merge($defaultOptions, $options));
    }

    /**
     * Upload a raw/document file (PDF, DOCX, ZIP, etc.) to Cloudinary.
     * Raw uploads are preserved untouched without transformations.
     *
     * @param string|UploadedFile $file
     * @param string $folder
     * @param string|null $publicId
     * @param array $options
     * @return array|null Returns ['secure_url' => ..., 'public_id' => ..., 'format' => ..., 'bytes' => ...] or null on failure
     */
    public function uploadRawFile($file, string $folder, ?string $publicId = null, array $options = []): ?array
    {
        return $this->uploadAsset($file, $folder, 'raw', $publicId, $options);
    }

    /**
     * Transforms a Cloudinary delivery URL with named presets (card, thumbnail, avatar, detail, logo, banner).
     * Pure string manipulation without server-side API calls.
     */
    public static function transformUrl(?string $url, string $variant = 'card'): string
    {
        if ($url === null || trim($url) === '') {
            return '';
        }

        $url = trim($url);

        // Only transform Cloudinary URLs
        if (!str_contains($url, 'res.cloudinary.com') && !str_contains($url, 'cloudinary.com')) {
            return $url;
        }

        // Do not transform raw resources (PDFs, docs)
        if (str_contains($url, '/raw/upload/')) {
            return $url;
        }

        $transform = self::VARIANTS[$variant] ?? (self::VARIANTS['card'] ?? 'f_auto,q_auto');
        if ($transform === '') {
            return $url;
        }

        $prefix = '/image/upload/';
        $pos = strpos($url, $prefix);
        if ($pos === false) {
            return $url;
        }

        $base = substr($url, 0, $pos + strlen($prefix));
        $rest = substr($url, $pos + strlen($prefix));

        // Check if $rest starts with an existing transformation segment
        if (preg_match('#^([a-z0-9_:,.-]+)/(.*)$#i', $rest, $matches)) {
            $firstSegment = $matches[1];
            $afterFirst   = $matches[2];

            $isVersion = (bool) preg_match('#^v\d+$#', $firstSegment);
            $isFolder  = ($firstSegment === 'blax' || str_starts_with($firstSegment, 'blax_'));

            if (!$isVersion && !$isFolder) {
                return $base . $transform . '/' . $afterFirst;
            }
        }

        return $base . $transform . '/' . $rest;
    }

    /**
     * Core asset upload implementation.
     */
    protected function uploadAsset($file, string $folder, string $resourceType, ?string $publicId = null, array $options = []): ?array
    {
        if (!$this->isConfigured()) {
            log_message('error', 'CloudinaryService::uploadAsset: Cloudinary is not configured. Check CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, and CLOUDINARY_API_SECRET in environment.');
            return null;
        }

        $filePath = $this->resolveFilePath($file);
        if ($filePath === null || !is_file($filePath)) {
            log_message('error', 'CloudinaryService::uploadAsset: Invalid or missing file path.');
            return null;
        }

        $params = array_merge([
            'folder'        => $folder,
            'resource_type' => $resourceType,
            'overwrite'     => true,
            'invalidate'    => true,
        ], $options);

        if ($publicId !== null && trim($publicId) !== '') {
            $params['public_id'] = $publicId;
        }

        try {
            $response = $this->uploadApi->upload($filePath, $params);

            return [
                'secure_url'    => (string) ($response['secure_url'] ?? $response['url'] ?? ''),
                'public_id'     => (string) ($response['public_id'] ?? ''),
                'resource_type' => (string) ($response['resource_type'] ?? $resourceType),
                'format'        => (string) ($response['format'] ?? ''),
                'bytes'         => (int) ($response['bytes'] ?? 0),
                'created_at'    => (string) ($response['created_at'] ?? date('Y-m-d H:i:s')),
            ];
        } catch (\Throwable $e) {
            log_message('error', "Cloudinary upload failed ({$resourceType} in {$folder}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete an asset from Cloudinary using its public_id.
     *
     * @param string $publicId
     * @param string $resourceType 'image' or 'raw'
     * @return bool
     */
    public function deleteAsset(string $publicId, string $resourceType = 'image'): bool
    {
        $publicId = trim($publicId);
        if ($publicId === '') {
            return false;
        }

        try {
            $result = $this->uploadApi->destroy($publicId, [
                'resource_type' => $resourceType,
                'invalidate'    => true,
            ]);

            return isset($result['result']) && in_array($result['result'], ['ok', 'not found'], true);
        } catch (\Throwable $e) {
            log_message('error', "Cloudinary destroy failed for [{$publicId}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a given URL points to Cloudinary storage.
     */
    public function isCloudinaryUrl(?string $url): bool
    {
        if ($url === null || trim($url) === '') {
            return false;
        }

        return str_contains($url, 'res.cloudinary.com') || str_contains($url, 'cloudinary.com');
    }

    /**
     * Extract public_id from a Cloudinary URL.
     */
    public function extractPublicId(?string $url): ?string
    {
        if (!$this->isCloudinaryUrl($url)) {
            return null;
        }

        $parsed = parse_url((string) $url, PHP_URL_PATH);
        if (!$parsed) {
            return null;
        }

        $isRaw = str_contains($parsed, '/raw/upload/');

        if ($isRaw) {
            if (preg_match('#/upload/(?:v\d+/)?(.+)$#', $parsed, $matches)) {
                return $matches[1];
            }
        } else {
            if (preg_match('#/upload/(?:v\d+/)?(.+?)(?:\.[a-zA-Z0-9]+)?$#', $parsed, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Resolves the real filesystem path of a file parameter.
     */
    protected function resolveFilePath($file): ?string
    {
        if ($file instanceof UploadedFile) {
            if (!$file->isValid()) {
                return null;
            }
            return $file->getRealPath() ?: $file->getTempName();
        }

        if (is_string($file) && is_file($file)) {
            return $file;
        }

        return null;
    }
}
