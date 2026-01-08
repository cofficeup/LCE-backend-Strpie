<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PickupZone;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            [
                'zip' => '94065',
                'city' => 'San Carlos',
                'state' => 'CA',
                'day_monday' => true,
                'day_tuesday' => false,
                'day_wednesday' => true,
                'day_thursday' => false,
                'day_friday' => true,
                'area' => 'SC',
                'geo_location' => 'SF',
                'order' => 1,
            ],
            [
                'zip' => '94402',
                'city' => 'San Mateo',
                'state' => 'CA',
                'day_monday' => true,
                'day_tuesday' => true,
                'day_wednesday' => false,
                'day_thursday' => true,
                'day_friday' => false,
                'area' => 'SM',
                'geo_location' => 'SF',
                'order' => 2,
            ],
        ];

        foreach ($zones as $zone) {
            PickupZone::updateOrCreate(
                ['zip' => $zone['zip']],
                $zone
            );
        }

        $this->command->info('Seeded ' . count($zones) . ' pickup zones');
    }
}
