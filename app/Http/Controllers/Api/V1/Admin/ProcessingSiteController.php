<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcessingSite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProcessingSiteController extends Controller
{
    /**
     * List all processing sites.
     */
    public function index(): JsonResponse
    {
        return response()->json(['data' => ProcessingSite::all()]);
    }

    /**
     * Create a new processing site.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:processing_sites,code',
            'wash_fold_enabled' => 'boolean',
            'dry_clean_enabled' => 'boolean',
            'daily_capacity_lbs' => 'integer|min:0',
            'address_line1' => 'required|string|max:100',
            'city' => 'required|string|max:50',
            'state' => 'required|string|max:2',
            'zip_code' => 'required|string|max:10',
            'served_area_codes' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $site = ProcessingSite::create($validated);
        return response()->json(['data' => $site], 201);
    }

    /**
     * Show a processing site.
     */
    public function show($id): JsonResponse
    {
        $site = ProcessingSite::findOrFail($id);
        return response()->json(['data' => $site]);
    }

    /**
     * Update a processing site.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $site = ProcessingSite::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'code' => 'sometimes|string|max:20|unique:processing_sites,code,' . $id,
            'wash_fold_enabled' => 'boolean',
            'dry_clean_enabled' => 'boolean',
            'daily_capacity_lbs' => 'integer|min:0',
            'address_line1' => 'sometimes|string|max:100',
            'city' => 'sometimes|string|max:50',
            'state' => 'sometimes|string|max:2',
            'zip_code' => 'sometimes|string|max:10',
            'served_area_codes' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $site->update($validated);
        return response()->json(['data' => $site]);
    }

    /**
     * Delete a processing site.
     */
    public function destroy($id): JsonResponse
    {
        $site = ProcessingSite::findOrFail($id);
        $site->delete();
        return response()->json(['message' => 'Processing site deleted']);
    }
}
