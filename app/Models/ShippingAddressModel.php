<?php

namespace App\Models;

use CodeIgniter\Model;

class ShippingAddressModel extends Model
{
    protected $table            = 'shipping_addresses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'province',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'place_id',
        'geocoded_at',
        'is_default',
        'created_at',
        'updated_at',
    ];
}
