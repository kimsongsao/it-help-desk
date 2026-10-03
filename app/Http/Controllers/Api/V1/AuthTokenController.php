<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthTokenController extends Controller
{
    /**
     * Exchange credentials for a personal API token.
     */
    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::firstWhere('email', $credentials['email']);

        if (! $user || ! $user->is_active || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if (! $user->hasPermission(Permission::ApiAccess)) {
            abort(Response::HTTP_FORBIDDEN, __('Your role does not allow API access.'));
        }

        return response()->json([
            'token' => $user->createToken($credentials['device_name'])->plainTextToken,
            'token_type' => 'Bearer',
        ], Response::HTTP_CREATED);
    }

    /**
     * Get the authenticated user.
     */
    public function show(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    /**
     * Revoke the token used for the current request.
     */
    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
