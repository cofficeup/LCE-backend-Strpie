<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'schedule_monday',
        'schedule_tuesday',
        'schedule_wednesday',
        'schedule_thursday',
        'schedule_friday',
        'schedule_saturday',
        'schedule_sunday',
        'order_type',
        'default_bags',
        'default_weight',
        'start_date',
        'end_date',
        'notes',
        'active'
    ];

    protected $casts = [
        'schedule_monday' => 'boolean',
        'schedule_tuesday' => 'boolean',
        'schedule_wednesday' => 'boolean',
        'schedule_thursday' => 'boolean',
        'schedule_friday' => 'boolean',
        'schedule_saturday' => 'boolean',
        'schedule_sunday' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
        'default_weight' => 'decimal:2'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if pickup should be generated for a specific date.
     */
    public function shouldGenerateForDate($date): bool
    {
        $dayOfWeek = strtolower($date->format('l')); // monday, tuesday...
        $field = 'schedule_' . $dayOfWeek;

        if (!$this->$field) return false;

        if ($date->lt($this->start_date)) return false;
        if ($this->end_date && $date->gt($this->end_date)) return false;

        return true;
    }
}
