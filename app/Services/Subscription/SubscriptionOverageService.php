<?php

namespace App\Services\Subscription;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\AuditLog;
use App\Services\Stripe\StripeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class SubscriptionOverageService
{
    protected StripeService $stripeService;
    protected StripeClient $stripe;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
        $this->stripe = new StripeClient(config('stripe.secret'));
    }

    /**
     * Handle overage when user exceeds bag limit.
     * Creates PPO invoice and charges via Stripe.
     *
     * @param UserSubscription $subscription
     * @param int $overageBags Number of bags over the limit
     * @param string|null $description Optional description for invoice
     * @return array Invoice and payment intent details
     */
    public function handleOverage(
        UserSubscription $subscription,
        int $overageBags = 1,
        ?string $description = null
    ): array {
        if ($overageBags <= 0) {
            throw new \InvalidArgumentException('Overage bags must be positive');
        }

        $plan = $subscription->plan;

        if (!$plan->shouldChargePPOOnOverage()) {
            throw new \DomainException('Plan does not support PPO overage charges');
        }

        $pricePerBag = $plan->overage_price_per_bag;
        if (!$pricePerBag || $pricePerBag <= 0) {
            throw new \DomainException('Plan does not have overage price configured');
        }

        $totalAmount = $overageBags * $pricePerBag;
        $user = $subscription->user;

        return DB::transaction(function () use ($subscription, $user, $overageBags, $pricePerBag, $totalAmount, $description) {
            // Create PPO invoice
            $invoice = Invoice::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'type' => 'ppo_overage',
                'status' => 'pending',
                'currency' => 'USD',
                'subtotal' => $totalAmount,
                'tax' => 0,
                'total' => $totalAmount,
                'issued_at' => now(),
                'due_at' => now(),
                'metadata' => [
                    'overage_bags' => $overageBags,
                    'price_per_bag' => $pricePerBag,
                    'subscription_id' => $subscription->id,
                    'plan_id' => $subscription->plan_id,
                ],
            ]);

            // Create invoice line
            InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'type' => 'overage',
                'description' => $description ?? "Overage charge: {$overageBags} bag(s) @ \${$pricePerBag}/bag",
                'quantity' => $overageBags,
                'unit_price' => $pricePerBag,
                'amount' => $totalAmount,
            ]);

            // Create Stripe payment intent
            $stripeCustomerId = $this->stripeService->createOrGetCustomer($user);

            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) round($totalAmount * 100),
                'currency' => 'usd',
                'customer' => $stripeCustomerId,
                'description' => "Overage charge for {$overageBags} bag(s)",
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'type' => 'ppo_overage',
                    'overage_bags' => $overageBags,
                ],
                // Use default payment method if available
                'payment_method' => $this->getDefaultPaymentMethod($stripeCustomerId),
                'confirm' => true,
                'off_session' => true,
            ], [
                'idempotency_key' => "overage_{$invoice->id}_{$subscription->id}",
            ]);

            // Create payment record
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'amount' => $totalAmount,
                'currency' => 'USD',
                'status' => $this->mapPaymentStatus($paymentIntent->status),
                'payment_method' => 'card',
                'metadata' => [
                    'type' => 'ppo_overage',
                    'overage_bags' => $overageBags,
                ],
            ]);

            // Update invoice if payment succeeded
            if ($paymentIntent->status === 'succeeded') {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
                $payment->update(['status' => Payment::STATUS_SUCCEEDED]);
            }

            // Audit log
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'overage_charge_created',
                'entity_type' => 'invoice',
                'entity_id' => $invoice->id,
                'metadata' => [
                    'overage_bags' => $overageBags,
                    'amount' => $totalAmount,
                    'payment_intent_id' => $paymentIntent->id,
                    'payment_status' => $paymentIntent->status,
                    'subscription_id' => $subscription->id,
                ],
            ]);

            Log::info('Overage charge created', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $subscription->id,
                'overage_bags' => $overageBags,
                'amount' => $totalAmount,
                'status' => $paymentIntent->status,
            ]);

            return [
                'invoice' => $invoice->fresh(),
                'payment' => $payment->fresh(),
                'payment_intent_id' => $paymentIntent->id,
                'payment_status' => $paymentIntent->status,
                'client_secret' => $paymentIntent->client_secret,
                'requires_action' => $paymentIntent->status === 'requires_action',
            ];
        });
    }

    /**
     * Check if overage charge is needed and process it.
     *
     * @param UserSubscription $subscription
     * @param int $bagsToUse Number of bags being used
     * @return array|null Returns charge details if overage occurred, null otherwise
     */
    public function checkAndChargeOverage(UserSubscription $subscription, int $bagsToUse = 1): ?array
    {
        $plan = $subscription->plan;

        // Check if plan supports PPO overage
        if (!$plan->shouldChargePPOOnOverage()) {
            return null;
        }

        // Calculate if this will cause overage
        $currentUsed = $subscription->bags_plan_used;
        $limit = $subscription->bags_plan_total;
        $newTotal = $currentUsed + $bagsToUse;

        if ($newTotal <= $limit) {
            // No overage
            return null;
        }

        // Calculate overage amount
        $overageBags = $newTotal - $limit;

        // Only charge for new overage, not existing
        if ($currentUsed >= $limit) {
            // Already over limit, charge for all new bags
            $overageBags = $bagsToUse;
        }

        try {
            return $this->handleOverage($subscription, $overageBags);
        } catch (\Exception $e) {
            Log::error('Failed to charge overage', [
                'subscription_id' => $subscription->id,
                'overage_bags' => $overageBags,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get default payment method for customer.
     */
    protected function getDefaultPaymentMethod(string $customerId): ?string
    {
        try {
            $customer = $this->stripe->customers->retrieve($customerId);
            return $customer->invoice_settings->default_payment_method
                ?? $customer->default_source
                ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Map Stripe payment intent status to local status.
     */
    protected function mapPaymentStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded' => Payment::STATUS_SUCCEEDED,
            'processing' => Payment::STATUS_PENDING,
            'requires_action', 'requires_payment_method' => Payment::STATUS_PENDING,
            'canceled' => Payment::STATUS_FAILED,
            default => Payment::STATUS_PENDING,
        };
    }
}
