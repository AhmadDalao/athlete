<?php

namespace Tests\Feature;

use App\Livewire\Athlete\WorkoutDetail;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RebuildSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_by_role(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);

        $this->actingAs($owner)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($coach)->get('/dashboard')->assertRedirect(route('coach.home'));
        $this->actingAs($athlete)->get('/dashboard')->assertRedirect(route('app.home'));
    }

    public function test_admin_only_dashboard_scope(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);

        $this->actingAs($coach)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($athlete)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_athlete_can_view_only_their_program(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);
        $otherAthlete = User::factory()->create(['role' => 'athlete']);

        $program = TrainingProgram::create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Assigned Plan',
            'status' => 'active',
        ]);

        $otherProgram = TrainingProgram::create([
            'coach_id' => $coach->id,
            'athlete_id' => $otherAthlete->id,
            'title' => 'Other Plan',
            'status' => 'active',
        ]);

        $this->actingAs($athlete)->get(route('app.programs.show', $program))->assertOk();
        $this->actingAs($athlete)->get(route('app.programs.show', $otherProgram))->assertForbidden();
    }

    public function test_athlete_can_mark_workout_complete(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);

        $program = TrainingProgram::create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Assigned Plan',
            'status' => 'active',
        ]);

        $session = TrainingSession::create([
            'training_program_id' => $program->id,
            'title' => 'Lower Strength',
            'scheduled_on' => now()->toDateString(),
            'status' => 'scheduled',
            'exercises' => [['name' => 'Squat', 'sets' => 3, 'reps' => 5]],
        ]);

        $this->actingAs($athlete)
            ->get(route('app.workouts.show', $session))
            ->assertOk();

        Livewire::test(WorkoutDetail::class, ['session' => $session])
            ->set('notes', 'Felt controlled.')
            ->call('mark', 'completed')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('workout_logs', [
            'training_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'status' => 'completed',
            'notes' => 'Felt controlled.',
        ]);
    }
}
