<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RecurringSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class RecurringScheduleController extends Controller
{
    /**
     * List user's recurring schedules.
     */
    public function index(Request $request): JsonResponse
    {
        $schedules = RecurringSchedule::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $schedules]);
    }

    /**
     * Create a recurring schedule.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_type' => 'required|in:ppo,subscription',
            'frequency' => 'in:weekly,bi_weekly',
            'schedule_monday' => 'boolean',
            'schedule_tuesday' => 'boolean',
            'schedule_wednesday' => 'boolean',
            'schedule_thursday' => 'boolean',
            'schedule_friday' => 'boolean',
            'schedule_saturday' => 'boolean',
            'schedule_sunday' => 'boolean',
            'default_bags' => 'nullable|integer|min:1',
            'default_weight' => 'nullable|numeric|min:0',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = $request->user()->id;

        $schedule = RecurringSchedule::create($validated);

        return response()->json(['data' => $schedule], 201);
    }

    /**
     * Update a recurring schedule.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $schedule = RecurringSchedule::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'order_type' => 'sometimes|in:ppo,subscription',
            'frequency' => 'in:weekly,bi_weekly',
            'schedule_monday' => 'boolean',
            'schedule_tuesday' => 'boolean',
            'schedule_wednesday' => 'boolean',
            'schedule_thursday' => 'boolean',
            'schedule_friday' => 'boolean',
            'schedule_saturday' => 'boolean',
            'schedule_sunday' => 'boolean',
            'default_bags' => 'nullable|integer|min:1',
            'default_weight' => 'nullable|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $schedule->update($validated);

        return response()->json(['data' => $schedule]);
    }

    /**
     * Delete a recurring schedule.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $schedule = RecurringSchedule::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $schedule->delete();

        return response()->json(['message' => 'Schedule deleted']);
    }
}
