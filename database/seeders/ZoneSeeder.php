<?php

namespace Database\Seeders;

use App\Models\PickupZone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        // 1. San Francisco (Full Service)
        PickupZone::create([
            'zip_code' => '94105',
            'city' => 'San Francisco',
            'state' => 'CA',
            'service_monday' => true,
            'service_tuesday' => true,
            'service_wednesday' => true,
            'service_thursday' => true,
            'service_friday' => true,
            'active' => true,
            'geo_enabled' => false,
        ]);

        // 2. Redwood City (MWF)
        PickupZone::create([
            'zip_code' => '94065',
            'city' => 'Redwood City',
            'state' => 'CA',
            'service_monday' => true,
            'service_wednesday' => true,
            'service_friday' => true,
            'active' => true,
        ]);

        // 3. Palo Alto (TTh)
        PickupZone::create([
            'zip_code' => '94301',
            'city' => 'Palo Alto',
            'state' => 'CA',
            'service_tuesday' => true,
            'service_thursday' => true,
            'active' => true,
        ]);
    }
}
