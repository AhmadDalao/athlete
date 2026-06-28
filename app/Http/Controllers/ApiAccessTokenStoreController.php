<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PlatformAuditLogger;
use App\Support\ApiAbilityCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApiAccessTokenStoreController extends Controller
{
    public function __invoke(Request $request, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('roles');
        $allowedAbilities = ApiAbilityCatalog::allowedFor($user);
        $validated = $request->validate([
            'token_name' => ['required', 'string', 'max:80'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string'],
        ]);

        $requestedAbilities = $validated['abilities'] ?? [];
        $abilities = $requestedAbilities !== [] ? array_values($requestedAbilities) : $allowedAbilities;
        $disallowedAbilities = collect($abilities)
            ->reject(fn (string $ability) => in_array($ability, $allowedAbilities, true))
            ->values();

        if ($disallowedAbilities->isNotEmpty()) {
            return back()
                ->withErrors(['abilities' => 'One or more requested abilities are not allowed for this user.'])
                ->withInput();
        }

        $expiresAt = now()->addDays((int) config('throughline.api.token_ttl_days', 30));
        $token = $user->createToken(
            $validated['token_name'],
            $abilities,
            $expiresAt,
        );

        $auditLogger->record(
            $request,
            'api_token.created',
            $user,
            "{$user->name} created API token {$validated['token_name']}.",
            [
                'token_name' => $validated['token_name'],
                'abilities' => $abilities,
                'expires_at' => $expiresAt->toDateTimeString(),
            ],
        );

        return to_route('api-access.index')
            ->with('success', 'New API token created. Copy it now because the plain text value will not show up again.')
            ->with('generatedApiToken', [
                'token' => $token->plainTextToken,
                'tokenName' => $validated['token_name'],
                'abilities' => $abilities,
                'expiresAt' => $expiresAt->toDateTimeString(),
            ]);
    }
}
