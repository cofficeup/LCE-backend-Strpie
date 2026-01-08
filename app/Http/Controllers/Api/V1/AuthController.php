<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'      => 'required|email|unique:lce_user_info,email',
            'password'   => 'required|string|min:8|confirmed',
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone_1'    => 'nullable|string|max:20',
            'zip'        => 'nullable|string|max:10',
        ]);

        $user = User::create([
            'email'                   => $validated['email'],
            'user_md'                 => md5($validated['password']), // Legacy MD5 hash
            'first_name'              => $validated['first_name'],
            'last_name'               => $validated['last_name'],
            'phone_1'                 => $validated['phone_1'] ?? null,
            'zip'                     => $validated['zip'] ?? null,
            'customer_type'           => 'residential',
            'country'                 => 'US',
            'wash_fold_instructions'  => '',
            'custom_minimum_charge'   => 0,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'data' => [
                'user' => $this->formatUser($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }


    /**
     * Login and issue token.
     *
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Check MD5 hash (legacy format)
        if (!$user || $user->user_md !== md5($validated['password'])) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke all previous tokens (prevents multiple active sessions)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => $this->formatUser($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Logout and revoke current token.
     *
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get authenticated user info.
     *
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->formatUser($request->user()),
        ]);
    }

    /**
     * Format user data for API response.
     */
    private function formatUser(User $user): array
    {
        return [
            'id'            => $user->id,
            'email'         => $user->email,
            'first_name'    => $user->first_name,
            'last_name'     => $user->last_name,
            'phone'         => $user->phone_1,
            'zip'           => $user->zip,
            'customer_type' => $user->customer_type,
        ];
    }
}
