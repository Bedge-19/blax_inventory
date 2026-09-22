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
        if ($addressText === '') {
            return null;
        }

        if (empty($this->apiKey)) {
            $centroid = \App\Models\DeliveryModel::getBarangayCoordinate($addressText);
            if ($centroid) {
                return [
                    'lat'               => $centroid['lat'],
                    'lng'               => $centroid['lng'],
                    'place_id'          => null,
                    'formatted_address' => $addressText . ', South Cotabato, Philippines',
                ];
            }
            return null;
        }

        // Add South Cotabato, Philippines bias if not already present
        $searchAddress = $addressText;
        if (!preg_match('/polomolok|tupi/i', $searchAddress)) {
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            $response = curl_exec($ch);
            $err = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($err || $httpCode !== 200 || !$response) {
                $centroid = \App\Models\DeliveryModel::getBarangayCoordinate($addressText);
                if ($centroid) {
                    return [
                        'lat'               => $centroid['lat'],
                        'lng'               => $centroid['lng'],
                        'place_id'          => null,
                        'formatted_address' => $addressText,
                    ];
                }
                return null;
            }

            $data = json_decode($response, true);
            if (!is_array($data) || ($data['status'] ?? '') !== 'OK' || empty($data['results'])) {
                $centroid = \App\Models\DeliveryModel::getBarangayCoordinate($addressText);
                if ($centroid) {
                    return [
                        'lat'               => $centroid['lat'],
                        'lng'               => $centroid['lng'],
                        'place_id'          => null,
                        'formatted_address' => $addressText,
                    ];
                }
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
     * Compute a real road route between origin and destination.
     * Tries Google Routes API first if apiKey exists, then falls back to OSRM road engine.
     *
     * @param array{lat: float, lng: float} $origin
     * @param array{lat: float, lng: float} $destination
     * @param string $travelMode "DRIVE" or "TWO_WHEELER"
     * @return array{encodedPolyline: string, points: array, distanceMeters: int, duration: string, durationMinutes: int}|null
     */
    public function computeRoute(array $origin, array $destination, string $travelMode = 'DRIVE'): ?array
    {
        $originLat = (float) ($origin['lat'] ?? $origin['latitude'] ?? 0);
        $originLng = (float) ($origin['lng'] ?? $origin['longitude'] ?? 0);
        $destLat   = (float) ($destination['lat'] ?? $destination['latitude'] ?? 0);
        $destLng   = (float) ($destination['lng'] ?? $destination['longitude'] ?? 0);

        if ($originLat == 0 || $originLng == 0 || $destLat == 0 || $destLng == 0) {
            return null;
        }

        // 1. If Google Maps API Key is configured, attempt Google Routes API
        if (!empty($this->apiKey)) {
            $googleResult = $this->queryGoogleRoutes($originLat, $originLng, $destLat, $destLng, $travelMode);
            if ($googleResult !== null) {
                return $googleResult;
            }
        }

        // 2. Query OSRM (Open Source Routing Machine) driving API for actual road network path
        return $this->queryOsrmRoutes($originLat, $originLng, $destLat, $destLng);
    }

    private function queryGoogleRoutes(float $oLat, float $oLng, float $dLat, float $dLng, string $travelMode): ?array
    {
        $url = 'https://routes.googleapis.com/directions/v2:computeRoutes';

        $payload = [
            'origin' => [
                'location' => [
                    'latLng' => [
                        'latitude'  => $oLat,
                        'longitude' => $oLng,
                    ],
                ],
            ],
            'destination' => [
                'location' => [
                    'latLng' => [
                        'latitude'  => $dLat,
                        'longitude' => $dLng,
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (!empty($data['routes'][0])) {
                    $firstRoute = $data['routes'][0];
                    $poly = $firstRoute['polyline']['encodedPolyline'] ?? '';
                    $dist = (int) ($firstRoute['distanceMeters'] ?? 0);
                    $durStr = (string) ($firstRoute['duration'] ?? '0s');
                    $durSec = (int) preg_replace('/[^0-9]/', '', $durStr);
                    $distKm = round($dist / 1000, 1);
                    $distText = ($distKm >= 1 ? $distKm . ' km' : $dist . ' m');
                    $durMins = max(1, (int) round($durSec / 60));
                    $durText = $durMins >= 60 ? floor($durMins / 60) . ' hr ' . ($durMins % 60) . ' mins' : $durMins . ' mins';

                    return [
                        'encodedPolyline'  => $poly,
                        'points'           => self::decodePolyline($poly),
                        'distanceMeters'   => $dist,
                        'distance_meters'  => $dist,
                        'distance_text'    => $distText,
                        'duration'         => $durStr,
                        'duration_seconds' => $durSec,
                        'duration_text'    => $durText,
                        'durationMinutes'  => $durMins,
                        'source'           => 'google',
                    ];
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', '[GoogleMapsService] Google Routes API failed: ' . $e->getMessage());
        }

        return null;
    }

    private function queryOsrmRoutes(float $oLat, float $oLng, float $dLat, float $dLng): ?array
    {
        $url = sprintf(
            'https://router.project-osrm.org/route/v1/driving/%F,%F;%F,%F?overview=full&geometries=geojson',
            $oLng,
            $oLat,
            $dLng,
            $dLat
        );

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_USERAGENT, 'BlaxMarketplace/1.0');
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (($data['code'] ?? '') === 'Ok' && !empty($data['routes'][0])) {
                    $route = $data['routes'][0];
                    $coords = $route['geometry']['coordinates'] ?? [];
                    
                    $points = [];
                    foreach ($coords as $c) {
                        if (isset($c[0], $c[1])) {
                            $points[] = [
                                'lat' => (float) $c[1],
                                'lng' => (float) $c[0],
                            ];
                        }
                    }

                    if (empty($points)) {
                        $points = [
                            ['lat' => $oLat, 'lng' => $oLng],
                            ['lat' => $dLat, 'lng' => $dLng],
                        ];
                    }

                    $dist = (int) round($route['distance'] ?? 0);
                    $durSec = (int) round($route['duration'] ?? 0);
                    $distKm = round($dist / 1000, 1);
                    $distText = ($distKm >= 1 ? $distKm . ' km' : $dist . ' m');
                    $durMins = max(1, (int) round($durSec / 60));
                    $durText = $durMins >= 60 ? floor($durMins / 60) . ' hr ' . ($durMins % 60) . ' mins' : $durMins . ' mins';

                    return [
                        'encodedPolyline'  => self::encodePolyline($points),
                        'points'           => $points,
                        'distanceMeters'   => $dist,
                        'distance_meters'  => $dist,
                        'distance_text'    => $distText,
                        'duration'         => $durSec . 's',
                        'duration_seconds' => $durSec,
                        'duration_text'    => $durText,
                        'durationMinutes'  => $durMins,
                        'source'           => 'osrm',
                    ];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[GoogleMapsService] OSRM Routes exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Encode array of ['lat' => ..., 'lng' => ...] points to Google encoded polyline format.
     */
    public static function encodePolyline(array $points): string
    {
        $encoded = '';
        $lastLat = 0;
        $lastLng = 0;
        foreach ($points as $p) {
            $lat = (int) round($p['lat'] * 1e5);
            $lng = (int) round($p['lng'] * 1e5);
            $dLat = $lat - $lastLat;
            $dLng = $lng - $lastLng;
            $lastLat = $lat;
            $lastLng = $lng;

            $encoded .= self::encodePolylineValue($dLat) . self::encodePolylineValue($dLng);
        }
        return $encoded;
    }

    private static function encodePolylineValue(int $val): string
    {
        $val = $val < 0 ? ~($val << 1) : ($val << 1);
        $chunks = '';
        while ($val >= 0x20) {
            $chunks .= chr((0x20 | ($val & 0x1f)) + 63);
            $val >>= 5;
        }
        $chunks .= chr($val + 63);
        return $chunks;
    }

    /**
     * Decode Google encoded polyline string to array of ['lat' => ..., 'lng' => ...]
     */
    public static function decodePolyline(string $encoded): array
    {
        $len = strlen($encoded);
        $index = 0;
        $points = [];
        $lat = 0;
        $lng = 0;

        while ($index < $len) {
            $b = 0;
            $shift = 0;
            $result = 0;
            do {
                if ($index >= $len) break 2;
                $b = ord($encoded[$index++]) - 63;
                $result |= ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b >= 0x20);
            $dlat = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lat += $dlat;

            $shift = 0;
            $result = 0;
            do {
                if ($index >= $len) break 2;
                $b = ord($encoded[$index++]) - 63;
                $result |= ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b >= 0x20);
            $dlng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lng += $dlng;

            $points[] = [
                'lat' => $lat * 1e-5,
                'lng' => $lng * 1e-5,
            ];
        }

        return $points;
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
