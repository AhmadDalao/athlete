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
use Tests\TestCase;

class WorkoutLogStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_athlete_can_create_and_update_their_workout_log(): void
    {
        [$athlete, $session] = $this->athleteSessionFixture();

        $createResponse = $this->actingAs($athlete)->post("/training/sessions/{$session->id}/log", [
            'completion_status' => WorkoutCompletionStatus::Partial->value,
            'performed_at' => now()->subDay()->toDateString(),
            'duration_minutes' => 41,
            'exertion_rating' => 6,
            'notes' => 'Started flat, finished better.',
        ]);

        $createResponse->assertRedirect('/training');
        $this->assertDatabaseHas('workout_logs', [
            'training_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'completion_status' => WorkoutCompletionStatus::Partial->value,
            'duration_minutes' => 41,
            'exertion_rating' => 6,
        ]);

        $updateResponse = $this->actingAs($athlete)->post("/training/sessions/{$session->id}/log", [
            'completion_status' => WorkoutCompletionStatus::Completed->value,
            'performed_at' => now()->subDay()->toDateString(),
            'duration_minutes' => 55,
            'exertion_rating' => 7,
            'notes' => 'Updated after cooldown.',
        ]);

        $updateResponse->assertRedirect('/training');
        $this->assertDatabaseCount('workout_logs', 1);
        $this->assertDatabaseHas('workout_logs', [
            'training_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'completion_status' => WorkoutCompletionStatus::Completed->value,
            'duration_minutes' => 55,
            'exertion_rating' => 7,
        ]);
    }

    public function test_athlete_cannot_log_a_session_that_is_not_theirs(): void
    {
        [$athlete, $session] = $this->athleteSessionFixture();
        $otherAthlete = User::factory()->create();
        $otherAthlete->assignRole(RoleName::Athlete);

        $response = $this->actingAs($otherAthlete)->post("/training/sessions/{$session->id}/log", [
            'completion_status' => WorkoutCompletionStatus::Missed->value,
            'performed_at' => now()->toDateString(),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('workout_logs', 0);
    }

    /**
     * @return array{User, TrainingSession}
     */
    private function athleteSessionFixture(): array
    {
        $coach = User::factory()->create();
        $athlete = User::factory()->create();

        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Conditioning',
            'notes' => null,
            'started_at' => now()->subDays(20)->toDateString(),
            'ended_at' => null,
        ]);

        $program = TrainingProgram::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Athlete Program',
            'goal' => 'Consistency',
            'status' => TrainingProgramStatus::Active,
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'notes' => null,
        ]);

        $session = TrainingSession::query()->create([
            'training_program_id' => $program->id,
            'title' => 'Main conditioning day',
            'scheduled_date' => now()->subDay()->toDateString(),
            'focus' => 'Work capacity',
            'instructions' => 'Stay even.',
            'exercises' => [
                ['name' => 'Run', 'prescription' => '30 min steady', 'note' => 'No drift'],
            ],
            'sort_order' => 1,
        ]);

        return [$athlete, $session];
    }
}
