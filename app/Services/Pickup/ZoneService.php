<?php

namespace App\Services\Pickup;

use App\Models\PickupZone;
use App\Models\PickupHoliday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ZoneService
{
    /**
     * Check if a ZIP code is serviceable.
     */
    public function isServiceable(string $zipCode): bool
    {
        return PickupZone::active()
            ->forZip($zipCode)
            ->exists();
    }

    /**
     * Get zone information for a ZIP code.
     */
    public function getZoneByZip(string $zipCode): ?PickupZone
    {
        return PickupZone::active()
            ->forZip($zipCode)
            ->first();
    }

    /**
     * Get available service days for a ZIP code.
     */
    public function getAvailableServiceDays(string $zipCode): ?array
    {
        $zone = $this->getZoneByZip($zipCode);

        if (!$zone) {
            return null;
        }

        return $zone->service_days;
    }

    /**
     * Check if service is available on a specific date and ZIP.
     */
    public function isAvailableOnDate(string $zipCode, $date): array
    {
        $zone = $this->getZoneByZip($zipCode);

        if (!$zone) {
            return [
                'available' => false,
                'reason' => 'Service not available in this ZIP code',
            ];
        }

        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->format('l'); // Monday, Tuesday, etc.

        // Check if service is available on this day of week
        if (!$zone->isServiceAvailableOnDay($dayOfWeek)) {
            return [
                'available' => false,
                'reason' => 'No service available on ' . $dayOfWeek . 's in this area',
            ];
        }

        // Check if it's a holiday
        $holiday = PickupHoliday::active()
            ->forDate($date)
            ->forArea($zone->area)
            ->first();

        if ($holiday) {
            return [
                'available' => false,
                'reason' => 'Holiday: ' . $holiday->name,
            ];
        }

        return [
            'available' => true,
            'zone' => $zone,
        ];
    }

    /**
     * Get available pickup dates for a ZIP code in a given month.
     */
    public function getAvailableDates(string $zipCode, ?string $month = null): array
    {
        $zone = $this->getZoneByZip($zipCode);

        if (!$zone) {
            return [
                'success' => false,
                'message' => 'Service not available in this ZIP code',
                'dates' => [],
            ];
        }

        // Default to current month
        $startDate = $month ? Carbon::parse($month)->startOfMonth() : Carbon::now()->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get holidays for this period
        $holidays = PickupHoliday::active()
            ->forArea($zone->area)
            ->inDateRange($startDate, $endDate)
            ->pluck('date')
            ->toArray();

        $availableDates = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dayOfWeek = $current->format('l');
            $dateString = $current->format('Y-m-d');

            // Check if service is available on this day
            if ($zone->isServiceAvailableOnDay($dayOfWeek) && !in_array($dateString, $holidays)) {
                $availableDates[] = [
                    'date' => $dateString,
                    'day' => $dayOfWeek,
                    'formatted' => $current->format('M d, Y'),
                ];
            }

            $current->addDay();
        }

        return [
            'success' => true,
            'zip_code' => $zipCode,
            'area' => $zone->area,
            'month' => $startDate->format('Y-m'),
            'dates' => $availableDates,
        ];
    }

    /**
     * Get all active zones (admin).
     */
    public function getAllZones(): Collection
    {
        return PickupZone::orderBy('state')->orderBy('city')->orderBy('zip_code')->get();
    }

    /**
     * Create a new zone.
     */
    public function createZone(array $data): PickupZone
    {
        return PickupZone::create($data);
    }

    /**
     * Update a zone.
     */
    public function updateZone(PickupZone $zone, array $data): bool
    {
        return $zone->update($data);
    }

    /**
     * Delete a zone.
     */
    public function deleteZone(PickupZone $zone): bool
    {
        return $zone->delete();
    }
}
