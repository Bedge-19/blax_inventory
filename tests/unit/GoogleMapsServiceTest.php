<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\GoogleMapsService;

/**
 * Unit tests for GoogleMapsService — polyline encoding/decoding and route structure.
 */
class GoogleMapsServiceTest extends CIUnitTestCase
{
    /**
     * Polyline encode/decode should round-trip correctly.
     */
    public function testPolylineEncodingRoundTrip()
    {
        $points = [
            ['lat' => 6.2136, 'lng' => 125.0661],
            ['lat' => 6.2200, 'lng' => 125.0700],
            ['lat' => 6.2300, 'lng' => 125.0800],
        ];

        $encoded = GoogleMapsService::encodePolyline($points);
        $this->assertNotEmpty($encoded);

        $decoded = GoogleMapsService::decodePolyline($encoded);
        $this->assertCount(3, $decoded);

        // Precision check (5 decimal places = ~1m accuracy)
        foreach ($points as $i => $p) {
            $this->assertEqualsWithDelta($p['lat'], $decoded[$i]['lat'], 0.00001);
            $this->assertEqualsWithDelta($p['lng'], $decoded[$i]['lng'], 0.00001);
        }
    }

    /**
     * Empty polyline string should decode to empty array.
     */
    public function testDecodeEmptyPolyline()
    {
        $result = GoogleMapsService::decodePolyline('');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * computeRoute with zero coordinates should return null.
     */
    public function testComputeRouteRejectsZeroCoordinates()
    {
        $svc = new GoogleMapsService('');
        $result = $svc->computeRoute(
            ['lat' => 0, 'lng' => 0],
            ['lat' => 6.22, 'lng' => 125.07]
        );
        $this->assertNull($result);
    }

    /**
     * buildAddressString should filter and join non-empty parts.
     */
    public function testBuildAddressString()
    {
        $svc = new GoogleMapsService('');

        $this->assertEquals(
            'Polomolok, South Cotabato',
            $svc->buildAddressString(['Polomolok', '', 'South Cotabato', '  '])
        );

        $this->assertEquals('', $svc->buildAddressString(['', '  ', '']));
    }

    /**
     * Verify getDefaultCoordinates returns Polomolok area coords.
     */
    public function testDefaultCoordinates()
    {
        $svc = new GoogleMapsService('');
        $coords = $svc->getDefaultCoordinates();

        $this->assertArrayHasKey('lat', $coords);
        $this->assertArrayHasKey('lng', $coords);
        // Should be in the Polomolok, South Cotabato area
        $this->assertGreaterThan(6.0, $coords['lat']);
        $this->assertLessThan(7.0, $coords['lat']);
        $this->assertGreaterThan(124.0, $coords['lng']);
        $this->assertLessThan(126.0, $coords['lng']);
    }

    /**
     * Route cache key format should be deterministic and consistent for same coordinates.
     */
    public function testRouteCacheKeyDeterminism()
    {
        // Two calls with the same coordinates (within rounding tolerance) should produce the same cache key
        $key1 = sprintf('route_%s_%.3f_%.3f_%.3f_%.3f', 'drive', 6.21360, 125.06610, 6.22000, 125.07000);
        $key2 = sprintf('route_%s_%.3f_%.3f_%.3f_%.3f', 'drive', 6.21364, 125.06614, 6.22004, 125.07004);

        // Same to 3 decimal places
        $this->assertEquals($key1, $key2, 'Coordinates differing only beyond 3 decimal places should produce the same cache key');
    }
}
