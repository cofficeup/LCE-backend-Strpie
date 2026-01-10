<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_user_subscriptions';

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'billing_cycle',
        'start_date',
        'end_date',
        'next_renewal_date',
        'bags_plan_period',
        'bags_plan_total',
        'bags_plan_balance',
        'bags_plan_used',
        'bags_available',
        'created_via',
        'payment_last',
        'payment_discount',
        'payment_balance',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_renewal_date' => 'date',
        'bags_plan_period' => 'integer',
        'bags_plan_total' => 'integer',
        'bags_plan_balance' => 'integer',
        'bags_plan_used' => 'integer',
        'bags_available' => 'integer',
        'payment_last' => 'decimal:2',
        'payment_discount' => 'decimal:2',
        'payment_balance' => 'decimal:2',
    ];

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'mdate';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function usageRecords()
    {
        return $this->hasMany(SubscriptionUsage::class, 'user_subscription_id');
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user can use a bag.
     */
    public function canUseBag(): bool
    {
        return $this->isActive() && $this->bags_available > 0;
    }
}
