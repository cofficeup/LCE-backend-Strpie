<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoLaundrySmsLog extends Model
{
    protected $table = 'lce_nolaundry_sms_log';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'pickup_id',
        'nolaundry_date',
        'phone_number',
        'msg_sent',
        'reply_text',
        'reply_date',
        'status',
    ];

    protected $casts = [
        'nolaundry_date' => 'datetime',
        'reply_date' => 'datetime',
        'msg_sent' => 'boolean',
        'status' => 'integer',
    ];

    /**
     * Get the user associated with this SMS log
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the pickup associated with this SMS log
     */
    public function pickup()
    {
        return $this->belongsTo(Pickup::class, 'pickup_id');
    }

    /**
     * Scope to get sent messages
     */
    public function scopeSent($query)
    {
        return $query->where('msg_sent', true);
    }

    /**
     * Scope to get messages with replies
     */
    public function scopeWithReplies($query)
    {
        return $query->whereNotNull('reply_text');
    }
}
