<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class GoogleMaps extends BaseConfig
{
    /**
     * Google Maps Platform API Key
     */
    public string $apiKey = '';

    /**
     * Default map center (Polomolok, South Cotabato)
     */
    public float $defaultLat = 6.2136;
    public float $defaultLng = 125.0661;
    public int $defaultZoom = 13;

    /**
     * Polomolok bounding box for map restrictions and search biasing
     */
    public array $bounds = [
        'south' => 6.10,
        'west'  => 124.95,
        'north' => 6.32,
        'east'  => 125.18,
    ];

    /**
     * Country / Region biasing code
     */
    public string $region = 'ph';

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = (string) (env('GOOGLE_MAPS_API_KEY') ?? $this->apiKey);
    }
}
