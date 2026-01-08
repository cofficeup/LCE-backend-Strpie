<?php

/**
 * Test Script: Customer & Admin Payment/Subscription Features
 * 
 * Run: php test_subscription_system.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\Invoice;
use App\Services\Subscription\SubscriptionService;
use App\Services\Stripe\StripeProductService;
use App\Services\Stripe\StripeSubscriptionService;
use App\Services\Payment\PaymentService;

echo "===========================================\n";
echo "STRIPE SUBSCRIPTION SYSTEM TEST\n";
echo "===========================================\n\n";

// =============================================
// 1. TEST DATABASE STATE
// =============================================
echo "1. CHECKING DATABASE STATE\n";
echo "-------------------------------------------\n";

$customerCount = User::whereHas('roles', fn($q) => $q->where('name', 'customer'))->count();
$adminCount = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->count();
$planCount = SubscriptionPlan::count();
$subscriptionCount = UserSubscription::count();

echo "   Customers: {$customerCount}\n";
echo "   Admins: {$adminCount}\n";
echo "   Plans: {$planCount}\n";
echo "   Subscriptions: {$subscriptionCount}\n\n";

// Get test users
$customer = User::whereHas('roles', fn($q) => $q->where('name', 'customer'))->first();
$admin = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();

if (!$customer) {
    echo "❌ No customer found. Creating test customer...\n";
    $customer = User::create([
        'name' => 'Test Customer',
        'email' => 'test.customer.' . time() . '@example.com',
        'password' => bcrypt('password123'),
    ]);
    $customer->roles()->attach(\App\Models\Role::where('name', 'customer')->first()?->id ?? 1);
    echo "✅ Test customer created: {$customer->email}\n";
} else {
    echo "✅ Customer found: {$customer->email}\n";
}

if (!$admin) {
    echo "❌ No admin found. Please create an admin user first.\n";
} else {
    echo "✅ Admin found: {$admin->email}\n";
}

echo "\n";

// =============================================
// 2. TEST SUBSCRIPTION PLANS
// =============================================
echo "2. TESTING SUBSCRIPTION PLANS\n";
echo "-------------------------------------------\n";

$plans = SubscriptionPlan::all();

if ($plans->isEmpty()) {
    echo "   No plans found. Creating test plan...\n";

    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic-plan',
        'description' => 'Basic subscription with 4 bags per month',
        'bags_per_month' => 4,
        'bags_per_week' => 1,
        'bags_per_day' => 1,
        'price_monthly' => 49.99,
        'price_weekly' => 14.99,
        'price_daily' => 4.99,
        'price_annual' => 499.99,
        'overage_policy' => 'block',
        'is_active' => true,
    ]);

    echo "   ✅ Created plan: {$plan->name}\n";
    $plans = SubscriptionPlan::all();
}

foreach ($plans as $plan) {
    echo "   Plan: {$plan->name} (ID: {$plan->id})\n";
    echo "      - Monthly: \${$plan->price_monthly}\n";
    echo "      - Bags/Month: {$plan->bags_per_month}\n";
    echo "      - Stripe Product ID: " . ($plan->stripe_product_id ?? 'NOT SYNCED') . "\n";
    echo "      - Stripe Price (Monthly): " . ($plan->stripe_price_id_monthly ?? 'NOT SYNCED') . "\n";
}

echo "\n";

// =============================================
// 3. TEST STRIPE PLAN SYNC (ADMIN FEATURE)
// =============================================
echo "3. TESTING STRIPE PLAN SYNC (ADMIN)\n";
echo "-------------------------------------------\n";

$productService = app(StripeProductService::class);

$unsyncedPlans = SubscriptionPlan::whereNull('stripe_product_id')->where('is_active', true)->get();

if ($unsyncedPlans->isNotEmpty()) {
    echo "   Found {$unsyncedPlans->count()} plans not synced to Stripe.\n";

    foreach ($unsyncedPlans as $plan) {
        try {
            echo "   Syncing plan: {$plan->name}... ";
            $productService->syncPlanToStripe($plan);
            echo "✅ DONE\n";
            echo "      - Stripe Product ID: {$plan->fresh()->stripe_product_id}\n";
        } catch (Exception $e) {
            echo "❌ FAILED: {$e->getMessage()}\n";
        }
    }
} else {
    echo "   All active plans are already synced to Stripe.\n";
}

echo "\n";

// =============================================
// 4. TEST CUSTOMER SUBSCRIPTION CREATION
// =============================================
echo "4. TESTING CUSTOMER SUBSCRIPTION CREATION\n";
echo "-------------------------------------------\n";

$subscriptionService = app(SubscriptionService::class);

// Check if customer already has subscription
$existingSub = $customer->subscriptions()
    ->whereIn('status', ['active', 'pending', 'paused'])
    ->first();

if ($existingSub) {
    echo "   Customer already has subscription: ID {$existingSub->id}, Status: {$existingSub->status}\n";
    echo "   Stripe Subscription ID: " . ($existingSub->stripe_subscription_id ?? 'None') . "\n";
} else {
    $plan = SubscriptionPlan::where('is_active', true)
        ->whereNotNull('stripe_price_id_monthly')
        ->first();

    if (!$plan) {
        echo "   ❌ No plan with Stripe price found. Cannot create subscription.\n";
    } else {
        echo "   Creating subscription for customer to plan: {$plan->name}...\n";

        try {
            $result = $subscriptionService->create($customer, $plan, 'monthly');

            echo "   ✅ Subscription created!\n";
            echo "      - Subscription ID: {$result['subscription']->id}\n";
            echo "      - Stripe Subscription ID: {$result['stripe_subscription_id']}\n";
            echo "      - Client Secret: " . substr($result['client_secret'] ?? 'N/A', 0, 30) . "...\n";
            echo "      - Status: {$result['subscription']->status}\n";
        } catch (Exception $e) {
            echo "   ❌ Failed: {$e->getMessage()}\n";
        }
    }
}

echo "\n";

// =============================================
// 5. TEST CUSTOMER SUBSCRIPTION MANAGEMENT
// =============================================
echo "5. TESTING CUSTOMER SUBSCRIPTION MANAGEMENT\n";
echo "-------------------------------------------\n";

$subscription = $customer->subscriptions()
    ->whereIn('status', ['active', 'pending'])
    ->first();

if ($subscription) {
    echo "   Current subscription: ID {$subscription->id}\n";
    echo "   Status: {$subscription->status}\n";
    echo "   Billing Cycle: {$subscription->billing_cycle}\n";
    echo "   Bags Available: {$subscription->getRemainingBags()}\n";

    // Test cancel
    if ($subscription->isActive() && !$subscription->cancel_at_period_end) {
        echo "\n   Testing cancel at period end...\n";
        try {
            $cancelled = $subscriptionService->cancel($subscription, 'Testing cancellation');
            echo "   ✅ Cancel scheduled: {$cancelled->cancel_at_period_end}\n";

            // Test reactivate
            echo "   Testing reactivation...\n";
            $reactivated = $subscriptionService->reactivate($cancelled);
            echo "   ✅ Reactivated: cancel_at_period_end = " . ($reactivated->cancel_at_period_end ? 'true' : 'false') . "\n";
        } catch (Exception $e) {
            echo "   ⚠️ Cancel/Reactivate test skipped: {$e->getMessage()}\n";
        }
    }
} else {
    echo "   No active subscription to test management features.\n";
}

echo "\n";

// =============================================
// 6. TEST INVOICE SYSTEM
// =============================================
echo "6. TESTING INVOICE SYSTEM\n";
echo "-------------------------------------------\n";

$invoices = Invoice::where('user_id', $customer->id)
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

echo "   Recent invoices for customer:\n";
if ($invoices->isEmpty()) {
    echo "   (No invoices found)\n";
} else {
    foreach ($invoices as $invoice) {
        echo "      - ID: {$invoice->id}, Type: {$invoice->type}, Status: {$invoice->status}, Total: \${$invoice->total}\n";
    }
}

echo "\n";

// =============================================
// 7. TEST ADMIN SUBSCRIPTION MANAGEMENT
// =============================================
echo "7. TESTING ADMIN FEATURES\n";
echo "-------------------------------------------\n";

// List all subscriptions (admin view)
$allSubs = UserSubscription::with(['user', 'plan'])
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

echo "   Recent subscriptions (admin view):\n";
foreach ($allSubs as $sub) {
    echo "      - User: {$sub->user->email}, Plan: " . ($sub->plan->name ?? 'N/A') . ", Status: {$sub->status}\n";
}

echo "\n";

// =============================================
// 8. SUMMARY
// =============================================
echo "===========================================\n";
echo "TEST SUMMARY\n";
echo "===========================================\n";

$syncedPlans = SubscriptionPlan::whereNotNull('stripe_product_id')->count();
$activeSubs = UserSubscription::where('status', 'active')->count();
$pendingSubs = UserSubscription::where('status', 'pending')->count();

echo "   Synced Plans: {$syncedPlans}\n";
echo "   Active Subscriptions: {$activeSubs}\n";
echo "   Pending Subscriptions: {$pendingSubs}\n";

echo "\n✅ Test completed!\n";
echo "\nTo fully test payment flow:\n";
echo "1. Use the client_secret with Stripe.js in frontend\n";
echo "2. Or use Stripe CLI to simulate webhooks:\n";
echo "   stripe trigger invoice.paid\n";
echo "   stripe trigger customer.subscription.updated\n";
