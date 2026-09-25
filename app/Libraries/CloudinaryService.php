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

    protected ?UploadApi $uploadApi = null;
    protected ?AdminApi $adminApi = null;
    protected CloudinaryConfig $config;
    protected bool $initialized = false;
    protected ?string $lastError = null;
    protected ?array $lastErrorDetails = null;

    public function __construct(?CloudinaryConfig $config = null)
    {
        $this->config = $config ?? config(CloudinaryConfig::class);
        $this->initCloudinary();
    }

    /**
     * Check if Cloudinary credentials are fully configured and valid.
     */
    public function isConfigured(): bool
    {
        $dummyCloudNames = ['blax', 'your_cloud_name', 'your-cloud-name', 'placeholder', '<your_cloud_name>'];
        $cleanCloudName = strtolower(trim(str_replace(['<', '>'], '', $this->config->cloudName ?? '')));

        if (empty($cleanCloudName) || in_array($cleanCloudName, $dummyCloudNames, true)) {
            return false;
        }

        $cleanKey    = trim(str_replace(['<', '>'], '', $this->config->apiKey ?? ''));
        $cleanSecret = trim(str_replace(['<', '>'], '', $this->config->apiSecret ?? ''));

        return !empty($cleanKey)
            && !empty($cleanSecret)
            && !str_starts_with(strtolower($cleanKey), 'your_')
            && !str_starts_with(strtolower($cleanSecret), 'your_');
    }

    /**
     * Get the last error message recorded during an upload or API call.
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Get the last error details array.
     */
    public function getLastErrorDetails(): ?array
    {
        return $this->lastErrorDetails;
    }

    /**
     * Get the Cloudinary configuration instance.
     */
    public function getConfig(): CloudinaryConfig
    {
        return $this->config;
    }

    /**
     * Test Cloudinary connection and credentials.
     * Returns an informative diagnostics array with success status, error details, and actionable hints.
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Cloudinary is not configured. Cloud name is missing or set to placeholder.',
                'details' => [
                    'cloud_name'     => $this->config->cloudName,
                    'has_api_key'    => !empty($this->config->apiKey),
                    'has_api_secret' => !empty($this->config->apiSecret),
                ],
                'hint' => 'Ensure CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, and CLOUDINARY_API_SECRET are set in .env without angle brackets.',
            ];
        }

        try {
            if ($this->adminApi === null) {
                $this->initCloudinary();
            }
            $res = $this->adminApi->ping();
            return [
                'success' => true,
                'message' => 'Cloudinary connection verified successfully.',
                'details' => $res,
            ];
        } catch (\Throwable $e) {
            $rawMsg = $e->getMessage();
            $hint = '';
            if (str_contains(strtolower($rawMsg), 'cloud_name mismatch')) {
                $hint = "Your API Key and Secret are valid, but the cloud name '{$this->config->cloudName}' does not match your Cloudinary account. Log in to https://console.cloudinary.com and copy the exact 'Cloud name' from your dashboard into CLOUDINARY_CLOUD_NAME.";
            } elseif (str_contains(strtolower($rawMsg), 'invalid api_key') || str_contains(strtolower($rawMsg), 'unknown api_key')) {
                $hint = 'The provided CLOUDINARY_API_KEY was not recognized by Cloudinary. Verify the API key in your Cloudinary console.';
            } elseif (str_contains(strtolower($rawMsg), 'signature') || str_contains(strtolower($rawMsg), 'authorization')) {
                $hint = 'Authentication failed. Check that CLOUDINARY_API_SECRET is correct.';
            }

            return [
                'success' => false,
                'message' => $rawMsg,
                'details' => ['raw_error' => $rawMsg],
                'hint'    => $hint,
            ];
        }
    }

    /**
     * Initialize Cloudinary SDK configuration using sanitized URL or individual credentials.
     */
    protected function initCloudinary(): void
    {
        $cleanUrl = $this->sanitizeString($this->config->cloudinaryUrl ?? '');

        if (!empty($cleanUrl) && str_starts_with($cleanUrl, 'cloudinary://')) {
            $cldConfig = Configuration::instance($cleanUrl);
        } else {
            $cldConfig = new Configuration([
                'cloud' => [
                    'cloud_name' => $this->sanitizeString($this->config->cloudName ?? ''),
                    'api_key'    => $this->sanitizeString($this->config->apiKey ?? ''),
                    'api_secret' => $this->sanitizeString($this->config->apiSecret ?? ''),
                ],
                'url' => [
                    'secure' => true,
                ],
            ]);
        }

        $this->uploadApi   = new UploadApi($cldConfig);
        $this->adminApi    = new AdminApi($cldConfig);
        $this->initialized = true;
    }

    /**
     * Upload an image file (JPG, PNG, WEBP, GIF) to Cloudinary.
     * Applies safe maximum dimension bounding (1920x1920) and optimal compression.
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
            'quality'        => 'auto:good',
            'fetch_format'   => 'auto',
            'flags'          => 'progressive',
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
     * Core asset upload implementation with automatic transient retry.
     */
    protected function uploadAsset($file, string $folder, string $resourceType, ?string $publicId = null, array $options = []): ?array
    {
        $this->lastError = null;
        $this->lastErrorDetails = null;

        if (!$this->isConfigured()) {
            $this->lastError = "Cloudinary is not configured. Cloud name '{$this->config->cloudName}' is invalid or missing credentials.";
            log_message('warning', "CloudinaryService::uploadAsset: {$this->lastError}");
            return null;
        }

        $filePath = $this->resolveFilePath($file);
        if ($filePath === null || !is_file($filePath)) {
            $this->lastError = 'Invalid or missing file path for upload.';
            log_message('error', "CloudinaryService::uploadAsset: {$this->lastError}");
            return null;
        }

        $params = array_merge([
            'folder'        => $folder,
            'resource_type' => $resourceType,
            'overwrite'     => true,
            'invalidate'    => true,
        ], $options);

        if ($publicId !== null && trim($publicId) !== '') {
            $params['public_id'] = $this->sanitizePublicId($publicId);
        }

        if ($this->uploadApi === null) {
            $this->initCloudinary();
        }

        $maxAttempts = 2;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;
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
                $msg = $e->getMessage();
                $this->lastError = $msg;
                $this->lastErrorDetails = ['exception' => get_class($e), 'message' => $msg, 'attempt' => $attempt];

                // If authentication or cloud name mismatch error, fail fast without useless retry
                if (str_contains(strtolower($msg), 'cloud_name mismatch')
                    || str_contains(strtolower($msg), 'invalid api_key')
                    || str_contains(strtolower($msg), 'unknown api_key')
                    || str_contains(strtolower($msg), 'signature')
                ) {
                    log_message('error', "[Cloudinary] Authentication failure: {$msg}");
                    return null;
                }

                if ($attempt < $maxAttempts) {
                    usleep(200000); // 200ms backoff before retry
                    continue;
                }

                log_message('error', "Cloudinary upload failed ({$resourceType} in {$folder}) after {$attempt} attempts: {$msg}");
                return null;
            }
        }

        return null;
    }

    /**
     * Resilient high-level asset upload:
     * Attempts Cloudinary upload first. If Cloudinary is unconfigured or encounters an error,
     * it gracefully falls back to local disk storage (public/uploads/{localSubdir}) when
     * allowLocalFallback is enabled.
     *
     * @param string|UploadedFile $file
     * @param string $cldFolder Cloudinary folder constant (e.g. FOLDER_SHOP_LOGOS)
     * @param string $localSubdir Subdirectory relative to public/uploads/ (e.g. 'shop_logos')
     * @param string|null $publicId
     * @param string $resourceType 'image' or 'raw'
     * @param array $options
     * @return string|null Returns Cloudinary HTTPS URL or relative local path 'uploads/...'
     */
    public function uploadOrFallback(
        $file,
        string $cldFolder,
        string $localSubdir,
        ?string $publicId = null,
        string $resourceType = 'image',
        array $options = []
    ): ?string {
        // 1. Attempt Cloudinary upload if configured
        if ($this->isConfigured()) {
            try {
                $uploadRes = ($resourceType === 'raw')
                    ? $this->uploadRawFile($file, $cldFolder, $publicId, $options)
                    : $this->uploadImage($file, $cldFolder, $publicId, $options);

                if ($uploadRes && !empty($uploadRes['secure_url'])) {
                    return $uploadRes['secure_url'];
                }
            } catch (\Throwable $e) {
                $this->lastError = $e->getMessage();
                log_message('error', "[CloudinaryService::uploadOrFallback] Cloudinary exception: " . $e->getMessage());
            }
        }

        // 2. If Cloudinary failed or unconfigured, check if local fallback is allowed
        if (!$this->config->allowLocalFallback) {
            log_message('error', "[CloudinaryService::uploadOrFallback] Cloudinary upload unavailable/failed (" . ($this->lastError ?? 'unconfigured') . ") and local fallback is disabled in this environment.");
            return null;
        }

        // 3. Perform resilient local disk storage write
        $cleanSubdir = trim(str_replace(['..', '\\'], ['', '/'], $localSubdir), '/');
        $targetDir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanSubdir);

        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            log_message('error', "[CloudinaryService::uploadOrFallback] Failed to create local directory: {$targetDir}");
            return null;
        }

        try {
            if ($file instanceof UploadedFile) {
                if (!$file->isValid() || $file->hasMoved()) {
                    log_message('error', "[CloudinaryService::uploadOrFallback] Invalid uploaded file or file already moved.");
                    return null;
                }
                $newName = $file->getRandomName();
                $file->move($targetDir, $newName);
                $relPath = 'uploads/' . $cleanSubdir . '/' . $newName;
            } elseif (is_string($file) && is_file($file)) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $newName = time() . '_' . bin2hex(random_bytes(6)) . ($ext ? '.' . $ext : '');
                if (!@copy($file, $targetDir . DIRECTORY_SEPARATOR . $newName)) {
                    log_message('error', "[CloudinaryService::uploadOrFallback] Failed to copy file to local target.");
                    return null;
                }
                $relPath = 'uploads/' . $cleanSubdir . '/' . $newName;
            } else {
                log_message('error', "[CloudinaryService::uploadOrFallback] Unsupported file parameter provided.");
                return null;
            }

            log_message('notice', "[CloudinaryService::uploadOrFallback] Cloudinary unconfigured/failed (" . ($this->lastError ?? 'not configured') . "). Stored safely to local disk: {$relPath}");
            return $relPath;
        } catch (\Throwable $e) {
            log_message('error', "[CloudinaryService::uploadOrFallback] Local fallback storage error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete an existing asset, whether it is hosted on Cloudinary or local disk.
     */
    public function deleteOldAsset(?string $assetUrl, string $resourceType = 'image'): bool
    {
        if ($assetUrl === null || trim($assetUrl) === '') {
            return false;
        }

        $assetUrl = trim($assetUrl);

        if ($this->isCloudinaryUrl($assetUrl)) {
            $publicId = $this->extractPublicId($assetUrl);
            return $publicId ? $this->deleteAsset($publicId, $resourceType) : false;
        }

        if (str_starts_with($assetUrl, 'uploads/')) {
            $cleanRel = trim(str_replace(['..', '\\'], ['', '/'], $assetUrl), '/');
            $localPath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $cleanRel);
            if (is_file($localPath)) {
                return @unlink($localPath);
            }
        }

        return false;
    }

    /**
     * Delete an asset from Cloudinary using its public_id.
     */
    public function deleteAsset(string $publicId, string $resourceType = 'image'): bool
    {
        $publicId = trim($publicId);
        if ($publicId === '' || !$this->isConfigured()) {
            return false;
        }

        try {
            if ($this->uploadApi === null) {
                $this->initCloudinary();
            }
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

    /**
     * Sanitize string removing quotes and angle brackets.
     */
    protected function sanitizeString(string $val): string
    {
        return str_replace(['<', '>'], '', trim($val, " \t\n\r\0\x0B\"'<>"));
    }

    /**
     * Sanitize public_id for safe Cloudinary asset naming.
     */
    protected function sanitizePublicId(string $id): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-\/]/', '_', trim($id));
    }
}
