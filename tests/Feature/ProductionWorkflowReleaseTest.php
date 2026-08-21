<?php

namespace Tests\Feature;

use App\Livewire\Admin\RosterAssignments;
use App\Models\CoachAthleteAssignment;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\ProgramAssignment;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\ProgramPersonalizationService;
use App\Services\TrainingProgramManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionWorkflowReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_presets_are_deep_copied_and_athlete_plans_remain_isolated(): void
    {
        [$organization, $coach, $athlete] = $this->team();
        $preset = $this->preset($organization, $coach);
        $sourceSession = $preset->sessions()->firstOrFail();
        $sourceExercise = $sourceSession->prescribedExercises()->firstOrFail();
        $personalization = app(ProgramPersonalizationService::class);

        $duplicate = $personalization->duplicatePreset($preset, $coach, 'Reusable copy');
        $this->assertTrue($duplicate->is_template);
        $this->assertSame($preset->id, $duplicate->source_program_id);
        $this->assertSame('Reusable copy', $duplicate->title);
        $this->assertNotSame($sourceSession->id, $duplicate->sessions->first()->id);
        $this->assertNotSame($sourceExercise->id, $duplicate->sessions->first()->prescribedExercises->first()->id);
        $this->assertSame('https://example.com/squat.mp4', $duplicate->sessions->first()->prescribedExercises->first()->media_url);
        $this->assertDatabaseMissing('program_assignments', ['training_program_id' => $duplicate->id]);

        $assignment = $personalization->personalize(
            $preset,
            $athlete,
            $coach,
            '2026-08-24',
            'Personalize before publishing',
            false,
        );
        $plan = $assignment->program;
        $planSession = $plan->sessions()->firstOrFail();

        $this->assertFalse($plan->is_template);
        $this->assertSame($preset->id, $plan->source_program_id);
        $this->assertSame($athlete->id, $plan->athlete_id);
        $this->assertSame('draft', $assignment->status);
        $this->assertNull($assignment->published_at);
        $this->assertDatabaseCount('scheduled_workouts', 0);

        $sourceSession->update(['title' => 'Changed source session']);
        $sourceExercise->update(['target_load' => '140']);
        $this->assertSame('Strength day', $planSession->fresh()->title);
        $this->assertSame('100', $planSession->prescribedExercises()->firstOrFail()->target_load);

        $response = $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson("/api/v1/coach/programs/{$preset->id}/duplicate", ['title' => 'API copy'])
            ->assertCreated()
            ->assertJsonPath('data.kind', 'preset')
            ->assertJsonPath('data.source_program_id', $preset->id);
        $this->assertDatabaseHas('training_programs', ['id' => $response->json('data.id'), 'title' => 'API copy']);
    }

    public function test_draft_plan_publishes_once_and_generates_one_notification(): void
    {
        [$organization, $coach, $athlete] = $this->team();
        $assignment = app(ProgramPersonalizationService::class)->personalize(
            $this->preset($organization, $coach),
            $athlete,
            $coach,
            today()->toDateString(),
            publish: false,
        );

        $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson("/api/v1/coach/assignments/{$assignment->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.can_publish', false);

        $this->assertNotNull($assignment->fresh()->published_at);
        $this->assertSame(1, $assignment->scheduledWorkouts()->count());
        $this->assertSame(1, $athlete->notifications()->where('data->category', 'program')->count());

        $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson("/api/v1/coach/assignments/{$assignment->id}/publish")
            ->assertUnprocessable();

        $this->assertSame(1, $assignment->scheduledWorkouts()->count());
        $this->assertSame(1, $athlete->notifications()->where('data->category', 'program')->count());
    }

    public function test_assignment_isolation_command_preserves_schedule_and_execution_history(): void
    {
        [$organization, $coach, $athlete] = $this->team();
        $preset = $this->preset($organization, $coach);
        $session = $preset->sessions()->firstOrFail();
        $exercise = $session->prescribedExercises()->firstOrFail();
        $assignment = ProgramAssignment::create([
            'organization_id' => $organization->id,
            'training_program_id' => $preset->id,
            'athlete_id' => $athlete->id,
            'assigned_by' => $coach->id,
            'status' => 'active',
            'starts_on' => today(),
            'ends_on' => today(),
            'timezone' => 'Asia/Riyadh',
        ]);
        $workout = $assignment->scheduledWorkouts()->create([
            'organization_id' => $organization->id,
            'training_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'coach_id' => $coach->id,
            'scheduled_for' => now(),
            'status' => 'completed',
        ]);
        $log = $workout->logs()->create([
            'organization_id' => $organization->id,
            'training_session_id' => $session->id,
            'program_assignment_id' => $assignment->id,
            'athlete_id' => $athlete->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        $setLog = $log->setLogs()->create([
            'organization_id' => $organization->id,
            'scheduled_workout_id' => $workout->id,
            'training_session_exercise_id' => $exercise->id,
            'athlete_id' => $athlete->id,
            'exercise_index' => 0,
            'exercise_name' => 'Back squat',
            'set_number' => 1,
            'actual_reps' => 5,
            'actual_load' => 100,
            'completed_at' => now(),
        ]);

        $this->artisan('throughline:isolate-program-assignments')
            ->expectsOutput('Dry run: 1 assignment(s) still reference reusable presets.')
            ->assertSuccessful();
        $this->assertSame($preset->id, $assignment->fresh()->training_program_id);

        $this->artisan('throughline:isolate-program-assignments --execute')->assertSuccessful();
        $assignment->refresh();

        $this->assertNotSame($preset->id, $assignment->training_program_id);
        $this->assertSame($preset->id, $assignment->program->source_program_id);
        $this->assertFalse($assignment->program->is_template);
        $historySessionId = $workout->fresh()->training_session_id;
        $this->assertNotSame($session->id, $historySessionId);
        $this->assertSame($historySessionId, $log->fresh()->training_session_id);
        $this->assertNotSame($exercise->id, $setLog->fresh()->training_session_exercise_id);
        $this->assertSame('completed', $workout->status);
        $this->assertSame(1, $assignment->scheduledWorkouts()->count());
    }

    public function test_workout_completion_requires_exact_sets_and_retry_notifies_once(): void
    {
        [$organization, $coach, $athlete] = $this->team();
        $assignment = app(ProgramPersonalizationService::class)->personalize(
            $this->preset($organization, $coach),
            $athlete,
            $coach,
            today()->toDateString(),
        );
        $workout = $assignment->scheduledWorkouts()->firstOrFail();
        $exercise = $workout->session->prescribedExercises()->firstOrFail();
        $firstSet = $this->setPayload($exercise->id, 1);
        $secondSet = $this->setPayload($exercise->id, 2);
        $endpoint = "/api/v1/app/workouts/{$workout->id}/execution";

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->putJson($endpoint, ['status' => 'completed', 'confirmed_complete' => true, 'sets' => [$firstSet]])
            ->assertUnprocessable();

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->putJson($endpoint, ['status' => 'partial', 'sets' => [$firstSet]])
            ->assertOk()
            ->assertJsonPath('data.execution.status', 'partial');

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->putJson($endpoint, ['status' => 'completed', 'sync_version' => 1, 'sets' => [$firstSet, $secondSet]])
            ->assertUnprocessable();

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->putJson($endpoint, [
                'status' => 'completed',
                'confirmed_complete' => true,
                'sync_version' => 1,
                'sets' => [$firstSet, $secondSet],
            ])
            ->assertOk();
        $this->assertSame(1, $coach->notifications()->where('data->category', 'workout')->count());

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->putJson($endpoint, [
                'status' => 'completed',
                'confirmed_complete' => true,
                'sync_version' => 2,
                'sets' => [$firstSet, $secondSet],
            ])
            ->assertOk();
        $this->assertSame(1, $coach->notifications()->where('data->category', 'workout')->count());
        $this->assertSame(2, $workout->executionLog()->firstOrFail()->setLogs()->count());
    }

    public function test_editing_a_published_plan_freezes_logged_history_and_updates_only_open_workouts(): void
    {
        [$organization, $coach, $athlete] = $this->team();
        $assignment = app(ProgramPersonalizationService::class)->personalize(
            $this->preset($organization, $coach),
            $athlete,
            $coach,
            today()->toDateString(),
        );
        $workout = $assignment->scheduledWorkouts()->firstOrFail();
        $session = $workout->session;
        $exercise = $session->prescribedExercises()->firstOrFail();
        $log = $workout->logs()->create([
            'organization_id' => $organization->id,
            'training_session_id' => $session->id,
            'program_assignment_id' => $assignment->id,
            'athlete_id' => $athlete->id,
            'status' => 'partial',
        ]);
        $setLog = $log->setLogs()->create([
            'organization_id' => $organization->id,
            'scheduled_workout_id' => $workout->id,
            'training_session_exercise_id' => $exercise->id,
            'athlete_id' => $athlete->id,
            'exercise_index' => 0,
            'exercise_name' => 'Back squat',
            'set_number' => 1,
            'actual_reps' => 5,
            'completed_at' => now(),
        ]);

        app(TrainingProgramManager::class)->updateSession($assignment->program, $session->id, [
            'title' => 'Personalized strength day',
            'focus' => 'Power',
            'day_offset' => 1,
            'sort_order' => 1,
            'estimated_minutes' => 50,
            'exercises' => [[
                'name' => 'Front squat',
                'section' => 'Main work',
                'sets' => 3,
                'reps' => '4',
                'load' => '90',
                'unit' => 'kg',
            ]],
        ]);

        $historySessionId = $workout->fresh()->training_session_id;
        $this->assertNotSame($session->id, $historySessionId);
        $this->assertSame($historySessionId, $log->fresh()->training_session_id);
        $this->assertNotSame($exercise->id, $setLog->fresh()->training_session_exercise_id);
        $this->assertSame('Strength day (history)', $workout->fresh('session')->session->title);
        $this->assertSame('Personalized strength day', $session->fresh()->title);
        $this->assertSame('Front squat', $session->prescribedExercises()->firstOrFail()->name);
        $this->assertSame(1, $assignment->scheduledWorkouts()->count());
    }

    public function test_admin_manages_multiple_coaches_while_only_plan_owner_can_edit(): void
    {
        [$organization, $coachOne, $athlete] = $this->team();
        $coachTwo = User::factory()->create(['role' => 'coach']);
        $admin = User::factory()->create(['role' => 'owner']);
        $otherOrganization = Organization::create(['name' => 'Other', 'slug' => 'other', 'status' => 'active']);
        $outsiderCoach = User::factory()->create(['role' => 'coach']);
        $this->join($organization, $coachTwo, 'coach');
        $this->join($organization, $admin, 'owner');
        $this->join($otherOrganization, $outsiderCoach, 'coach');

        Livewire::actingAs($admin)
            ->test(RosterAssignments::class)
            ->set('coachId', $coachTwo->id)
            ->set('athleteId', $athlete->id)
            ->call('assign')
            ->assertHasNoErrors()
            ->set('coachId', $outsiderCoach->id)
            ->set('athleteId', $athlete->id)
            ->call('assign')
            ->assertHasErrors('coachId');

        $this->assertSame(2, CoachAthleteAssignment::query()->where('athlete_id', $athlete->id)->where('status', 'active')->count());
        $preset = $this->preset($organization, $coachOne);
        $plan = app(ProgramPersonalizationService::class)
            ->personalize($preset, $athlete, $coachOne, today()->toDateString(), publish: false)
            ->program;

        $this->actingAs($coachTwo, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->putJson("/api/v1/coach/programs/{$plan->id}", [
                'title' => 'Hijacked plan',
                'goal' => null,
                'status' => 'draft',
                'visibility' => 'private',
                'estimated_weeks' => 4,
                'notes' => null,
            ])
            ->assertForbidden();
        $this->actingAs($coachTwo, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson("/api/v1/coach/athletes/{$athlete->id}")
            ->assertOk()
            ->assertJsonPath('data.programs.0.program.id', $plan->id)
            ->assertJsonPath('data.programs.0.can_edit', false)
            ->assertJsonStructure(['data' => ['progress_summary' => ['adherence', 'sets', 'averages', 'counts']]]);
        $this->actingAs($coachTwo)->get('/admin/roster')->assertForbidden();
    }

    /** @return array{Organization, User, User} */
    private function team(): array
    {
        $organization = Organization::create([
            'name' => 'Release QA Team',
            'slug' => 'release-qa-team',
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
        ]);
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);
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

    private function preset(Organization $organization, User $coach): TrainingProgram
    {
        $preset = TrainingProgram::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'title' => 'Four week strength',
            'goal' => 'Build strength',
            'status' => 'active',
            'notes' => 'Reusable source notes',
            'is_template' => true,
            'visibility' => 'private',
            'estimated_weeks' => 4,
        ]);
        $phase = $preset->phases()->create([
            'organization_id' => $organization->id,
            'title' => 'Foundation',
            'description' => 'Build movement quality',
            'sort_order' => 1,
            'duration_weeks' => 4,
        ]);
        $session = $preset->sessions()->create([
            'organization_id' => $organization->id,
            'program_phase_id' => $phase->id,
            'title' => 'Strength day',
            'focus' => 'Force',
            'status' => 'scheduled',
            'day_offset' => 0,
            'sort_order' => 1,
            'estimated_minutes' => 45,
            'coach_notes' => 'Keep two reps in reserve',
            'media_url' => 'https://example.com/session.mp4',
        ]);
        $session->prescribedExercises()->create([
            'organization_id' => $organization->id,
            'sort_order' => 1,
            'section' => 'Main work',
            'name' => 'Back squat',
            'target_sets' => 2,
            'target_reps' => '5',
            'target_load' => '100',
            'unit' => 'kg',
            'rest_seconds' => 120,
            'notes' => 'Brace before descending',
            'media_url' => 'https://example.com/squat.mp4',
        ]);

        return $preset;
    }

    /** @return array<string, mixed> */
    private function setPayload(int $exerciseId, int $set): array
    {
        return [
            'exercise_id' => $exerciseId,
            'exercise_index' => 0,
            'exercise_name' => 'Back squat',
            'set_number' => $set,
            'target_reps' => '5',
            'target_load' => '100',
            'actual_reps' => 5,
            'actual_load' => 100,
            'completed' => true,
        ];
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
