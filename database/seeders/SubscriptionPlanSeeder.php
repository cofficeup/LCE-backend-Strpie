<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'SUB_M_1BAG',
                'name' => 'Subscribe & Save Monthly - 1 Bag',
                'bags_per_month' => 1,
                'price_per_bag' => 70.00,
                'billing_cycle' => 'monthly',
                'annual_discount' => 0.00,
                'active' => true,
            ],
            [
                'code' => 'SUB_M_2BAG',
                'name' => 'Subscribe & Save Monthly - 2 Bags',
                'bags_per_month' => 2,
                'price_per_bag' => 67.00,
                'billing_cycle' => 'monthly',
                'annual_discount' => 0.00,
                'active' => true,
            ],
            [
                'code' => 'SUB_M_4BAG',
                'name' => 'Subscribe & Save Monthly - 4 Bags',
                'bags_per_month' => 4,
                'price_per_bag' => 65.00,
                'billing_cycle' => 'monthly',
                'annual_discount' => 0.00,
                'active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['code' => $plan['code']],
                $plan
            );
        }

        $this->command->info('Seeded ' . count($plans) . ' subscription plans');
    }
}
