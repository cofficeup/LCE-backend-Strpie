<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $table = 'lce_subscription_plans';

    protected $fillable = [
        'name',
        'slug',
        'description',

        // Bag allocations
        'bags_per_day',
        'bags_per_week',
        'bags_per_month',

        // Pricing
        'price_daily',
        'price_weekly',
        'price_monthly',
        'price_annual',

        // Stripe IDs
        'stripe_product_id',
        'stripe_price_id_daily',
        'stripe_price_id_weekly',
        'stripe_price_id_monthly',
        'stripe_price_id_annual',

        // Overage policy
        'overage_policy',
        'overage_price_per_bag',

        'is_active',
    ];

    protected $casts = [
        'price_daily' => 'decimal:2',
        'price_weekly' => 'decimal:2',
        'price_monthly' => 'decimal:2',
        'price_annual' => 'decimal:2',
        'overage_price_per_bag' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Billing cycle constants
    const CYCLE_DAILY = 'daily';
    const CYCLE_WEEKLY = 'weekly';
    const CYCLE_MONTHLY = 'monthly';
    const CYCLE_ANNUAL = 'annual';

    const CYCLES = [
        self::CYCLE_DAILY,
        self::CYCLE_WEEKLY,
        self::CYCLE_MONTHLY,
        self::CYCLE_ANNUAL,
    ];

    // Overage policy constants
    const OVERAGE_BLOCK = 'block';
    const OVERAGE_CHARGE_PPO = 'charge_ppo';

    public function userSubscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'plan_id');
    }

    /**
     * Get price for a specific billing cycle.
     */
    public function getPriceForCycle(string $cycle): ?float
    {
        return match ($cycle) {
            self::CYCLE_DAILY => $this->price_daily,
            self::CYCLE_WEEKLY => $this->price_weekly,
            self::CYCLE_MONTHLY => $this->price_monthly,
            self::CYCLE_ANNUAL => $this->price_annual,
            default => null,
        };
    }

    /**
     * Get bags allocation for a specific billing cycle.
     */
    public function getBagsForCycle(string $cycle): ?int
    {
        return match ($cycle) {
            self::CYCLE_DAILY => $this->bags_per_day,
            self::CYCLE_WEEKLY => $this->bags_per_week,
            self::CYCLE_MONTHLY => $this->bags_per_month,
            self::CYCLE_ANNUAL => $this->bags_per_month * 12,
            default => null,
        };
    }

    /**
     * Get Stripe price ID for a specific billing cycle.
     */
    public function getStripePriceIdForCycle(string $cycle): ?string
    {
        return match ($cycle) {
            self::CYCLE_DAILY => $this->stripe_price_id_daily,
            self::CYCLE_WEEKLY => $this->stripe_price_id_weekly,
            self::CYCLE_MONTHLY => $this->stripe_price_id_monthly,
            self::CYCLE_ANNUAL => $this->stripe_price_id_annual,
            default => null,
        };
    }

    /**
     * Check if plan is synced to Stripe.
     */
    public function isSyncedToStripe(): bool
    {
        return !empty($this->stripe_product_id);
    }

    /**
     * Check if overage should block pickup.
     */
    public function shouldBlockOnOverage(): bool
    {
        return $this->overage_policy === self::OVERAGE_BLOCK;
    }

    /**
     * Check if overage should charge PPO.
     */
    public function shouldChargePPOOnOverage(): bool
    {
        return $this->overage_policy === self::OVERAGE_CHARGE_PPO;
    }
}
