<?php

namespace Tests\Feature;

use App\Livewire\Admin\CoachesTable;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\UserDetail;
use App\Livewire\Admin\UsersTable;
use App\Models\MembershipPermissionOverride;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPeopleIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_admin_people_table_and_export_are_tenant_scoped(): void
    {
        $admin = User::factory()->create(['role' => 'athlete']);
        $first = $this->organization('First Team');
        $second = $this->organization('Second Team');
        $this->join($first, $admin, 'organization_admin', true);
        $visible = User::factory()->create(['role' => 'athlete', 'email' => 'visible-athlete@example.com']);
        $hidden = User::factory()->create(['role' => 'athlete', 'email' => 'hidden-athlete@example.com']);
        $this->join($first, $visible, 'athlete');
        $this->join($second, $hidden, 'athlete');

        Livewire::actingAs($admin)
            ->test(UsersTable::class)
            ->assertSee('visible-athlete@example.com')
            ->assertDontSee('hidden-athlete@example.com');

        $response = $this->actingAs($admin)
            ->get(route('admin.users.export', ['role' => 'athlete']))
            ->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('visible-athlete@example.com', $csv);
        $this->assertStringNotContainsString('hidden-athlete@example.com', $csv);
    }

    public function test_organization_admin_cannot_open_another_organizations_user(): void
    {
        $admin = User::factory()->create(['role' => 'athlete']);
        $first = $this->organization('First Team');
        $second = $this->organization('Second Team');
        $this->join($first, $admin, 'organization_admin', true);
        $hidden = User::factory()->create(['role' => 'athlete']);
        $this->join($second, $hidden, 'athlete');

        $this->actingAs($admin)
            ->get(route('admin.users.show', $hidden))
            ->assertNotFound();
    }

    public function test_organization_admin_creates_an_organization_member_not_a_platform_admin(): void
    {
        $admin = User::factory()->create(['role' => 'athlete']);
        $organization = $this->organization('Creation Team');
        $this->join($organization, $admin, 'organization_admin', true);

        Livewire::actingAs($admin)
            ->test(UsersTable::class)
            ->set('name', 'Organization Admin')
            ->set('email', 'new-org-admin@example.com')
            ->set('phone', '+15550000009')
            ->set('newRole', 'organization_admin')
            ->set('password', 'Password123!')
            ->call('createUser')
            ->assertHasNoErrors();

        $created = User::query()->where('email', 'new-org-admin@example.com')->firstOrFail();
        $this->assertSame('athlete', $created->role);
        $this->assertSame($organization->id, $created->current_organization_id);
        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organization->id,
            'user_id' => $created->id,
            'role' => 'organization_admin',
            'status' => 'active',
        ]);
    }

    public function test_organization_admin_disables_only_the_current_membership(): void
    {
        $admin = User::factory()->create(['role' => 'athlete']);
        $first = $this->organization('First Team');
        $second = $this->organization('Second Team');
        $this->join($first, $admin, 'organization_admin', true);
        $sharedAthlete = User::factory()->create([
            'role' => 'athlete',
            'status' => 'active',
            'current_organization_id' => $first->id,
        ]);
        $this->join($first, $sharedAthlete, 'athlete');
        $this->join($second, $sharedAthlete, 'athlete');

        Livewire::actingAs($admin)
            ->test(UsersTable::class)
            ->call('toggleStatus', $sharedAthlete->id)
            ->assertHasNoErrors();

        $this->assertSame('active', $sharedAthlete->fresh()->status);
        $this->assertSame($second->id, $sharedAthlete->fresh()->current_organization_id);
        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $first->id,
            'user_id' => $sharedAthlete->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $second->id,
            'user_id' => $sharedAthlete->id,
            'status' => 'active',
        ]);
    }

    public function test_organization_admin_dashboard_metrics_are_tenant_scoped(): void
    {
        $admin = User::factory()->create(['role' => 'athlete']);
        $first = $this->organization('First Team');
        $second = $this->organization('Second Team');
        $this->join($first, $admin, 'organization_admin', true);
        $visibleCoach = User::factory()->create(['role' => 'coach']);
        $hiddenCoach = User::factory()->create(['role' => 'coach']);
        $this->join($first, $visibleCoach, 'coach');
        $this->join($second, $hiddenCoach, 'coach');
        TrainingProgram::create([
            'organization_id' => $first->id,
            'coach_id' => $visibleCoach->id,
            'title' => 'Visible Program',
            'status' => 'active',
        ]);
        TrainingProgram::create([
            'organization_id' => $second->id,
            'coach_id' => $hiddenCoach->id,
            'title' => 'Hidden Program',
            'status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertViewHas('stats', fn (array $stats): bool => $stats['users'] === 2
                && $stats['coaches'] === 1
                && $stats['activePrograms'] === 1);
    }

    public function test_denied_people_permission_is_enforced_by_the_route(): void
    {
        $admin = User::factory()->create(['role' => 'athlete']);
        $organization = $this->organization('Restricted Team');
        $membership = $this->join($organization, $admin, 'organization_admin', true);
        MembershipPermissionOverride::create([
            'organization_membership_id' => $membership->id,
            'permission' => 'users.manage',
            'allowed' => false,
        ]);

        $this->actingAs($admin->fresh())
            ->get(route('admin.users'))
            ->assertForbidden();
    }

    public function test_specialized_people_tables_cannot_be_tampered_into_another_role(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $coach = User::factory()->create(['role' => 'coach', 'email' => 'only-coach@example.com']);
        $athlete = User::factory()->create(['role' => 'athlete', 'email' => 'hidden-athlete@example.com']);

        Livewire::actingAs($owner)
            ->test(CoachesTable::class)
            ->set('role', 'athlete')
            ->assertSee($coach->email)
            ->assertDontSee($athlete->email);
    }

    public function test_organization_admin_cannot_tamper_user_detail_into_platform_mode(): void
    {
        $admin = User::factory()->create(['role' => 'athlete']);
        $organization = $this->organization('Tamper Team');
        $this->join($organization, $admin, 'organization_admin', true);
        $athlete = User::factory()->create(['role' => 'athlete', 'status' => 'active']);
        $membership = $this->join($organization, $athlete, 'athlete');

        Livewire::actingAs($admin)
            ->test(UserDetail::class, ['user' => $athlete])
            ->set('platformMode', true)
            ->set('role', 'coach')
            ->set('status', 'inactive')
            ->call('updateUser')
            ->assertHasNoErrors();

        $this->assertSame('active', $athlete->fresh()->status);
        $this->assertSame('coach', $membership->fresh()->role);
        $this->assertSame('inactive', $membership->fresh()->status);
    }

    private function organization(string $name): Organization
    {
        return Organization::create([
            'name' => $name,
            'slug' => str($name)->slug()->append('-'.str()->random(6))->toString(),
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
            'default_theme' => 'system',
        ]);
    }

    private function join(Organization $organization, User $user, string $role, bool $select = false): OrganizationMembership
    {
        $membership = OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        if ($select) {
            $user->forceFill(['current_organization_id' => $organization->id])->saveQuietly();
        }

        return $membership;
    }
}
