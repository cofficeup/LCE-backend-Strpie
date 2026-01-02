<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ImpersonationController extends Controller
{
    /**
     * Impersonate a user (Admin only).
     *
     * POST /api/v1/admin/users/{id}/impersonate
     */
    public function impersonate(Request $request, int $id): JsonResponse
    {
        $userToImpersonate = User::findOrFail($id);

        // Prevent impersonating other admins for security
        if ($userToImpersonate->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot impersonate another administrator.',
            ], 403);
        }

        // Generate a new token for this user
        $token = $userToImpersonate->createToken('impersonation_token', ['*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => "Impersonating user {$userToImpersonate->email}",
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $userToImpersonate->id,
                    'email' => $userToImpersonate->email,
                    'first_name' => $userToImpersonate->first_name,
                    'last_name' => $userToImpersonate->last_name,
                    'roles' => $userToImpersonate->roles->pluck('name'),
                ],
                'is_impersonating' => true,
            ],
        ]);
    }
}
