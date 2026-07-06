<?php

namespace App\Http\Controllers\Api;

use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\DeviceConnection;
use App\Models\User;
use App\Services\DeviceMetricIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileWearableSyncController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(Request $request, DeviceMetricIngestionService $ingestionService): JsonResponse
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
            'records' => ['required', 'array', 'min:1', 'max:45'],
            'records.*.metric_date' => ['required', 'date'],
            'records.*.external_event_id' => ['nullable', 'string', 'max:255'],
            'records.*.metrics' => ['required', 'array', 'min:1'],
            'records.*.metrics.readiness_score' => ['nullable', 'numeric', 'between:0,100'],
            'records.*.metrics.strain_score' => ['nullable', 'numeric', 'between:0,100'],
            'records.*.metrics.sleep_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'records.*.metrics.sleep_need_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'records.*.metrics.sleep_performance_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'records.*.metrics.sleep_consistency_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'records.*.metrics.sleep_efficiency_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'records.*.metrics.rem_sleep_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'records.*.metrics.slow_wave_sleep_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'records.*.metrics.steps' => ['nullable', 'integer', 'min:0'],
            'records.*.metrics.distance_meters' => ['nullable', 'integer', 'min:0'],
            'records.*.metrics.calories_burned' => ['nullable', 'integer', 'min:0'],
            'records.*.metrics.active_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'records.*.metrics.resting_heart_rate' => ['nullable', 'integer', 'min:20', 'max:250'],
            'records.*.metrics.heart_rate_variability' => ['nullable', 'numeric', 'min:0'],
            'records.*.metrics.respiratory_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'records.*.metrics.blood_oxygen_percent' => ['nullable', 'numeric', 'between:0,100'],
            'records.*.metrics.skin_temperature_celsius' => ['nullable', 'numeric', 'between:20,45'],
            'records.*.metrics.training_load' => ['nullable', 'numeric', 'min:0'],
            'records.*.raw_payload' => ['nullable', 'array'],
        ]);

        $provider = DeviceProvider::from($validated['provider']);

        $connection = DeviceConnection::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider->value,
            ],
            [
                'status' => DeviceConnectionStatus::Connected->value,
                'auth_type' => 'mobile',
                'external_user_id' => $validated['device_id'] ?? null,
                'granted_scopes' => $validated['scopes'] ?? [],
                'provider_account_payload' => [
                    'device_name' => $validated['device_name'] ?? null,
                    'platform' => $validated['platform'] ?? null,
                    'source' => 'native_mobile',
                ],
                'last_sync_started_at' => now(),
                'last_error_at' => null,
                'last_error_message' => null,
            ],
        );

        $snapshots = [];

        foreach ($validated['records'] as $record) {
            $externalEventId = $record['external_event_id']
                ?? "mobile-{$provider->value}-{$user->id}-{$record['metric_date']}";

            $result = $ingestionService->ingest($connection, [
                'metric_date' => $record['metric_date'],
                'external_event_id' => $externalEventId,
                'metrics' => $record['metrics'],
                'raw_payload' => $record['raw_payload'] ?? [
                    'provider' => $provider->value,
                    'device_id' => $validated['device_id'] ?? null,
                    'metric_date' => $record['metric_date'],
                    'metrics' => $record['metrics'],
                ],
            ]);

            $snapshots[] = $this->snapshotPayload($result['snapshot']);
        }

        $connection->refresh();
        $latestSnapshot = collect($snapshots)
            ->filter(fn ($snapshot): bool => is_array($snapshot))
            ->sortByDesc(fn (array $snapshot): string => (string) ($snapshot['metricDate'] ?? ''))
            ->first();
        $recordCounts = collect($validated['records'])
            ->map(fn (array $record): array => $record['raw_payload']['record_counts'] ?? [])
            ->filter()
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'connection' => [
                    'id' => $connection->id,
                    'publicId' => $connection->public_id,
                    'provider' => $connection->provider->value,
                    'providerLabel' => $connection->provider->label(),
                    'status' => $connection->status->value,
                    'authType' => $connection->auth_type,
                    'lastSyncedAt' => $connection->last_synced_at?->toIso8601String(),
                ],
                'receivedRecordCount' => count($validated['records']),
                'acceptedCount' => count($snapshots),
                'acceptedMetricDates' => collect($snapshots)
                    ->pluck('metricDate')
                    ->filter()
                    ->values()
                    ->all(),
                'latestSnapshot' => $latestSnapshot,
                'recordCounts' => $recordCounts,
                'syncMessage' => count($snapshots) > 0
                    ? 'Mobile health records were synced.'
                    : 'No mobile health records were accepted.',
                'snapshots' => $snapshots,
            ],
            'meta' => $this->metaPayload(),
        ], 202);
    }
}
