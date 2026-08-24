<?php

namespace Tests\Feature;

use App\Models\MembershipPermissionOverride;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\ProgressEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_login_returns_a_sanctum_token_and_organization_context(): void
    {
        [$organization, $user] = $this->athleteInOrganization('North Team');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => strtoupper($user->email),
            'password' => 'password',
            'device_name' => 'Android test phone',
        ]);

        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.current_organization_id', $organization->id)
            ->assertJsonPath('data.user.permissions.0', 'athlete.access')
            ->assertJsonPath('data.organizations.0.id', $organization->id)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user', 'organizations']]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_web_login_normalizes_email_and_redirects_to_the_role_workspace(): void
    {
        [, $athlete] = $this->athleteInOrganization('Login Team');

        $this->post(route('login.store'), [
            'email' => '  '.strtoupper($athlete->email).'  ',
            'password' => 'password',
        ])->assertRedirect(route('app.home'));

        $this->assertAuthenticatedAs($athlete);
    }

    public function test_organization_context_prevents_cross_organization_progress_leaks(): void
    {
        [$north, $athlete] = $this->athleteInOrganization('North Team');
        $south = Organization::create([
            'name' => 'South Team',
            'slug' => 'south-team',
            'status' => 'active',
        ]);
        OrganizationMembership::create([
            'organization_id' => $south->id,
            'user_id' => $athlete->id,
            'role' => 'athlete',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        ProgressEntry::create([
            'organization_id' => $north->id,
            'athlete_id' => $athlete->id,
            'logged_on' => today(),
            'notes' => 'North-only progress',
        ]);
        ProgressEntry::create([
            'organization_id' => $south->id,
            'athlete_id' => $athlete->id,
            'logged_on' => today(),
            'notes' => 'South-only progress',
        ]);

        $this->actingAs($athlete)
            ->get(route('app.progress'))
            ->assertOk()
            ->assertSee('North-only progress')
            ->assertDontSee('South-only progress');
    }

    public function test_api_rejects_an_organization_the_user_cannot_access(): void
    {
        [, $athlete] = $this->athleteInOrganization('Assigned Team');
        $other = Organization::create([
            'name' => 'Private Team',
            'slug' => 'private-team',
            'status' => 'active',
        ]);

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $other->id)
            ->getJson('/api/v1/auth/me')
            ->assertForbidden();
    }

    public function test_organization_owner_cannot_be_denied_core_organization_access_by_override(): void
    {
        $owner = User::factory()->create(['role' => 'athlete']);
        $organization = Organization::create([
            'owner_id' => $owner->id,
            'name' => 'Owner Team',
            'slug' => 'owner-team',
            'status' => 'active',
        ]);
        $membership = OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => 'organization_owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);
        MembershipPermissionOverride::create([
            'organization_membership_id' => $membership->id,
            'permission' => 'admin.access',
            'allowed' => false,
        ]);
        $owner->forceFill(['current_organization_id' => $organization->id])->save();

        $this->assertTrue($owner->fresh()->hasPermission('admin.access'));
        $this->assertFalse($owner->fresh()->hasPermission('admin.settings'));
    }

    public function test_user_can_persist_theme_preference(): void
    {
        [, $athlete] = $this->athleteInOrganization('Theme Team');

        $this->actingAs($athlete)
            ->postJson(route('appearance.update'), ['theme' => 'dark'])
            ->assertOk()
            ->assertJsonPath('data.theme_preference', 'dark');

        $this->assertDatabaseHas('users', [
            'id' => $athlete->id,
            'theme_preference' => 'dark',
        ]);
    }

    public function test_user_can_switch_to_an_active_organization_membership(): void
    {
        [$north, $athlete] = $this->athleteInOrganization('North Team');
        $south = Organization::create([
            'name' => 'South Team',
            'slug' => 'south-team',
            'status' => 'active',
        ]);
        OrganizationMembership::create([
            'organization_id' => $south->id,
            'user_id' => $athlete->id,
            'role' => 'athlete',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($athlete)
            ->post(route('organizations.select', $south))
            ->assertRedirect(route('app.home'))
            ->assertSessionHas('active_organization_id', $south->id);

        $this->assertNotSame($north->id, $south->id);
        $this->assertSame($south->id, $athlete->fresh()->current_organization_id);
    }

    public function test_active_organization_membership_is_authoritative_for_role_and_permissions(): void
    {
        $organization = Organization::create([
            'name' => 'Role Boundary Team',
            'slug' => 'role-boundary-team',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'role' => 'coach',
            'current_organization_id' => $organization->id,
        ]);
        $user->permissions()->create(['permission' => 'programs.manage']);
        OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'athlete',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $user = $user->fresh();

        $this->assertTrue($user->isAthlete());
        $this->assertFalse($user->isCoach());
        $this->assertTrue($user->hasPermission('athlete.access'));
        $this->assertFalse($user->hasPermission('coach.access'));
        $this->assertFalse($user->hasPermission('programs.manage'));
        $this->assertSame(route('app.home'), $user->landingPath());

        $this->actingAs($user)->get(route('coach.home'))->assertForbidden();
        $this->actingAs($user)->get(route('app.home'))->assertOk();
    }

    public function test_switching_organizations_switches_the_users_effective_role(): void
    {
        $user = User::factory()->create(['role' => 'athlete']);
        $coachOrganization = Organization::create(['name' => 'Coach Team', 'slug' => 'coach-team', 'status' => 'active']);
        $athleteOrganization = Organization::create(['name' => 'Athlete Team', 'slug' => 'athlete-team', 'status' => 'active']);

        OrganizationMembership::create([
            'organization_id' => $coachOrganization->id,
            'user_id' => $user->id,
            'role' => 'coach',
            'status' => 'active',
            'joined_at' => now(),
        ]);
        OrganizationMembership::create([
            'organization_id' => $athleteOrganization->id,
            'user_id' => $user->id,
            'role' => 'athlete',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $user->forceFill(['current_organization_id' => $coachOrganization->id])->saveQuietly();
        $this->assertTrue($user->fresh()->isCoach());
        $this->assertSame(route('coach.home'), $user->fresh()->landingPath());

        $user->forceFill(['current_organization_id' => $athleteOrganization->id])->saveQuietly();
        $this->assertTrue($user->fresh()->isAthlete());
        $this->assertFalse($user->fresh()->isCoach());
        $this->assertSame(route('app.home'), $user->fresh()->landingPath());
    }

    public function test_platform_admin_access_is_not_downgraded_by_an_organization_membership(): void
    {
        $organization = Organization::create(['name' => 'Platform Team', 'slug' => 'platform-team', 'status' => 'active']);
        $admin = User::factory()->create([
            'role' => 'admin',
            'current_organization_id' => $organization->id,
        ]);
        OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'role' => 'athlete',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $admin = $admin->fresh();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isAthlete());
        $this->assertTrue($admin->hasPermission('admin.access'));
        $this->assertSame(route('admin.dashboard'), $admin->landingPath());
    }

    public function test_demo_seed_data_is_attached_to_the_default_organization(): void
    {
        $this->seed();

        $coach = User::query()->where('email', 'coach@throughline.test')->firstOrFail();
        $athlete = User::query()->where('email', 'athlete@throughline.test')->firstOrFail();

        $this->assertNotNull($coach->current_organization_id);
        $this->assertSame($coach->current_organization_id, $athlete->current_organization_id);
        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $coach->current_organization_id,
            'user_id' => $coach->id,
            'role' => 'coach',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('training_programs', [
            'organization_id' => $coach->current_organization_id,
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
        ]);
    }

    /** @return array{Organization, User} */
    private function athleteInOrganization(string $name): array
    {
        $athlete = User::factory()->create(['role' => 'athlete']);
        $organization = Organization::create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'status' => 'active',
        ]);
        OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $athlete->id,
            'role' => 'athlete',
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $athlete->forceFill(['current_organization_id' => $organization->id])->save();

        return [$organization, $athlete->fresh()];
    }
}
