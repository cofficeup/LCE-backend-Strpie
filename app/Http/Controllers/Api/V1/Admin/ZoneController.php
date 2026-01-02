<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Pickup\ZoneService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ZoneController extends Controller
{
    protected $zoneService;

    public function __construct(ZoneService $zoneService)
    {
        $this->zoneService = $zoneService;
    }

    /**
     * List all pickup zones.
     */
    public function index(): JsonResponse
    {
        $zones = $this->zoneService->getAllZones();
        return response()->json(['data' => $zones]);
    }

    /**
     * Create a new pickup zone.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zip_code' => 'required|string|max:10|unique:pickup_zones,zip_code',
            'city' => 'nullable|string|max:64',
            'state' => 'nullable|string|max:2',
            'service_monday' => 'boolean',
            'service_tuesday' => 'boolean',
            'service_wednesday' => 'boolean',
            'service_thursday' => 'boolean',
            'service_friday' => 'boolean',
            'service_saturday' => 'boolean',
            'service_sunday' => 'boolean',
            'area_code' => 'nullable|string|max:10',
            'active' => 'boolean',
            'display_order' => 'integer',
        ]);

        $zone = $this->zoneService->createZone($validated);

        return response()->json([
            'message' => 'Zone created successfully',
            'data' => $zone
        ], 201);
    }

    /**
     * Update a pickup zone.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $zone = $this->zoneService->getZoneByZip($request->zip_code)
            ?? \App\Models\PickupZone::findOrFail($id);

        $validated = $request->validate([
            'zip_code' => 'sometimes|string|max:10|unique:pickup_zones,zip_code,' . $zone->id,
            'city' => 'nullable|string|max:64',
            'state' => 'nullable|string|max:2',
            'service_monday' => 'boolean',
            'service_tuesday' => 'boolean',
            'service_wednesday' => 'boolean',
            'service_thursday' => 'boolean',
            'service_friday' => 'boolean',
            'service_saturday' => 'boolean',
            'service_sunday' => 'boolean',
            'area_code' => 'nullable|string|max:10',
            'active' => 'boolean',
            'display_order' => 'integer',
        ]);

        $this->zoneService->updateZone($zone, $validated);

        return response()->json([
            'message' => 'Zone updated successfully',
            'data' => $zone->fresh()
        ]);
    }

    /**
     * Delete a pickup zone.
     */
    public function destroy($id): JsonResponse
    {
        $zone = \App\Models\PickupZone::findOrFail($id);
        $this->zoneService->deleteZone($zone);

        return response()->json(['message' => 'Zone deleted successfully']);
    }
}
