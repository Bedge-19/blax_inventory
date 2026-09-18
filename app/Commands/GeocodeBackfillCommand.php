<?php

namespace App\Commands;

use App\Models\ShopModel;
use App\Models\ShippingAddressModel;
use App\Services\GoogleMapsService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GeocodeBackfillCommand extends BaseCommand
{
    protected $group       = 'Maps';
    protected $name        = 'geocode:backfill';
    protected $description = 'Backfill missing latitude/longitude coordinates for shops and shipping addresses via Google Geocoding API.';
    protected $usage       = 'geocode:backfill';

    public function run(array $params)
    {
        CLI::write('Starting Geocoding Backfill...', 'yellow');

        $mapsService = new GoogleMapsService();
        if (empty($mapsService->getApiKey())) {
            CLI::error('GOOGLE_MAPS_API_KEY is not configured in .env or GoogleMaps config.');
            return EXIT_ERROR;
        }

        $shopModel = new ShopModel();
        $addressModel = new ShippingAddressModel();

        // 1. Backfill Shops
        $shops = $shopModel->groupStart()
            ->where('latitude IS NULL', null, false)
            ->orWhere('longitude IS NULL', null, false)
            ->groupEnd()
            ->findAll();

        CLI::write('Found ' . count($shops) . ' shop(s) needing geocoding.', 'cyan');

        $geocodedShops = 0;
        foreach ($shops as $shop) {
            $addressParts = array_filter([
                $shop['street'] ?? '',
                $shop['barangay'] ?? '',
                $shop['address_line'] ?? '',
                $shop['city'] ?? 'Polomolok',
                $shop['province'] ?? 'South Cotabato',
            ]);

            $addressStr = implode(', ', $addressParts);
            CLI::write("Geocoding shop #{$shop['id']} ('{$shop['shop_name']}'): {$addressStr}...");

            $geo = $mapsService->geocodeAddress($addressStr);
            if ($geo) {
                $shopModel->update($shop['id'], [
                    'latitude'    => $geo['lat'],
                    'longitude'   => $geo['lng'],
                    'geocoded_at' => date('Y-m-d H:i:s'),
                ]);
                $geocodedShops++;
                CLI::write("  -> Success: [{$geo['lat']}, {$geo['lng']}]", 'green');
            } else {
                CLI::write("  -> Failed to geocode address.", 'red');
            }

            usleep(100000); // 100ms rate limit
        }

        // 2. Backfill Shipping Addresses
        $addresses = $addressModel->groupStart()
            ->where('latitude IS NULL', null, false)
            ->orWhere('longitude IS NULL', null, false)
            ->groupEnd()
            ->findAll();

        CLI::write('Found ' . count($addresses) . ' shipping address(es) needing geocoding.', 'cyan');

        $geocodedAddresses = 0;
        foreach ($addresses as $addr) {
            $addressParts = array_filter([
                $addr['address_line1'] ?? '',
                $addr['address_line2'] ?? '',
                $addr['city'] ?? 'Polomolok',
                $addr['province'] ?? 'South Cotabato',
                $addr['country'] ?? 'Philippines',
            ]);

            $addressStr = implode(', ', $addressParts);
            CLI::write("Geocoding address #{$addr['id']} ('{$addr['recipient_name']}'): {$addressStr}...");

            $geo = $mapsService->geocodeAddress($addressStr);
            if ($geo) {
                $addressModel->update($addr['id'], [
                    'latitude'    => $geo['lat'],
                    'longitude'   => $geo['lng'],
                    'place_id'    => $geo['place_id'] ?? null,
                    'geocoded_at' => date('Y-m-d H:i:s'),
                ]);
                $geocodedAddresses++;
                CLI::write("  -> Success: [{$geo['lat']}, {$geo['lng']}]", 'green');
            } else {
                CLI::write("  -> Failed to geocode address.", 'red');
            }

            usleep(100000); // 100ms rate limit
        }

        CLI::write("Backfill finished. Geocoded {$geocodedShops} shop(s) and {$geocodedAddresses} address(es).", 'green');
        return EXIT_SUCCESS;
    }
}
