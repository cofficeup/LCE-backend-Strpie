<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidayLog extends Model
{
    protected $table = 'lce_holidays_logs';
    public $timestamps = false;

    protected $fillable = [
        'holiday_date',
        'email_ids',
        'log_time',
        'mail_sent',
        'filter_date',
        'email_count',
    ];

    protected $casts = [
        'log_time' => 'datetime',
        'filter_date' => 'date',
        'email_count' => 'integer',
    ];

    /**
     * Scope to get logs for a specific holiday
     */
    public function scopeForHoliday($query, string $holidayDate)
    {
        return $query->where('holiday_date', $holidayDate);
    }

    /**
     * Scope to get sent emails
     */
    public function scopeSent($query)
    {
        return $query->where('mail_sent', 'Yes');
    }

    /**
     * Scope to get unsuccessful sends
     */
    public function scopeFailed($query)
    {
        return $query->where('mail_sent', 'No');
    }
}
