<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupLog extends Model
{
    protected $table = 'lce_user_group_log';
    protected $primaryKey = 'intid';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'group_admin_id',
        'pickup_id',
        'action',
        'note',
        'date_added',
    ];

    protected $casts = [
        'date_added' => 'date',
    ];

    /**
     * Get the user associated with this log entry
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the group admin associated with this log entry
     */
    public function groupAdmin()
    {
        return $this->belongsTo(GroupAdmin::class, 'group_admin_id', 'group_id');
    }

    /**
     * Get the pickup associated with this log entry
     */
    public function pickup()
    {
        return $this->belongsTo(Pickup::class, 'pickup_id');
    }
}
