<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lce_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed initial values
        DB::table('lce_configurations')->insert([
            ['key' => 'ppo_price_per_lb', 'value' => '2.99', 'description' => 'Pay-Per-Order Price per Pound'],
            ['key' => 'ppo_minimum_charge', 'value' => '30.00', 'description' => 'Minimum Laundry Charge'],
            ['key' => 'fee_pickup_delivery', 'value' => '9.99', 'description' => 'Pickup & Delivery Fee'],
            ['key' => 'fee_service', 'value' => '5.00', 'description' => 'Service Fee'],
            ['key' => 'sub_overage_price_per_lb', 'value' => '2.99', 'description' => 'Subscription Overage Price per Pound'],
            ['key' => 'sub_bag_max_weight', 'value' => '20', 'description' => 'Max Weight per Bag (lbs)'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lce_configurations');
    }
};
