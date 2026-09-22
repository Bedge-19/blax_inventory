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
     * Individual credentials as fallback
     */
    public string $cloudName = 'blax';
    public string $apiKey    = '533212736738489';
    public string $apiSecret = '-GXmGMS_v-Pkko35mImPie6O7SM';

    /**
     * Folder prefix in Cloudinary
     */
    public string $rootFolder = 'blax';

    public function __construct()
    {
        parent::__construct();

        $this->cloudinaryUrl = env('CLOUDINARY_URL', $this->cloudinaryUrl);
        $this->cloudName     = env('CLOUDINARY_CLOUD_NAME', $this->cloudName);
        $this->apiKey        = env('CLOUDINARY_API_KEY', $this->apiKey);
        $this->apiSecret     = env('CLOUDINARY_API_SECRET', $this->apiSecret);
    }
}
