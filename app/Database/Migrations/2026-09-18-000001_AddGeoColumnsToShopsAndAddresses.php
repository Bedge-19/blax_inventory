<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGeoColumnsToShopsAndAddresses extends Migration
{
    public function up()
    {
        // Add geo columns to shops table
        $shopFields = [];
        if (!$this->db->fieldExists('latitude', 'shops')) {
            $shopFields['latitude'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,7',
                'null'       => true,
                'after'      => 'province',
            ];
        }
        if (!$this->db->fieldExists('longitude', 'shops')) {
            $shopFields['longitude'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,7',
                'null'       => true,
                'after'      => 'latitude',
            ];
        }
        if (!$this->db->fieldExists('geocoded_at', 'shops')) {
            $shopFields['geocoded_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'longitude',
            ];
        }

        if (!empty($shopFields)) {
            $this->forge->addColumn('shops', $shopFields);
        }

        // Add geo columns to shipping_addresses table
        $addressFields = [];
        if (!$this->db->fieldExists('latitude', 'shipping_addresses')) {
            $addressFields['latitude'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,7',
                'null'       => true,
                'after'      => 'country',
            ];
        }
        if (!$this->db->fieldExists('longitude', 'shipping_addresses')) {
            $addressFields['longitude'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,7',
                'null'       => true,
                'after'      => 'latitude',
            ];
        }
        if (!$this->db->fieldExists('place_id', 'shipping_addresses')) {
            $addressFields['place_id'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'longitude',
            ];
        }
        if (!$this->db->fieldExists('geocoded_at', 'shipping_addresses')) {
            $addressFields['geocoded_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'place_id',
            ];
        }

        if (!empty($addressFields)) {
            $this->forge->addColumn('shipping_addresses', $addressFields);
        }
    }

    public function down()
    {
        // Drop geo columns from shops table
        $shopDrop = [];
        if ($this->db->fieldExists('latitude', 'shops')) {
            $shopDrop[] = 'latitude';
        }
        if ($this->db->fieldExists('longitude', 'shops')) {
            $shopDrop[] = 'longitude';
        }
        if ($this->db->fieldExists('geocoded_at', 'shops')) {
            $shopDrop[] = 'geocoded_at';
        }

        if (!empty($shopDrop)) {
            $this->forge->dropColumn('shops', $shopDrop);
        }

        // Drop geo columns from shipping_addresses table
        $addressDrop = [];
        if ($this->db->fieldExists('latitude', 'shipping_addresses')) {
            $addressDrop[] = 'latitude';
        }
        if ($this->db->fieldExists('longitude', 'shipping_addresses')) {
            $addressDrop[] = 'longitude';
        }
        if ($this->db->fieldExists('place_id', 'shipping_addresses')) {
            $addressDrop[] = 'place_id';
        }
        if ($this->db->fieldExists('geocoded_at', 'shipping_addresses')) {
            $addressDrop[] = 'geocoded_at';
        }

        if (!empty($addressDrop)) {
            $this->forge->dropColumn('shipping_addresses', $addressDrop);
        }
    }
}
