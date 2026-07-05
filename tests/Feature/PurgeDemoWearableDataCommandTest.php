<?php

namespace Tests\Feature;

use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Models\AthleteCheckIn;
use App\Models\DeviceConnection;
use App\Models\DeviceMetricIngest;
use App\Models\MetricSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeDemoWearableDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_wearable_purge_preserves_live_mobile_and_real_whoop_data(): void
    {
        $demoAthlete = User::factory()->create(['email' => 'athlete1@throughline.test']);
        $demoAthlete->assignRole(RoleName::Athlete);
        $realAthlete = User::factory()->create(['email' => 'real-athlete@example.com']);
        $realAthlete->assignRole(RoleName::Athlete);

        $garmin = $this->connection($demoAthlete, DeviceProvider::Garmin, 'garmin-athlete-001');
        $seededWhoop = $this->connection($demoAthlete, DeviceProvider::Whoop, 'whoop-athlete-002', 'oauth');
        $healthConnect = $this->connection($realAthlete, DeviceProvider::HealthConnect, 'galaxy-watch', 'mobile');
        $realWhoop = $this->connection($realAthlete, DeviceProvider::Whoop, 'real-whoop-user', 'oauth', 'encrypted-token-placeholder');

        $this->snapshot($garmin, $demoAthlete, 8000);
        $this->snapshot($seededWhoop, $demoAthlete, 7000);
        $this->snapshot($healthConnect, $realAthlete, 9021);
        $this->snapshot($realWhoop, $realAthlete, 6500);

        AthleteCheckIn::query()->create([
            'user_id' => $demoAthlete->id,
            'logged_date' => now()->toDateString(),
            'weight_kg' => 82.2,
        ]);

        $this->artisan('throughline:wearables:purge-demo-data', ['--include-check-ins' => true])
            ->expectsOutput('Mode: dry run')
            ->expectsOutput('Demo device connections: 2')
            ->expectsOutput('Demo metric ingests: 2')
            ->expectsOutput('Demo metric snapshots: 2')
            ->expectsOutput('Demo manual check-ins: 1')
            ->assertExitCode(0);

        $this->assertDatabaseCount('device_connections', 4);
        $this->assertDatabaseCount('metric_snapshots', 4);
        $this->assertDatabaseCount('device_metric_ingests', 4);
        $this->assertDatabaseCount('athlete_check_ins', 1);

        $this->artisan('throughline:wearables:purge-demo-data', [
            '--apply' => true,
            '--include-check-ins' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('device_connections', ['id' => $garmin->id]);
        $this->assertDatabaseMissing('device_connections', ['id' => $seededWhoop->id]);
        $this->assertDatabaseHas('device_connections', ['id' => $healthConnect->id]);
        $this->assertDatabaseHas('device_connections', ['id' => $realWhoop->id]);
        $this->assertDatabaseCount('metric_snapshots', 2);
        $this->assertDatabaseCount('device_metric_ingests', 2);
        $this->assertDatabaseCount('athlete_check_ins', 0);
    }

    private function connection(
        User $user,
        DeviceProvider $provider,
        string $externalUserId,
        string $authType = 'ingest_key',
        ?string $accessToken = null,
    ): DeviceConnection {
        return DeviceConnection::query()->create([
            'user_id' => $user->id,
            'provider' => $provider->value,
            'status' => DeviceConnectionStatus::Connected->value,
            'auth_type' => $authType,
            'external_user_id' => $externalUserId,
            'access_token' => $accessToken,
            'granted_scopes' => ['sleep', 'activity'],
            'last_synced_at' => now(),
        ]);
    }

    private function snapshot(DeviceConnection $connection, User $user, int $steps): void
    {
        DeviceMetricIngest::query()->create([
            'device_connection_id' => $connection->id,
            'metric_date' => now()->toDateString(),
            'external_event_id' => "test-{$connection->id}",
            'payload' => [
                'metrics' => ['steps' => $steps],
            ],
            'processing_status' => 'processed',
            'received_at' => now(),
            'processed_at' => now(),
        ]);

        MetricSnapshot::query()->create([
            'user_id' => $user->id,
            'device_connection_id' => $connection->id,
            'provider' => $connection->provider->value,
            'metric_date' => now()->toDateString(),
            'steps' => $steps,
        ]);
    }
}
