<?php

namespace Tests\Feature;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Models\CoachAthleteAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RosterManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_athletes_cannot_open_the_roster_workspace(): void
    {
        $athlete = User::factory()->create();
        $athlete->assignRole(RoleName::Athlete);

        $this->actingAs($athlete)
            ->get('/roster')
            ->assertForbidden();
    }

    public function test_coach_can_view_only_their_visible_assignments(): void
    {
        [$coachOne, $coachTwo, $athleteOne, $athleteTwo, $athleteThree] = $this->rosterFixture();

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coachOne->id,
            'athlete_id' => $athleteOne->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Strength block',
            'notes' => 'Primary roster athlete',
            'started_at' => now()->subDays(12)->toDateString(),
        ]);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coachTwo->id,
            'athlete_id' => $athleteTwo->id,
            'status' => CoachAthleteStatus::Paused,
            'goal' => 'Return to run',
            'notes' => null,
            'started_at' => now()->subDays(8)->toDateString(),
        ]);

        $this->actingAs($coachOne)
            ->get('/roster')
            ->assertInertia(fn (Assert $page) => $page
                ->component('roster/index')
                ->where('viewerRole', RoleName::Coach->value)
                ->where('summary.totalAssignments', 1)
                ->where('summary.activeAssignments', 1)
                ->where('summary.availableAthletes', 2)
                ->where('assignments.data.0.athlete.name', $athleteOne->name)
                ->where('assignments.data.0.coach.name', $coachOne->name)
            );
    }

    public function test_coach_can_create_an_assignment_for_themself_even_if_the_request_tries_to_set_another_coach(): void
    {
        [$coachOne, $coachTwo, $athleteOne] = $this->createCoachAndAthleteFixture();

        $response = $this->actingAs($coachOne)->post(route('roster.assignments.store', absolute: false), [
            'coach_id' => $coachTwo->id,
            'athlete_id' => $athleteOne->id,
            'status' => CoachAthleteStatus::Active->value,
            'goal' => 'Build work capacity',
            'notes' => 'Coach owns this athlete now.',
            'started_at' => now()->subDays(3)->toDateString(),
        ]);

        $response->assertRedirect('/roster');

        $this->assertDatabaseHas('coach_athlete_assignments', [
            'coach_id' => $coachOne->id,
            'athlete_id' => $athleteOne->id,
            'status' => CoachAthleteStatus::Active->value,
            'goal' => 'Build work capacity',
        ]);
    }

    public function test_admin_can_create_an_assignment_for_another_coach(): void
    {
        $admin = User::factory()->create();
        $coach = User::factory()->create();
        $athlete = User::factory()->create();

        $admin->assignRole(RoleName::Admin);
        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        $response = $this->actingAs($admin)->post(route('roster.assignments.store', absolute: false), [
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Paused->value,
            'goal' => 'Post-season reset',
            'notes' => 'Admin seeded this relationship.',
            'started_at' => now()->subDays(2)->toDateString(),
        ]);

        $response->assertRedirect('/roster');

        $this->assertDatabaseHas('coach_athlete_assignments', [
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Paused->value,
            'goal' => 'Post-season reset',
        ]);
    }

    public function test_coach_can_update_their_assignment_status_and_notes(): void
    {
        [$coach, $athlete] = $this->coachAssignmentFixture();

        $assignment = CoachAthleteAssignment::query()->firstOrFail();

        $response = $this->actingAs($coach)->patch(route('roster.assignments.update', $assignment, absolute: false), [
            'status' => CoachAthleteStatus::Archived->value,
            'goal' => 'Closed block',
            'notes' => 'Athlete moved off the roster cleanly.',
            'started_at' => $assignment->started_at?->toDateString(),
            'ended_at' => now()->toDateString(),
        ]);

        $response->assertRedirect('/roster');

        $this->assertDatabaseHas('coach_athlete_assignments', [
            'id' => $assignment->id,
            'status' => CoachAthleteStatus::Archived->value,
            'goal' => 'Closed block',
            'notes' => 'Athlete moved off the roster cleanly.',
        ]);
        $this->assertSame(now()->toDateString(), $assignment->fresh()->ended_at?->toDateString());
    }

    public function test_coach_cannot_update_someone_elses_assignment(): void
    {
        [$coachOne, $coachTwo, $athleteOne] = $this->createCoachAndAthleteFixture();
        $athleteTwo = User::factory()->create();
        $athleteTwo->assignRole(RoleName::Athlete);

        $assignment = CoachAthleteAssignment::query()->create([
            'coach_id' => $coachTwo->id,
            'athlete_id' => $athleteTwo->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Not your athlete',
            'notes' => null,
            'started_at' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->actingAs($coachOne)->patch(route('roster.assignments.update', $assignment, absolute: false), [
            'status' => CoachAthleteStatus::Paused->value,
            'goal' => 'Tampered goal',
            'notes' => 'Should never save.',
            'started_at' => now()->subDays(5)->toDateString(),
            'ended_at' => null,
        ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('coach_athlete_assignments', [
            'id' => $assignment->id,
            'status' => CoachAthleteStatus::Active->value,
            'goal' => 'Not your athlete',
        ]);
    }

    /**
     * @return array{User, User, User, User, User}
     */
    private function rosterFixture(): array
    {
        $coachOne = User::factory()->create();
        $coachTwo = User::factory()->create();
        $athleteOne = User::factory()->create();
        $athleteTwo = User::factory()->create();
        $athleteThree = User::factory()->create();

        $coachOne->assignRole(RoleName::Coach);
        $coachTwo->assignRole(RoleName::Coach);
        $athleteOne->assignRole(RoleName::Athlete);
        $athleteTwo->assignRole(RoleName::Athlete);
        $athleteThree->assignRole(RoleName::Athlete);

        return [$coachOne, $coachTwo, $athleteOne, $athleteTwo, $athleteThree];
    }

    /**
     * @return array{User, User, User}
     */
    private function createCoachAndAthleteFixture(): array
    {
        $coachOne = User::factory()->create();
        $coachTwo = User::factory()->create();
        $athlete = User::factory()->create();

        $coachOne->assignRole(RoleName::Coach);
        $coachTwo->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        return [$coachOne, $coachTwo, $athlete];
    }

    /**
     * @return array{User, User}
     */
    private function coachAssignmentFixture(): array
    {
        $coach = User::factory()->create();
        $athlete = User::factory()->create();

        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Base build',
            'notes' => 'Original assignment',
            'started_at' => now()->subDays(14)->toDateString(),
        ]);

        return [$coach, $athlete];
    }
}
