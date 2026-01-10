<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecurringSchedule extends Model
{
    protected $connection = 'client';
    protected $table = 'lce_user_rs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'day_monday',
        'day_tuesday',
        'day_wednesday',
        'day_thursday',
        'day_friday',
        'day_saturday',
        'day_sunday',
        'delivey_type',
        'comments',
        'start_date',
    ];

    protected $casts = [
        'day_monday' => 'string', // Legacy uses varchar 'on'/null
        'day_tuesday' => 'string',
        'day_wednesday' => 'string',
        'day_thursday' => 'string',
        'day_friday' => 'string',
        'day_saturday' => 'string',
        'day_sunday' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if scheduled for a given day.
     */
    public function isScheduledForDay(string $day): bool
    {
        $column = 'day_' . strtolower($day);
        return !empty($this->$column);
    }

    /**
     * Get scheduled days as array.
     */
    public function getScheduledDaysAttribute(): array
    {
        $days = [];
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            if (!empty($this->{"day_{$day}"})) {
                $days[] = ucfirst($day);
            }
        }
        return $days;
    }
}
