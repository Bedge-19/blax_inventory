<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class TextBee extends BaseConfig
{
    /**
     * TextBee API Key.
     */
    public string $apiKey = '';

    /**
     * TextBee Android Device ID.
     */
    public string $deviceId = '';

    /**
     * TextBee Gateway Base URL.
     */
    public string $baseUrl = 'https://api.textbee.dev/api/v1';

    public function __construct()
    {
        parent::__construct();

        $this->apiKey   = (string) (env('TEXTBEE_API_KEY') ?: $this->apiKey);
        $this->deviceId = (string) (env('TEXTBEE_DEVICE_ID') ?: $this->deviceId);
        $this->baseUrl  = rtrim((string) (env('TEXTBEE_BASE_URL') ?: $this->baseUrl), '/');
    }
}
