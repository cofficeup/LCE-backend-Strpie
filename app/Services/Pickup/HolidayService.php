<?php

namespace App\Services\Pickup;

use App\Models\PickupHoliday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HolidayService
{
    /**
     * Check if a date is a holiday.
     */
    public function isHoliday($date, ?string $areaCode = null): bool
    {
        return PickupHoliday::active()
            ->forDate($date)
            ->forArea($areaCode)
            ->exists();
    }

    /**
     * Get holiday information for a specific date.
     */
    public function getHoliday($date, ?string $areaCode = null): ?PickupHoliday
    {
        return PickupHoliday::active()
            ->forDate($date)
            ->forArea($areaCode)
            ->first();
    }

    /**
     * Get all holidays in a date range.
     */
    public function getHolidaysInRange($startDate, $endDate, ?string $areaCode = null): Collection
    {
        return PickupHoliday::active()
            ->inDateRange($startDate, $endDate)
            ->forArea($areaCode)
            ->orderBy('holiday_date')
            ->get();
    }

    /**
     * Get upcoming holidays (next 90 days).
     */
    public function getUpcomingHolidays(?string $areaCode = null, int $days = 90): Collection
    {
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays($days);

        return $this->getHolidaysInRange($startDate, $endDate, $areaCode);
    }

    /**
     * Create a new holiday.
     */
    public function createHoliday(array $data): PickupHoliday
    {
        return PickupHoliday::create($data);
    }

    /**
     * Update a holiday.
     */
    public function updateHoliday(PickupHoliday $holiday, array $data): bool
    {
        return $holiday->update($data);
    }

    /**
     * Delete a holiday.
     */
    public function deleteHoliday(PickupHoliday $holiday): bool
    {
        return $holiday->delete();
    }

    /**
     * Get all holidays (admin).
     */
    public function getAllHolidays(): Collection
    {
        return PickupHoliday::orderBy('holiday_date', 'desc')->get();
    }
}
