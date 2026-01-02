<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Pickup\HolidayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HolidayController extends Controller
{
    protected $holidayService;

    public function __construct(HolidayService $holidayService)
    {
        $this->holidayService = $holidayService;
    }

    /**
     * List all holidays.
     */
    public function index(): JsonResponse
    {
        $holidays = $this->holidayService->getAllHolidays();
        return response()->json(['data' => $holidays]);
    }

    /**
     * Create a new holiday.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'holiday_date' => 'required|date',
            'holiday_name' => 'required|string|max:100',
            'area_code' => 'nullable|string|max:10',
            'active' => 'boolean',
        ]);

        $holiday = $this->holidayService->createHoliday($validated);

        return response()->json([
            'message' => 'Holiday created successfully',
            'data' => $holiday
        ], 201);
    }

    /**
     * Update a holiday.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $holiday = \App\Models\PickupHoliday::findOrFail($id);

        $validated = $request->validate([
            'holiday_date' => 'sometimes|date',
            'holiday_name' => 'sometimes|string|max:100',
            'area_code' => 'nullable|string|max:10',
            'active' => 'boolean',
        ]);

        $this->holidayService->updateHoliday($holiday, $validated);

        return response()->json([
            'message' => 'Holiday updated successfully',
            'data' => $holiday->fresh()
        ]);
    }

    /**
     * Delete a holiday.
     */
    public function destroy($id): JsonResponse
    {
        $holiday = \App\Models\PickupHoliday::findOrFail($id);
        $this->holidayService->deleteHoliday($holiday);

        return response()->json(['message' => 'Holiday deleted successfully']);
    }
}
