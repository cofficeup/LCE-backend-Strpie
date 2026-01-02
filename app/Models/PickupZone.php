<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupZone extends Model
{
    use HasFactory;

    protected $table = 'pickup_zones';

    protected $fillable = [
        'zip_code',
        'city',
        'state',
        'service_monday',
        'service_tuesday',
        'service_wednesday',
        'service_thursday',
        'service_friday',
        'service_saturday',
        'service_sunday',
        'area_code',
        'driver_ids',
        'polygon_coordinates',
        'geo_enabled',
        'display_order',
        'active',
    ];

    protected $casts = [
        'service_monday' => 'boolean',
        'service_tuesday' => 'boolean',
        'service_wednesday' => 'boolean',
        'service_thursday' => 'boolean',
        'service_friday' => 'boolean',
        'service_saturday' => 'boolean',
        'service_sunday' => 'boolean',
        'driver_ids' => 'array',
        'polygon_coordinates' => 'array',
        'geo_enabled' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Get service days as array.
     */
    public function getServiceDaysAttribute(): array
    {
        return [
            'monday' => $this->service_monday,
            'tuesday' => $this->service_tuesday,
            'wednesday' => $this->service_wednesday,
            'thursday' => $this->service_thursday,
            'friday' => $this->service_friday,
            'saturday' => $this->service_saturday,
            'sunday' => $this->service_sunday,
        ];
    }

    /**
     * Check if service is available on a specific day.
     */
    public function isServiceAvailableOnDay(string $dayOfWeek): bool
    {
        $dayField = 'service_' . strtolower($dayOfWeek);
        return $this->$dayField ?? false;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForZip($query, string $zipCode)
    {
        return $query->where('zip_code', $zipCode);
    }

    public function scopeForArea($query, string $areaCode)
    {
        return $query->where('area_code', $areaCode);
    }
}
