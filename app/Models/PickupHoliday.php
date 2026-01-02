<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PickupHoliday extends Model
{
    use HasFactory;

    protected $table = 'pickup_holidays';

    protected $fillable = [
        'holiday_date',
        'holiday_name',
        'area_code',
        'active',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'active' => 'boolean',
    ];

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForDate($query, $date)
    {
        $carbonDate = Carbon::parse($date);
        return $query->where('holiday_date', $carbonDate->format('Y-m-d'));
    }

    public function scopeForArea($query, ?string $areaCode = null)
    {
        // If area_code is null, get global holidays
        // If area_code is provided, get both global and area-specific
        return $query->where(function ($q) use ($areaCode) {
            $q->whereNull('area_code');
            if ($areaCode) {
                $q->orWhere('area_code', $areaCode);
            }
        });
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('holiday_date', [
            Carbon::parse($startDate)->format('Y-m-d'),
            Carbon::parse($endDate)->format('Y-m-d'),
        ]);
    }
}
