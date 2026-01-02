<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PromoCodeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => PromoCode::orderBy('created_at', 'desc')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|unique:promo_codes,code|alpha_dash|uppercase',
            'discount_type' => 'required|in:percentage,fixed_amount,free_shipping',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:start_date',
            'max_uses' => 'nullable|integer|min:1',
            'active' => 'boolean'
        ]);

        $promo = PromoCode::create($validated);
        return response()->json(['data' => $promo], 201);
    }

    public function destroy($id): JsonResponse
    {
        $promo = PromoCode::findOrFail($id);
        $promo->delete();
        return response()->json(['message' => 'Promo code deleted']);
    }
}
