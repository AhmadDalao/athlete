<?php

namespace Tests\Feature;

use App\Enums\BillingInterval;
use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\MembershipStatus;
use App\Enums\RoleName;
use App\Models\CoachAthleteAssignment;
use App\Models\DeviceConnection;
use App\Models\Membership;
use App\Models\SubscriptionPlan;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SearchIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_searches_across_core_operational_records(): void
    {
        $admin = User::factory()->create();
        $coach = User::factory()->create(['name' => 'Coach Lane']);
        $athlete = User::factory()->create(['name' => 'Mona Athlete', 'email' => 'mona@example.com']);
        $plan = SubscriptionPlan::query()->create([
            'slug' => 'search-monthly',
            'name' => 'Search Monthly',
            'description' => null,
            'billing_interval' => BillingInterval::Monthly,
            'duration_days' => 30,
            'price' => 99,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $admin->assignRole(RoleName::Admin);
        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        TrainingProgram::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Mona Strength Block',
            'goal' => 'Build strength',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        Membership::query()->create([
            'user_id' => $athlete->id,
            'subscription_plan_id' => $plan->id,
            'status' => MembershipStatus::Active,
            'starts_at' => now()->subDays(2)->toDateString(),
            'renews_at' => now()->addDays(28)->toDateString(),
            'ends_at' => now()->addDays(28)->toDateString(),
            'price' => 99,
            'currency' => 'USD',
            'auto_renew' => true,
        ]);

        DeviceConnection::query()->create([
            'user_id' => $athlete->id,
            'provider' => DeviceProvider::Whoop,
            'status' => DeviceConnectionStatus::Connected,
            'external_user_id' => 'mona-whoop',
            'last_synced_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('search.index', ['q' => 'Mona']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('search/index')
                ->where('query', 'Mona')
                ->where('sections.0.items.0.title', 'Mona Athlete')
                ->where('sections.1.items.0.title', 'Mona Strength Block')
                ->where('sections.2.items.0.title', 'Mona Athlete')
                ->where('sections.3.items.0.title', 'Mona Athlete')
            );
    }

    public function test_coach_search_does_not_expose_unassigned_athletes(): void
    {
        $coach = User::factory()->create();
        $assignedAthlete = User::factory()->create(['name' => 'Visible Athlete']);
        $hiddenAthlete = User::factory()->create(['name' => 'Hidden Athlete']);

        $coach->assignRole(RoleName::Coach);
        $assignedAthlete->assignRole(RoleName::Athlete);
        $hiddenAthlete->assignRole(RoleName::Athlete);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $assignedAthlete->id,
            'status' => CoachAthleteStatus::Active,
            'started_at' => now()->toDateString(),
        ]);

        $this->actingAs($coach)
            ->get(route('search.index', ['q' => 'Visible']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('search/index')
                ->where('sections.0.items.0.title', 'Visible Athlete')
            );

        $this->actingAs($coach)
            ->get(route('search.index', ['q' => 'Hidden']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('search/index')
                ->where('sections.0.items', [])
            );
    }
}
