<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupHoliday extends Model
{
    protected $table = 'lce_pickup_nonworking_days';

    public $timestamps = false;

    protected $fillable = [
        'date',
        'name',
        'area',
    ];

    /**
     * Check if a date is a holiday in an area.
     */
    public function scopeForDate($query, $date)
    {
        $dateString = is_string($date) ? $date : $date->format('Y-m-d');
        return $query->where('date', $dateString);
    }

    public function scopeForArea($query, string $area)
    {
        return $query->where(function ($q) use ($area) {
            $q->where('area', $area)
                ->orWhere('area', '')
                ->orWhereNull('area');
        });
    }

    /**
     * Scope for active holidays (all holidays in legacy schema).
     */
    public function scopeActive($query)
    {
        return $query;
    }

    /**
     * Scope for holidays within a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        ]);
    }
}
