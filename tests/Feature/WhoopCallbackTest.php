<?php

namespace Tests\Feature;

use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Models\DeviceConnection;
use App\Models\MetricSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhoopCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_whoop_callback_creates_oauth_connection_and_syncs_metrics(): void
    {
        config()->set('services.whoop.client_id', 'whoop-client-id');
        config()->set('services.whoop.client_secret', 'whoop-client-secret');

        Http::preventStrayRequests();
        Http::fake([
            'https://api.prod.whoop.com/oauth/oauth2/token' => Http::response([
                'access_token' => 'whoop-access-token',
                'refresh_token' => 'whoop-refresh-token',
                'expires_in' => 3600,
                'scope' => 'offline read:profile read:recovery read:sleep read:cycles read:workout read:body_measurement',
            ]),
            'https://api.prod.whoop.com/developer/v2/user/profile/basic' => Http::response([
                'user_id' => 884422,
                'email' => 'athlete@whoop.test',
                'first_name' => 'Lina',
                'last_name' => 'Brooks',
            ]),
            'https://api.prod.whoop.com/developer/v2/user/measurement/body' => Http::response([
                'height_meter' => 1.7,
                'weight_kilogram' => 61.5,
            ]),
            'https://api.prod.whoop.com/developer/v2/cycle*' => Http::response([
                'records' => [
                    [
                        'id' => 93845,
                        'end' => '2026-06-13T06:00:00Z',
                        'score_state' => 'SCORED',
                        'score' => [
                            'strain' => 13.2,
                            'kilojoule' => 8200,
                        ],
                    ],
                ],
            ]),
            'https://api.prod.whoop.com/developer/v2/recovery*' => Http::response([
                'records' => [
                    [
                        'created_at' => '2026-06-13T07:00:00Z',
                        'score' => [
                            'recovery_score' => 82,
                            'resting_heart_rate' => 48,
                            'hrv_rmssd_milli' => 78.5,
                            'spo2_percentage' => 96.1,
                            'skin_temp_celsius' => 33.2,
                        ],
                    ],
                ],
            ]),
            'https://api.prod.whoop.com/developer/v2/activity/sleep*' => Http::response([
                'records' => [
                    [
                        'end' => '2026-06-13T06:00:00Z',
                        'score' => [
                            'stage_summary' => [
                                'total_light_sleep_time_milli' => 14400000,
                                'total_slow_wave_sleep_time_milli' => 5400000,
                                'total_rem_sleep_time_milli' => 4500000,
                            ],
                            'sleep_needed' => [
                                'baseline_milli' => 28800000,
                                'need_from_sleep_debt_milli' => 900000,
                                'need_from_recent_strain_milli' => 600000,
                                'need_from_recent_nap_milli' => 0,
                            ],
                            'respiratory_rate' => 15.8,
                            'sleep_performance_percentage' => 96,
                            'sleep_consistency_percentage' => 88,
                            'sleep_efficiency_percentage' => 91.2,
                        ],
                    ],
                ],
            ]),
            'https://api.prod.whoop.com/developer/v2/activity/workout*' => Http::response([
                'records' => [
                    [
                        'start' => '2026-06-13T15:00:00Z',
                        'end' => '2026-06-13T16:00:00Z',
                        'score' => [
                            'distance_meter' => 6500,
                            'kilojoule' => 1200,
                        ],
                    ],
                ],
            ]),
        ]);

        $athlete = User::factory()->create();
        $athlete->assignRole(RoleName::Athlete);

        $response = $this->actingAs($athlete)
            ->withSession(['throughline.whoop.oauth_state' => 'state-123'])
            ->get(route('wearables.whoop.callback', [
                'state' => 'state-123',
                'code' => 'oauth-code-123',
            ]));

        $response->assertRedirect(route('wearables.index', absolute: false));

        $connection = DeviceConnection::query()
            ->where('user_id', $athlete->id)
            ->where('provider', DeviceProvider::Whoop->value)
            ->firstOrFail();

        $this->assertSame('oauth', $connection->auth_type);
        $this->assertSame('884422', $connection->external_user_id);
        $this->assertSame('whoop-refresh-token', $connection->refresh_token);

        $snapshot = MetricSnapshot::query()
            ->where('device_connection_id', $connection->id)
            ->firstOrFail();

        $this->assertSame(82.0, $snapshot->readiness_score);
        $this->assertSame(13.2, $snapshot->strain_score);
        $this->assertSame(405, $snapshot->sleep_minutes);
        $this->assertSame(505, $snapshot->sleep_need_minutes);
        $this->assertSame(6500, $snapshot->distance_meters);
        $this->assertSame(60, $snapshot->active_minutes);
        $this->assertSame(15.8, $snapshot->respiratory_rate);
    }
}
