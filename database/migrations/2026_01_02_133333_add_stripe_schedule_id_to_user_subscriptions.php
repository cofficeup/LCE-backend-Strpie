<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add stripe_schedule_id to track subscription schedules.
     * This prevents duplicate schedules and orphaned Stripe objects.
     */
    public function up(): void
    {
        Schema::table('lce_user_subscriptions', function (Blueprint $table) {
            // Store Stripe subscription schedule ID for downgrades/plan changes
            $table->string('stripe_schedule_id')->nullable()->after('stripe_customer_id');

            // Index for quick lookup
            $table->index('stripe_schedule_id');
        });
    }

    public function down(): void
    {
        Schema::table('lce_user_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['stripe_schedule_id']);
            $table->dropColumn('stripe_schedule_id');
        });
    }
};
