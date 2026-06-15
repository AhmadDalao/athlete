<?php

namespace App\Services;

use App\Enums\DeviceConnectionStatus;
use App\Models\DeviceConnection;
use App\Models\DeviceMetricIngest;
use App\Models\MetricSnapshot;

class DeviceMetricIngestionService
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array{ingest: DeviceMetricIngest, snapshot: MetricSnapshot}
     */
    public function ingest(DeviceConnection $connection, array $validated): array
    {
        $payload = $validated['raw_payload'] ?? [
            'metric_date' => $validated['metric_date'],
            'metrics' => $validated['metrics'],
        ];

        $ingest = $validated['external_event_id'] ?? null
            ? DeviceMetricIngest::query()->updateOrCreate(
                [
                    'device_connection_id' => $connection->id,
                    'external_event_id' => $validated['external_event_id'],
                ],
                [
                    'metric_date' => $validated['metric_date'],
                    'payload' => $payload,
                    'processing_status' => 'processed',
                    'received_at' => now(),
                    'processed_at' => now(),
                ],
            )
            : DeviceMetricIngest::query()->create([
                'device_connection_id' => $connection->id,
                'metric_date' => $validated['metric_date'],
                'external_event_id' => null,
                'payload' => $payload,
                'processing_status' => 'processed',
                'received_at' => now(),
                'processed_at' => now(),
            ]);

        $metrics = $validated['metrics'];

        $snapshot = MetricSnapshot::query()->updateOrCreate(
            [
                'device_connection_id' => $connection->id,
                'metric_date' => $validated['metric_date'],
            ],
            [
                'user_id' => $connection->user_id,
                'provider' => $connection->provider->value,
                'readiness_score' => $metrics['readiness_score'] ?? null,
                'strain_score' => $metrics['strain_score'] ?? null,
                'sleep_minutes' => $metrics['sleep_minutes'] ?? null,
                'sleep_need_minutes' => $metrics['sleep_need_minutes'] ?? null,
                'sleep_performance_percentage' => $metrics['sleep_performance_percentage'] ?? null,
                'sleep_consistency_percentage' => $metrics['sleep_consistency_percentage'] ?? null,
                'sleep_efficiency_percentage' => $metrics['sleep_efficiency_percentage'] ?? null,
                'rem_sleep_minutes' => $metrics['rem_sleep_minutes'] ?? null,
                'slow_wave_sleep_minutes' => $metrics['slow_wave_sleep_minutes'] ?? null,
                'steps' => $metrics['steps'] ?? null,
                'distance_meters' => $metrics['distance_meters'] ?? null,
                'calories_burned' => $metrics['calories_burned'] ?? null,
                'active_minutes' => $metrics['active_minutes'] ?? null,
                'resting_heart_rate' => $metrics['resting_heart_rate'] ?? null,
                'heart_rate_variability' => $metrics['heart_rate_variability'] ?? null,
                'respiratory_rate' => $metrics['respiratory_rate'] ?? null,
                'blood_oxygen_percent' => $metrics['blood_oxygen_percent'] ?? null,
                'skin_temperature_celsius' => $metrics['skin_temperature_celsius'] ?? null,
                'training_load' => $metrics['training_load'] ?? null,
            ],
        );

        $connection->forceFill([
            'status' => DeviceConnectionStatus::Connected,
            'last_synced_at' => now(),
        ])->save();

        return [
            'ingest' => $ingest,
            'snapshot' => $snapshot,
        ];
    }
}
