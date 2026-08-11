<?php

namespace Tests\Feature;

use App\Models\CoachAthleteAssignment;
use App\Models\Conversation;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\ProgramAssignment;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\ProgramScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_athlete_api_returns_assigned_program_calendar_and_workout_execution(): void
    {
        [$organization, $coach, $athlete] = $this->team();
        $assignment = $this->assignedProgram($organization, $coach, $athlete);
        $workout = $assignment->scheduledWorkouts()->firstOrFail();

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/app/home')
            ->assertOk()
            ->assertJsonPath('data.programs.0.id', $assignment->id)
            ->assertJsonPath('data.upcoming_workouts.0.id', $workout->id)
            ->assertJsonStructure(['data', 'meta', 'links']);

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/app/calendar?month='.today()->format('Y-m').'&date='.today()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.selected_day_workouts.0.id', $workout->id);

        $exercise = $workout->session->prescribedExercises()->firstOrFail();
        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->putJson("/api/v1/app/workouts/{$workout->id}/execution", [
                'status' => 'completed',
                'duration_minutes' => 45,
                'rpe' => 8,
                'sets' => [[
                    'exercise_id' => $exercise->id,
                    'exercise_index' => 0,
                    'exercise_name' => $exercise->name,
                    'set_number' => 1,
                    'target_reps' => '5',
                    'target_load' => '100',
                    'target_rest_seconds' => 120,
                    'actual_reps' => 5,
                    'actual_load' => 100,
                    'rpe' => 8,
                    'completed' => true,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('data.execution.status', 'completed')
            ->assertJsonPath('data.execution.sets.0.actual_load', '100.00');

        $this->assertDatabaseHas('scheduled_workouts', ['id' => $workout->id, 'status' => 'completed']);
        $this->assertDatabaseHas('workout_set_logs', ['scheduled_workout_id' => $workout->id, 'actual_reps' => 5]);
    }

    public function test_progress_api_upserts_daily_check_in_and_limits_page_size(): void
    {
        [$organization, , $athlete] = $this->team();

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson('/api/v1/app/progress', [
                'logged_on' => today()->toDateString(),
                'weight_kg' => 82.5,
                'protein_g' => 165,
                'energy' => 8,
            ])
            ->assertCreated()
            ->assertJsonPath('data.weight_kg', 82.5);

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/app/progress?per_page=500')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonPath('data.0.protein_g', 165);
    }

    public function test_coach_roster_and_athlete_detail_are_assignment_scoped(): void
    {
        [$organization, $coach, $athlete] = $this->team();
        $other = User::factory()->create(['role' => 'athlete']);
        $this->join($organization, $other, 'athlete');

        $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/coach/roster')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $athlete->id);

        $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson("/api/v1/coach/athletes/{$other->id}")
            ->assertForbidden();
    }

    public function test_coach_can_build_and_assign_a_program_through_the_mobile_api(): void
    {
        [$organization, $coach, $athlete] = $this->team();

        $programId = $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson('/api/v1/coach/programs', [
                'title' => 'Mobile speed block',
                'goal' => 'Acceleration',
                'status' => 'active',
                'visibility' => 'private',
                'estimated_weeks' => 4,
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Mobile speed block')
            ->json('data.id');

        $phaseId = TrainingProgram::findOrFail($programId)->phases()->firstOrFail()->id;

        $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson("/api/v1/coach/programs/{$programId}/sessions", [
                'title' => 'Acceleration day',
                'focus' => 'Speed',
                'day_offset' => 2,
                'estimated_minutes' => 50,
                'program_phase_id' => $phaseId,
                'exercises' => [[
                    'section' => 'Main work',
                    'name' => 'Sled sprint',
                    'sets' => 4,
                    'reps' => '20 m',
                    'rest_seconds' => 120,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.exercises.0.name', 'Sled sprint');

        $assignmentId = $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson("/api/v1/coach/programs/{$programId}/assignments", [
                'athlete_id' => $athlete->id,
                'starts_on' => today()->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.athlete.id', $athlete->id)
            ->json('data.id');

        $this->assertDatabaseHas('scheduled_workouts', [
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
        ]);
        $workout = ProgramAssignment::findOrFail($assignmentId)->scheduledWorkouts()->firstOrFail();

        $rescheduleResponse = $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->patchJson("/api/v1/coach/schedule/{$workout->id}/reschedule", [
                'scheduled_for' => today()->addWeek()->toDateString(),
            ])
            ->assertOk();
        $this->assertStringStartsWith(
            today()->addWeek()->toDateString(),
            $rescheduleResponse->json('data.scheduled_for'),
        );

        $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->patchJson("/api/v1/coach/assignments/{$assignmentId}/status", [
                'status' => 'paused',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'paused');

        $otherCoach = User::factory()->create(['role' => 'coach']);
        $this->join($organization, $otherCoach, 'coach');
        $this->actingAs($otherCoach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson("/api/v1/coach/programs/{$programId}")
            ->assertForbidden();

        $this->actingAs($otherCoach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->patchJson("/api/v1/coach/schedule/{$workout->id}/reschedule", [
                'scheduled_for' => today()->addDays(9)->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_coach_can_create_and_cancel_an_athlete_invitation_through_the_mobile_api(): void
    {
        Mail::fake();
        [$organization, $coach] = $this->team();

        $invitationId = $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson('/api/v1/coach/invitations', [
                'name' => 'New Athlete',
                'email' => 'new.athlete@example.com',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.email_sent', true)
            ->json('data.id');

        $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->deleteJson("/api/v1/coach/invitations/{$invitationId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'invite.cancelled',
            'entity_id' => $invitationId,
        ]);
    }

    public function test_message_api_only_exposes_participant_conversations(): void
    {
        [$organization, $coach, $athlete] = $this->team();
        $conversation = Conversation::create([
            'organization_id' => $organization->id,
            'type' => 'direct',
            'subject' => 'Training review',
        ]);
        $conversation->participants()->attach([
            $coach->id => ['role' => 'coach'],
            $athlete->id => ['role' => 'athlete'],
        ]);

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson("/api/v1/messages/{$conversation->id}", ['body' => 'Session complete.'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Session complete.');

        $outsider = User::factory()->create(['role' => 'athlete']);
        $this->join($organization, $outsider, 'athlete');
        $this->actingAs($outsider, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson("/api/v1/messages/{$conversation->id}")
            ->assertNotFound();
    }

    public function test_api_errors_use_the_versioned_envelope(): void
    {
        $this->getJson('/api/v1/app/home')
            ->assertUnauthorized()
            ->assertJsonPath('data', null)
            ->assertJsonPath('error.code', 'unauthenticated')
            ->assertJsonStructure(['data', 'meta', 'links', 'error']);

        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['fields' => ['email', 'password', 'device_name']]]);

        [$organization, $coach, $athlete] = $this->team();
        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/coach/home')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/messages/999999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    /** @return array{Organization, User, User} */
    private function team(): array
    {
        $organization = Organization::create([
            'name' => 'API Team',
            'slug' => 'api-team',
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

    private function assignedProgram(Organization $organization, User $coach, User $athlete): ProgramAssignment
    {
        $program = TrainingProgram::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'title' => 'API strength block',
            'goal' => 'Build strength',
            'status' => 'active',
            'is_template' => true,
        ]);
        $session = $program->sessions()->create([
            'organization_id' => $organization->id,
            'title' => 'Heavy day',
            'focus' => 'Strength',
            'status' => 'scheduled',
            'day_offset' => 0,
        ]);
        $session->prescribedExercises()->create([
            'organization_id' => $organization->id,
            'sort_order' => 1,
            'name' => 'Trap bar deadlift',
            'target_sets' => 1,
            'target_reps' => '5',
            'target_load' => '100',
            'unit' => 'kg',
            'rest_seconds' => 120,
        ]);

        return app(ProgramScheduleService::class)->assign($program, $athlete, $coach, today()->toDateString());
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
