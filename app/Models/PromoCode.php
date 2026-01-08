<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $table = 'lce_promo_codes';

    public $timestamps = false;

    protected $fillable = [
        'promocode',
        'promocode_type',
        'promocode_value',
        'publish',
        'promocode_time_period',
        'time_period_value',
        'promo_expiry_date',
        'promocode_for',
        'promocode_description',
        'created_date',
    ];

    protected $casts = [
        'promocode_value' => 'float',
        'publish' => 'boolean',
        'promo_expiry_date' => 'date',
        'created_date' => 'date',
    ];

    /**
     * Check if promo is active and not expired.
     */
    public function isValid(): bool
    {
        if (!$this->publish) {
            return false;
        }

        if ($this->promo_expiry_date && $this->promo_expiry_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if promo is percentage type.
     */
    public function isPercentage(): bool
    {
        return $this->promocode_type === 'percentage';
    }

    /**
     * Calculate discount amount.
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->isPercentage()) {
            return $amount * ($this->promocode_value / 100);
        }
        return min($this->promocode_value, $amount);
    }

    public function usages()
    {
        return $this->hasMany(UserPromoCode::class, 'promocode_id');
    }

    public function scopeActive($query)
    {
        return $query->where('publish', true)
            ->where(function ($q) {
                $q->whereNull('promo_expiry_date')
                    ->orWhere('promo_expiry_date', '>=', now()->toDateString());
            });
    }
}
