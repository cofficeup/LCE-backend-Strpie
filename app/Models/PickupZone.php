<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupZone extends Model
{
    protected $table = 'lce_pickup_zones';

    public $timestamps = false;

    protected $fillable = [
        'zip',
        'city',
        'state',
        'day_monday',
        'day_tuesday',
        'day_wednesday',
        'day_thursday',
        'day_friday',
        'area',
        'drivers',
        'order',
        'geometry',
        'geo_location',
    ];

    protected $casts = [
        'day_monday' => 'boolean',
        'day_tuesday' => 'boolean',
        'day_wednesday' => 'boolean',
        'day_thursday' => 'boolean',
        'day_friday' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Check if service is available on a given day.
     */
    public function isServiceAvailableOnDay(string $day): bool
    {
        $column = 'day_' . strtolower($day);
        return $this->$column ?? false;
    }

    /**
     * Get available days as array.
     */
    public function getAvailableDaysAttribute(): array
    {
        $days = [];
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
            if ($this->{"day_{$day}"}) {
                $days[] = ucfirst($day);
            }
        }
        return $days;
    }

    /**
     * Scope for active zones (has at least one service day).
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('day_monday', true)
                ->orWhere('day_tuesday', true)
                ->orWhere('day_wednesday', true)
                ->orWhere('day_thursday', true)
                ->orWhere('day_friday', true);
        });
    }

    /**
     * Scope for filtering by ZIP code.
     */
    public function scopeForZip($query, string $zipCode)
    {
        return $query->where('zip', $zipCode);
    }
}
