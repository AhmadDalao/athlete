<?php

namespace App\Http\Controllers;

use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Models\DeviceConnection;
use App\Models\User;
use App\Models\WhoopWebhookEvent;
use App\Services\DeviceConnectionHealthReviewService;
use App\Services\MetricAnalyticsService;
use App\Services\Whoop\WhoopApiClient;
use App\Services\Whoop\WhoopWebhookService;
use App\Support\TablePageSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WearableIndexController extends Controller
{
    public function __invoke(
        Request $request,
        MetricAnalyticsService $metricAnalytics,
        WhoopApiClient $whoopClient,
        DeviceConnectionHealthReviewService $healthReview,
        WhoopWebhookService $whoopWebhooks,
    ): Response
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('roles');
        $statusFilter = $request->string('status')->value();
        $allowedStatuses = collect(DeviceConnectionStatus::cases())->map->value->all();
        $normalizedStatusFilter = in_array($statusFilter, $allowedStatuses, true) ? $statusFilter : null;

        $baseQuery = $this->visibleConnectionsQuery($user);
        $tableQuery = (clone $baseQuery)
            ->with(['user.roles', 'latestSnapshot'])
            ->when($normalizedStatusFilter, fn (Builder $query, string $status) => $query->where('status', $status));

        $connections = $tableQuery
            ->orderByRaw(
                "case status
                    when 'attention' then 0
                    when 'disconnected' then 1
                    when 'connected' then 2
                    else 3
                end"
            )
            ->orderByDesc('last_synced_at')
            ->paginate(TablePageSize::resolve($request, $tableQuery))
            ->withQueryString()
            ->through(fn (DeviceConnection $connection): array => [
                'id' => $connection->id,
                'publicId' => $connection->public_id,
                'provider' => $connection->provider->value,
                'providerLabel' => $connection->provider->label(),
                'status' => $connection->status->value,
                'userName' => $connection->user->name,
                'userEmail' => $connection->user->email,
                'userRole' => $connection->user->primaryRoleName(),
                'authType' => $connection->auth_type,
                'grantedScopes' => $connection->granted_scopes ?? [],
                'lastSyncedAt' => $connection->last_synced_at?->toDateTimeString(),
                'ingest' => $this->canManageConnection($user, $connection) && ! $connection->usesOauth() ? [
                    'key' => $connection->ingest_key,
                    'lastFour' => $connection->ingest_key_last_four,
                    'path' => "/api/device-connections/{$connection->public_id}/ingest",
                ] : null,
                'latestSnapshot' => $connection->latestSnapshot ? [
                    'metricDate' => $connection->latestSnapshot->metric_date?->toDateString(),
                    'readinessScore' => $connection->latestSnapshot->readiness_score,
                    'readinessBand' => $connection->latestSnapshot->readinessBand(),
                    'strainScore' => $connection->latestSnapshot->strain_score,
                    'sleepHours' => $connection->latestSnapshot->sleepHours(),
                    'sleepNeedHours' => $connection->latestSnapshot->sleepNeedHours(),
                    'sleepDebtHours' => $connection->latestSnapshot->sleepDebtHours(),
                    'sleepPerformancePercentage' => $connection->latestSnapshot->sleep_performance_percentage,
                    'sleepConsistencyPercentage' => $connection->latestSnapshot->sleep_consistency_percentage,
                    'steps' => $connection->latestSnapshot->steps,
                    'trainingLoad' => $connection->latestSnapshot->training_load,
                    'restingHeartRate' => $connection->latestSnapshot->resting_heart_rate,
                    'heartRateVariability' => $connection->latestSnapshot->heart_rate_variability,
                    'respiratoryRate' => $connection->latestSnapshot->respiratory_rate,
                    'bloodOxygenPercent' => $connection->latestSnapshot->blood_oxygen_percent,
                    'skinTemperatureCelsius' => $connection->latestSnapshot->skin_temperature_celsius,
                ] : null,
                'analytics' => $metricAnalytics->forConnection($connection),
                'review' => $healthReview->review($connection),
            ]);

        $reviewQueue = (clone $baseQuery)
            ->with(['user.roles', 'latestSnapshot'])
            ->get()
            ->map(function (DeviceConnection $connection) use ($healthReview): array {
                $review = $healthReview->review($connection);

                return [
                    'id' => $connection->id,
                    'userName' => $connection->user->name,
                    'providerLabel' => $connection->provider->label(),
                    'status' => $connection->status->value,
                    ...$review,
                ];
            })
            ->filter(fn (array $entry) => $entry['severity'] !== 'stable')
            ->sortByDesc(fn (array $entry) => [
                match ($entry['severity']) {
                    'high' => 3,
                    'medium' => 2,
                    'low' => 1,
                    default => 0,
                },
                $entry['syncFailuresCount'],
                $entry['staleHours'] ?? 0,
            ])
            ->values()
            ->take(6)
            ->all();

        return Inertia::render('wearables/index', [
            'viewerRole' => $user->primaryRoleName(),
            'scopeLabel' => $this->scopeLabel($user),
            'filters' => [
                'status' => $normalizedStatusFilter,
                'per_page' => TablePageSize::queryValue($request),
            ],
            'summary' => [
                'totalConnections' => (clone $baseQuery)->count(),
                'healthyConnections' => (clone $baseQuery)->where('status', DeviceConnectionStatus::Connected->value)->count(),
                'attentionRequired' => (clone $baseQuery)->where('status', DeviceConnectionStatus::Attention->value)->count(),
                'syncedToday' => (clone $baseQuery)->where('last_synced_at', '>=', now()->startOfDay())->count(),
                'averageReadiness' => $this->averageReadiness($baseQuery),
            ],
            'connections' => $connections,
            'reviewQueue' => $reviewQueue,
            'whoopIntegration' => [
                'oauthReady' => $whoopClient->isConfigured(),
                'connectUrl' => route('wearables.whoop.connect'),
                'scopes' => $whoopClient->scopes(),
                'lookbackDays' => (int) config('throughline.integrations.whoop.lookback_days', 10),
                'capabilities' => config('throughline.integrations.whoop.capabilities', []),
                'connectedCount' => DeviceConnection::query()
                    ->where('provider', DeviceProvider::Whoop->value)
                    ->count(),
                'failingCount' => DeviceConnection::query()
                    ->where('provider', DeviceProvider::Whoop->value)
                    ->where(function (Builder $query): void {
                        $query
                            ->whereNotNull('last_error_at')
                            ->orWhereIn('status', [DeviceConnectionStatus::Attention->value, DeviceConnectionStatus::Disconnected->value]);
                    })
                    ->count(),
                'webhookReady' => $whoopWebhooks->webhookEnabled(),
                'webhookUrl' => route('wearables.whoop.webhook', absolute: true),
                'pendingEvents' => WhoopWebhookEvent::query()
                    ->where('processing_status', 'pending')
                    ->count(),
                'failedEvents' => WhoopWebhookEvent::query()
                    ->where('processing_status', 'failed')
                    ->count(),
                'lastReceivedAt' => WhoopWebhookEvent::query()->max('received_at'),
                'syncCommand' => 'php artisan throughline:whoop:sync',
                'webhookProcessCommand' => 'php artisan throughline:whoop:webhooks:process',
            ],
            'sampleCurl' => 'curl -X POST "$APP_URL/api/device-connections/{public_id}/ingest" -H "X-Throughline-Key: {ingest_key}" -H "Content-Type: application/json" -d \'{"metric_date":"2026-06-10","metrics":{"readiness_score":82,"sleep_minutes":445,"strain_score":13.4}}\'',
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

    private function scopeLabel(User $user): string
    {
        if ($user->hasRole(RoleName::Admin)) {
            return 'All platform device connections and normalized recovery snapshots';
        }

        if ($user->hasRole(RoleName::Coach)) {
            return 'Wearable coverage and latest recovery data across your roster';
        }

        return 'Your connected devices, ingest credentials, and latest recovery metrics';
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
