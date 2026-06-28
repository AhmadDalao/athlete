<?php

namespace Tests\Feature;

use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Models\CoachAthleteAssignment;
use App\Models\DeviceConnection;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CoachDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_coach_directory_renders_real_coach_data(): void
    {
        $coach = User::factory()->create([
            'name' => 'Nadia Stone',
            'email' => 'nadia@example.test',
            'primary_goal' => 'Strength and recovery coaching',
        ]);
        $athlete = User::factory()->create();

        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Build strength',
            'notes' => null,
            'started_at' => now()->subDays(10)->toDateString(),
        ]);

        TrainingProgram::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Summer Strength',
            'goal' => 'Build force output',
            'status' => TrainingProgramStatus::Active,
            'start_date' => now()->subDays(4)->toDateString(),
            'end_date' => now()->addDays(21)->toDateString(),
        ]);

        DeviceConnection::query()->create([
            'user_id' => $athlete->id,
            'provider' => DeviceProvider::Whoop,
            'status' => DeviceConnectionStatus::Connected,
            'auth_type' => 'oauth',
            'external_user_id' => 'whoop-athlete-directory',
            'access_token' => 'token',
            'refresh_token' => 'refresh-token',
            'token_expires_at' => now()->addHour(),
            'last_synced_at' => now()->subHour(),
        ]);

        $this->get(route('coaches.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('coaches/index')
                ->where('summary.totalCoaches', 1)
                ->where('summary.activePrograms', 1)
                ->where('coaches.0.name', 'Nadia Stone')
                ->where('coaches.0.rosterCount', 1)
                ->where('coaches.0.connectedAthletesCount', 1)
                ->where('coaches.0.whoopRosterCount', 1)
            );
    }

    public function test_public_coach_directory_can_filter_by_search_term(): void
    {
        $matchingCoach = User::factory()->create([
            'name' => 'Rami Haddad',
            'primary_goal' => 'Sprint performance',
        ]);
        $otherCoach = User::factory()->create([
            'name' => 'Leila Morse',
            'primary_goal' => 'Hypertrophy',
        ]);

        $matchingCoach->assignRole(RoleName::Coach);
        $otherCoach->assignRole(RoleName::Coach);

        $this->get(route('coaches.index', ['q' => 'Sprint']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('coaches/index')
                ->where('filters.q', 'Sprint')
                ->where('summary.totalCoaches', 1)
                ->where('coaches.0.name', 'Rami Haddad')
            );
    }
}
