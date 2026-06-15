<?php

namespace App\Http\Controllers\Api;

use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\DeviceConnection;
use App\Models\User;
use App\Services\MetricAnalyticsService;
use App\Services\Whoop\WhoopApiClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WearableIndexController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(Request $request, MetricAnalyticsService $metricAnalytics, WhoopApiClient $whoopClient): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('roles');
        $statusFilter = $request->string('status')->value();
        $allowedStatuses = collect(DeviceConnectionStatus::cases())->map->value->all();
        $normalizedStatusFilter = in_array($statusFilter, $allowedStatuses, true) ? $statusFilter : null;

        $baseQuery = $this->visibleConnectionsQuery($user);
        $connections = (clone $baseQuery)
            ->with(['user.roles', 'latestSnapshot'])
            ->when($normalizedStatusFilter, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByRaw(
                "case status
                    when 'attention' then 0
                    when 'disconnected' then 1
                    when 'connected' then 2
                    else 3
                end"
            )
            ->orderByDesc('last_synced_at')
            ->paginate(12)
            ->withQueryString()
            ->through(function (DeviceConnection $connection) use ($metricAnalytics, $user): array {
                return [
                    'id' => $connection->id,
                    'publicId' => $connection->public_id,
                    'provider' => $connection->provider->value,
                    'providerLabel' => $connection->provider->label(),
                    'status' => $connection->status->value,
                    'user' => [
                        'id' => $connection->user->id,
                        'name' => $connection->user->name,
                        'email' => $connection->user->email,
                        'role' => $connection->user->primaryRoleName(),
                    ],
                    'authType' => $connection->auth_type,
                    'grantedScopes' => $connection->granted_scopes ?? [],
                    'lastSyncedAt' => $connection->last_synced_at?->toDateTimeString(),
                    'ingest' => $this->canManageConnection($user, $connection) && ! $connection->usesOauth() ? [
                        'lastFour' => $connection->ingest_key_last_four,
                        'path' => "/api/device-connections/{$connection->public_id}/ingest",
                    ] : null,
                    'latestSnapshot' => $this->snapshotPayload($connection->latestSnapshot),
                    'analytics' => $metricAnalytics->forConnection($connection),
                ];
            });

        return response()->json([
            'data' => [
                'viewerRole' => $user->primaryRoleName(),
                'filters' => [
                    'status' => $normalizedStatusFilter,
                ],
                'summary' => [
                    'totalConnections' => (clone $baseQuery)->count(),
                    'healthyConnections' => (clone $baseQuery)->where('status', DeviceConnectionStatus::Connected->value)->count(),
                    'attentionRequired' => (clone $baseQuery)->where('status', DeviceConnectionStatus::Attention->value)->count(),
                    'syncedToday' => (clone $baseQuery)->where('last_synced_at', '>=', now()->startOfDay())->count(),
                    'averageReadiness' => $this->averageReadiness($baseQuery),
                ],
                'connections' => $this->paginationPayload($connections),
                'whoopIntegration' => [
                    'oauthReady' => $whoopClient->isConfigured(),
                    'connectUrl' => route('wearables.whoop.connect'),
                    'scopes' => $whoopClient->scopes(),
                    'lookbackDays' => (int) config('throughline.integrations.whoop.lookback_days', 10),
                    'capabilities' => config('throughline.integrations.whoop.capabilities', []),
                    'connectedCount' => DeviceConnection::query()
                        ->where('provider', DeviceProvider::Whoop->value)
                        ->count(),
                    'syncCommand' => 'php artisan throughline:whoop:sync',
                ],
            ],
            'meta' => $this->metaPayload(),
        ]);
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

    private function canManageConnection(User $viewer, DeviceConnection $connection): bool
    {
        return $viewer->hasRole(RoleName::Admin) || $viewer->id === $connection->user_id;
    }

    private function averageReadiness(Builder $baseQuery): ?float
    {
        $connectionIds = (clone $baseQuery)->pluck('id');

        if ($connectionIds->isEmpty()) {
            return null;
        }

        $latestScores = DeviceConnection::query()
            ->whereIn('id', $connectionIds)
            ->with('latestSnapshot')
            ->get()
            ->pluck('latestSnapshot')
            ->filter()
            ->pluck('readiness_score')
            ->filter(fn ($score) => $score !== null);

        if ($latestScores->isEmpty()) {
            return null;
        }

        return round($latestScores->avg(), 1);
    }
}
