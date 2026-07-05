<?php

namespace App\Http\Controllers\Api;

use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\DeviceConnection;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileWearableLinkController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('roles');

        abort_unless($user->hasRole(RoleName::Athlete), 403);

        $providerValues = [
            DeviceProvider::AppleHealth->value,
            DeviceProvider::HealthConnect->value,
        ];

        $validated = $request->validate([
            'provider' => ['required', 'string', Rule::in($providerValues)],
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:80'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', 'max:120'],
            'permission_status' => ['nullable', 'string', Rule::in(['granted', 'partial', 'denied'])],
        ]);

        $provider = DeviceProvider::from($validated['provider']);
        $permissionStatus = $validated['permission_status'] ?? 'granted';

        $connection = DeviceConnection::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider->value,
            ],
            [
                'status' => $permissionStatus === 'denied'
                    ? DeviceConnectionStatus::Attention->value
                    : DeviceConnectionStatus::Connected->value,
                'auth_type' => 'mobile',
                'external_user_id' => $validated['device_id'] ?? null,
                'granted_scopes' => $validated['scopes'] ?? [],
                'provider_account_payload' => [
                    'device_name' => $validated['device_name'] ?? null,
                    'platform' => $validated['platform'] ?? null,
                    'permission_status' => $permissionStatus,
                    'source' => 'native_mobile',
                ],
                'last_error_at' => $permissionStatus === 'denied' ? now() : null,
                'last_error_message' => $permissionStatus === 'denied'
                    ? 'Mobile health permissions were denied.'
                    : null,
            ],
        );

        return response()->json([
            'data' => [
                'connection' => [
                    'id' => $connection->id,
                    'publicId' => $connection->public_id,
                    'provider' => $connection->provider->value,
                    'providerLabel' => $connection->provider->label(),
                    'status' => $connection->status->value,
                    'authType' => $connection->auth_type,
                    'grantedScopes' => $connection->granted_scopes ?? [],
                    'lastSyncedAt' => $connection->last_synced_at?->toIso8601String(),
                ],
            ],
            'meta' => $this->metaPayload(),
        ], 202);
    }
}
