<?php

namespace Database\Seeders;

use App\Models\ProcessingSite;
use Illuminate\Database\Seeder;

class ProcessingSiteSeeder extends Seeder
{
    public function run(): void
    {
        ProcessingSite::create([
            'name' => 'Redwood City Hub',
            'code' => 'RWC-01',
            'address_line1' => '123 Industrial Way',
            'city' => 'Redwood City',
            'state' => 'CA',
            'zip_code' => '94063',
            'wash_fold_enabled' => true,
            'dry_clean_enabled' => true,
            'daily_capacity_lbs' => 2000,
            'active' => true,
        ]);
    }
}
