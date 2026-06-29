<?php

namespace Tests\Feature;

use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Enums\WorkoutCompletionStatus;
use App\Models\AthleteCheckIn;
use App\Models\CoachAthleteAssignment;
use App\Models\DeviceConnection;
use App\Models\MetricSnapshot;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Models\WorkoutSetLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AthleteAppExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_athlete_can_open_app_and_see_today_workout_without_wearable(): void
    {
        [$athlete, $session] = $this->athleteWorkoutFixture();

        $this->actingAs($athlete)
            ->get('/app')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('athlete-app/index')
                ->where('viewer.email', $athlete->email)
                ->where('training.todaySession.session.title', $session->title)
                ->where('wearable.latestSnapshot', null)
            );
    }

    public function test_athlete_app_lists_assigned_programs_and_selected_day_schedule(): void
    {
        [$athlete, $session] = $this->athleteWorkoutFixture();

        $this->actingAs($athlete)
            ->get('/app?date='.now()->toDateString().'&month='.now()->format('Y-m'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('athlete-app/index')
                ->where('programs.0.title', 'Strength Block')
                ->where('programs.0.coach.name', 'Coach Riley')
                ->where('schedule.selectedDate', now()->toDateString())
                ->where('schedule.month', now()->format('Y-m'))
                ->where('selectedDaySessions.0.id', $session->id)
                ->where('selectedDaySessions.0.title', 'Lower strength')
                ->where('selectedDaySessions.0.program.title', 'Strength Block')
                ->where('selectedDaySessions.0.exercisePreview.0', 'Back squat - 2 x 6 - Load 120 kg')
            );
    }

    public function test_athlete_can_open_assigned_program_detail(): void
    {
        [$athlete, $session] = $this->athleteWorkoutFixture();

        $this->actingAs($athlete)
            ->get(route('athlete.programs.show', $session->program, absolute: false))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('athlete-app/program')
                ->where('program.title', 'Strength Block')
                ->where('program.coach.name', 'Coach Riley')
                ->where('program.sessions.0.title', 'Lower strength')
                ->where('program.sessions.0.mediaItems.0.type', 'video')
                ->where('program.sessions.0.exercises.0.sets', 2)
            );
    }

    public function test_athlete_cannot_open_another_athletes_program(): void
    {
        [, $session] = $this->athleteWorkoutFixture();
        $otherAthlete = User::factory()->create();
        $otherAthlete->assignRole(RoleName::Athlete);

        $this->actingAs($otherAthlete)
            ->get(route('athlete.programs.show', $session->program, absolute: false))
            ->assertForbidden();
    }

    public function test_athlete_app_includes_health_chart_series(): void
    {
        [$athlete] = $this->athleteWorkoutFixture();

        $connection = DeviceConnection::query()->create([
            'user_id' => $athlete->id,
            'provider' => DeviceProvider::Whoop,
            'status' => DeviceConnectionStatus::Connected,
            'external_user_id' => 'whoop-athlete-1',
            'last_synced_at' => now(),
        ]);

        MetricSnapshot::query()->create([
            'user_id' => $athlete->id,
            'device_connection_id' => $connection->id,
            'provider' => DeviceProvider::Whoop,
            'metric_date' => now()->subDay()->toDateString(),
            'readiness_score' => 78,
            'strain_score' => 11.2,
            'sleep_minutes' => 430,
            'calories_burned' => 2450,
            'heart_rate_variability' => 58,
            'resting_heart_rate' => 47,
        ]);

        AthleteCheckIn::query()->create([
            'user_id' => $athlete->id,
            'logged_date' => now()->toDateString(),
            'weight_kg' => 82.1,
            'protein_grams' => 168,
            'water_liters' => 3.2,
            'energy_score' => 8,
            'soreness_score' => 3,
            'stress_score' => 4,
            'sleep_quality_score' => 7,
        ]);

        $this->actingAs($athlete)
            ->get('/app')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('charts.rangeDays', 30)
                ->where('charts.wearable.readiness.0.value', 78)
                ->where('charts.progress.weightKg.0.value', 82.1)
                ->where('charts.progress.sleepQualityScore.0.value', 7)
            );
    }

    public function test_athlete_can_open_assigned_workout_execution_route(): void
    {
        [$athlete, $session] = $this->athleteWorkoutFixture();

        $this->actingAs($athlete)
            ->get(route('athlete.workouts.show', $session, absolute: false))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('athlete-app/workout')
                ->where('execution.session.title', $session->title)
                ->where('execution.session.mediaItems.0.type', 'video')
                ->where('execution.session.mediaItems.1.type', 'image')
                ->where('execution.session.mediaItems.1.url', 'https://cdn.example.com/workouts/squat-side.jpg')
                ->where('execution.setLogs.0.exerciseName', 'Back squat')
                ->where('execution.setLogs.1.setNumber', 2)
            );
    }

    public function test_athlete_cannot_open_another_athletes_workout(): void
    {
        [, $session] = $this->athleteWorkoutFixture();
        $otherAthlete = User::factory()->create();
        $otherAthlete->assignRole(RoleName::Athlete);

        $this->actingAs($otherAthlete)
            ->get(route('athlete.workouts.show', $session, absolute: false))
            ->assertForbidden();
    }

    public function test_athlete_can_save_set_logs_and_partial_status_is_recorded(): void
    {
        [$athlete, $session] = $this->athleteWorkoutFixture();

        $this->actingAs($athlete)
            ->from(route('athlete.workouts.show', $session, absolute: false))
            ->post(route('athlete.workouts.sets.store', $session, absolute: false), [
                'sets' => [
                    [
                        'exercise_index' => 0,
                        'set_number' => 1,
                        'actual_reps' => '6',
                        'actual_load' => '120 kg',
                        'actual_rpe' => 8,
                        'completed' => true,
                        'notes' => 'Moved well.',
                    ],
                    [
                        'exercise_index' => 0,
                        'set_number' => 2,
                        'actual_reps' => '4',
                        'actual_load' => '120 kg',
                        'actual_rpe' => 9,
                        'completed' => false,
                        'notes' => 'Stopped early.',
                    ],
                ],
            ])
            ->assertRedirect(route('athlete.workouts.show', $session, absolute: false));

        $this->assertDatabaseHas('workout_logs', [
            'training_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'completion_status' => WorkoutCompletionStatus::Partial->value,
        ]);

        $this->assertDatabaseHas('workout_set_logs', [
            'training_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'exercise_index' => 0,
            'set_number' => 1,
            'actual_reps' => '6',
            'actual_load' => '120 kg',
            'actual_rpe' => 8,
        ]);

        $this->assertNull(
            WorkoutSetLog::query()
                ->where('training_session_id', $session->id)
                ->where('athlete_id', $athlete->id)
                ->where('exercise_index', 0)
                ->where('set_number', 2)
                ->firstOrFail()
                ->completed_at
        );
    }

    public function test_athlete_can_complete_workout_and_opt_out_as_missed(): void
    {
        [$athlete, $session] = $this->athleteWorkoutFixture();

        $this->actingAs($athlete)
            ->from(route('athlete.workouts.show', $session, absolute: false))
            ->post(route('athlete.workouts.complete', $session, absolute: false), [
                'completion_status' => WorkoutCompletionStatus::Completed->value,
                'duration_minutes' => 52,
                'exertion_rating' => 8,
                'energy_score' => 7,
                'soreness_score' => 4,
                'stress_score' => 3,
                'sleep_quality_score' => 8,
                'notes' => 'Completed from the app.',
            ])
            ->assertRedirect(route('athlete.workouts.show', $session, absolute: false));

        $log = WorkoutLog::query()
            ->where('training_session_id', $session->id)
            ->where('athlete_id', $athlete->id)
            ->firstOrFail();

        $this->assertSame(WorkoutCompletionStatus::Completed, $log->completion_status);
        $this->assertSame(3, WorkoutSetLog::query()->where('workout_log_id', $log->id)->whereNotNull('completed_at')->count());

        $this->actingAs($athlete)
            ->from(route('athlete.workouts.show', $session, absolute: false))
            ->post(route('athlete.workouts.complete', $session, absolute: false), [
                'completion_status' => WorkoutCompletionStatus::Missed->value,
                'notes' => 'Opted out after coach instruction.',
            ])
            ->assertRedirect(route('athlete.workouts.show', $session, absolute: false));

        $this->assertSame(WorkoutCompletionStatus::Missed, $log->fresh()->completion_status);
    }

    public function test_api_can_fetch_execution_save_sets_and_complete_session(): void
    {
        [$athlete, $session] = $this->athleteWorkoutFixture();

        Sanctum::actingAs($athlete, ['training:read', 'training:write']);

        $this->getJson(route('api.v1.training.sessions.execution', $session))
            ->assertOk()
            ->assertJsonPath('data.session.title', $session->title)
            ->assertJsonPath('data.session.mediaItems.1.url', 'https://cdn.example.com/workouts/squat-side.jpg')
            ->assertJsonPath('data.setLogs.0.exerciseName', 'Back squat');

        $this->postJson(route('api.v1.training.sessions.sets.store', $session), [
            'sets' => [
                [
                    'exercise_index' => 0,
                    'set_number' => 1,
                    'actual_reps' => '6',
                    'actual_load' => '122.5 kg',
                    'actual_rpe' => 8,
                    'completed' => true,
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.workoutLog.completionStatus', 'partial');

        $this->postJson(route('api.v1.training.sessions.complete', $session), [
            'completion_status' => 'completed',
            'duration_minutes' => 48,
            'notes' => 'API completion worked.',
        ])
            ->assertOk()
            ->assertJsonPath('data.workoutLog.completionStatus', 'completed')
            ->assertJsonPath('data.workoutLog.durationMinutes', 48);
    }

    /**
     * @return array{User, TrainingSession}
     */
    private function athleteWorkoutFixture(): array
    {
        $coach = User::factory()->create(['name' => 'Coach Riley']);
        $athlete = User::factory()->create(['name' => 'Athlete Sam']);

        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Build strength and repeatability',
            'notes' => null,
            'started_at' => now()->subDays(10)->toDateString(),
        ]);

        $program = TrainingProgram::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Strength Block',
            'goal' => 'Better output without recovery debt',
            'status' => TrainingProgramStatus::Active,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'notes' => 'Stay honest on RPE.',
        ]);

        $session = TrainingSession::query()->create([
            'training_program_id' => $program->id,
            'title' => 'Lower strength',
            'scheduled_date' => now()->toDateString(),
            'focus' => 'Strength',
            'instructions' => 'Stop two reps before form breaks.',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'media_items' => [
                [
                    'type' => 'image',
                    'url' => 'https://cdn.example.com/workouts/squat-side.jpg',
                    'title' => 'Side angle squat reference',
                ],
                [
                    'type' => 'image',
                    'url' => 'https://cdn.example.com/workouts/squat-depth.jpg',
                    'title' => 'Depth reference',
                ],
            ],
            'exercises' => [
                [
                    'name' => 'Back squat',
                    'prescription' => '2 x 6 · load 120 kg · rest 150s · RPE 8',
                    'sets' => 2,
                    'reps' => '6',
                    'load' => '120 kg',
                    'rest_seconds' => 150,
                    'rest_label' => '150s',
                    'target' => 'RPE 8',
                    'note' => 'Full depth.',
                ],
                [
                    'name' => 'Split squat',
                    'prescription' => '8/side',
                    'sets' => null,
                    'reps' => '8/side',
                    'load' => 'bodyweight',
                    'rest_seconds' => 60,
                    'rest_label' => '60s',
                    'target' => 'Clean reps',
                    'note' => null,
                ],
            ],
            'sort_order' => 1,
        ]);

        return [$athlete, $session];
    }
}
