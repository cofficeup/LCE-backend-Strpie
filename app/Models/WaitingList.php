<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitingList extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_waiting_list';

    public $timestamps = false;

    protected $fillable = [
        'zip',
        'notify_email',
        'notified',
        'notify_date',
    ];

    protected $casts = [
        'notified' => 'boolean',
        'notify_date' => 'datetime',
    ];

    public function scopePending($query)
    {
        return $query->where('notified', '0');
    }
}
