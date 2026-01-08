<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $table = 'lce_subscription_plans';

    protected $fillable = [
        'code',
        'name',
        'bags_per_month',
        'price_per_bag',
        'billing_cycle',
        'annual_discount',
        'active',
    ];

    protected $casts = [
        'bags_per_month' => 'integer',
        'price_per_bag' => 'decimal:2',
        'annual_discount' => 'decimal:2',
        'active' => 'boolean',
    ];

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'mdate';

    /**
     * Calculate monthly price.
     */
    public function getMonthlyPriceAttribute(): float
    {
        return $this->bags_per_month * $this->price_per_bag;
    }

    /**
     * Calculate annual price with discount.
     */
    public function getAnnualPriceAttribute(): float
    {
        $monthly = $this->monthly_price;
        $annual = $monthly * 12;
        return $annual * (1 - ($this->annual_discount / 100));
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'plan_id');
    }
}
