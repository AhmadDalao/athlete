<?php

namespace Tests\Feature;

use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Models\CoachAthleteAssignment;
use App\Models\DeviceConnection;
use App\Models\MetricSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WearableIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_only_sees_assigned_athlete_connections_and_not_ingest_keys(): void
    {
        $coach = User::factory()->create();
        $athlete = User::factory()->create();
        $otherAthlete = User::factory()->create();

        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);
        $otherAthlete->assignRole(RoleName::Athlete);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Strength block',
            'notes' => null,
            'started_at' => now()->subDays(12)->toDateString(),
            'ended_at' => null,
        ]);

        $visibleConnection = $this->connectionWithSnapshot($athlete, DeviceProvider::Garmin, 'thl_visible_key_1111');
        $this->connectionWithSnapshot($otherAthlete, DeviceProvider::Whoop, 'thl_hidden_key_2222');

        $this->actingAs($coach)
            ->get('/wearables')
            ->assertInertia(fn (Assert $page) => $page
                ->component('wearables/index')
                ->where('viewerRole', RoleName::Coach->value)
                ->where('summary.totalConnections', 1)
                ->where('connections.total', 1)
                ->where('connections.data.0.userName', $athlete->name)
                ->where('connections.data.0.provider', $visibleConnection->provider->value)
                ->where('connections.data.0.ingest', null)
                ->where('connections.data.0.review.severity', 'stable')
            );
    }

    public function test_athlete_sees_their_ingest_credentials_and_latest_snapshot(): void
    {
        $athlete = User::factory()->create();
        $athlete->assignRole(RoleName::Athlete);

        $connection = $this->connectionWithSnapshot($athlete, DeviceProvider::Strava, 'thl_athlete_key_3333');

        $this->actingAs($athlete)
            ->get('/wearables')
            ->assertInertia(fn (Assert $page) => $page
                ->component('wearables/index')
                ->where('viewerRole', RoleName::Athlete->value)
                ->where('summary.totalConnections', 1)
                ->where('whoopIntegration.connectedCount', 0)
                ->where('whoopIntegration.webhookReady', false)
                ->where('connections.data.0.publicId', $connection->public_id)
                ->where('connections.data.0.ingest.key', 'thl_athlete_key_3333')
                ->where('connections.data.0.latestSnapshot.readinessScore', 83)
                ->where('connections.data.0.latestSnapshot.sleepHours', 7.4)
                ->where('connections.data.0.latestSnapshot.sleepPerformancePercentage', 95)
                ->where('connections.data.0.analytics.overview.daysTracked', 1)
            );
    }

    public function test_admin_review_queue_exposes_sync_failure_context(): void
    {
        $admin = User::factory()->create();
        $athlete = User::factory()->create();

        $admin->assignRole(RoleName::Admin);
        $athlete->assignRole(RoleName::Athlete);

        DeviceConnection::query()->create([
            'user_id' => $athlete->id,
            'provider' => DeviceProvider::Whoop,
            'status' => DeviceConnectionStatus::Attention,
            'auth_type' => 'oauth',
            'external_user_id' => 'whoop-review-1',
            'access_token' => 'review-token',
            'refresh_token' => 'review-refresh-token',
            'token_expires_at' => now()->addHour(),
            'last_synced_at' => now()->subHours(30),
            'last_error_at' => now()->subHours(2),
            'last_error_message' => 'WHOOP returned 401 on refresh.',
            'sync_failures_count' => 2,
        ]);

        $this->actingAs($admin)
            ->get('/wearables')
            ->assertInertia(fn (Assert $page) => $page
                ->component('wearables/index')
                ->where('reviewQueue.0.severity', 'high')
                ->where('reviewQueue.0.issue', 'Recent sync failed.')
                ->where('reviewQueue.0.syncFailuresCount', 2)
                ->where('connections.data.0.review.lastErrorMessage', 'WHOOP returned 401 on refresh.')
            );
    }

    private function connectionWithSnapshot(User $user, DeviceProvider $provider, string $ingestKey): DeviceConnection
    {
        $connection = DeviceConnection::query()->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'status' => DeviceConnectionStatus::Connected,
            'external_user_id' => "{$provider->value}-{$user->id}",
            'ingest_key' => $ingestKey,
            'granted_scopes' => ['sleep', 'hrv'],
            'last_synced_at' => now()->subHour(),
        ]);

        MetricSnapshot::query()->create([
            'user_id' => $user->id,
            'device_connection_id' => $connection->id,
            'provider' => $provider,
            'metric_date' => now()->toDateString(),
            'readiness_score' => 83,
            'strain_score' => 12.2,
            'sleep_minutes' => 444,
            'sleep_need_minutes' => 480,
            'sleep_performance_percentage' => 95,
            'sleep_consistency_percentage' => 88,
            'sleep_efficiency_percentage' => 92,
            'rem_sleep_minutes' => 95,
            'slow_wave_sleep_minutes' => 82,
            'steps' => 10400,
            'distance_meters' => 7800,
            'calories_burned' => 2010,
            'active_minutes' => 68,
            'resting_heart_rate' => 49,
            'heart_rate_variability' => 69.5,
            'respiratory_rate' => 15.4,
            'blood_oxygen_percent' => 96.2,
            'skin_temperature_celsius' => 33.5,
            'training_load' => 415.4,
        ]);

        return $connection;
    }
}
