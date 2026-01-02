<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminCustomerController extends Controller
{
    /**
     * Search customers.
     *
     * GET /api/v1/admin/customers
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with(['activeSubscription.plan', 'roles']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Additional filters can be added here (zip, etc.)

        $customers = $query->paginate(20);

        return response()->json(['success' => true, 'data' => $customers]);
    }

    /**
     * Get customer details.
     *
     * GET /api/v1/admin/customers/{id}
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with([
            'subscriptions',
            'activeSubscription.plan',
            'pickups' => function($q) { $q->latest()->take(5); },
            'invoices' => function($q) { $q->latest()->take(5); },
            'credits',
            'roles'
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $user]);
    }
}
