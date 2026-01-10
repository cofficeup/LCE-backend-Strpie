<?php

namespace App\Services\Stripe;

use App\Models\User;
use App\Models\Invoice;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use App\Models\AuditLog;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeSubscriptionService
{
    protected StripeClient $stripe;
    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripe = new StripeClient(config('stripe.secret'));
        $this->stripeService = $stripeService;
    }

    /**
     * Create a Stripe Subscription for a user.
     */
    public function createSubscription(
        User $user,
        SubscriptionPlan $plan,
        string $billingCycle,
        bool $startImmediately = true
    ): array {
        // Validate plan has Stripe price for this cycle
        $stripePriceId = $plan->getStripePriceIdForCycle($billingCycle);

        if (!$stripePriceId) {
            throw new \DomainException("Plan does not have a Stripe price for {$billingCycle} billing cycle.");
        }

        // Ensure customer exists in Stripe
        $stripeCustomerId = $this->stripeService->createOrGetCustomer($user);

        try {
            $subscriptionParams = [
                'customer' => $stripeCustomerId,
                'items' => [
                    ['price' => $stripePriceId],
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'billing_cycle' => $billingCycle,
                ],
                'payment_behavior' => 'default_incomplete', // Require payment confirmation
                'payment_settings' => [
                    'save_default_payment_method' => 'on_subscription',
                ],
                'expand' => ['latest_invoice.payment_intent'],
            ];

            // Apply proration for immediate start
            if ($startImmediately) {
                $subscriptionParams['proration_behavior'] = 'create_prorations';
            }

            $stripeSubscription = $this->stripe->subscriptions->create($subscriptionParams, [
                'idempotency_key' => "sub_create_{$user->id}_{$plan->id}_{$billingCycle}_" . date('YmdH'),
            ]);

            Log::info('Created Stripe subscription', [
                'user_id' => $user->id,
                'stripe_subscription_id' => $stripeSubscription->id,
                'status' => $stripeSubscription->status,
            ]);

            return [
                'stripe_subscription_id' => $stripeSubscription->id,
                'stripe_customer_id' => $stripeCustomerId,
                'status' => $stripeSubscription->status,
                'current_period_start' => $stripeSubscription->current_period_start,
                'current_period_end' => $stripeSubscription->current_period_end,
                'client_secret' => $stripeSubscription->latest_invoice?->payment_intent?->client_secret,
                'latest_invoice_id' => $stripeSubscription->latest_invoice?->id,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe subscription', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to create subscription: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a subscription at period end.
     */
    public function cancelAtPeriodEnd(UserSubscription $subscription, ?string $reason = null): array
    {
        if (!$subscription->stripe_subscription_id) {
            throw new \DomainException('Subscription is not linked to Stripe.');
        }

        try {
            $stripeSubscription = $this->stripe->subscriptions->update(
                $subscription->stripe_subscription_id,
                ['cancel_at_period_end' => true]
            );

            Log::info('Set subscription to cancel at period end', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $subscription->stripe_subscription_id,
            ]);

            return [
                'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
                'cancel_at' => $stripeSubscription->cancel_at,
                'current_period_end' => $stripeSubscription->current_period_end,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to cancel Stripe subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to cancel subscription: ' . $e->getMessage());
        }
    }

    /**
     * Cancel subscription immediately.
     */
    public function cancelImmediately(UserSubscription $subscription): bool
    {
        if (!$subscription->stripe_subscription_id) {
            throw new \DomainException('Subscription is not linked to Stripe.');
        }

        try {
            $this->stripe->subscriptions->cancel($subscription->stripe_subscription_id);

            Log::info('Cancelled subscription immediately', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $subscription->stripe_subscription_id,
            ]);

            return true;
        } catch (ApiErrorException $e) {
            Log::error('Failed to cancel Stripe subscription immediately', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to cancel subscription: ' . $e->getMessage());
        }
    }

    /**
     * Reactivate a subscription (remove cancel_at_period_end).
     */
    public function reactivate(UserSubscription $subscription): array
    {
        if (!$subscription->stripe_subscription_id) {
            throw new \DomainException('Subscription is not linked to Stripe.');
        }

        try {
            $stripeSubscription = $this->stripe->subscriptions->update(
                $subscription->stripe_subscription_id,
                ['cancel_at_period_end' => false]
            );

            Log::info('Reactivated subscription', [
                'subscription_id' => $subscription->id,
            ]);

            return [
                'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
                'status' => $stripeSubscription->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to reactivate Stripe subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to reactivate subscription: ' . $e->getMessage());
        }
    }

    /**
     * Update subscription to a new plan (upgrade/downgrade).
     */
    public function updatePlan(
        UserSubscription $subscription,
        SubscriptionPlan $newPlan,
        string $newBillingCycle,
        string $prorationBehavior = 'create_prorations' // or 'none' for no proration
    ): array {
        if (!$subscription->stripe_subscription_id) {
            throw new \DomainException('Subscription is not linked to Stripe.');
        }

        $newPriceId = $newPlan->getStripePriceIdForCycle($newBillingCycle);

        if (!$newPriceId) {
            throw new \DomainException("New plan does not have a Stripe price for {$newBillingCycle} billing cycle.");
        }

        try {
            // Get current subscription to find the item ID
            $stripeSubscription = $this->stripe->subscriptions->retrieve($subscription->stripe_subscription_id);
            $itemId = $stripeSubscription->items->data[0]->id;

            // Update the subscription
            $idempotencyKey = "sub_upgrade_{$subscription->id}_{$newPlan->id}";

            $updatedSubscription = $this->stripe->subscriptions->update(
                $subscription->stripe_subscription_id,
                [
                    'items' => [
                        [
                            'id' => $itemId,
                            'price' => $newPriceId,
                        ],
                    ],
                    'proration_behavior' => $prorationBehavior,
                    'metadata' => [
                        'plan_id' => $newPlan->id,
                        'billing_cycle' => $newBillingCycle,
                    ],
                ],
                ['idempotency_key' => $idempotencyKey]
            );

            Log::info('Updated subscription plan', [
                'subscription_id' => $subscription->id,
                'old_plan_id' => $subscription->plan_id,
                'new_plan_id' => $newPlan->id,
                'proration_behavior' => $prorationBehavior,
            ]);

            return [
                'status' => $updatedSubscription->status,
                'current_period_start' => $updatedSubscription->current_period_start,
                'current_period_end' => $updatedSubscription->current_period_end,
                'latest_invoice_id' => $updatedSubscription->latest_invoice,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to update Stripe subscription plan', [
                'subscription_id' => $subscription->id,
                'new_plan_id' => $newPlan->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to update subscription: ' . $e->getMessage());
        }
    }

    /**
     * Schedule plan change for end of period (for downgrades).
     */
    public function schedulePlanChange(
        UserSubscription $subscription,
        SubscriptionPlan $newPlan,
        string $newBillingCycle
    ): array {
        if (!$subscription->stripe_subscription_id) {
            throw new \DomainException('Subscription is not linked to Stripe.');
        }

        $newPriceId = $newPlan->getStripePriceIdForCycle($newBillingCycle);

        if (!$newPriceId) {
            throw new \DomainException("New plan does not have a Stripe price for {$newBillingCycle} billing cycle.");
        }

        try {
            // Get current subscription
            $stripeSubscription = $this->stripe->subscriptions->retrieve($subscription->stripe_subscription_id);
            $itemId = $stripeSubscription->items->data[0]->id;

            // Create a schedule to change at billing period end
            $idempotencyKey = "sub_downgrade_{$subscription->id}_{$newPlan->id}";

            $schedule = $this->stripe->subscriptionSchedules->create([
                'from_subscription' => $subscription->stripe_subscription_id,
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            // Update the schedule with new phase
            $this->stripe->subscriptionSchedules->update($schedule->id, [
                'phases' => [
                    [
                        'items' => [
                            ['price' => $stripeSubscription->items->data[0]->price->id],
                        ],
                        'end_date' => $stripeSubscription->current_period_end,
                    ],
                    [
                        'items' => [
                            ['price' => $newPriceId],
                        ],
                    ],
                ],
            ]);

            Log::info('Scheduled plan change for end of period', [
                'subscription_id' => $subscription->id,
                'new_plan_id' => $newPlan->id,
                'effective_date' => date('Y-m-d', $stripeSubscription->current_period_end),
            ]);

            return [
                'schedule_id' => $schedule->id,
                'effective_date' => $stripeSubscription->current_period_end,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to schedule plan change', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to schedule plan change: ' . $e->getMessage());
        }
    }

    /**
     * Pause subscription payment collection.
     */
    public function pauseCollection(UserSubscription $subscription): bool
    {
        if (!$subscription->stripe_subscription_id) {
            throw new \DomainException('Subscription is not linked to Stripe.');
        }

        try {
            $this->stripe->subscriptions->update($subscription->stripe_subscription_id, [
                'pause_collection' => [
                    'behavior' => 'mark_uncollectible',
                ],
            ]);

            Log::info('Paused subscription collection', [
                'subscription_id' => $subscription->id,
            ]);

            return true;
        } catch (ApiErrorException $e) {
            Log::error('Failed to pause subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to pause subscription: ' . $e->getMessage());
        }
    }

    /**
     * Resume subscription payment collection.
     */
    public function resumeCollection(UserSubscription $subscription): bool
    {
        if (!$subscription->stripe_subscription_id) {
            throw new \DomainException('Subscription is not linked to Stripe.');
        }

        try {
            $this->stripe->subscriptions->update($subscription->stripe_subscription_id, [
                'pause_collection' => '',
            ]);

            Log::info('Resumed subscription collection', [
                'subscription_id' => $subscription->id,
            ]);

            return true;
        } catch (ApiErrorException $e) {
            Log::error('Failed to resume subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to resume subscription: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve subscription from Stripe.
     */
    public function retrieveSubscription(string $stripeSubscriptionId): \Stripe\Subscription
    {
        try {
            return $this->stripe->subscriptions->retrieve($stripeSubscriptionId);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException('Failed to retrieve subscription: ' . $e->getMessage());
        }
    }

    /**
     * Get upcoming invoice for a subscription.
     */
    public function getUpcomingInvoice(UserSubscription $subscription): ?\Stripe\Invoice
    {
        if (!$subscription->stripe_subscription_id || !$subscription->stripe_customer_id) {
            return null;
        }

        try {
            /** @noinspection PhpUndefinedMethodInspection - Stripe SDK uses magic methods */
            return $this->stripe->invoices->upcoming([
                'customer' => $subscription->stripe_customer_id,
                'subscription' => $subscription->stripe_subscription_id,
            ]);
        } catch (ApiErrorException $e) {
            Log::warning('Could not retrieve upcoming invoice', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
