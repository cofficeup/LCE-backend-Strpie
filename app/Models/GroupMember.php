<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_user_group_members';

    protected $primaryKey = 'intid';

    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'admin_user_id',
        'user_id',
        'group_code',
        'monthly_transaction_limit',
        'monthly_wf_limit',
        'monthly_dc_limit',
        'monthly_wf_dc_limit',
        'department',
        'approved',
        'date_added',
        'published',
        'current_status',
    ];

    protected $casts = [
        'monthly_transaction_limit' => 'decimal:2',
        'monthly_wf_limit' => 'boolean',
        'monthly_dc_limit' => 'boolean',
        'approved' => 'boolean',
        'published' => 'boolean',
        'date_added' => 'date',
    ];

    public function group()
    {
        return $this->belongsTo(GroupAdmin::class, 'group_id', 'group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
