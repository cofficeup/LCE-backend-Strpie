<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TmpNoLaundryTransaction extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_tmp_nolaundry_transactions';
    protected $primaryKey = 'intid';
    public $timestamps = false;

    protected $fillable = [
        'pickup_id',
        'user_id',
        'transaction_id',
        'date_added',
        'status',
        'type',
        'amount',
    ];

    protected $casts = [
        'date_added' => 'datetime',
        'transaction_id' => 'integer',
        'amount' => 'float',
    ];

    /**
     * Get the user associated with this transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the pickup associated with this transaction
     */
    public function pickup()
    {
        return $this->belongsTo(Pickup::class, 'pickup_id');
    }
}
