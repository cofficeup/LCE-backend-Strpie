<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationSettings extends Model
{
    protected $table = 'lce_communication_settings';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'pickup_confirm_email',
        'pickup_reminder_email',
        'picked_up_email',
        'outfordelivery_email',
        'delivered_email',
        'outfordelivery_sms',
        'picked_up_sms',
        'delivered_sms',
        'pickup_confirm_sms',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
