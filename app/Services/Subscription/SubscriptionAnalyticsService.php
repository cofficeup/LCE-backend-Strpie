<?php

namespace App\Services\Subscription;

use App\Models\UserSubscription;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionAnalyticsService
{
    /**
     * Get revenue analytics by plan.
     */
    public function getRevenueByPlan(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        $revenue = Invoice::select(
            'lce_subscription_plans.id as plan_id',
            'lce_subscription_plans.name as plan_name',
            DB::raw('COUNT(lce_invoices.id) as invoice_count'),
            DB::raw('SUM(lce_invoices.total) as total_revenue'),
            DB::raw('AVG(lce_invoices.total) as avg_invoice_amount')
        )
            ->join('lce_user_subscriptions', 'lce_invoices.subscription_id', '=', 'lce_user_subscriptions.id')
            ->join('lce_subscription_plans', 'lce_user_subscriptions.plan_id', '=', 'lce_subscription_plans.id')
            ->where('lce_invoices.status', 'paid')
            ->where('lce_invoices.type', 'subscription')
            ->whereBetween('lce_invoices.paid_at', [$startDate, $endDate])
            ->groupBy('lce_subscription_plans.id', 'lce_subscription_plans.name')
            ->get();

        $totalRevenue = $revenue->sum('total_revenue');

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'total_revenue' => $totalRevenue,
            'by_plan' => $revenue->map(function ($item) use ($totalRevenue) {
                return [
                    'plan_id' => $item->plan_id,
                    'plan_name' => $item->plan_name,
                    'invoice_count' => $item->invoice_count,
                    'total_revenue' => round($item->total_revenue, 2),
                    'avg_invoice_amount' => round($item->avg_invoice_amount, 2),
                    'percentage' => $totalRevenue > 0 ? round(($item->total_revenue / $totalRevenue) * 100, 1) : 0,
                ];
            }),
        ];
    }

    /**
     * Get subscription status distribution.
     */
    public function getStatusDistribution(): array
    {
        $distribution = UserSubscription::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $total = array_sum($distribution);

        return [
            'total' => $total,
            'active' => $distribution[UserSubscription::STATUS_ACTIVE] ?? 0,
            'pending' => $distribution[UserSubscription::STATUS_PENDING] ?? 0,
            'paused' => $distribution[UserSubscription::STATUS_PAUSED] ?? 0,
            'past_due' => $distribution[UserSubscription::STATUS_PAST_DUE] ?? 0,
            'cancelled' => $distribution[UserSubscription::STATUS_CANCELLED] ?? 0,
            'distribution' => collect($distribution)->map(function ($count) use ($total) {
                return [
                    'count' => $count,
                    'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                ];
            }),
        ];
    }

    /**
     * Calculate churn rate.
     * Churn = (Cancelled in period) / (Active at start of period) * 100
     */
    public function getChurnRate(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subMonth()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->subMonth()->endOfMonth();

        // Subscriptions active at start of period
        $activeAtStart = UserSubscription::where('created_at', '<', $startDate)
            ->whereIn('status', [
                UserSubscription::STATUS_ACTIVE,
                UserSubscription::STATUS_CANCELLED,
            ])
            ->whereNull('cancelled_at')
            ->orWhere('cancelled_at', '>', $startDate)
            ->count();

        // Subscriptions cancelled during period
        $cancelledDuringPeriod = UserSubscription::where('status', UserSubscription::STATUS_CANCELLED)
            ->whereBetween('cancelled_at', [$startDate, $endDate])
            ->count();

        $churnRate = $activeAtStart > 0
            ? round(($cancelledDuringPeriod / $activeAtStart) * 100, 2)
            : 0;

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'active_at_start' => $activeAtStart,
            'cancelled_during_period' => $cancelledDuringPeriod,
            'churn_rate' => $churnRate,
        ];
    }

    /**
     * Get overage revenue.
     */
    public function getOverageRevenue(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        $overageInvoices = Invoice::where('type', 'ppo_overage')
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->get();

        $totalRevenue = $overageInvoices->sum('total');
        $totalBags = $overageInvoices->sum(function ($invoice) {
            return $invoice->metadata['overage_bags'] ?? 0;
        });

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'total_overage_revenue' => round($totalRevenue, 2),
            'total_overage_invoices' => $overageInvoices->count(),
            'total_overage_bags' => $totalBags,
            'avg_per_invoice' => $overageInvoices->count() > 0
                ? round($totalRevenue / $overageInvoices->count(), 2)
                : 0,
        ];
    }

    /**
     * Get monthly recurring revenue (MRR).
     */
    public function getMRR(): array
    {
        $activeSubscriptions = UserSubscription::where('status', UserSubscription::STATUS_ACTIVE)
            ->with('plan')
            ->get();

        $mrr = 0;
        foreach ($activeSubscriptions as $subscription) {
            $price = $subscription->plan->getPriceForCycle($subscription->billing_cycle);
            if ($price) {
                // Normalize to monthly
                $monthlyPrice = match ($subscription->billing_cycle) {
                    'daily' => $price * 30,
                    'weekly' => $price * 4,
                    'monthly' => $price,
                    'annual' => $price / 12,
                    default => $price,
                };
                $mrr += $monthlyPrice;
            }
        }

        return [
            'mrr' => round($mrr, 2),
            'arr' => round($mrr * 12, 2),
            'active_subscriptions' => $activeSubscriptions->count(),
            'avg_revenue_per_subscription' => $activeSubscriptions->count() > 0
                ? round($mrr / $activeSubscriptions->count(), 2)
                : 0,
        ];
    }

    /**
     * Get dashboard summary.
     */
    public function getDashboardSummary(): array
    {
        return [
            'mrr' => $this->getMRR(),
            'status_distribution' => $this->getStatusDistribution(),
            'churn_rate' => $this->getChurnRate(),
            'overage_revenue' => $this->getOverageRevenue(),
        ];
    }
}
