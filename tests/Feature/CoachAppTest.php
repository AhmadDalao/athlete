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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CoachAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_open_home_and_only_sees_owned_work(): void
    {
        [$coach, $athlete, $program, $session] = $this->coachFixture();

        $this->actingAs($coach)
            ->get('/coach')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('coach-app/index')
                ->where('viewer.email', $coach->email)
                ->where('summary.assignedAthletes', 1)
                ->where('summary.activePrograms', 1)
                ->where('athletes.0.id', $athlete->id)
                ->where('programs.0.id', $program->id)
                ->where('schedule.0.id', $session->id)
                ->where('schedule.0.athlete.name', $athlete->name)
                ->missing('schedule.1')
            );
    }

    public function test_non_coaches_are_redirected_to_their_landing_path(): void
    {
        $athlete = User::factory()->create();
        $athlete->assignRole(RoleName::Athlete);

        $this->actingAs($athlete)
            ->get('/coach')
            ->assertRedirect('/app');
    }

    /**
     * @return array{User, User, TrainingProgram, TrainingSession}
     */
    private function coachFixture(): array
    {
        $coach = User::factory()->create(['name' => 'Coach Owner']);
        $otherCoach = User::factory()->create(['name' => 'Coach Hidden']);
        $athlete = User::factory()->create(['name' => 'Visible Athlete']);
        $hiddenAthlete = User::factory()->create(['name' => 'Hidden Athlete']);

        $coach->assignRole(RoleName::Coach);
        $otherCoach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);
        $hiddenAthlete->assignRole(RoleName::Athlete);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Build capacity',
            'notes' => null,
            'started_at' => now()->subDays(3)->toDateString(),
        ]);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $otherCoach->id,
            'athlete_id' => $hiddenAthlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Should stay hidden',
            'notes' => null,
            'started_at' => now()->subDays(3)->toDateString(),
        ]);

        $program = TrainingProgram::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Visible Program',
            'goal' => 'Own athlete work',
            'status' => TrainingProgramStatus::Active,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'notes' => null,
        ]);

        $hiddenProgram = TrainingProgram::query()->create([
            'coach_id' => $otherCoach->id,
            'athlete_id' => $hiddenAthlete->id,
            'title' => 'Hidden Program',
            'goal' => 'Other coach work',
            'status' => TrainingProgramStatus::Active,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'notes' => null,
        ]);

        $session = TrainingSession::query()->create([
            'training_program_id' => $program->id,
            'title' => 'Visible Session',
            'scheduled_date' => now()->addDay()->toDateString(),
            'focus' => 'Strength',
            'instructions' => null,
            'exercises' => [],
            'sort_order' => 1,
        ]);

        TrainingSession::query()->create([
            'training_program_id' => $hiddenProgram->id,
            'title' => 'Hidden Session',
            'scheduled_date' => now()->addDay()->toDateString(),
            'focus' => 'Conditioning',
            'instructions' => null,
            'exercises' => [],
            'sort_order' => 1,
        ]);

        return [$coach, $athlete, $program, $session];
    }
}
