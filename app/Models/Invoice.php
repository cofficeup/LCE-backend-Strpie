<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'stripe_invoice_id',
        'user_id',
        'pickup_id',
        'subscription_id',
        'type',
        'status',
        'currency',
        'subtotal',
        'tax',
        'total',
        'metadata',
        'issued_at',
        'paid_at',
        'refunded_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pickup(): BelongsTo
    {
        return $this->belongsTo(Pickup::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id');
    }

    /**
     * Check if invoice has an active payment (pending or succeeded).
     */
    public function hasActivePayment(): bool
    {
        return $this->payments()
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUCCEEDED])
            ->exists();
    }

    /**
     * Check if invoice is already paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Check if invoice is partially refunded.
     */
    public function isPartiallyRefunded(): bool
    {
        return $this->status === 'partially_refunded';
    }
}
