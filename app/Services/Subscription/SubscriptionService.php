<?php

namespace App\Services\Subscription;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\AuditLog;
use App\Services\Stripe\StripeSubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    protected StripeSubscriptionService $stripeSubscription;

    public function __construct(StripeSubscriptionService $stripeSubscription)
    {
        $this->stripeSubscription = $stripeSubscription;
    }

    /**
     * Create a new subscription with Stripe integration.
     * Returns local subscription + Stripe client_secret for payment confirmation.
     */
    public function create(
        User $user,
        SubscriptionPlan $plan,
        string $billingCycle
    ): array {
        // Validate billing cycle
        if (!in_array($billingCycle, SubscriptionPlan::CYCLES)) {
            throw new \InvalidArgumentException('Invalid billing cycle: ' . $billingCycle);
        }

        // Validate plan has price for this cycle
        $price = $plan->getPriceForCycle($billingCycle);
        if (!$price) {
            throw new \DomainException("Plan does not support {$billingCycle} billing cycle.");
        }

        // Validate plan is synced to Stripe
        if (!$plan->getStripePriceIdForCycle($billingCycle)) {
            throw new \DomainException("Plan is not synced to Stripe for {$billingCycle} cycle.");
        }

        // Prevent duplicate active subscriptions
        $existingActive = $user->subscriptions()
            ->whereIn('status', [
                UserSubscription::STATUS_PENDING,
                UserSubscription::STATUS_ACTIVE,
                UserSubscription::STATUS_PAUSED,
            ])
            ->exists();

        if ($existingActive) {
            throw new \DomainException('User already has an active or pending subscription.');
        }

        return DB::transaction(function () use ($user, $plan, $billingCycle) {
            // Calculate bag allocation
            $bagsForCycle = $plan->getBagsForCycle($billingCycle);

            // Create local subscription (pending)
            $subscription = UserSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => UserSubscription::STATUS_PENDING,
                'billing_cycle' => $billingCycle,
                'bags_plan_period' => $bagsForCycle,
                'bags_plan_total' => $bagsForCycle,
                'bags_plan_balance' => $bagsForCycle,
                'bags_plan_used' => 0,
                'bags_available' => $bagsForCycle,
            ]);

            // Create Stripe subscription
            $stripeData = $this->stripeSubscription->createSubscription(
                $user,
                $plan,
                $billingCycle
            );

            // Update local subscription with Stripe data
            $subscription->update([
                'stripe_subscription_id' => $stripeData['stripe_subscription_id'],
                'stripe_customer_id' => $stripeData['stripe_customer_id'],
                'current_period_start' => \Carbon\Carbon::createFromTimestamp($stripeData['current_period_start']),
                'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeData['current_period_end']),
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'subscription_created',
                'entity_type' => 'subscription',
                'entity_id' => $subscription->id,
                'metadata' => [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'billing_cycle' => $billingCycle,
                    'stripe_subscription_id' => $stripeData['stripe_subscription_id'],
                ],
            ]);

            Log::info('Created subscription', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $stripeData['stripe_subscription_id'],
            ]);

            return [
                'subscription' => $subscription->fresh()->load('plan'),
                'client_secret' => $stripeData['client_secret'],
                'stripe_subscription_id' => $stripeData['stripe_subscription_id'],
            ];
        });
    }

    /**
     * Cancel subscription at period end with refund calculation for annual plans.
     */
    public function cancel(UserSubscription $subscription, ?string $reason = null): UserSubscription
    {
        if (!in_array($subscription->status, [
            UserSubscription::STATUS_ACTIVE,
            UserSubscription::STATUS_PAUSED,
            UserSubscription::STATUS_PENDING,
        ])) {
            throw new \DomainException('Subscription cannot be cancelled in its current state.');
        }

        return DB::transaction(function () use ($subscription, $reason) {
            // Calculate Refund if Annual
            if ($subscription->billing_cycle === 'annual') {
                $paymentBalance = $subscription->payment_balance ?? 0;
                $daysSinceStart = now()->diffInDays($subscription->start_date);

                // Refund logic per spec 5.4
                $refundAmount = 0;

                if ($daysSinceStart < 5 && $paymentBalance > 0) {
                     // Full refund minus nothing? Spec says: "refund = payment_balance" if < 5 days
                     $refundAmount = $paymentBalance;
                } elseif ($paymentBalance > 0) {
                     // "refund = max(0, payment_balance - 100.00)"
                     $refundAmount = max(0, $paymentBalance - 100.00);
                }

                if ($refundAmount > 0) {
                    // Trigger refund (assuming payment service handles stripe refund logic)
                    // For now we just log it as a pending refund action or create a credit
                    // Note: Ideally call RefundService here.
                    Log::info("Pending annual cancellation refund: {$refundAmount}", ['subscription_id' => $subscription->id]);
                    // Create manual credit for refund (Spec 5.7 says create negative invoice/credit)
                     \App\Models\Credit::create([
                        'user_id' => $subscription->user_id,
                        'type' => 'refund',
                        'description' => 'Annual Subscription Cancellation Refund',
                        'amount' => $refundAmount,
                        'balance' => $refundAmount,
                    ]);
                }
            }

            // Cancel in Stripe (at period end)
            if ($subscription->stripe_subscription_id) {
                $stripeData = $this->stripeSubscription->cancelAtPeriodEnd($subscription, $reason);

                $subscription->update([
                    'cancel_at_period_end' => true,
                    'cancel_reason' => $reason,
                ]);
            } else {
                // No Stripe subscription, cancel immediately
                $subscription->update([
                    'status' => UserSubscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancel_reason' => $reason,
                ]);

                // Remove from user
                if ($subscription->user->subscription_id === $subscription->id) {
                    $subscription->user->update(['subscription_id' => null]);
                }
            }

            // Audit log
            AuditLog::create([
                'user_id' => $subscription->user_id,
                'action' => 'subscription_cancel_requested',
                'entity_type' => 'subscription',
                'entity_id' => $subscription->id,
                'metadata' => [
                    'reason' => $reason,
                    'at_period_end' => $subscription->cancel_at_period_end,
                ],
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Reactivate a subscription that was scheduled for cancellation.
     */
    public function reactivate(UserSubscription $subscription): UserSubscription
    {
        if (!$subscription->cancel_at_period_end) {
            throw new \DomainException('Subscription is not scheduled for cancellation.');
        }

        if (!$subscription->isActive()) {
            throw new \DomainException('Only active subscriptions can be reactivated.');
        }

        return DB::transaction(function () use ($subscription) {
            if ($subscription->stripe_subscription_id) {
                $this->stripeSubscription->reactivate($subscription);
            }

            $subscription->update([
                'cancel_at_period_end' => false,
                'cancel_reason' => null,
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => $subscription->user_id,
                'action' => 'subscription_reactivated',
                'entity_type' => 'subscription',
                'entity_id' => $subscription->id,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Upgrade subscription to a new plan (immediate, with proration).
     */
    public function upgrade(
        UserSubscription $subscription,
        SubscriptionPlan $newPlan,
        ?string $newBillingCycle = null
    ): UserSubscription {
        if (!$subscription->isActive()) {
            throw new \DomainException('Only active subscriptions can be upgraded.');
        }

        $newCycle = $newBillingCycle ?? $subscription->billing_cycle;

        // Validate new plan has price for the cycle
        if (!$newPlan->getStripePriceIdForCycle($newCycle)) {
            throw new \DomainException("New plan does not support {$newCycle} billing cycle.");
        }

        // Ensure it's actually an upgrade
        $currentPrice = $subscription->plan->getPriceForCycle($subscription->billing_cycle);
        $newPrice = $newPlan->getPriceForCycle($newCycle);

        if ($newPrice <= $currentPrice) {
            throw new \DomainException('Use downgrade for reducing plan level.');
        }

        return DB::transaction(function () use ($subscription, $newPlan, $newCycle) {
            $oldPlan = $subscription->plan;

            // Update in Stripe with proration
            if ($subscription->stripe_subscription_id) {
                $this->stripeSubscription->updatePlan(
                    $subscription,
                    $newPlan,
                    $newCycle,
                    'create_prorations'
                );
            }

            // Update local subscription
            $bagsForCycle = $newPlan->getBagsForCycle($newCycle);

            $subscription->update([
                'plan_id' => $newPlan->id,
                'billing_cycle' => $newCycle,
                'bags_plan_total' => $bagsForCycle,
                'bags_plan_balance' => $bagsForCycle - $subscription->bags_plan_used,
                'bags_available' => $bagsForCycle - $subscription->bags_plan_used,
                'status' => UserSubscription::STATUS_UPGRADED,
            ]);

            // Immediately change to active
            $subscription->update(['status' => UserSubscription::STATUS_ACTIVE]);

            // Audit log
            AuditLog::create([
                'user_id' => $subscription->user_id,
                'action' => 'subscription_upgraded',
                'entity_type' => 'subscription',
                'entity_id' => $subscription->id,
                'metadata' => [
                    'old_plan_id' => $oldPlan->id,
                    'old_plan_name' => $oldPlan->name,
                    'new_plan_id' => $newPlan->id,
                    'new_plan_name' => $newPlan->name,
                    'billing_cycle' => $newCycle,
                ],
            ]);

            return $subscription->fresh()->load('plan');
        });
    }

    /**
     * Downgrade subscription (effective at end of period).
     */
    public function downgrade(
        UserSubscription $subscription,
        SubscriptionPlan $newPlan,
        ?string $newBillingCycle = null
    ): UserSubscription {
        if (!$subscription->isActive()) {
            throw new \DomainException('Only active subscriptions can be downgraded.');
        }

        $newCycle = $newBillingCycle ?? $subscription->billing_cycle;

        // Validate new plan
        if (!$newPlan->getStripePriceIdForCycle($newCycle)) {
            throw new \DomainException("New plan does not support {$newCycle} billing cycle.");
        }

        return DB::transaction(function () use ($subscription, $newPlan, $newCycle) {
            $scheduleId = null;

            // Schedule in Stripe (no immediate change)
            if ($subscription->stripe_subscription_id) {
                $scheduleData = $this->stripeSubscription->schedulePlanChange(
                    $subscription,
                    $newPlan,
                    $newCycle
                );
                $scheduleId = $scheduleData['schedule_id'] ?? null;
            }

            // Store pending plan change locally (including schedule ID)
            $subscription->update([
                'pending_plan_id' => $newPlan->id,
                'pending_billing_cycle' => $newCycle,
                'stripe_schedule_id' => $scheduleId,
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => $subscription->user_id,
                'action' => 'subscription_downgrade_scheduled',
                'entity_type' => 'subscription',
                'entity_id' => $subscription->id,
                'metadata' => [
                    'current_plan_id' => $subscription->plan_id,
                    'new_plan_id' => $newPlan->id,
                    'new_plan_name' => $newPlan->name,
                    'effective_at' => $subscription->current_period_end?->toDateString(),
                ],
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Pause subscription billing.
     */
    public function pause(UserSubscription $subscription, ?string $reason = null): UserSubscription
    {
        if (!$subscription->isActive()) {
            throw new \DomainException('Only active subscriptions can be paused.');
        }

        return DB::transaction(function () use ($subscription, $reason) {
            // Pause in Stripe
            if ($subscription->stripe_subscription_id) {
                $this->stripeSubscription->pauseCollection($subscription);
            }

            $subscription->update([
                'status' => UserSubscription::STATUS_PAUSED,
                'notes' => $reason,
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => $subscription->user_id,
                'action' => 'subscription_paused',
                'entity_type' => 'subscription',
                'entity_id' => $subscription->id,
                'metadata' => ['reason' => $reason],
            ]);

            Log::info('Subscription paused', ['subscription_id' => $subscription->id]);

            return $subscription->fresh();
        });
    }

    /**
     * Resume paused subscription billing.
     */
    public function resume(UserSubscription $subscription): UserSubscription
    {
        if ($subscription->status !== UserSubscription::STATUS_PAUSED) {
            throw new \DomainException('Only paused subscriptions can be resumed.');
        }

        return DB::transaction(function () use ($subscription) {
            // Resume in Stripe
            if ($subscription->stripe_subscription_id) {
                $this->stripeSubscription->resumeCollection($subscription);
            }

            $subscription->update([
                'status' => UserSubscription::STATUS_ACTIVE,
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => $subscription->user_id,
                'action' => 'subscription_resumed',
                'entity_type' => 'subscription',
                'entity_id' => $subscription->id,
            ]);

            Log::info('Subscription resumed', ['subscription_id' => $subscription->id]);

            return $subscription->fresh();
        });
    }

    /**
     * Record bag usage with overage check.
     * If usage exceeds limit and plan allows PPO, triggers overage charge.
     *
     * @param UserSubscription $subscription
     * @param int $bags Number of bags to record
     * @param SubscriptionOverageService|null $overageService
     * @return array ['subscription' => UserSubscription, 'overage_charge' => array|null]
     */
    public function recordBagUsageWithOverageCheck(
        UserSubscription $subscription,
        int $bags = 1,
        ?SubscriptionOverageService $overageService = null
    ): array {
        if (!$subscription->isActive()) {
            throw new \DomainException('Can only record usage on active subscriptions.');
        }

        $overageCharge = null;

        // Check if this will cause overage and handle it
        if ($overageService && $subscription->plan->shouldChargePPOOnOverage()) {
            $overageCharge = $overageService->checkAndChargeOverage($subscription, $bags);
        }

        // Record the usage
        $subscription->increment('bags_plan_used', $bags);
        $subscription->decrement('bags_plan_balance', $bags);
        $subscription->decrement('bags_available', $bags);

        return [
            'subscription' => $subscription->fresh(),
            'overage_charge' => $overageCharge,
        ];
    }

    /**
     * Record bag usage.
     */
    public function recordBagUsage(UserSubscription $subscription, int $bags = 1): UserSubscription
    {
        if (!$subscription->isActive()) {
            throw new \DomainException('Can only record usage on active subscriptions.');
        }

        $subscription->increment('bags_plan_used', $bags);
        $subscription->decrement('bags_plan_balance', $bags);
        $subscription->decrement('bags_available', $bags);

        return $subscription->fresh();
    }

    /**
     * Get available bags for current period.
     */
    public function getAvailableBags(UserSubscription $subscription): int
    {
        if (!$subscription->isActive()) {
            return 0;
        }

        return max(0, $subscription->bags_plan_total - $subscription->bags_plan_used);
    }

    /**
     * Check if pickup can proceed based on subscription.
     *
     * @return array ['allowed' => bool, 'reason' => string|null, 'requires_ppo' => bool]
     */
    public function canProceedWithPickup(UserSubscription $subscription, int $bags = 1): array
    {
        if (!$subscription->isActive()) {
            return [
                'allowed' => false,
                'reason' => 'Subscription is not active',
                'requires_ppo' => false,
            ];
        }

        $plan = $subscription->plan;
        $availableBags = $this->getAvailableBags($subscription);

        if ($availableBags >= $bags) {
            // Has enough bags
            return [
                'allowed' => true,
                'reason' => null,
                'requires_ppo' => false,
            ];
        }

        // Over limit - check policy
        if ($plan->shouldChargePPOOnOverage()) {
            return [
                'allowed' => true,
                'reason' => 'Overage will be charged as PPO',
                'requires_ppo' => true,
                'overage_bags' => $bags - $availableBags,
                'overage_amount' => ($bags - $availableBags) * $plan->overage_price_per_bag,
            ];
        }

        if ($plan->shouldBlockOnOverage()) {
            return [
                'allowed' => false,
                'reason' => 'Bag limit exceeded. Please upgrade your plan.',
                'requires_ppo' => false,
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'Unable to process pickup',
            'requires_ppo' => false,
        ];
    }
}
