<?php

namespace Tests\Feature;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Models\CoachAthleteAssignment;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingProgramStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_create_a_program_with_its_first_session(): void
    {
        [$coach, $athlete] = $this->coachRosterFixture();

        $response = $this->actingAs($coach)->post('/training/programs', [
            'athlete_id' => $athlete->id,
            'title' => 'Off-Season Build',
            'goal' => 'Raise aerobic ceiling',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(28)->toDateString(),
            'notes' => 'Keep intensity honest.',
            'first_session_title' => 'Run intervals',
            'first_session_date' => now()->addDay()->toDateString(),
            'first_session_focus' => 'Threshold work',
            'first_session_instructions' => 'Relax into pace before you push.',
            'first_session_video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'first_session_exercises' => "Back squat | 4 | 6 | 120 kg | 150s | RPE 8 | Full depth\nBike flush | 1 | 10 min zone 1 | Easy spin | 0s | Nasal breathing | Calm exit",
        ]);

        $response->assertRedirect('/training');

        $this->assertDatabaseHas('training_programs', [
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Off-Season Build',
            'status' => TrainingProgramStatus::Active->value,
        ]);

        $program = TrainingProgram::query()->where('title', 'Off-Season Build')->firstOrFail();
        $session = $program->sessions()->firstOrFail();

        $this->assertSame('Run intervals', $session->title);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $session->video_url);
        $this->assertSame('Back squat', $session->exercises[0]['name']);
        $this->assertSame(4, $session->exercises[0]['sets']);
        $this->assertSame('6', $session->exercises[0]['reps']);
        $this->assertSame('120 kg', $session->exercises[0]['load']);
        $this->assertSame(150, $session->exercises[0]['rest_seconds']);
        $this->assertSame('RPE 8', $session->exercises[0]['target']);
        $this->assertSame('Full depth', $session->exercises[0]['note']);
    }

    public function test_coach_can_add_a_session_to_their_existing_program(): void
    {
        [$coach, $athlete] = $this->coachRosterFixture();

        $program = TrainingProgram::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Competition Prep',
            'goal' => 'Stay explosive',
            'status' => TrainingProgramStatus::Active,
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(),
            'notes' => null,
        ]);

        $response = $this->actingAs($coach)->post("/training/programs/{$program->id}/sessions", [
            'title' => 'Aerobic reset',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'focus' => 'Low-intensity volume',
            'instructions' => 'Keep breathing easy.',
            'video_url' => 'https://cdn.example.com/workouts/aerobic-reset.mp4',
            'exercises' => "Bike | 40 min zone 2 | No surges\nMobility | 3 rounds | Move slowly",
        ]);

        $response->assertRedirect('/training');

        $session = TrainingSession::query()->where('training_program_id', $program->id)->firstOrFail();

        $this->assertSame('Aerobic reset', $session->title);
        $this->assertSame('https://cdn.example.com/workouts/aerobic-reset.mp4', $session->video_url);
        $this->assertSame('Bike', $session->exercises[0]['name']);
        $this->assertSame('40 min zone 2', $session->exercises[0]['prescription']);
        $this->assertNull($session->exercises[0]['sets']);
        $this->assertSame('No surges', $session->exercises[0]['note']);
    }

    public function test_coach_cannot_create_a_program_for_an_unassigned_athlete(): void
    {
        $coach = User::factory()->create();
        $athlete = User::factory()->create();

        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        $response = $this->actingAs($coach)->post('/training/programs', [
            'athlete_id' => $athlete->id,
            'title' => 'Bad idea block',
            'goal' => 'Nope',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'notes' => null,
            'first_session_title' => 'Session one',
            'first_session_date' => now()->addDay()->toDateString(),
            'first_session_focus' => null,
            'first_session_instructions' => null,
            'first_session_exercises' => null,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('training_programs', 0);
    }

    /**
     * @return array{User, User}
     */
    private function coachRosterFixture(): array
    {
        $coach = User::factory()->create();
        $athlete = User::factory()->create();

        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Performance block',
            'notes' => null,
            'started_at' => now()->subDays(15)->toDateString(),
            'ended_at' => null,
        ]);

        return [$coach, $athlete];
    }
}
