<?php

namespace Tests\Feature;

use App\Livewire\Admin\OrganizationDetail;
use App\Livewire\Admin\OrganizationMemberPermissions;
use App\Livewire\Admin\OrganizationsTable;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_platform_staff_with_permission_can_open_organization_control(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $admin = User::factory()->create(['role' => 'admin']);
        $organizationAdmin = User::factory()->create(['role' => 'athlete']);
        $organization = $this->organization('Member Team', $organizationAdmin);
        OrganizationMembership::query()->where('organization_id', $organization->id)->where('user_id', $organizationAdmin->id)->update(['role' => 'organization_admin']);

        $this->actingAs($owner)->get(route('admin.organizations'))->assertOk();
        $this->actingAs($admin)->get(route('admin.organizations'))->assertOk();
        $this->actingAs($organizationAdmin->fresh())->get(route('admin.organizations'))->assertForbidden();
    }

    public function test_platform_owner_can_create_an_organization_with_a_protected_owner(): void
    {
        $platformOwner = User::factory()->create(['role' => 'owner']);
        $organizationOwner = User::factory()->create(['role' => 'coach', 'email' => 'studio-owner@example.com']);

        Livewire::actingAs($platformOwner)
            ->test(OrganizationsTable::class)
            ->set('name', 'Velocity Studio')
            ->set('slug', 'velocity-studio')
            ->set('ownerEmail', $organizationOwner->email)
            ->set('timezone', 'Asia/Riyadh')
            ->set('defaultTheme', 'dark')
            ->set('planKey', 'coach-plus')
            ->call('createOrganization')
            ->assertHasNoErrors();

        $organization = Organization::query()->where('slug', 'velocity-studio')->firstOrFail();
        $this->assertSame($organizationOwner->id, $organization->owner_id);
        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organization->id,
            'user_id' => $organizationOwner->id,
            'role' => 'organization_owner',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'action' => 'organization.created',
            'entity_id' => $organization->id,
        ]);
    }

    public function test_organization_owner_membership_cannot_be_demoted_or_disabled(): void
    {
        $platformOwner = User::factory()->create(['role' => 'owner']);
        $organizationOwner = User::factory()->create(['role' => 'coach']);
        $organization = $this->organization('Protected Team', $organizationOwner);
        $membership = $organization->memberships()->where('user_id', $organizationOwner->id)->firstOrFail();

        Livewire::actingAs($platformOwner)
            ->test(OrganizationDetail::class, ['organization' => $organization])
            ->set("membershipRoles.{$membership->id}", 'athlete')
            ->call('saveMemberRole', $membership->id)
            ->call('toggleMemberStatus', $membership->id)
            ->assertHasNoErrors();

        $membership->refresh();
        $this->assertSame('organization_owner', $membership->role);
        $this->assertSame('active', $membership->status);
    }

    public function test_reassigning_owner_keeps_the_previous_owner_as_an_active_admin(): void
    {
        $platformOwner = User::factory()->create(['role' => 'owner']);
        $previousOwner = User::factory()->create(['role' => 'coach']);
        $newOwner = User::factory()->create(['role' => 'coach', 'email' => 'new-owner@example.com']);
        $organization = $this->organization('Ownership Team', $previousOwner);

        Livewire::actingAs($platformOwner)
            ->test(OrganizationDetail::class, ['organization' => $organization])
            ->set('ownerEmail', $newOwner->email)
            ->call('saveOrganization')
            ->assertHasNoErrors();

        $this->assertSame($newOwner->id, $organization->fresh()->owner_id);
        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organization->id,
            'user_id' => $newOwner->id,
            'role' => 'organization_owner',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organization->id,
            'user_id' => $previousOwner->id,
            'role' => 'organization_admin',
            'status' => 'active',
        ]);
    }

    public function test_member_filters_and_exports_respect_the_selected_organization(): void
    {
        $platformOwner = User::factory()->create(['role' => 'owner']);
        $firstOwner = User::factory()->create(['role' => 'coach']);
        $secondOwner = User::factory()->create(['role' => 'coach']);
        $first = $this->organization('First Team', $firstOwner);
        $second = $this->organization('Second Team', $secondOwner);
        $firstAthlete = User::factory()->create(['role' => 'athlete', 'email' => 'first-athlete@example.com']);
        $secondAthlete = User::factory()->create(['role' => 'athlete', 'email' => 'second-athlete@example.com']);

        OrganizationMembership::create(['organization_id' => $first->id, 'user_id' => $firstAthlete->id, 'role' => 'athlete', 'status' => 'active', 'joined_at' => now()]);
        OrganizationMembership::create(['organization_id' => $second->id, 'user_id' => $secondAthlete->id, 'role' => 'athlete', 'status' => 'active', 'joined_at' => now()]);

        Livewire::actingAs($platformOwner)
            ->test(OrganizationDetail::class, ['organization' => $first])
            ->set('search', 'first-athlete')
            ->assertSee('first-athlete@example.com')
            ->assertDontSee('second-athlete@example.com');

        $response = $this->actingAs($platformOwner)
            ->get(route('admin.organizations.members.export', ['organization' => $first, 'role' => 'athlete']))
            ->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('first-athlete@example.com', $csv);
        $this->assertStringNotContainsString('second-athlete@example.com', $csv);
    }

    public function test_organization_admin_can_apply_audited_member_permission_overrides(): void
    {
        $organizationAdmin = User::factory()->create(['role' => 'athlete']);
        $coach = User::factory()->create(['role' => 'coach']);
        $organization = $this->organization('Permission Team', User::factory()->create(['role' => 'coach']));
        OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $organizationAdmin->id,
            'role' => 'organization_admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership = OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $coach->id,
            'role' => 'coach',
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $organizationAdmin->forceFill(['current_organization_id' => $organization->id])->saveQuietly();
        $coach->forceFill(['current_organization_id' => $organization->id])->saveQuietly();

        Livewire::actingAs($organizationAdmin)
            ->test(OrganizationMemberPermissions::class, ['organization' => $organization, 'membership' => $membership])
            ->set('access.messages__send', 'deny')
            ->set('access.photos__manage', 'allow')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($coach->fresh()->hasPermission('messages.send'));
        $this->assertTrue($coach->fresh()->hasPermission('photos.manage'));
        $this->assertDatabaseHas('membership_permission_overrides', [
            'organization_membership_id' => $membership->id,
            'permission' => 'messages.send',
            'allowed' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'action' => 'organization.member_permissions',
            'entity_id' => $membership->id,
        ]);
    }

    public function test_organization_admin_cannot_manage_another_organizations_member_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'athlete']);
        $first = $this->organization('Admin Team', User::factory()->create(['role' => 'coach']));
        $second = $this->organization('Foreign Team', User::factory()->create(['role' => 'coach']));
        OrganizationMembership::create(['organization_id' => $first->id, 'user_id' => $admin->id, 'role' => 'organization_admin', 'status' => 'active', 'joined_at' => now()]);
        $foreignMembership = OrganizationMembership::create(['organization_id' => $second->id, 'user_id' => User::factory()->create()->id, 'role' => 'athlete', 'status' => 'active', 'joined_at' => now()]);
        $admin->forceFill(['current_organization_id' => $first->id])->saveQuietly();

        Livewire::actingAs($admin)
            ->test(OrganizationMemberPermissions::class, ['organization' => $second, 'membership' => $foreignMembership])
            ->assertForbidden();
    }

    public function test_changing_a_member_role_removes_stale_permission_overrides(): void
    {
        $platformOwner = User::factory()->create(['role' => 'owner']);
        $organization = $this->organization('Role Reset Team', User::factory()->create(['role' => 'coach']));
        $member = User::factory()->create(['role' => 'coach']);
        $membership = OrganizationMembership::create(['organization_id' => $organization->id, 'user_id' => $member->id, 'role' => 'coach', 'status' => 'active', 'joined_at' => now()]);
        $membership->permissionOverrides()->create(['permission' => 'programs.manage', 'allowed' => false]);

        Livewire::actingAs($platformOwner)
            ->test(OrganizationDetail::class, ['organization' => $organization])
            ->set("membershipRoles.{$membership->id}", 'athlete')
            ->call('saveMemberRole', $membership->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('membership_permission_overrides', [
            'organization_membership_id' => $membership->id,
        ]);
    }

    private function organization(string $name, User $owner): Organization
    {
        $organization = Organization::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
            'default_theme' => 'system',
        ]);
        OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => 'organization_owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $owner->forceFill(['current_organization_id' => $organization->id])->saveQuietly();

        return $organization;
    }
}
