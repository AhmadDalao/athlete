<?php

namespace Tests\Feature;

use App\Livewire\Coach\ExerciseLibraryTable;
use App\Livewire\Coach\ProgramDetail;
use App\Livewire\Coach\Reports;
use App\Models\CoachAthleteAssignment;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Services\ProgramScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CoachWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_session_is_normalized_and_added_to_existing_athlete_schedule(): void
    {
        [$organization, $coach, $athlete] = $this->coachTeam();
        $program = TrainingProgram::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'title' => 'Strength Base',
            'status' => 'active',
            'is_template' => true,
            'visibility' => 'private',
        ]);
        $phase = $program->phases()->create([
            'organization_id' => $organization->id,
            'title' => 'Foundation',
            'sort_order' => 1,
        ]);
        $assignment = app(ProgramScheduleService::class)->assign(
            $program,
            $athlete,
            $coach,
            '2026-08-11',
        );
        $this->assertTrue(
            $athlete->unreadNotifications()->where('data->category', 'program')->exists(),
            'Assigning a program should notify the athlete.',
        );

        Livewire::actingAs($coach)
            ->test(ProgramDetail::class, ['program' => $program])
            ->set('sessionForm.title', 'Lower strength')
            ->set('sessionForm.focus', 'Force production')
            ->set('sessionForm.phaseId', $phase->id)
            ->set('sessionForm.dayOffset', 2)
            ->set('sessionForm.estimatedMinutes', 45)
            ->set('sessionForm.exercises.0.section', 'Main work')
            ->set('sessionForm.exercises.0.name', 'Trap bar deadlift')
            ->set('sessionForm.exercises.0.sets', 4)
            ->set('sessionForm.exercises.0.reps', '5')
            ->set('sessionForm.exercises.0.load', '80')
            ->set('sessionForm.exercises.0.unit', 'kg')
            ->set('sessionForm.exercises.0.rest_seconds', 120)
            ->set('sessionForm.exercises.0.media_url', 'https://example.com/deadlift.mp4')
            ->call('createSession')
            ->assertHasNoErrors();

        $session = $program->sessions()->firstOrFail();
        $this->assertSame($organization->id, $session->organization_id);
        $this->assertDatabaseHas('training_session_exercises', [
            'organization_id' => $organization->id,
            'training_session_id' => $session->id,
            'section' => 'Main work',
            'name' => 'Trap bar deadlift',
            'target_sets' => 4,
            'target_reps' => '5',
            'target_load' => '80',
            'unit' => 'kg',
            'rest_seconds' => 120,
        ]);
        $this->assertDatabaseHas('scheduled_workouts', [
            'program_assignment_id' => $assignment->id,
            'training_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'coach_id' => $coach->id,
            'status' => 'scheduled',
        ]);
        $this->assertSame('2026-08-13', $assignment->fresh()->ends_on->toDateString());
        $this->assertSame('2026-08-13', $assignment->scheduledWorkouts()->firstOrFail()->scheduled_for->toDateString());
    }

    public function test_exercise_library_is_editable_by_owner_and_visible_as_a_filtered_export(): void
    {
        [, $coach] = $this->coachTeam();

        Livewire::actingAs($coach)
            ->test(ExerciseLibraryTable::class)
            ->set('name', 'Split squat')
            ->set('section', 'Accessory')
            ->set('movementType', 'Strength')
            ->set('defaultSets', 3)
            ->set('defaultReps', '8/side')
            ->set('defaultRestSeconds', 75)
            ->set('isShared', true)
            ->call('save')
            ->assertHasNoErrors();

        $response = $this->actingAs($coach)
            ->get(route('coach.exercises.export', ['search' => 'Split', 'status' => 'active']))
            ->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Split squat', $csv);
        $this->assertStringContainsString('8/side', $csv);
    }

    public function test_program_and_schedule_exports_are_scoped_to_the_logged_in_coach(): void
    {
        [$organization, $coach, $athlete] = $this->coachTeam();
        $otherCoach = User::factory()->create(['role' => 'coach']);
        $this->join($organization, $otherCoach, 'coach');

        $ownProgram = TrainingProgram::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'title' => 'Visible Program',
            'status' => 'active',
            'is_template' => true,
        ]);
        $ownProgram->sessions()->create([
            'organization_id' => $organization->id,
            'title' => 'Visible Session',
            'day_offset' => 0,
            'status' => 'scheduled',
        ]);
        app(ProgramScheduleService::class)->assign($ownProgram, $athlete, $coach, today()->toDateString());

        TrainingProgram::create([
            'organization_id' => $organization->id,
            'coach_id' => $otherCoach->id,
            'title' => 'Private Other Coach Program',
            'status' => 'active',
            'is_template' => true,
        ]);

        $programCsv = $this->actingAs($coach)
            ->get(route('coach.programs.export'))
            ->assertOk()
            ->streamedContent();
        $scheduleCsv = $this->actingAs($coach)
            ->get(route('coach.schedule.export', ['from' => today()->subDay()->toDateString(), 'to' => today()->addDay()->toDateString()]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Visible Program', $programCsv);
        $this->assertStringNotContainsString('Private Other Coach Program', $programCsv);
        $this->assertStringContainsString('Visible Session', $scheduleCsv);
        $this->assertStringContainsString($athlete->email, $scheduleCsv);
    }

    public function test_coach_report_calculates_adherence_and_excludes_other_coaches(): void
    {
        [$organization, $coach, $athlete] = $this->coachTeam();
        $otherCoach = User::factory()->create(['role' => 'coach']);
        $otherAthlete = User::factory()->create(['role' => 'athlete']);
        $this->join($organization, $otherCoach, 'coach');
        $this->join($organization, $otherAthlete, 'athlete');
        CoachAthleteAssignment::create([
            'organization_id' => $organization->id,
            'coach_id' => $otherCoach->id,
            'athlete_id' => $otherAthlete->id,
            'status' => 'active',
            'started_at' => today(),
        ]);

        $ownProgram = TrainingProgram::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'title' => 'Report Program',
            'status' => 'active',
            'is_template' => true,
        ]);
        $session = $ownProgram->sessions()->create([
            'organization_id' => $organization->id,
            'title' => 'Report Session',
            'day_offset' => 0,
            'status' => 'scheduled',
        ]);
        $assignment = app(ProgramScheduleService::class)->assign($ownProgram, $athlete, $coach, today()->toDateString());
        $scheduledWorkout = $assignment->scheduledWorkouts()->firstOrFail();
        WorkoutLog::create([
            'organization_id' => $organization->id,
            'training_session_id' => $session->id,
            'program_assignment_id' => $assignment->id,
            'scheduled_workout_id' => $scheduledWorkout->id,
            'athlete_id' => $athlete->id,
            'status' => 'completed',
            'duration_minutes' => 48,
            'rpe' => 8,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($coach)
            ->test(Reports::class)
            ->assertSee($athlete->name)
            ->assertSee('100%')
            ->assertDontSee($otherAthlete->name);

        $csv = $this->actingAs($coach)
            ->get(route('coach.reports.export', [
                'from' => today()->subDay()->toDateString(),
                'to' => today()->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString($athlete->email, $csv);
        $this->assertStringContainsString('100%', $csv);
        $this->assertStringNotContainsString($otherAthlete->email, $csv);
    }

    /** @return array{Organization, User, User} */
    private function coachTeam(): array
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);
        $organization = Organization::create([
            'name' => 'Coach Workflow Team',
            'slug' => 'coach-workflow-team',
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
        ]);
        $this->join($organization, $coach, 'coach');
        $this->join($organization, $athlete, 'athlete');
        CoachAthleteAssignment::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => 'active',
            'started_at' => today(),
        ]);

        return [$organization, $coach->fresh(), $athlete->fresh()];
    }

    private function join(Organization $organization, User $user, string $role): void
    {
        OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $user->forceFill(['current_organization_id' => $organization->id])->saveQuietly();
    }
}
