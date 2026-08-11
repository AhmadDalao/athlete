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
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Android test phone',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.current_organization_id', $organization->id)
            ->assertJsonPath('data.organizations.0.id', $organization->id)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user', 'organizations']]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
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
