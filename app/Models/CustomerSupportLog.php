<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSupportLog extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_user_cs_log';

    protected $fillable = [
        'cs_id',
        'note',
        'action',
        'cuser_id',
        'muser_id',
    ];

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'mdate';

    public function ticket()
    {
        return $this->belongsTo(CustomerSupport::class, 'cs_id');
    }
}
