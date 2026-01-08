<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $table = 'lce_user_credits';

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'amount',
        'balance',
        'expires_at',
        'used',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'mdate';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('used', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
