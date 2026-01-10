<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPromoCode extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_user_promocode';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'promocode_id',
        'promocode',
        'active',
        'expiry_date',
    ];

    protected $casts = [
        'active' => 'boolean',
        'expiry_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class, 'promocode_id');
    }
}
