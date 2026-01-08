<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TmpGroupMember extends Model
{
    protected $table = 'lce_tmp_group_members';
    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'email',
        'monthly_transaction_limit',
        'monthly_wf_limit',
        'monthly_dc_limit',
        'department',
        'cdate',
    ];

    protected $casts = [
        'cdate' => 'date',
        'monthly_transaction_limit' => 'decimal:2',
        'monthly_wf_limit' => 'boolean',
        'monthly_dc_limit' => 'boolean',
    ];
}
