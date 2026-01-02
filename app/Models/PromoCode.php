<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'max_uses',
        'current_uses',
        'min_order_amount',
        'start_date',
        'expiry_date',
        'applies_to',
        'description',
        'active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'expiry_date' => 'date',
        'active' => 'boolean',
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
    ];

    /**
     * Check if promo code is valid for use.
     */
    public function isValid(?float $orderAmount = null): bool
    {
        if (!$this->active) return false;

        $now = Carbon::now();

        // Date checks
        if ($this->start_date && $now->lt($this->start_date)) return false;
        if ($this->expiry_date && $now->gt($this->expiry_date)) return false;

        // Usage limits
        if ($this->max_uses && $this->current_uses >= $this->max_uses) return false;

        // Order amount check
        if ($orderAmount !== null && $this->min_order_amount && $orderAmount < $this->min_order_amount) return false;

        return true;
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
