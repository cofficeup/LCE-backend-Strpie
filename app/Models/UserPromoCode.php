<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'promo_code_id',
        'invoice_id',
        'discount_applied',
        'used_at'
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'discount_applied' => 'decimal:2'
    ];

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
