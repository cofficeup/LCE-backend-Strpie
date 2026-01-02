<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        PromoCode::create([
            'code' => 'WELCOME20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'max_uses' => 100,
            'description' => '20% off for new customers',
            'active' => true,
        ]);

        PromoCode::create([
            'code' => 'SAVE10',
            'discount_type' => 'fixed_amount',
            'discount_value' => 10,
            'description' => '$10 off your order',
            'active' => true,
        ]);
    }
}
