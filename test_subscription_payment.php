<?php

/**
 * Test Script: Create a REAL Stripe Subscription with Payment
 * 
 * This script will:
 * 1. Create a new subscription in Stripe using TEST TOKENS
 * 2. Show the balance update
 * 
 * Run: php test_subscription_payment.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Stripe\StripeClient;

echo "===========================================\n";
echo "REAL STRIPE SUBSCRIPTION PAYMENT TEST\n";
echo "===========================================\n\n";

$stripe = new StripeClient(config('stripe.secret'));

// Step 1: Get a customer or create one
echo "STEP 1: Setting up customer...\n";
echo "-------------------------------------------\n";

$user = User::where('email', 'john.doe@example.com')->first();

if (!$user) {
    echo "❌ No test user found!\n";
    exit(1);
}

echo "   User: {$user->name} ({$user->email})\n";

// Check if customer exists in Stripe
$stripeCustomer = \App\Models\StripeCustomer::where('user_id', $user->id)->first();

if (!$stripeCustomer) {
    echo "   Creating Stripe customer...\n";
    $customer = $stripe->customers->create([
        'email' => $user->email,
        'name' => $user->name,
        'metadata' => ['user_id' => $user->id],
    ]);

    \App\Models\StripeCustomer::create([
        'user_id' => $user->id,
        'stripe_customer_id' => $customer->id,
    ]);

    $stripeCustomerId = $customer->id;
    echo "   ✅ Created Stripe customer: {$stripeCustomerId}\n";
} else {
    $stripeCustomerId = $stripeCustomer->stripe_customer_id;
    echo "   ✅ Using existing Stripe customer: {$stripeCustomerId}\n";
}

echo "\n";

// Step 2: Get a plan with Stripe price
echo "STEP 2: Getting subscription plan...\n";
echo "-------------------------------------------\n";

$plan = SubscriptionPlan::where('is_active', true)
    ->whereNotNull('stripe_price_id_monthly')
    ->first();

if (!$plan) {
    echo "❌ No plan with Stripe price found!\n";
    exit(1);
}

echo "   Plan: {$plan->name}\n";
echo "   Price: \${$plan->price_monthly}/month\n";
echo "   Stripe Price ID: {$plan->stripe_price_id_monthly}\n";

echo "\n";

// Step 3: Attach test payment method using Stripe's test token
echo "STEP 3: Attaching test payment method...\n";
echo "-------------------------------------------\n";

// Use Stripe's built-in test payment method token
// pm_card_visa is a pre-made test payment method
$paymentMethod = $stripe->paymentMethods->attach('pm_card_visa', [
    'customer' => $stripeCustomerId,
]);

echo "   ✅ Payment method attached: {$paymentMethod->id}\n";
echo "   Card: {$paymentMethod->card->brand} ending in {$paymentMethod->card->last4}\n";

// Set as default payment method
$stripe->customers->update($stripeCustomerId, [
    'invoice_settings' => [
        'default_payment_method' => $paymentMethod->id,
    ],
]);

echo "   ✅ Set as default payment method\n";

echo "\n";

// Step 4: Create subscription
echo "STEP 4: Creating Stripe subscription...\n";
echo "-------------------------------------------\n";

try {
    $subscription = $stripe->subscriptions->create([
        'customer' => $stripeCustomerId,
        'items' => [
            ['price' => $plan->stripe_price_id_monthly],
        ],
        'default_payment_method' => $paymentMethod->id,
        'metadata' => [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ],
    ]);

    echo "   ✅ Subscription created!\n";
    echo "   Stripe Subscription ID: {$subscription->id}\n";
    echo "   Status: {$subscription->status}\n";
    echo "   Current Period Start: " . date('Y-m-d H:i:s', $subscription->current_period_start) . "\n";
    echo "   Current Period End: " . date('Y-m-d H:i:s', $subscription->current_period_end) . "\n";

    // Get the latest invoice
    $invoice = $stripe->invoices->retrieve($subscription->latest_invoice);
    echo "   Latest Invoice: {$invoice->id}\n";
    echo "   Invoice Status: {$invoice->status}\n";
    echo "   Amount Paid: \$" . ($invoice->amount_paid / 100) . "\n";
} catch (Exception $e) {
    echo "   ❌ Failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Step 5: Create local subscription record
echo "STEP 5: Creating local subscription record...\n";
echo "-------------------------------------------\n";

// Cancel any existing active subscriptions for this user
UserSubscription::where('user_id', $user->id)
    ->whereIn('status', ['active', 'pending'])
    ->update(['status' => 'cancelled']);

$localSub = UserSubscription::create([
    'user_id' => $user->id,
    'plan_id' => $plan->id,
    'stripe_subscription_id' => $subscription->id,
    'stripe_customer_id' => $stripeCustomerId,
    'status' => $subscription->status,
    'billing_cycle' => 'monthly',
    'start_date' => now(),
    'current_period_start' => \Carbon\Carbon::createFromTimestamp($subscription->current_period_start),
    'current_period_end' => \Carbon\Carbon::createFromTimestamp($subscription->current_period_end),
    'bags_plan_period' => $plan->bags_per_month,
    'bags_plan_total' => $plan->bags_per_month,
    'bags_plan_balance' => $plan->bags_per_month,
    'bags_plan_used' => 0,
    'bags_available' => $plan->bags_per_month,
]);

echo "   ✅ Local subscription created: ID {$localSub->id}\n";
echo "   Stripe Subscription ID: {$localSub->stripe_subscription_id}\n";
echo "   Status: {$localSub->status}\n";

echo "\n";

// Step 6: Verify balance
echo "STEP 6: Checking Stripe balance...\n";
echo "-------------------------------------------\n";

$balance = $stripe->balance->retrieve();

echo "   Available Balance:\n";
foreach ($balance->available as $b) {
    echo "      {$b->currency}: \$" . ($b->amount / 100) . "\n";
}

echo "   Pending Balance:\n";
foreach ($balance->pending as $b) {
    echo "      {$b->currency}: \$" . ($b->amount / 100) . "\n";
}

echo "\n";

// Step 7: Get recent charges
echo "STEP 7: Recent charges...\n";
echo "-------------------------------------------\n";

$charges = $stripe->charges->all(['limit' => 5]);

foreach ($charges->data as $charge) {
    $status = $charge->status;
    $amount = $charge->amount / 100;
    $created = date('Y-m-d H:i:s', $charge->created);
    echo "   Charge: {$charge->id}\n";
    echo "      Amount: \${$amount} | Status: {$status} | Created: {$created}\n";
}

echo "\n";
echo "===========================================\n";
echo "✅ SUBSCRIPTION PAYMENT COMPLETE!\n";
echo "===========================================\n";
echo "\nCheck your Stripe dashboard at:\n";
echo "https://dashboard.stripe.com/test/payments\n";
echo "https://dashboard.stripe.com/test/subscriptions\n";
