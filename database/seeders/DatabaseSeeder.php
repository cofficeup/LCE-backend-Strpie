<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user if not exists
        $admin = User::where('email', 'admin@lce.com')->first();

        if (!$admin) {
            User::create([
                'email' => 'admin@lce.com',
                'user_md' => md5('password'), // Legacy MD5 hash
                'first_name' => 'Admin',
                'last_name' => 'User',
                'phone_1' => '555-0100',
                'address_1' => '123 Admin St',
                'city' => 'San Francisco',
                'state' => 'CA',
                'zip' => '94065',
                'country' => 'US',
                'customer_type' => 'admin',
                // Required legacy columns with NOT NULL
                'wash_fold_instructions' => '',
                'custom_minimum_charge' => 0,
            ]);
        }

        // Create test customer if not exists
        $customer = User::where('email', 'customer@example.com')->first();

        if (!$customer) {
            User::create([
                'email' => 'customer@example.com',
                'user_md' => md5('password'), // Legacy MD5 hash
                'first_name' => 'Test',
                'last_name' => 'Customer',
                'phone_1' => '555-0101',
                'address_1' => '456 Customer Ave',
                'city' => 'San Carlos',
                'state' => 'CA',
                'zip' => '94065',
                'country' => 'US',
                'customer_type' => 'residential',
                // Required legacy columns with NOT NULL
                'wash_fold_instructions' => '',
                'custom_minimum_charge' => 0,
            ]);
        }

        $this->command->info('Database seeding completed!');
        $this->command->info('Admin: admin@lce.com / password');
        $this->command->info('Customer: customer@example.com / password');

        // Seed zones and plans
        $this->call([
            ZoneSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);
    }
}
