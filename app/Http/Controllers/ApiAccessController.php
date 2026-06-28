<?php

namespace App\Http\Controllers;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Models\DeviceConnection;
use App\Models\User;
use App\Support\ApiAbilityCatalog;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class ApiAccessController extends Controller
{
    public function __invoke(): Response
    {
        /** @var User $user */
        $user = request()->user()->loadMissing('roles');
        $apiBaseUrl = $this->apiBaseUrl();
        $managedConnections = $this->manageableConnectionsQuery($user)
            ->with('user.roles')
            ->orderBy('provider')
            ->get()
            ->map(fn (DeviceConnection $connection): array => [
                'id' => $connection->id,
                'publicId' => $connection->public_id,
                'provider' => $connection->provider->value,
                'providerLabel' => $connection->provider->label(),
                'ownerName' => $connection->user->name,
                'ownerRole' => $connection->user->primaryRoleName(),
                'status' => $connection->status->value,
                'authType' => $connection->auth_type,
                'lastSyncedAt' => $connection->last_synced_at?->toDateTimeString(),
                'grantedScopes' => $connection->granted_scopes ?? [],
                'ingest' => ! $connection->usesOauth() ? [
                    'key' => $connection->ingest_key,
                    'lastFour' => $connection->ingest_key_last_four,
                    'path' => "/api/device-connections/{$connection->public_id}/ingest",
                    'absoluteUrl' => url("/api/device-connections/{$connection->public_id}/ingest"),
                ] : null,
            ])
            ->values()
            ->all();

        return Inertia::render('api-access', [
            'viewer' => [
                'name' => $user->name,
                'email' => $user->email,
                'primaryRole' => $user->primaryRoleName(),
            ],
            'abilities' => ApiAbilityCatalog::payloadFor($user),
            'tokens' => $user->tokens()
                ->latest('id')
                ->get()
                ->map(fn ($token): array => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => is_array($token->abilities) ? $token->abilities : [],
                    'createdAt' => $token->created_at?->toDateTimeString(),
                    'lastUsedAt' => $token->last_used_at?->toDateTimeString(),
                    'expiresAt' => $token->expires_at?->toDateTimeString(),
                ])
                ->values()
                ->all(),
            'managedConnections' => $managedConnections,
            'generatedToken' => request()->session()->pull('generatedApiToken'),
            'api' => [
                'baseUrl' => $apiBaseUrl,
                'tokenIssueUrl' => route('api.v1.auth.tokens.store', absolute: true),
                'tokenTtlDays' => (int) config('throughline.api.token_ttl_days', 30),
                'billingWebhookUrl' => route('billing.webhooks.stripe', absolute: true),
                'sampleTokenCurl' => implode(" \\\n", [
                    'curl -X POST "'.route('api.v1.auth.tokens.store', absolute: true).'"',
                    '-H "Content-Type: application/json"',
                    '-d \'{',
                    '  "email": "'.$user->email.'",',
                    '  "password": "{YOUR_PASSWORD}",',
                    '  "token_name": "integration-script",',
                    '  "abilities": ["'.implode('", "', array_slice(ApiAbilityCatalog::allowedFor($user), 0, 2)).'"]',
                    '}\'',
                ]),
                'sampleDashboardCurl' => implode(" \\\n", [
                    'curl -X GET "'.$apiBaseUrl.'/dashboard"',
                    '-H "Authorization: Bearer {YOUR_TOKEN}"',
                    '-H "Accept: application/json"',
                ]),
                'sampleIngestCurl' => implode(" \\\n", [
                    'curl -X POST "'.url('/api/device-connections/{public_id}/ingest').'"',
                    '-H "X-Throughline-Key: {INGEST_KEY}"',
                    '-H "Content-Type: application/json"',
                    '-d \'{"metric_date":"2026-06-20","metrics":{"readiness_score":82,"sleep_minutes":445,"strain_score":13.4}}\'',
                ]),
            ],
        ]);
    }

    private function apiBaseUrl(): string
    {
        return rtrim(str_replace('/me', '', route('api.v1.me', absolute: true)), '/');
    }

    private function visibleConnectionsQuery(User $user): Builder
    {
        $query = DeviceConnection::query();

        if ($user->hasRole(RoleName::Admin)) {
            return $query;
        }

        if ($user->hasRole(RoleName::Coach)) {
            $athleteIds = $user->coachAssignments()
                ->where('status', CoachAthleteStatus::Active->value)
                ->pluck('athlete_id')
                ->push($user->id)
                ->unique()
                ->values();

            return $query->whereIn('user_id', $athleteIds);
        }

        return $query->where('user_id', $user->id);
    }

    private function manageableConnectionsQuery(User $user): Builder
    {
        if ($user->hasRole(RoleName::Admin)) {
            return DeviceConnection::query();
        }

        return $this->visibleConnectionsQuery($user)->where('user_id', $user->id);
    }
}
