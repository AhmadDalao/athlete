<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiTokenStoreRequest;
use App\Models\User;
use App\Support\ApiAbilityCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthTokenStoreController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(ApiTokenStoreRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = User::query()
            ->where('email', $request->validated('email'))
            ->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.'],
            ]);
        }

        $user->loadMissing('roles');

        $allowedAbilities = ApiAbilityCatalog::allowedFor($user);
        $requestedAbilities = $request->validated('abilities');
        $abilities = $requestedAbilities ?: $allowedAbilities;
        $disallowedAbilities = collect($abilities)
            ->reject(fn (string $ability) => in_array($ability, $allowedAbilities, true))
            ->values();

        if ($disallowedAbilities->isNotEmpty()) {
            throw ValidationException::withMessages([
                'abilities' => ['One or more requested abilities are not allowed for this user.'],
            ]);
        }

        $expiresAt = now()->addDays((int) config('throughline.api.token_ttl_days', 30));
        $token = $user->createToken(
            $request->validated('token_name'),
            array_values($abilities),
            $expiresAt,
        );

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'tokenType' => 'Bearer',
                'tokenName' => $request->validated('token_name'),
                'abilities' => array_values($abilities),
                'expiresAt' => $expiresAt->toIso8601String(),
                'user' => $this->viewerPayload($user),
            ],
            'meta' => $this->metaPayload(),
        ], 201);
    }
}
