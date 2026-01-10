<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_user_transactions';

    protected $fillable = [
        'user_id',
        'type',
        'invoice_id',
        'transactionId',
        'name',
        'amount',
        'description',
        'note',
        'cuserid',
        'muserid',
        'group_admin_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'transactionId' => 'integer',
    ];

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'mdate';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
