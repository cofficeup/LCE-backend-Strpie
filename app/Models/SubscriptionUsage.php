<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionUsage extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_user_subscription_usage';

    protected $fillable = [
        'user_subscription_id',
        'invoice_id',
        'pickup_id',
        'bags_used',
    ];

    protected $casts = [
        'bags_used' => 'integer',
    ];

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'mdate';

    public function subscription()
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function pickup()
    {
        return $this->belongsTo(Pickup::class, 'pickup_id');
    }
}
