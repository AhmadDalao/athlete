<?php

namespace Tests\Feature;

use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Models\DeviceConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceMetricIngestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_metric_ingest_creates_raw_ingest_and_snapshot(): void
    {
        $user = User::factory()->create();
        $connection = DeviceConnection::query()->create([
            'user_id' => $user->id,
            'provider' => DeviceProvider::Garmin,
            'status' => DeviceConnectionStatus::Attention,
            'external_user_id' => 'garmin-athlete-test',
            'ingest_key' => 'thl_test_ingest_1234567890',
            'granted_scopes' => ['sleep', 'hrv', 'activity'],
            'last_synced_at' => null,
        ]);

        $this->postJson(
            route('api.device-connections.ingest', $connection),
            [
                'metric_date' => '2026-06-10',
                'external_event_id' => 'garmin-event-1',
                'metrics' => [
                    'readiness_score' => 87,
                    'strain_score' => 12.4,
                    'sleep_minutes' => 438,
                    'sleep_need_minutes' => 485,
                    'sleep_performance_percentage' => 94.5,
                    'sleep_consistency_percentage' => 88.2,
                    'sleep_efficiency_percentage' => 91.7,
                    'rem_sleep_minutes' => 92,
                    'slow_wave_sleep_minutes' => 84,
                    'steps' => 11340,
                    'resting_heart_rate' => 47,
                    'heart_rate_variability' => 74.8,
                    'respiratory_rate' => 15.6,
                    'blood_oxygen_percent' => 96.4,
                    'skin_temperature_celsius' => 33.4,
                    'training_load' => 421.6,
                ],
                'raw_payload' => [
                    'provider' => 'garmin',
                    'source' => 'test-suite',
                ],
            ],
            [
                'X-Throughline-Key' => 'thl_test_ingest_1234567890',
            ],
        )
            ->assertAccepted()
            ->assertJsonPath('device_connection.public_id', $connection->public_id)
            ->assertJsonPath('snapshot.metric_date', '2026-06-10')
            ->assertJsonPath('snapshot.readiness_score', 87)
            ->assertJsonPath('snapshot.sleep_performance_percentage', 94.5);

        $this->assertDatabaseHas('device_metric_ingests', [
            'device_connection_id' => $connection->id,
            'external_event_id' => 'garmin-event-1',
            'processing_status' => 'processed',
        ]);

        $this->assertDatabaseHas('metric_snapshots', [
            'device_connection_id' => $connection->id,
            'user_id' => $user->id,
            'provider' => DeviceProvider::Garmin->value,
            'sleep_minutes' => 438,
            'sleep_need_minutes' => 485,
            'steps' => 11340,
        ]);

        $connection->refresh();

        $this->assertSame(DeviceConnectionStatus::Connected, $connection->status);
        $this->assertNotNull($connection->last_synced_at);
    }

    public function test_device_metric_ingest_rejects_invalid_key(): void
    {
        $user = User::factory()->create();
        $connection = DeviceConnection::query()->create([
            'user_id' => $user->id,
            'provider' => DeviceProvider::Whoop,
            'status' => DeviceConnectionStatus::Connected,
            'external_user_id' => 'whoop-athlete-test',
            'ingest_key' => 'thl_valid_key_1234567890',
            'granted_scopes' => ['recovery', 'strain'],
            'last_synced_at' => null,
        ]);

        $this->postJson(
            route('api.device-connections.ingest', $connection),
            [
                'metric_date' => '2026-06-10',
                'metrics' => [
                    'readiness_score' => 80,
                ],
            ],
            [
                'X-Throughline-Key' => 'thl_wrong_key',
            ],
        )
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid ingest key.');

        $this->assertDatabaseCount('device_metric_ingests', 0);
        $this->assertDatabaseCount('metric_snapshots', 0);
    }
}
