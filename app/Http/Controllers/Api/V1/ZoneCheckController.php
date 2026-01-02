<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Pickup\ZoneService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ZoneCheckController extends Controller
{
    protected $zoneService;

    public function __construct(ZoneService $zoneService)
    {
        $this->zoneService = $zoneService;
    }

    /**
     * Check if a zip code is serviceable and return available dates.
     * 
     * GET /api/v1/zones/check?zip=94065&month=2026-01
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'zip' => 'required|string|max:10',
            'month' => 'nullable|date_format:Y-m',
        ]);

        $result = $this->zoneService->getAvailableDates(
            $request->zip,
            $request->month
        );

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }
}
