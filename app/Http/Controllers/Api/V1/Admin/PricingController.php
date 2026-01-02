<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingItem;
use App\Models\PriceList;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PricingController extends Controller
{
    /**
     * List all pricing items (services).
     */
    public function items(): JsonResponse
    {
        return response()->json(['data' => PricingItem::orderBy('service_type')->get()]);
    }

    /**
     * Create a pricing item.
     */
    public function storeItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'required|unique:pricing_items,sku',
            'item_name' => 'required|string',
            'service_type' => 'required|in:wash_fold,dry_clean,household,other',
            'unit' => 'required|string',
            'active' => 'boolean'
        ]);

        $item = PricingItem::create($validated);
        return response()->json(['data' => $item], 201);
    }

    /**
     * List all price lists.
     */
    public function lists(): JsonResponse
    {
        return response()->json(['data' => PriceList::withCount('items')->get()]);
    }

    /**
     * Create a price list.
     */
    public function storeList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:residential,commercial',
            'zip_codes' => 'nullable|string',
            'active' => 'boolean'
        ]);

        $list = PriceList::create($validated);
        return response()->json(['data' => $list], 201);
    }

    /**
     * Update prices for items in a list.
     */
    public function updateListPrices(Request $request, $listId): JsonResponse
    {
        $list = PriceList::findOrFail($listId);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:pricing_items,id',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $syncData = [];
        foreach ($validated['items'] as $item) {
            $syncData[$item['item_id']] = ['price' => $item['price']];
        }

        $list->items()->syncWithoutDetaching($syncData);

        return response()->json(['message' => 'Prices updated successfully']);
    }
}
