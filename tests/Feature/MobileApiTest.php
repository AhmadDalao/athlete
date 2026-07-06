<?php

namespace Tests\Feature;

use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Models\CoachAthleteAssignment;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_athlete_can_fetch_mobile_home_and_calendar(): void
    {
        [$coach, $athlete] = $this->coachAthletePair();
        $program = $this->programFor($coach, $athlete);
        $todaySession = $this->sessionFor($program, now()->toDateString(), 'Lower body session');
        $this->sessionFor($program, now()->addDay()->toDateString(), 'Recovery run');

        Sanctum::actingAs($athlete, ['training:read']);

        $this->getJson(route('api.v1.app.home'))
            ->assertOk()
            ->assertJsonPath('data.role', RoleName::Athlete->value)
            ->assertJsonPath('data.programs.0.title', 'Return to Performance')
            ->assertJsonPath('data.todaySessions.0.title', $todaySession->title);

        $this->getJson(route('api.v1.app.calendar', [
            'month' => now()->format('Y-m'),
            'date' => now()->toDateString(),
        ]))
            ->assertOk()
            ->assertJsonPath('data.selectedDate', now()->toDateString())
            ->assertJsonPath('data.selectedDaySessions.0.title', 'Lower body session');
    }

    public function test_coach_mobile_home_is_scoped_to_owned_programs(): void
    {
        [$coach, $athlete] = $this->coachAthletePair('coach-one@example.com', 'athlete-one@example.com');
        [$otherCoach, $otherAthlete] = $this->coachAthletePair('coach-two@example.com', 'athlete-two@example.com');

        $this->programFor($coach, $athlete, 'Coach One Program');
        $this->programFor($otherCoach, $otherAthlete, 'Other Coach Program');

        Sanctum::actingAs($coach, ['training:read']);

        $this->getJson(route('api.v1.app.home'))
            ->assertOk()
            ->assertJsonPath('data.role', RoleName::Coach->value)
            ->assertJsonPath('data.programs.0.title', 'Coach One Program')
            ->assertJsonMissing(['title' => 'Other Coach Program'])
            ->assertJsonMissing(['email' => 'athlete-two@example.com']);
    }

    public function test_mobile_messages_follow_assignment_scope(): void
    {
        [$coach, $athlete, $assignment] = $this->coachAthletePair();
        $otherAthlete = User::factory()->create(['email' => 'other-athlete@example.com']);
        $otherAthlete->assignRole(RoleName::Athlete);

        Sanctum::actingAs($athlete, ['messages:read', 'messages:write']);

        $this->postJson(route('api.v1.messages.store'), [
            'assignment_id' => $assignment->id,
            'body' => 'Coach, I finished the first block.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.message.body', 'Coach, I finished the first block.')
            ->assertJsonPath('data.thread.assignmentId', $assignment->id);

        $this->assertDatabaseHas('coach_athlete_messages', [
            'coach_athlete_assignment_id' => $assignment->id,
            'sender_id' => $athlete->id,
            'recipient_id' => $coach->id,
        ]);

        Sanctum::actingAs($coach, ['messages:read']);

        $this->getJson(route('api.v1.messages.index'))
            ->assertOk()
            ->assertJsonPath('data.threads.0.messages.0.body', 'Coach, I finished the first block.');

        Sanctum::actingAs($otherAthlete, ['messages:write']);

        $this->postJson(route('api.v1.messages.store'), [
            'assignment_id' => $assignment->id,
            'body' => 'Trying to enter another thread.',
        ])->assertForbidden();
    }

    public function test_athlete_mobile_sync_creates_health_connect_connection_and_snapshot(): void
    {
        $athlete = User::factory()->create(['email' => 'mobile-sync@example.com']);
        $athlete->assignRole(RoleName::Athlete);

        Sanctum::actingAs($athlete, ['wearable:read', 'wearable:write']);

        $this->postJson(route('api.v1.wearables.mobile-sync'), [
            'provider' => DeviceProvider::HealthConnect->value,
            'device_id' => 'galaxy-watch-test',
            'device_name' => 'Galaxy Watch',
            'platform' => 'android',
            'scopes' => ['steps', 'sleep', 'heart_rate'],
            'records' => [
                [
                    'metric_date' => now()->toDateString(),
                    'raw_payload' => [
                        'record_counts' => [
                            'steps' => 1,
                            'sleep' => 1,
                            'resting_heart_rate' => 1,
                        ],
                    ],
                    'metrics' => [
                        'steps' => 9021,
                        'calories_burned' => 2410,
                        'sleep_minutes' => 418,
                        'resting_heart_rate' => 51,
                        'heart_rate_variability' => 64.5,
                    ],
                ],
            ],
        ])
            ->assertAccepted()
            ->assertJsonPath('data.connection.provider', DeviceProvider::HealthConnect->value)
            ->assertJsonPath('data.acceptedCount', 1)
            ->assertJsonPath('data.receivedRecordCount', 1)
            ->assertJsonPath('data.latestSnapshot.steps', 9021)
            ->assertJsonPath('data.recordCounts.0.steps', 1)
            ->assertJsonPath('data.snapshots.0.steps', 9021);

        $this->getJson(route('api.v1.wearables'))
            ->assertOk()
            ->assertJsonPath('data.latestSnapshot.steps', 9021)
            ->assertJsonPath('data.syncState.hasLiveData', true)
            ->assertJsonPath('data.providerStatus.health_connect.linked', true)
            ->assertJsonPath('data.providerStatus.health_connect.latestSnapshot.steps', 9021);

        $this->assertDatabaseHas('device_connections', [
            'user_id' => $athlete->id,
            'provider' => DeviceProvider::HealthConnect->value,
            'auth_type' => 'mobile',
        ]);

        $this->assertDatabaseHas('metric_snapshots', [
            'user_id' => $athlete->id,
            'provider' => DeviceProvider::HealthConnect->value,
            'steps' => 9021,
        ]);
    }

    public function test_athlete_can_link_health_connect_before_records_are_available(): void
    {
        $athlete = User::factory()->create(['email' => 'mobile-link@example.com']);
        $athlete->assignRole(RoleName::Athlete);

        Sanctum::actingAs($athlete, ['wearable:write']);

        $this->postJson(route('api.v1.wearables.mobile-link'), [
            'provider' => DeviceProvider::HealthConnect->value,
            'device_id' => 'galaxy-watch-link-test',
            'device_name' => 'Samsung Health via Health Connect',
            'platform' => 'android',
            'permission_status' => 'granted',
            'scopes' => ['Steps', 'SleepSession', 'HeartRate'],
        ])
            ->assertAccepted()
            ->assertJsonPath('data.connection.provider', DeviceProvider::HealthConnect->value)
            ->assertJsonPath('data.connection.status', 'connected')
            ->assertJsonPath('data.connection.authType', 'mobile');

        $this->assertDatabaseHas('device_connections', [
            'user_id' => $athlete->id,
            'provider' => DeviceProvider::HealthConnect->value,
            'auth_type' => 'mobile',
            'status' => 'connected',
        ]);
    }

    public function test_admin_cannot_use_mobile_app_home(): void
    {
        $admin = User::factory()->create(['email' => 'admin-mobile-blocked@example.com']);
        $admin->assignRole(RoleName::Admin);

        Sanctum::actingAs($admin, ['training:read']);

        $this->getJson(route('api.v1.app.home'))->assertForbidden();
    }

    /**
     * @return array{0: User, 1: User, 2: CoachAthleteAssignment}
     */
    private function coachAthletePair(string $coachEmail = 'coach@example.com', string $athleteEmail = 'athlete@example.com'): array
    {
        $coach = User::factory()->create(['email' => $coachEmail]);
        $coach->assignRole(RoleName::Coach);

        $athlete = User::factory()->create(['email' => $athleteEmail]);
        $athlete->assignRole(RoleName::Athlete);

        $assignment = CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Build repeatable training.',
            'started_at' => now()->subDays(7)->toDateString(),
        ]);

        return [$coach, $athlete, $assignment];
    }

    private function programFor(User $coach, User $athlete, string $title = 'Return to Performance'): TrainingProgram
    {
        return TrainingProgram::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => $title,
            'goal' => 'Strength and return-to-play capacity.',
            'status' => TrainingProgramStatus::Active,
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => now()->addWeeks(5)->toDateString(),
        ]);
    }

    private function sessionFor(TrainingProgram $program, string $date, string $title): TrainingSession
    {
        return TrainingSession::query()->create([
            'training_program_id' => $program->id,
            'title' => $title,
            'scheduled_date' => $date,
            'focus' => 'Strength',
            'instructions' => 'Keep two reps in reserve.',
            'video_url' => 'https://example.com/workout-video.mp4',
            'exercises' => [
                [
                    'name' => 'Trap bar deadlift',
                    'sets' => 4,
                    'reps' => '5',
                    'load' => 'RPE 7',
                    'rest_seconds' => 90,
                ],
            ],
            'sort_order' => 1,
        ]);
    }
}
