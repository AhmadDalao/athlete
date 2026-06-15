<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiAbilityCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing(['roles', 'socialAccounts']);

        $currentToken = $user->currentAccessToken();

        return response()->json([
            'data' => [
                'viewer' => $this->viewerPayload($user),
                'token' => $currentToken ? [
                    'id' => $currentToken->id,
                    'name' => $currentToken->name,
                    'abilities' => $currentToken->abilities,
                    'lastUsedAt' => $currentToken->last_used_at?->toIso8601String(),
                    'expiresAt' => $currentToken->expires_at?->toIso8601String(),
                ] : null,
                'availableAbilities' => ApiAbilityCatalog::payloadFor($user),
                'socialAccounts' => $user->socialAccounts
                    ->map(fn ($account): array => [
                        'provider' => $account->provider,
                        'providerEmail' => $account->provider_email,
                        'providerAvatar' => $account->provider_avatar,
                    ])
                    ->values()
                    ->all(),
            ],
            'meta' => $this->metaPayload(),
        ]);
    }
}
