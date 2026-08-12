<?php

namespace Tests\Feature;

use App\Livewire\Admin\OrganizationsTable;
use App\Livewire\Athlete\ProgressPanel;
use App\Models\CoachAthleteAssignment;
use App\Models\MembershipPermissionOverride;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PermissionBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_livewire_component_rechecks_its_permission_without_route_middleware(): void
    {
        [$organization, $admin, $membership] = $this->member('organization_admin');

        MembershipPermissionOverride::create([
            'organization_membership_id' => $membership->id,
            'permission' => 'organizations.manage',
            'allowed' => false,
        ]);

        $admin->forceFill(['current_organization_id' => $organization->id])->saveQuietly();

        Livewire::actingAs($admin)
            ->test(OrganizationsTable::class)
            ->assertForbidden();
    }

    public function test_athlete_cannot_write_progress_when_the_action_permission_is_denied(): void
    {
        [$organization, $athlete, $membership] = $this->member('athlete');

        MembershipPermissionOverride::create([
            'organization_membership_id' => $membership->id,
            'permission' => 'progress.manage',
            'allowed' => false,
        ]);

        $athlete->forceFill(['current_organization_id' => $organization->id])->saveQuietly();

        Livewire::actingAs($athlete)
            ->test(ProgressPanel::class)
            ->set('loggedOn', today()->toDateString())
            ->set('weight', 80)
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseCount('progress_entries', 0);
    }

    public function test_coach_roster_uses_the_active_organization_role_not_the_global_role(): void
    {
        [$organization, $coach] = $this->member('coach');
        [, $athlete] = $this->member('athlete', $organization, 'coach');
        CoachAthleteAssignment::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => 'active',
            'started_at' => today(),
        ]);

        $coach->forceFill(['current_organization_id' => $organization->id])->saveQuietly();
        $athlete->forceFill(['current_organization_id' => $organization->id])->saveQuietly();

        $this->actingAs($coach)
            ->get(route('coach.athletes'))
            ->assertOk()
            ->assertSee($athlete->email);
    }

    /** @return array{Organization, User, OrganizationMembership} */
    private function member(string $role, ?Organization $organization = null, string $globalRole = 'athlete'): array
    {
        $organization ??= Organization::create([
            'name' => fake()->unique()->company(),
            'slug' => fake()->unique()->slug(),
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'role' => $globalRole,
            'current_organization_id' => $organization->id,
        ]);
        $membership = OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return [$organization, $user, $membership];
    }
}
