<?php

namespace Tests\Feature;

use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Models\DeviceConnection;
use App\Models\MetricSnapshot;
use App\Models\User;
use App\Models\WhoopWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhoopWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_whoop_webhook_is_accepted_and_queued(): void
    {
        config()->set('throughline.integrations.whoop.webhook_secret', 'whoop-webhook-secret');

        $payload = json_encode([
            'user_id' => 884422,
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'type' => 'sleep.updated',
            'trace_id' => 'trace-1',
        ], JSON_THROW_ON_ERROR);

        $timestamp = (string) now()->valueOf();

        $response = $this->call(
            'POST',
            route('wearables.whoop.webhook', absolute: false),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WHOOP_SIGNATURE_TIMESTAMP' => $timestamp,
                'HTTP_X_WHOOP_SIGNATURE' => $this->signatureFor($timestamp, $payload),
            ],
            $payload,
        );

        $response
            ->assertOk()
            ->assertJson([
                'received' => true,
                'queued' => true,
                'duplicate' => false,
            ]);

        $this->assertDatabaseHas('whoop_webhook_events', [
            'trace_id' => 'trace-1',
            'whoop_user_id' => '884422',
            'resource_id' => '550e8400-e29b-41d4-a716-446655440000',
            'event_type' => 'sleep.updated',
            'processing_status' => 'pending',
        ]);
    }

    public function test_duplicate_whoop_webhook_trace_ids_are_not_stored_twice(): void
    {
        config()->set('throughline.integrations.whoop.webhook_secret', 'whoop-webhook-secret');

        $payload = json_encode([
            'user_id' => 884422,
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'type' => 'sleep.updated',
            'trace_id' => 'trace-duplicate',
        ], JSON_THROW_ON_ERROR);

        $timestamp = (string) now()->valueOf();
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WHOOP_SIGNATURE_TIMESTAMP' => $timestamp,
            'HTTP_X_WHOOP_SIGNATURE' => $this->signatureFor($timestamp, $payload),
        ];

        $this->call('POST', route('wearables.whoop.webhook', absolute: false), [], [], [], $server, $payload)
            ->assertOk();

        $this->call('POST', route('wearables.whoop.webhook', absolute: false), [], [], [], $server, $payload)
            ->assertOk()
            ->assertJson([
                'received' => true,
                'queued' => false,
                'duplicate' => true,
            ]);

        $this->assertDatabaseCount('whoop_webhook_events', 1);
    }

    public function test_invalid_whoop_webhook_signature_is_rejected(): void
    {
        config()->set('throughline.integrations.whoop.webhook_secret', 'whoop-webhook-secret');

        $payload = json_encode([
            'user_id' => 884422,
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'type' => 'sleep.updated',
            'trace_id' => 'trace-invalid',
        ], JSON_THROW_ON_ERROR);

        $response = $this->call(
            'POST',
            route('wearables.whoop.webhook', absolute: false),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WHOOP_SIGNATURE_TIMESTAMP' => (string) now()->valueOf(),
                'HTTP_X_WHOOP_SIGNATURE' => 'definitely-not-valid',
            ],
            $payload,
        );

        $response->assertStatus(400);
        $this->assertDatabaseCount('whoop_webhook_events', 0);
    }

    public function test_whoop_webhook_processor_syncs_matching_connection(): void
    {
        config()->set('services.whoop.client_id', 'whoop-client-id');
        config()->set('services.whoop.client_secret', 'whoop-client-secret');
        config()->set('throughline.integrations.whoop.webhook_lookback_days', 14);

        Http::preventStrayRequests();
        Http::fake([
            'https://api.prod.whoop.com/developer/v2/cycle*' => Http::response([
                'records' => [
                    [
                        'id' => 'cycle-1',
                        'end' => '2026-06-13T06:00:00Z',
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
                        ],
                    ],
                ],
            ]),
        ]);

        $athlete = User::factory()->create();
        $athlete->assignRole(RoleName::Athlete);

        $connection = DeviceConnection::query()->create([
            'user_id' => $athlete->id,
            'provider' => DeviceProvider::Whoop,
            'status' => DeviceConnectionStatus::Attention,
            'auth_type' => 'oauth',
            'external_user_id' => '884422',
            'access_token' => 'whoop-access-token',
            'refresh_token' => 'whoop-refresh-token',
            'token_expires_at' => now()->addHour(),
        ]);

        WhoopWebhookEvent::query()->create([
            'device_connection_id' => $connection->id,
            'trace_id' => 'trace-process-1',
            'whoop_user_id' => '884422',
            'resource_id' => '550e8400-e29b-41d4-a716-446655440000',
            'event_type' => 'sleep.updated',
            'processing_status' => 'pending',
            'attempts' => 0,
            'received_at' => now(),
            'payload' => [
                'user_id' => 884422,
                'id' => '550e8400-e29b-41d4-a716-446655440000',
                'type' => 'sleep.updated',
                'trace_id' => 'trace-process-1',
            ],
        ]);

        $this->artisan('throughline:whoop:webhooks:process', ['--limit' => 10])
            ->assertExitCode(0);

        $event = WhoopWebhookEvent::query()->where('trace_id', 'trace-process-1')->firstOrFail();
        $snapshot = MetricSnapshot::query()
            ->where('device_connection_id', $connection->id)
            ->firstOrFail();

        $this->assertSame('processed', $event->processing_status);
        $this->assertNotNull($event->processed_at);
        $this->assertSame(1, $event->attempts);
        $this->assertSame(82.0, $snapshot->readiness_score);
        $this->assertSame(13.2, $snapshot->strain_score);
        $this->assertSame(405, $snapshot->sleep_minutes);
        $this->assertSame(6500, $snapshot->distance_meters);
    }

    private function signatureFor(string $timestamp, string $payload): string
    {
        return base64_encode(hash_hmac(
            'sha256',
            $timestamp.$payload,
            (string) config('throughline.integrations.whoop.webhook_secret'),
            true,
        ));
    }
}
