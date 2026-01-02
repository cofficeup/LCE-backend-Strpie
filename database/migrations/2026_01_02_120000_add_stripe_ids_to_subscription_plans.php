<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lce_subscription_plans', function (Blueprint $table) {
            // Stripe Product ID
            $table->string('stripe_product_id')->nullable()->after('is_active');

            // Stripe Price IDs for each billing cycle
            $table->string('stripe_price_id_daily')->nullable()->after('stripe_product_id');
            $table->string('stripe_price_id_weekly')->nullable()->after('stripe_price_id_daily');
            $table->string('stripe_price_id_monthly')->nullable()->after('stripe_price_id_weekly');
            $table->string('stripe_price_id_annual')->nullable()->after('stripe_price_id_monthly');

            // Pricing for daily and weekly (monthly and annual already exist)
            $table->decimal('price_daily', 10, 2)->nullable()->after('description');
            $table->decimal('price_weekly', 10, 2)->nullable()->after('price_daily');

            // Bag allocations per billing cycle
            $table->integer('bags_per_day')->nullable()->after('bags_per_month');
            $table->integer('bags_per_week')->nullable()->after('bags_per_day');

            // Overage policy: block pickup or charge PPO
            $table->enum('overage_policy', ['block', 'charge_ppo'])->default('block')->after('is_active');

            // PPO price for overage charges (when overage_policy = 'charge_ppo')
            $table->decimal('overage_price_per_bag', 10, 2)->nullable()->after('overage_policy');
        });
    }

    public function down(): void
    {
        Schema::table('lce_subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_product_id',
                'stripe_price_id_daily',
                'stripe_price_id_weekly',
                'stripe_price_id_monthly',
                'stripe_price_id_annual',
                'price_daily',
                'price_weekly',
                'bags_per_day',
                'bags_per_week',
                'overage_policy',
                'overage_price_per_bag',
            ]);
        });
    }
};
