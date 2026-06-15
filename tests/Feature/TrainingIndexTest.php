<?php

namespace Tests\Feature;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Enums\WorkoutCompletionStatus;
use App\Models\CoachAthleteAssignment;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrainingIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_all_programs_across_the_platform(): void
    {
        [$admin, $coachOne, $coachTwo, $athleteOne, $athleteTwo, $athleteThree] = $this->trainingFixtures();

        $programOne = $this->program($coachOne, $athleteOne, 'HYROX Build', now()->subDays(5)->toDateString());
        $programTwo = $this->program($coachOne, $athleteTwo, 'Power Peak', now()->subDays(2)->toDateString());
        $programThree = $this->program($coachTwo, $athleteThree, 'Return Ramp', now()->toDateString(), TrainingProgramStatus::Draft);

        $sessionOne = $this->makeTrainingSession($programOne, 'Tempo day', now()->subDay()->toDateString(), 1);
        $this->makeTrainingSession($programTwo, 'Lower speed day', now()->addDay()->toDateString(), 1);
        $this->makeTrainingSession($programThree, 'Base re-entry', now()->addDays(2)->toDateString(), 1);

        WorkoutLog::query()->create([
            'training_session_id' => $sessionOne->id,
            'athlete_id' => $athleteOne->id,
            'completion_status' => WorkoutCompletionStatus::Completed,
            'performed_at' => now()->subDay(),
            'duration_minutes' => 62,
            'exertion_rating' => 7,
            'notes' => 'Clean pacing.',
        ]);

        $this->actingAs($admin)
            ->get('/training')
            ->assertInertia(fn (Assert $page) => $page
                ->component('training/index')
                ->where('viewerRole', RoleName::Admin->value)
                ->where('summary.trackedPrograms', 3)
                ->where('summary.activePrograms', 2)
                ->where('summary.loggedSessions', 1)
                ->where('programs.total', 3)
                ->where('canCreatePrograms', false)
            );
    }

    public function test_coach_only_sees_owned_programs_and_active_roster(): void
    {
        [, $coachOne, $coachTwo, $athleteOne, $athleteTwo, $athleteThree] = $this->trainingFixtures();

        $visibleProgram = $this->program($coachOne, $athleteOne, 'Visible Block', now()->subDays(1)->toDateString());
        $staleProgram = $this->program($coachOne, $athleteTwo, 'Needs Log', now()->subDays(3)->toDateString());
        $this->program($coachTwo, $athleteThree, 'Hidden Block', now()->subDays(2)->toDateString());

        $completedSession = $this->makeTrainingSession($visibleProgram, 'Threshold builder', now()->addDay()->toDateString(), 1);
        WorkoutLog::query()->create([
            'training_session_id' => $completedSession->id,
            'athlete_id' => $athleteOne->id,
            'completion_status' => WorkoutCompletionStatus::Completed,
            'performed_at' => now(),
            'duration_minutes' => 58,
            'exertion_rating' => 7,
            'notes' => null,
        ]);

        $this->makeTrainingSession($staleProgram, 'Unlogged strength session', now()->subDay()->toDateString(), 1);

        $this->actingAs($coachOne)
            ->get('/training')
            ->assertInertia(fn (Assert $page) => $page
                ->component('training/index')
                ->where('viewerRole', RoleName::Coach->value)
                ->where('summary.trackedPrograms', 2)
                ->where('summary.pendingLogs', 1)
                ->where('programs.total', 2)
                ->has('rosterAthletes', 2)
                ->where('canCreatePrograms', true)
            );
    }

    public function test_athlete_only_sees_their_assigned_programs_and_logs(): void
    {
        [, $coachOne, $coachTwo, $athleteOne, $athleteTwo, $athleteThree] = $this->trainingFixtures();

        $visibleProgram = $this->program($coachOne, $athleteOne, 'Athlete View Block', now()->subDays(4)->toDateString());
        $this->program($coachOne, $athleteTwo, 'Other Athlete Block', now()->subDays(3)->toDateString());
        $this->program($coachTwo, $athleteThree, 'Other Coach Block', now()->subDays(2)->toDateString());

        $visibleSession = $this->makeTrainingSession($visibleProgram, 'Logged session', now()->subDay()->toDateString(), 1);
        WorkoutLog::query()->create([
            'training_session_id' => $visibleSession->id,
            'athlete_id' => $athleteOne->id,
            'completion_status' => WorkoutCompletionStatus::Completed,
            'performed_at' => now()->subDay(),
            'duration_minutes' => 47,
            'exertion_rating' => 6,
            'notes' => 'Felt controlled.',
        ]);

        $this->actingAs($athleteOne)
            ->get('/training')
            ->assertInertia(fn (Assert $page) => $page
                ->component('training/index')
                ->where('viewerRole', RoleName::Athlete->value)
                ->where('summary.trackedPrograms', 1)
                ->where('summary.completedThisWeek', 1)
                ->where('programs.total', 1)
                ->where('programs.data.0.athleteName', $athleteOne->name)
                ->where('programs.data.0.sessions.0.exercises.0.sets', 3)
                ->where('programs.data.0.sessions.0.exercises.0.reps', '5')
                ->where('programs.data.0.sessions.0.workoutLog.completionStatus', WorkoutCompletionStatus::Completed->value)
            );
    }

    /**
     * @return array{User, User, User, User, User, User}
     */
    private function trainingFixtures(): array
    {
        $admin = User::factory()->create();
        $coachOne = User::factory()->create();
        $coachTwo = User::factory()->create();
        $athleteOne = User::factory()->create();
        $athleteTwo = User::factory()->create();
        $athleteThree = User::factory()->create();

        $admin->assignRole(RoleName::Admin);
        $coachOne->assignRole(RoleName::Coach);
        $coachTwo->assignRole(RoleName::Coach);
        $athleteOne->assignRole(RoleName::Athlete);
        $athleteTwo->assignRole(RoleName::Athlete);
        $athleteThree->assignRole(RoleName::Athlete);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coachOne->id,
            'athlete_id' => $athleteOne->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Race prep',
            'notes' => null,
            'started_at' => now()->subDays(30)->toDateString(),
            'ended_at' => null,
        ]);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coachOne->id,
            'athlete_id' => $athleteTwo->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Strength block',
            'notes' => null,
            'started_at' => now()->subDays(20)->toDateString(),
            'ended_at' => null,
        ]);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coachTwo->id,
            'athlete_id' => $athleteThree->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Re-entry',
            'notes' => null,
            'started_at' => now()->subDays(10)->toDateString(),
            'ended_at' => null,
        ]);

        return [$admin, $coachOne, $coachTwo, $athleteOne, $athleteTwo, $athleteThree];
    }

    private function program(
        User $coach,
        User $athlete,
        string $title,
        string $startDate,
        TrainingProgramStatus $status = TrainingProgramStatus::Active,
    ): TrainingProgram {
        return TrainingProgram::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => $title,
            'goal' => 'Program goal',
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => now()->addDays(21)->toDateString(),
            'notes' => 'Program note',
        ]);
    }

    private function makeTrainingSession(TrainingProgram $program, string $title, string $scheduledDate, int $sortOrder): TrainingSession
    {
        return TrainingSession::query()->create([
            'training_program_id' => $program->id,
            'title' => $title,
            'scheduled_date' => $scheduledDate,
            'focus' => 'Session focus',
            'instructions' => 'Session instructions',
            'exercises' => [
                [
                    'name' => 'Main set',
                    'prescription' => '3 x 5 · load 80% 1RM · rest 90s · RPE 7',
                    'sets' => 3,
                    'reps' => '5',
                    'load' => '80% 1RM',
                    'rest_seconds' => 90,
                    'rest_label' => '90s',
                    'target' => 'RPE 7',
                    'note' => 'Stay sharp',
                ],
            ],
            'sort_order' => $sortOrder,
        ]);
    }
}
