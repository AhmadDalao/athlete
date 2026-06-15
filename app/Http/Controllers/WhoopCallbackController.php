<?php

namespace App\Http\Controllers;

use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Models\DeviceConnection;
use App\Services\Whoop\WhoopApiClient;
use App\Services\Whoop\WhoopSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WhoopCallbackController extends Controller
{
    public function __invoke(Request $request, WhoopApiClient $client, WhoopSyncService $syncService): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $state = $request->string('state')->toString();
        $expectedState = (string) $request->session()->pull('throughline.whoop.oauth_state');

        abort_unless($state !== '' && hash_equals($expectedState, $state), 403, 'WHOOP OAuth state mismatch.');

        $code = $request->string('code')->toString();
        abort_unless($code !== '', 422, 'WHOOP OAuth code is missing.');

        $tokens = $client->exchangeAuthorizationCode($code);
        $accessToken = (string) ($tokens['access_token'] ?? '');
        abort_unless($accessToken !== '', 502, 'WHOOP did not return an access token.');

        $profile = $client->fetchBasicProfile($accessToken);
        $grantedScopes = collect(explode(' ', (string) ($tokens['scope'] ?? '')))
            ->map(fn (string $scope): string => trim($scope))
            ->filter()
            ->values()
            ->all();

        $providerPayload = [
            'profile' => $profile,
        ];

        if (in_array('read:body_measurement', $grantedScopes, true)) {
            $providerPayload['body_measurement'] = $client->fetchBodyMeasurements($accessToken);
        }

        $connection = DeviceConnection::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'provider' => DeviceProvider::Whoop->value,
            ],
            [
                'status' => DeviceConnectionStatus::Connected,
                'auth_type' => 'oauth',
                'external_user_id' => ($profile['user_id'] ?? $profile['id'] ?? null) ? (string) ($profile['user_id'] ?? $profile['id']) : null,
                'access_token' => $accessToken,
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => isset($tokens['expires_in']) ? now()->addSeconds((int) $tokens['expires_in']) : null,
                'granted_scopes' => $grantedScopes,
                'provider_account_payload' => $providerPayload,
                'last_synced_at' => now(),
            ],
        );

        try {
            $syncService->syncConnection($connection);
        } catch (\Throwable $exception) {
            report($exception);

            $connection->forceFill([
                'status' => DeviceConnectionStatus::Attention,
            ])->save();
        }

        return redirect()->route('wearables.index');
    }
}
