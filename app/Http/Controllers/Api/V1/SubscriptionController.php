<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Subscription\SubscriptionService;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;

class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptions;

    public function __construct(SubscriptionService $subscriptions)
    {
        $this->subscriptions = $subscriptions;
    }

    /**
     * List available subscription plans.
     */
    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::where('active', true)
            ->select([
                'id',
                'code',
                'name',
                'bags_per_month',
                'price_per_bag',
                'billing_cycle',
                'annual_discount',
            ])
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'code' => $plan->code,
                    'name' => $plan->name,
                    'bags_per_month' => $plan->bags_per_month,
                    'price_per_bag' => $plan->price_per_bag,
                    'monthly_price' => $plan->monthly_price,
                    'annual_price' => $plan->annual_price,
                    'billing_cycle' => $plan->billing_cycle,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * Get current user's active subscription.
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $user->subscriptions()
            ->with('plan')
            ->whereIn('status', [
                UserSubscription::STATUS_ACTIVE,
                UserSubscription::STATUS_PAUSED,
                UserSubscription::STATUS_PENDING,
            ])
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No active subscription.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => $subscription,
                'bags_remaining' => $subscription->getRemainingBags(),
                'is_pending_cancellation' => $subscription->isPendingCancellation(),
                'has_pending_plan_change' => $subscription->hasPendingPlanChange(),
                'pending_plan' => $subscription->pendingPlan,
            ],
        ]);
    }

    /**
     * Create a new subscription.
     * Returns client_secret for payment confirmation.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:lce_subscription_plans,id',
            'billing_cycle' => 'required|in:daily,weekly,monthly,annual',
        ]);

        $plan = SubscriptionPlan::findOrFail($data['plan_id']);

        if (!$plan->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This plan is no longer available.',
            ], 422);
        }

        try {
            $result = $this->subscriptions->create(
                $request->user(),
                $plan,
                $data['billing_cycle']
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $result['subscription'],
                    'client_secret' => $result['client_secret'],
                    'stripe_subscription_id' => $result['stripe_subscription_id'],
                ],
                'message' => 'Subscription created. Complete payment to activate.',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription. Please try again.',
            ], 500);
        }
    }

    /**
     * Cancel subscription (at period end).
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $subscription = UserSubscription::findOrFail($id);

        // Ownership check
        if ($subscription->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to cancel this subscription.',
            ], 403);
        }

        try {
            $cancelled = $this->subscriptions->cancel(
                $subscription,
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'data' => $cancelled,
                'message' => $cancelled->cancel_at_period_end
                    ? 'Subscription will be cancelled at the end of the current billing period.'
                    : 'Subscription cancelled.',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reactivate a subscription scheduled for cancellation.
     */
    public function reactivate(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $subscription = UserSubscription::findOrFail($id);

        // Ownership check
        if ($subscription->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to reactivate this subscription.',
            ], 403);
        }

        try {
            $reactivated = $this->subscriptions->reactivate($subscription);

            return response()->json([
                'success' => true,
                'data' => $reactivated,
                'message' => 'Subscription reactivated successfully.',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Upgrade subscription to a higher plan.
     */
    public function upgrade(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'new_plan_id' => 'required|exists:lce_subscription_plans,id',
            'new_billing_cycle' => 'nullable|in:daily,weekly,monthly,annual',
        ]);

        $user = $request->user();
        $subscription = UserSubscription::findOrFail($id);

        // Ownership check
        if ($subscription->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to upgrade this subscription.',
            ], 403);
        }

        $newPlan = SubscriptionPlan::findOrFail($data['new_plan_id']);

        try {
            $upgraded = $this->subscriptions->upgrade(
                $subscription,
                $newPlan,
                $data['new_billing_cycle'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $upgraded,
                'message' => 'Subscription upgraded successfully. Prorated charge will be applied.',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Downgrade subscription to a lower plan (effective at period end).
     */
    public function downgrade(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'new_plan_id' => 'required|exists:lce_subscription_plans,id',
            'new_billing_cycle' => 'nullable|in:daily,weekly,monthly,annual',
        ]);

        $user = $request->user();
        $subscription = UserSubscription::findOrFail($id);

        // Ownership check
        if ($subscription->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to downgrade this subscription.',
            ], 403);
        }

        $newPlan = SubscriptionPlan::findOrFail($data['new_plan_id']);

        try {
            $downgraded = $this->subscriptions->downgrade(
                $subscription,
                $newPlan,
                $data['new_billing_cycle'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $downgraded,
                'message' => 'Downgrade scheduled for end of current billing period.',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Pause subscription billing.
     * POST /api/v1/subscriptions/{id}/pause
     */
    public function pause(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $subscription = UserSubscription::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $paused = $this->subscriptions->pause($subscription, $data['reason'] ?? null);

            return response()->json([
                'success' => true,
                'data' => $paused,
                'message' => 'Subscription billing paused successfully.',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Resume paused subscription.
     * POST /api/v1/subscriptions/{id}/resume
     */
    public function resume(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $subscription = UserSubscription::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        try {
            $resumed = $this->subscriptions->resume($subscription);

            return response()->json([
                'success' => true,
                'data' => $resumed,
                'message' => 'Subscription billing resumed successfully.',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check if pickup can proceed based on subscription.
     * GET /api/v1/subscriptions/check-pickup
     */
    public function checkPickup(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscriptions()
            ->where('status', UserSubscription::STATUS_ACTIVE)
            ->with('plan')
            ->first();

        if (!$subscription) {
            return response()->json([
                'allowed' => false,
                'reason' => 'No active subscription',
                'requires_ppo' => false,
            ]);
        }

        $data = $request->validate([
            'bags' => 'nullable|integer|min:1',
        ]);

        $bags = $data['bags'] ?? 1;
        $result = $this->subscriptions->canProceedWithPickup($subscription, $bags);

        return response()->json($result);
    }
}
