<?php

namespace App\Services;

use Config\GoogleMaps;

class GoogleMapsService
{
    private string $apiKey;
    private GoogleMaps $config;

    public function __construct(?string $apiKey = null)
    {
        $this->config = config('GoogleMaps') ?? new GoogleMaps();
        $this->apiKey = $apiKey ?: $this->config->apiKey;
    }

    /**
     * Get API key
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Geocode an address string to lat/lng coordinates using Google Geocoding API.
     *
     * @param string $addressText Full or partial address text
     * @return array{lat: float, lng: float, place_id: string|null, formatted_address: string|null}|null
     */
    public function geocodeAddress(string $addressText): ?array
    {
        $addressText = trim($addressText);
        if ($addressText === '' || empty($this->apiKey)) {
            return null;
        }

        // Add Polomolok, South Cotabato, Philippines bias if not already present in the string
        $searchAddress = $addressText;
        if (!preg_match('/polomolok/i', $searchAddress)) {
            $searchAddress .= ', Polomolok, South Cotabato';
        }
        if (!preg_match('/philippines/i', $searchAddress)) {
            $searchAddress .= ', Philippines';
        }

        $boundsParam = sprintf(
            '%F,%F|%F,%F',
            $this->config->bounds['south'],
            $this->config->bounds['west'],
            $this->config->bounds['north'],
            $this->config->bounds['east']
        );

        $params = [
            'address' => $searchAddress,
            'key'     => $this->apiKey,
            'region'  => $this->config->region,
            'bounds'  => $boundsParam,
        ];

        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query($params);

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $err = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($err) {
                log_message('warning', '[GoogleMapsService] Geocoding cURL error: ' . $err);
                return null;
            }

            if ($httpCode !== 200 || !$response) {
                log_message('warning', '[GoogleMapsService] Geocoding HTTP ' . $httpCode);
                return null;
            }

            $data = json_decode($response, true);
            if (!is_array($data) || ($data['status'] ?? '') !== 'OK' || empty($data['results'])) {
                $errDetail = $data['error_message'] ?? ($data['status'] ?? 'EMPTY');
                log_message('notice', '[GoogleMapsService] Geocoding error (' . ($data['status'] ?? 'UNKNOWN') . '): ' . $errDetail . ' for address: ' . $addressText);
                return null;
            }

            $result = $data['results'][0];
            $location = $result['geometry']['location'] ?? null;

            if (!$location || !isset($location['lat'], $location['lng'])) {
                return null;
            }

            return [
                'lat'               => (float) $location['lat'],
                'lng'               => (float) $location['lng'],
                'place_id'          => $result['place_id'] ?? null,
                'formatted_address' => $result['formatted_address'] ?? null,
            ];
        } catch (\Throwable $e) {
            log_message('error', '[GoogleMapsService] Geocoding exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Compute a road route between origin and destination using the Google Routes API (v2).
     *
     * @param array{lat: float, lng: float} $origin
     * @param array{lat: float, lng: float} $destination
     * @param string $travelMode "DRIVE" or "TWO_WHEELER"
     * @return array{encodedPolyline: string, distanceMeters: int, duration: string}|null
     */
    public function computeRoute(array $origin, array $destination, string $travelMode = 'DRIVE'): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $originLat = (float) ($origin['lat'] ?? $origin['latitude'] ?? 0);
        $originLng = (float) ($origin['lng'] ?? $origin['longitude'] ?? 0);
        $destLat   = (float) ($destination['lat'] ?? $destination['latitude'] ?? 0);
        $destLng   = (float) ($destination['lng'] ?? $destination['longitude'] ?? 0);

        if ($originLat == 0 || $originLng == 0 || $destLat == 0 || $destLng == 0) {
            return null;
        }

        $url = 'https://routes.googleapis.com/directions/v2:computeRoutes';

        $payload = [
            'origin' => [
                'location' => [
                    'latLng' => [
                        'latitude'  => $originLat,
                        'longitude' => $originLng,
                    ],
                ],
            ],
            'destination' => [
                'location' => [
                    'latLng' => [
                        'latitude'  => $destLat,
                        'longitude' => $destLng,
                    ],
                ],
            ],
            'travelMode'        => $travelMode,
            'routingPreference' => 'TRAFFIC_UNAWARE',
        ];

        $headers = [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . $this->apiKey,
            'X-Goog-FieldMask: routes.duration,routes.distanceMeters,routes.polyline.encodedPolyline',
        ];

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            $response = curl_exec($ch);
            $err = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($err) {
                log_message('warning', '[GoogleMapsService] Routes API cURL error: ' . $err);
                return null;
            }

            if ($httpCode !== 200 || !$response) {
                log_message('warning', '[GoogleMapsService] Routes API HTTP ' . $httpCode . ' response: ' . $response);
                return null;
            }

            $data = json_decode($response, true);
            if (!is_array($data) || empty($data['routes'])) {
                log_message('notice', '[GoogleMapsService] Routes API returned no routes.');
                return null;
            }

            $firstRoute = $data['routes'][0];

            return [
                'encodedPolyline' => $firstRoute['polyline']['encodedPolyline'] ?? '',
                'distanceMeters'  => (int) ($firstRoute['distanceMeters'] ?? 0),
                'duration'        => (string) ($firstRoute['duration'] ?? ''),
            ];
        } catch (\Throwable $e) {
            log_message('error', '[GoogleMapsService] Routes API exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Helper to compose an address line from parts.
     */
    public function buildAddressString(array $parts): string
    {
        $filtered = array_filter(array_map('trim', $parts));
        return implode(', ', $filtered);
    }

    /**
     * Default coordinates for Polomolok town center
     */
    public function getDefaultCoordinates(): array
    {
        return [
            'lat' => $this->config->defaultLat,
            'lng' => $this->config->defaultLng,
        ];
    }
}
