<?php

namespace Database\Seeders;

use App\Models\PriceList;
use App\Models\PricingItem;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    public function run(): void
    {
        // Create Standard Price List
        $standardList = PriceList::create([
            'name' => 'Bay Area Standard',
            'type' => 'residential',
            'zip_codes' => '94065,94105,94301',
            'active' => true,
        ]);

        // Create Pricing Items
        $washFold = PricingItem::create([
            'sku' => 'WF-001',
            'service_type' => 'wash_fold',
            'item_name' => 'Wash & Fold (per lb)',
            'description' => 'Regular laundry service by weight',
            'active' => true,
        ]);

        $comforter = PricingItem::create([
            'sku' => 'DC-001',
            'service_type' => 'dry_clean',
            'item_name' => 'Comforter (Queen)',
            'description' => 'Dry clean for large bedding',
            'active' => true,
        ]);

        // Attach Prices to List
        $standardList->items()->attach($washFold->id, ['price' => 2.50]);
        $standardList->items()->attach($comforter->id, ['price' => 25.00]);
    }
}
