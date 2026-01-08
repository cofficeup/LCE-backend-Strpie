<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMemberHistory extends Model
{
    protected $table = 'lce_user_group_members_history';
    protected $primaryKey = 'intid';
    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'user_id',
        'group_admin_id',
        'invoice_id',
        'transaction_date',
        'transaction_amount',
        'wf_orders',
        'dc_orders',
        'wf_dc_orders',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'transaction_amount' => 'float',
        'wf_orders' => 'integer',
        'dc_orders' => 'integer',
        'wf_dc_orders' => 'integer',
    ];

    /**
     * Get the user associated with this history entry
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the group admin associated with this history entry
     */
    public function groupAdmin()
    {
        return $this->belongsTo(GroupAdmin::class, 'group_admin_id', 'group_id');
    }

    /**
     * Get the invoice associated with this history entry
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
