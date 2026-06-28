<?php

namespace App\Services\Whoop;

use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Models\DeviceConnection;
use App\Services\DeviceMetricIngestionService;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class WhoopSyncService
{
    public function __construct(
        private readonly DeviceMetricIngestionService $ingestionService,
        private readonly WhoopApiClient $client,
    ) {}

    /**
     * @return array{processed:int,synced:int,failed:int,errors:list<string>}
     */
    public function syncEligibleConnections(?int $connectionId = null, ?int $lookbackDays = null): array
    {
        $connections = DeviceConnection::query()
            ->where('provider', DeviceProvider::Whoop->value)
            ->when($connectionId, fn ($query, int $id) => $query->whereKey($id))
            ->where(function ($query) {
                $query->whereNotNull('refresh_token')
                    ->orWhere(function ($nested) {
                        $nested->whereNotNull('access_token')
                            ->where('auth_type', 'oauth');
                    });
            })
            ->get();

        $summary = [
            'processed' => $connections->count(),
            'synced' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($connections as $connection) {
            try {
                $result = $this->syncConnection($connection, $lookbackDays);

                if ($result['snapshots_synced'] > 0) {
                    $summary['synced']++;
                }
            } catch (\Throwable $exception) {
                $summary['failed']++;
                $summary['errors'][] = sprintf('Connection %s failed: %s', $connection->public_id, $exception->getMessage());

                $connection->forceFill([
                    'status' => DeviceConnectionStatus::Attention,
                ])->save();
            }
        }

        return $summary;
    }

    /**
     * @return array{snapshots_synced:int,metric_dates:list<string>}
     */
    public function syncConnection(DeviceConnection $connection, ?int $lookbackDays = null): array
    {
        if ($connection->provider !== DeviceProvider::Whoop) {
            throw new InvalidArgumentException('WHOOP sync can only be used for WHOOP connections.');
        }

        $connection->forceFill([
            'last_sync_started_at' => now(),
        ])->save();

        try {
            $accessToken = $this->freshAccessToken($connection);
            $days = $lookbackDays ?? (int) config('throughline.integrations.whoop.lookback_days', 10);
            $start = now()->subDays(max($days - 1, 0))->startOfDay()->toIso8601String();
            $end = now()->endOfDay()->toIso8601String();

            $cycles = $this->client->fetchCycles($accessToken, $start, $end);
            $recoveries = $this->client->fetchRecoveries($accessToken, $start, $end);
            $sleepActivities = $this->client->fetchSleepActivities($accessToken, $start, $end);
            $workouts = $this->client->fetchWorkouts($accessToken, $start, $end);

            $dailyMetrics = [];

            foreach ($cycles as $cycle) {
                $metricDate = $this->metricDateFromPayload($cycle);

                if (! $metricDate) {
                    continue;
                }

                $score = $cycle['score'] ?? [];
                $dailyMetrics[$metricDate] ??= [];
                $dailyMetrics[$metricDate]['strain_score'] = $score['strain'] ?? ($dailyMetrics[$metricDate]['strain_score'] ?? null);
                $dailyMetrics[$metricDate]['training_load'] = $score['kilojoule'] ?? ($dailyMetrics[$metricDate]['training_load'] ?? null);
            }

            foreach ($recoveries as $recovery) {
                $metricDate = $this->metricDateFromPayload($recovery);

                if (! $metricDate) {
                    continue;
                }

                $score = $recovery['score'] ?? [];
                $dailyMetrics[$metricDate] ??= [];
                $dailyMetrics[$metricDate]['readiness_score'] = $score['recovery_score'] ?? ($dailyMetrics[$metricDate]['readiness_score'] ?? null);
                $dailyMetrics[$metricDate]['resting_heart_rate'] = $score['resting_heart_rate'] ?? ($dailyMetrics[$metricDate]['resting_heart_rate'] ?? null);
                $dailyMetrics[$metricDate]['heart_rate_variability'] = $score['hrv_rmssd_milli'] ?? ($dailyMetrics[$metricDate]['heart_rate_variability'] ?? null);
                $dailyMetrics[$metricDate]['blood_oxygen_percent'] = $score['spo2_percentage'] ?? ($dailyMetrics[$metricDate]['blood_oxygen_percent'] ?? null);
                $dailyMetrics[$metricDate]['skin_temperature_celsius'] = $score['skin_temp_celsius'] ?? ($dailyMetrics[$metricDate]['skin_temperature_celsius'] ?? null);
            }

            foreach ($sleepActivities as $sleep) {
                $metricDate = $this->metricDateFromPayload($sleep);

                if (! $metricDate) {
                    continue;
                }

                $score = $sleep['score'] ?? [];
                $stageSummary = $score['stage_summary'] ?? [];
                $sleepNeeded = $score['sleep_needed'] ?? [];
                $sleepMinutes = $this->millisecondsToMinutes(
                    ($stageSummary['total_light_sleep_time_milli'] ?? 0)
                    + ($stageSummary['total_slow_wave_sleep_time_milli'] ?? 0)
                    + ($stageSummary['total_rem_sleep_time_milli'] ?? 0),
                );

                $sleepNeedMinutes = $this->millisecondsToMinutes(
                    ($sleepNeeded['baseline_milli'] ?? 0)
                    + ($sleepNeeded['need_from_sleep_debt_milli'] ?? 0)
                    + ($sleepNeeded['need_from_recent_strain_milli'] ?? 0)
                    + ($sleepNeeded['need_from_recent_nap_milli'] ?? 0),
                );

                $dailyMetrics[$metricDate] ??= [];
                $dailyMetrics[$metricDate]['sleep_minutes'] = $sleepMinutes ?: ($dailyMetrics[$metricDate]['sleep_minutes'] ?? null);
                $dailyMetrics[$metricDate]['sleep_need_minutes'] = $sleepNeedMinutes ?: ($dailyMetrics[$metricDate]['sleep_need_minutes'] ?? null);
                $dailyMetrics[$metricDate]['rem_sleep_minutes'] = $this->millisecondsToMinutes($stageSummary['total_rem_sleep_time_milli'] ?? null);
                $dailyMetrics[$metricDate]['slow_wave_sleep_minutes'] = $this->millisecondsToMinutes($stageSummary['total_slow_wave_sleep_time_milli'] ?? null);
                $dailyMetrics[$metricDate]['sleep_performance_percentage'] = $score['sleep_performance_percentage'] ?? ($dailyMetrics[$metricDate]['sleep_performance_percentage'] ?? null);
                $dailyMetrics[$metricDate]['sleep_consistency_percentage'] = $score['sleep_consistency_percentage'] ?? ($dailyMetrics[$metricDate]['sleep_consistency_percentage'] ?? null);
                $dailyMetrics[$metricDate]['sleep_efficiency_percentage'] = $score['sleep_efficiency_percentage'] ?? ($dailyMetrics[$metricDate]['sleep_efficiency_percentage'] ?? null);
                $dailyMetrics[$metricDate]['respiratory_rate'] = $score['respiratory_rate'] ?? ($dailyMetrics[$metricDate]['respiratory_rate'] ?? null);
            }

            foreach ($workouts as $workout) {
                $metricDate = $this->metricDateFromPayload($workout);

                if (! $metricDate) {
                    continue;
                }

                $score = $workout['score'] ?? [];
                $activeMinutes = $this->durationMinutes($workout['start'] ?? null, $workout['end'] ?? null);

                $dailyMetrics[$metricDate] ??= [];
                $dailyMetrics[$metricDate]['distance_meters'] = ($dailyMetrics[$metricDate]['distance_meters'] ?? 0) + (int) round((float) ($score['distance_meter'] ?? 0));
                $dailyMetrics[$metricDate]['active_minutes'] = ($dailyMetrics[$metricDate]['active_minutes'] ?? 0) + ($activeMinutes ?? 0);
            }

            $metricDates = array_keys($dailyMetrics);

            foreach ($dailyMetrics as $metricDate => $metrics) {
                $this->ingestionService->ingest($connection, [
                    'metric_date' => $metricDate,
                    'external_event_id' => sprintf('whoop-%s-%s', $connection->external_user_id ?? $connection->user_id, $metricDate),
                    'metrics' => $metrics,
                    'raw_payload' => [
                        'provider' => 'whoop',
                        'metric_date' => $metricDate,
                        'collections' => [
                            'cycles' => array_values(array_filter($cycles, fn (array $cycle) => $this->metricDateFromPayload($cycle) === $metricDate)),
                            'recoveries' => array_values(array_filter($recoveries, fn (array $recovery) => $this->metricDateFromPayload($recovery) === $metricDate)),
                            'sleep' => array_values(array_filter($sleepActivities, fn (array $sleep) => $this->metricDateFromPayload($sleep) === $metricDate)),
                            'workouts' => array_values(array_filter($workouts, fn (array $workout) => $this->metricDateFromPayload($workout) === $metricDate)),
                        ],
                    ],
                ]);
            }

            $connection->forceFill([
                'status' => DeviceConnectionStatus::Connected,
                'last_synced_at' => now(),
                'last_sync_started_at' => null,
                'last_error_at' => null,
                'last_error_message' => null,
                'sync_failures_count' => 0,
            ])->save();

            return [
                'snapshots_synced' => count($metricDates),
                'metric_dates' => $metricDates,
            ];
        } catch (\Throwable $exception) {
            $this->markFailure($connection, $exception);

            throw $exception;
        }
    }

    private function freshAccessToken(DeviceConnection $connection): string
    {
        if ($connection->refresh_token && ($connection->tokenExpiresSoon() || ! $connection->access_token)) {
            $tokens = $this->client->refreshAccessToken((string) $connection->refresh_token);

            $connection->forceFill([
                'access_token' => $tokens['access_token'] ?? $connection->access_token,
                'refresh_token' => $tokens['refresh_token'] ?? $connection->refresh_token,
                'token_expires_at' => isset($tokens['expires_in']) ? now()->addSeconds((int) $tokens['expires_in']) : $connection->token_expires_at,
            ])->save();
        }

        if (! $connection->access_token) {
            throw new InvalidArgumentException('WHOOP access token is missing.');
        }

        return (string) $connection->access_token;
    }

    private function metricDateFromPayload(array $payload): ?string
    {
        $timestamp = $payload['end']
            ?? $payload['created_at']
            ?? $payload['updated_at']
            ?? $payload['start']
            ?? null;

        if (! is_string($timestamp) || trim($timestamp) === '') {
            return null;
        }

        return Carbon::parse($timestamp)->toDateString();
    }

    private function millisecondsToMinutes(float|int|null $milliseconds): ?int
    {
        if ($milliseconds === null || $milliseconds === 0) {
            return null;
        }

        return (int) round(((float) $milliseconds) / 60000);
    }

    private function durationMinutes(?string $start, ?string $end): ?int
    {
        if (! $start || ! $end) {
            return null;
        }

        return Carbon::parse($start)->diffInMinutes(Carbon::parse($end));
    }

    private function markFailure(DeviceConnection $connection, \Throwable $exception): void
    {
        $connection->forceFill([
            'status' => DeviceConnectionStatus::Attention,
            'last_sync_started_at' => null,
            'last_error_at' => now(),
            'last_error_message' => $exception->getMessage(),
            'sync_failures_count' => (int) $connection->sync_failures_count + 1,
        ])->save();
    }
}
