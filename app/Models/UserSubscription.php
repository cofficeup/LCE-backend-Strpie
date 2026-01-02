<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    // LCE 2.0 Subscriptions
    protected $table = 'lce_user_subscriptions';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_UPGRADED = 'upgraded';
    const STATUS_DOWNGRADED = 'downgraded';

    protected $fillable = [
        'user_id',
        'plan_id',
        'pending_plan_id',
        'pending_billing_cycle',

        // Stripe IDs
        'stripe_subscription_id',
        'stripe_customer_id',
        'stripe_schedule_id',

        // Status
        'status',
        'cancel_at_period_end',
        'cancel_reason',
        'cancelled_at',

        // Billing cycle
        'billing_cycle',

        // Dates
        'start_date',
        'end_date',
        'next_renewal_date',
        'current_period_start',
        'current_period_end',

        // Bag tracking
        'bags_plan_period',
        'bags_plan_total',
        'bags_plan_balance',
        'bags_plan_used',
        'bags_available',

        // Payment tracking
        'payment_last',
        'payment_discount',
        'payment_balance',

        // Proration
        'manual_proration_applied',
        'manual_proration_amount',

        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_renewal_date' => 'date',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'manual_proration_applied' => 'boolean',
        'payment_last' => 'decimal:2',
        'payment_discount' => 'decimal:2',
        'payment_balance' => 'decimal:2',
        'manual_proration_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function pendingPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'pending_plan_id');
    }

    public function bagUsages()
    {
        return $this->hasMany(SubscriptionBagUsage::class, 'user_subscription_id');
    }

    public function pickups()
    {
        return $this->hasMany(Pickup::class, 'subscription_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'subscription_id');
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if subscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if subscription is pending cancellation at period end.
     */
    public function isPendingCancellation(): bool
    {
        return $this->cancel_at_period_end && $this->isActive();
    }

    /**
     * Check if subscription is past due.
     */
    public function isPastDue(): bool
    {
        return $this->status === self::STATUS_PAST_DUE;
    }

    /**
     * Check if subscription has pending plan change.
     */
    public function hasPendingPlanChange(): bool
    {
        return !is_null($this->pending_plan_id);
    }

    /**
     * Check if subscription is synced to Stripe.
     */
    public function isSyncedToStripe(): bool
    {
        return !empty($this->stripe_subscription_id);
    }

    /**
     * Get remaining bags for current period.
     */
    public function getRemainingBags(): int
    {
        return max(0, $this->bags_plan_total - $this->bags_plan_used);
    }

    /**
     * Check if user can use another bag.
     */
    public function canUseBag(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // If overage policy allows PPO, always allow
        if ($this->plan && $this->plan->shouldChargePPOOnOverage()) {
            return true;
        }

        // Otherwise, check remaining bags
        return $this->getRemainingBags() > 0;
    }

    /**
     * Check if current usage is over limit.
     */
    public function isOverLimit(): bool
    {
        return $this->bags_plan_used >= $this->bags_plan_total;
    }
}
