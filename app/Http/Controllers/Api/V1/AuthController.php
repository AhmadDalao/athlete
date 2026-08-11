<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', (string) $request->string('email'))->first();

        if (! $user || $user->status !== 'active' || ! Hash::check((string) $request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match an active account.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken((string) $request->string('device_name'), ['mobile'])->plainTextToken;

        AuditLog::query()->create([
            'organization_id' => $user->current_organization_id,
            'user_id' => $user->id,
            'action' => 'api.login',
            'entity' => 'user',
            'entity_id' => $user->id,
            'summary' => "{$user->name} signed in from a mobile client.",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => UserResource::make($user),
                'organizations' => OrganizationResource::collection($user->organizations()->wherePivot('status', 'active')->get()),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => UserResource::make($request->user()),
                'organizations' => OrganizationResource::collection($request->user()->organizations()->wherePivot('status', 'active')->get()),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['data' => ['logged_out' => true]]);
    }

    public function revokeAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['data' => ['revoked' => true]]);
    }
}
