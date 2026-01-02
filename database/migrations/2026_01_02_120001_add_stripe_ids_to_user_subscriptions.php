<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lce_user_subscriptions', function (Blueprint $table) {
            // Stripe Subscription ID
            $table->string('stripe_subscription_id')->nullable()->unique()->after('plan_id');
            $table->string('stripe_customer_id')->nullable()->after('stripe_subscription_id');

            // Stripe billing period tracking
            $table->timestamp('current_period_start')->nullable()->after('next_renewal_date');
            $table->timestamp('current_period_end')->nullable()->after('current_period_start');

            // Cancellation tracking
            $table->boolean('cancel_at_period_end')->default(false)->after('status');
            $table->string('cancel_reason')->nullable()->after('cancel_at_period_end');
            $table->timestamp('cancelled_at')->nullable()->after('cancel_reason');

            // Pending plan change (for scheduled downgrades)
            $table->unsignedBigInteger('pending_plan_id')->nullable()->after('plan_id');
            $table->string('pending_billing_cycle')->nullable()->after('pending_plan_id');

            // Proration tracking
            $table->boolean('manual_proration_applied')->default(false)->after('payment_balance');
            $table->decimal('manual_proration_amount', 10, 2)->nullable()->after('manual_proration_applied');
        });

        // Update billing_cycle enum to include daily/weekly
        // MySQL doesn't allow direct enum modification, so we use raw SQL
        DB::statement("ALTER TABLE lce_user_subscriptions MODIFY COLUMN billing_cycle ENUM('daily', 'weekly', 'monthly', 'annual') DEFAULT 'monthly'");

        // Update status enum to include more states
        DB::statement("ALTER TABLE lce_user_subscriptions MODIFY COLUMN status ENUM('pending', 'active', 'paused', 'past_due', 'cancelled', 'upgraded', 'downgraded') DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('lce_user_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_subscription_id',
                'stripe_customer_id',
                'current_period_start',
                'current_period_end',
                'cancel_at_period_end',
                'cancel_reason',
                'cancelled_at',
                'pending_plan_id',
                'pending_billing_cycle',
                'manual_proration_applied',
                'manual_proration_amount',
            ]);
        });

        // Revert enum changes
        DB::statement("ALTER TABLE lce_user_subscriptions MODIFY COLUMN billing_cycle ENUM('monthly', 'annual') DEFAULT 'monthly'");
        DB::statement("ALTER TABLE lce_user_subscriptions MODIFY COLUMN status ENUM('pending', 'active', 'paused', 'cancelled', 'upgraded') DEFAULT 'active'");
    }
};
