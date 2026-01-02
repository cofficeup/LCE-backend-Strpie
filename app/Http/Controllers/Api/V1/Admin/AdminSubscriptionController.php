<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\Stripe\StripeProductService;
use App\Services\Stripe\StripeSubscriptionService;
use App\Services\Subscription\SubscriptionService;
use App\Services\Payment\RefundService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminSubscriptionController extends Controller
{
    protected StripeProductService $productService;
    protected StripeSubscriptionService $stripeSubscription;
    protected SubscriptionService $subscriptionService;
    protected RefundService $refundService;

    public function __construct(
        StripeProductService $productService,
        StripeSubscriptionService $stripeSubscription,
        SubscriptionService $subscriptionService,
        RefundService $refundService
    ) {
        $this->productService = $productService;
        $this->stripeSubscription = $stripeSubscription;
        $this->subscriptionService = $subscriptionService;
        $this->refundService = $refundService;
    }

    /**
     * List all subscription plans.
     */
    public function listPlans(): JsonResponse
    {
        $plans = SubscriptionPlan::all();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * Create a new subscription plan.
     */
    public function createPlan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:lce_subscription_plans,slug',
            'description' => 'nullable|string',
            'bags_per_day' => 'nullable|integer|min:1',
            'bags_per_week' => 'nullable|integer|min:1',
            'bags_per_month' => 'required|integer|min:1',
            'price_daily' => 'nullable|numeric|min:0',
            'price_weekly' => 'nullable|numeric|min:0',
            'price_monthly' => 'required|numeric|min:0',
            'price_annual' => 'nullable|numeric|min:0',
            'overage_policy' => 'nullable|in:block,charge_ppo',
            'overage_price_per_bag' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $plan = SubscriptionPlan::create($data);

        return response()->json([
            'success' => true,
            'data' => $plan,
            'message' => 'Plan created. Use sync-to-stripe to enable Stripe billing.',
        ], 201);
    }

    /**
     * Update a subscription plan.
     */
    public function updatePlan(Request $request, int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'bags_per_day' => 'nullable|integer|min:1',
            'bags_per_week' => 'nullable|integer|min:1',
            'bags_per_month' => 'sometimes|integer|min:1',
            'price_daily' => 'nullable|numeric|min:0',
            'price_weekly' => 'nullable|numeric|min:0',
            'price_monthly' => 'sometimes|numeric|min:0',
            'price_annual' => 'nullable|numeric|min:0',
            'overage_policy' => 'nullable|in:block,charge_ppo',
            'overage_price_per_bag' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $plan->update($data);

        return response()->json([
            'success' => true,
            'data' => $plan->fresh(),
            'message' => 'Plan updated. Re-sync to Stripe if prices changed.',
        ]);
    }

    /**
     * Sync a plan to Stripe.
     */
    public function syncPlanToStripe(int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        try {
            $syncedPlan = $this->productService->syncPlanToStripe($plan);

            return response()->json([
                'success' => true,
                'data' => $syncedPlan,
                'message' => 'Plan synced to Stripe successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync plan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync all active plans to Stripe.
     */
    public function syncAllPlansToStripe(): JsonResponse
    {
        try {
            $results = $this->productService->syncAllPlansToStripe();

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync plans: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all user subscriptions with filters.
     */
    public function listSubscriptions(Request $request): JsonResponse
    {
        $query = UserSubscription::with(['user', 'plan']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    /**
     * Force upgrade a user's subscription.
     */
    public function forceUpgrade(Request $request, int $subscriptionId): JsonResponse
    {
        $data = $request->validate([
            'new_plan_id' => 'required|exists:lce_subscription_plans,id',
            'new_billing_cycle' => 'nullable|in:daily,weekly,monthly,annual',
        ]);

        $subscription = UserSubscription::findOrFail($subscriptionId);
        $newPlan = SubscriptionPlan::findOrFail($data['new_plan_id']);

        try {
            $upgraded = $this->subscriptionService->upgrade(
                $subscription,
                $newPlan,
                $data['new_billing_cycle'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $upgraded->load('plan'),
                'message' => 'Subscription upgraded successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Force downgrade a user's subscription (immediate with optional refund).
     */
    public function forceDowngrade(Request $request, int $subscriptionId): JsonResponse
    {
        $data = $request->validate([
            'new_plan_id' => 'required|exists:lce_subscription_plans,id',
            'new_billing_cycle' => 'nullable|in:daily,weekly,monthly,annual',
            'issue_refund' => 'nullable|boolean',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        $subscription = UserSubscription::findOrFail($subscriptionId);
        $newPlan = SubscriptionPlan::findOrFail($data['new_plan_id']);

        try {
            return DB::transaction(function () use ($subscription, $newPlan, $data, $request) {
                // If immediate refund requested, process it first
                if (!empty($data['issue_refund']) && !empty($data['refund_amount'])) {
                    $invoice = $subscription->invoices()
                        ->where('status', 'paid')
                        ->latest()
                        ->first();

                    if ($invoice) {
                        $this->refundService->processPartialRefund(
                            $invoice,
                            $data['refund_amount'],
                            'Admin-initiated downgrade refund',
                            $request->user()
                        );
                    }
                }

                // Apply immediate downgrade
                $newCycle = $data['new_billing_cycle'] ?? $subscription->billing_cycle;
                $bagsForCycle = $newPlan->getBagsForCycle($newCycle);

                // Update in Stripe without proration (refund handled separately)
                if ($subscription->stripe_subscription_id) {
                    $this->stripeSubscription->updatePlan(
                        $subscription,
                        $newPlan,
                        $newCycle,
                        'none' // No proration, we handled refund manually
                    );
                }

                // Update local subscription
                $subscription->update([
                    'plan_id' => $newPlan->id,
                    'billing_cycle' => $newCycle,
                    'bags_plan_total' => $bagsForCycle,
                    'bags_plan_balance' => max(0, $bagsForCycle - $subscription->bags_plan_used),
                    'bags_available' => max(0, $bagsForCycle - $subscription->bags_plan_used),
                    'pending_plan_id' => null,
                    'pending_billing_cycle' => null,
                ]);

                // Audit log
                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'admin_force_downgrade',
                    'entity_type' => 'subscription',
                    'entity_id' => $subscription->id,
                    'metadata' => [
                        'new_plan_id' => $newPlan->id,
                        'refund_issued' => !empty($data['issue_refund']),
                        'refund_amount' => $data['refund_amount'] ?? null,
                    ],
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $subscription->fresh()->load('plan'),
                    'message' => 'Subscription downgraded immediately.',
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Apply manual proration to a subscription.
     */
    public function applyManualProration(Request $request, int $subscriptionId): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric',
            'reason' => 'required|string|max:500',
        ]);

        $subscription = UserSubscription::findOrFail($subscriptionId);

        try {
            $subscription->update([
                'manual_proration_applied' => true,
                'manual_proration_amount' => $data['amount'],
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'admin_manual_proration',
                'entity_type' => 'subscription',
                'entity_id' => $subscription->id,
                'metadata' => [
                    'amount' => $data['amount'],
                    'reason' => $data['reason'],
                ],
            ]);

            return response()->json([
                'success' => true,
                'data' => $subscription->fresh(),
                'message' => 'Manual proration applied.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel subscription immediately.
     */
    public function cancelImmediately(Request $request, int $subscriptionId): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $subscription = UserSubscription::findOrFail($subscriptionId);

        try {
            return DB::transaction(function () use ($subscription, $data, $request) {
                // Cancel in Stripe immediately
                if ($subscription->stripe_subscription_id) {
                    $this->stripeSubscription->cancelImmediately($subscription);
                }

                // Update local subscription
                $subscription->update([
                    'status' => UserSubscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancel_reason' => $data['reason'] ?? 'Admin cancellation',
                ]);

                // Remove from user
                if ($subscription->user->subscription_id === $subscription->id) {
                    $subscription->user->update(['subscription_id' => null]);
                }

                // Audit log
                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'admin_cancel_immediately',
                    'entity_type' => 'subscription',
                    'entity_id' => $subscription->id,
                    'metadata' => [
                        'reason' => $data['reason'] ?? 'Admin cancellation',
                    ],
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $subscription->fresh(),
                    'message' => 'Subscription cancelled immediately.',
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get subscription billing history.
     */
    public function billingHistory(int $subscriptionId): JsonResponse
    {
        $subscription = UserSubscription::findOrFail($subscriptionId);

        $invoices = $subscription->invoices()
            ->with('lines')
            ->orderBy('created_at', 'desc')
            ->get();

        $auditLogs = AuditLog::where('entity_type', 'subscription')
            ->where('entity_id', $subscriptionId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => $subscription->load('plan', 'user'),
                'invoices' => $invoices,
                'audit_logs' => $auditLogs,
            ],
        ]);
    }
}
