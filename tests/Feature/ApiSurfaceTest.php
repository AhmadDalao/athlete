<?php

namespace Tests\Feature;

use App\Models\AthleteCheckIn;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiSurfaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_athlete_can_fetch_core_api_surfaces(): void
    {
        $athlete = User::query()->where('email', 'athlete1@throughline.test')->firstOrFail();

        Sanctum::actingAs($athlete, [
            'profile:read',
            'dashboard:read',
            'training:read',
            'training:write',
            'progress:read',
            'progress:write',
            'membership:read',
            'wearable:read',
        ]);

        $this->getJson(route('api.v1.dashboard'))
            ->assertOk()
            ->assertJsonPath('data.viewer.email', 'athlete1@throughline.test')
            ->assertJsonPath('data.athlete.membership.planName', 'Athlete Performance Monthly');

        $this->getJson(route('api.v1.training'))
            ->assertOk()
            ->assertJsonPath('data.programs.data.0.title', 'HYROX Build Block');

        $this->getJson(route('api.v1.progress'))
            ->assertOk()
            ->assertJsonPath('data.athleteProfile.latestCheckIn.loggedDate', now()->toDateString());

        $this->getJson(route('api.v1.memberships'))
            ->assertOk()
            ->assertJsonPath('data.memberships.data.0.user.email', 'athlete1@throughline.test');

        $this->getJson(route('api.v1.wearables'))
            ->assertOk()
            ->assertJsonPath('data.connections.data.0.user.email', 'athlete1@throughline.test');
    }

    public function test_athlete_can_submit_check_in_and_workout_log_via_api(): void
    {
        $athlete = User::query()->where('email', 'athlete1@throughline.test')->firstOrFail();
        $session = TrainingSession::query()
            ->whereHas('program', fn ($query) => $query->where('athlete_id', $athlete->id))
            ->firstOrFail();

        Sanctum::actingAs($athlete, [
            'progress:write',
            'training:write',
        ]);

        $loggedDate = now()->addDay()->toDateString();

        $this->postJson(route('api.v1.progress.check-ins.store'), [
            'logged_date' => $loggedDate,
            'weight_kg' => 67.9,
            'calories_consumed' => 2550,
            'protein_grams' => 172,
            'water_liters' => 3.3,
            'energy_score' => 8,
        ])
            ->assertCreated()
            ->assertJsonPath('data.weightKg', 67.9)
            ->assertJsonPath('data.loggedDate', $loggedDate)
            ->assertJsonPath('data.proteinGrams', 172);

        $this->assertNotNull(
            AthleteCheckIn::query()
                ->where('user_id', $athlete->id)
                ->whereDate('logged_date', $loggedDate)
                ->where('protein_grams', 172)
                ->first()
        );

        $this->postJson(route('api.v1.training.sessions.workout-log.store', $session), [
            'completion_status' => 'completed',
            'duration_minutes' => 61,
            'exertion_rating' => 7,
            'notes' => 'API log worked.',
        ])
            ->assertOk()
            ->assertJsonPath('data.completionStatus', 'completed')
            ->assertJsonPath('data.durationMinutes', 61);
    }

    public function test_token_without_required_ability_is_blocked(): void
    {
        $athlete = User::query()->where('email', 'athlete1@throughline.test')->firstOrFail();

        Sanctum::actingAs($athlete, ['profile:read']);

        $this->getJson(route('api.v1.dashboard'))
            ->assertForbidden();
    }

    public function test_coach_can_fetch_roster_and_progress_but_not_admin_control(): void
    {
        $coach = User::query()->where('email', 'coach@throughline.test')->firstOrFail();

        Sanctum::actingAs($coach, [
            'dashboard:read',
            'roster:read',
            'training:read',
            'progress:read',
            'membership:read',
            'wearable:read',
        ]);

        $this->getJson(route('api.v1.roster'))
            ->assertOk()
            ->assertJsonPath('data.assignments.data.0.coach.email', 'coach@throughline.test');

        $this->getJson(route('api.v1.progress'))
            ->assertOk()
            ->assertJsonPath('data.athletes.data.0.coachName', 'Maya Carter');

        $this->getJson(route('api.v1.admin.control-center'))
            ->assertForbidden();
    }

    public function test_admin_can_fetch_control_center(): void
    {
        $admin = User::query()->where('email', 'admin@throughline.test')->firstOrFail();

        Sanctum::actingAs($admin, [
            'dashboard:read',
            'admin:read',
            'roster:read',
            'training:read',
            'progress:read',
            'membership:read',
            'wearable:read',
        ]);

        $this->getJson(route('api.v1.admin.control-center'))
            ->assertOk()
            ->assertJsonPath('data.summary.totalUsers', 6)
            ->assertJsonPath('data.summary.athletes', 3);
    }

    public function test_athlete_can_update_only_their_own_check_in_via_api(): void
    {
        $athlete = User::query()->where('email', 'athlete1@throughline.test')->firstOrFail();
        $otherAthleteCheckIn = AthleteCheckIn::query()
            ->whereHas('user', fn ($query) => $query->where('email', 'athlete2@throughline.test'))
            ->firstOrFail();

        Sanctum::actingAs($athlete, ['progress:write']);

        $this->patchJson(route('api.v1.progress.check-ins.update', $otherAthleteCheckIn), [
            'logged_date' => $otherAthleteCheckIn->logged_date?->toDateString(),
            'energy_score' => 9,
        ])
            ->assertNotFound();
    }
}
