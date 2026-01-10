<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSupport extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_user_cs';

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'invoice_number',
        'title',
        'description',
        'action',
        'hear_about',
        'owner_id',
        'cuser_id',
        'muser_id',
    ];

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'mdate';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function logs()
    {
        return $this->hasMany(CustomerSupportLog::class, 'cs_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
