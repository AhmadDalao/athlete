<?php

namespace Tests\Feature;

use App\Livewire\Admin\InvitationsTable;
use App\Livewire\Coach\InvitationsPanel;
use App\Livewire\Invite\AcceptInvite;
use App\Models\AthleteInvitation;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_admin_can_only_view_export_and_mutate_own_invitations(): void
    {
        Mail::fake();
        [$first, $admin, $firstCoach] = $this->organizationWithAdmin('First Invite Team');
        [$second, , $secondCoach] = $this->organizationWithAdmin('Second Invite Team');
        $visible = $this->invitation($first, $firstCoach, 'visible@example.com');
        $foreign = $this->invitation($second, $secondCoach, 'foreign@example.com');

        $component = Livewire::actingAs($admin)
            ->test(InvitationsTable::class)
            ->assertSee('visible@example.com')
            ->assertDontSee('foreign@example.com');

        try {
            $component->call('cancel', $foreign->id);
            $this->fail('A foreign invitation was mutable from the active organization.');
        } catch (ModelNotFoundException) {
            $this->assertTrue(true);
        }

        $this->assertSame('pending', $foreign->fresh()->status);
        $this->assertSame('pending', $visible->fresh()->status);

        $response = $this->actingAs($admin)
            ->get(route('admin.invitations.export'))
            ->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('visible@example.com', $csv);
        $this->assertStringNotContainsString('foreign@example.com', $csv);
    }

    public function test_invitation_delivery_and_audit_logs_keep_the_invitation_organization(): void
    {
        Mail::fake();
        [$organization, $admin, $coach] = $this->organizationWithAdmin('Logged Invite Team');
        $invitation = $this->invitation($organization, $coach, 'logged@example.com');

        Livewire::actingAs($admin)
            ->test(InvitationsTable::class)
            ->call('resend', $invitation->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('email_logs', [
            'organization_id' => $organization->id,
            'recipient' => 'logged@example.com',
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'action' => 'invite.resent',
            'entity_id' => $invitation->id,
        ]);
    }

    public function test_athlete_cannot_invoke_coach_invitation_component_directly(): void
    {
        $organization = Organization::create(['name' => 'Athlete Guard Team', 'slug' => 'athlete-guard-team', 'status' => 'active']);
        $athlete = User::factory()->create(['role' => 'coach', 'current_organization_id' => $organization->id]);
        OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $athlete->id,
            'role' => 'athlete',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Livewire::actingAs($athlete)
            ->test(InvitationsPanel::class)
            ->assertForbidden();
    }

    public function test_new_athlete_acceptance_creates_scoped_membership_profile_and_assignment(): void
    {
        [$organization, , $coach] = $this->organizationWithAdmin('Accepted Invite Team');
        $invitation = $this->invitation($organization, $coach, 'new-athlete@example.com');

        Livewire::test(AcceptInvite::class, ['token' => $invitation->token])
            ->set('name', 'New Athlete')
            ->set('password', 'secure-password')
            ->call('accept')
            ->assertRedirect(route('app.home'));

        $athlete = User::query()->where('email', 'new-athlete@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($athlete);
        $this->assertSame($organization->id, $athlete->current_organization_id);
        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organization->id,
            'user_id' => $athlete->id,
            'role' => 'athlete',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('athlete_profiles', ['organization_id' => $organization->id, 'user_id' => $athlete->id]);
        $this->assertDatabaseHas('coach_athlete_assignments', [
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'user_id' => $athlete->id,
            'action' => 'invite.accepted',
            'entity_id' => $invitation->id,
        ]);
    }

    public function test_existing_account_requires_its_current_password_and_keeps_global_role(): void
    {
        [$organization, , $coach] = $this->organizationWithAdmin('Existing Invite Team');
        $existing = User::factory()->create([
            'email' => 'existing@example.com',
            'password' => Hash::make('current-password'),
            'role' => 'coach',
        ]);
        $invitation = $this->invitation($organization, $coach, $existing->email);

        Livewire::test(AcceptInvite::class, ['token' => $invitation->token])
            ->set('name', $existing->name)
            ->set('password', 'wrong-password')
            ->call('accept')
            ->assertHasErrors('password');

        $this->assertGuest();
        $this->assertSame('pending', $invitation->fresh()->status);
        $this->assertDatabaseMissing('organization_memberships', [
            'organization_id' => $organization->id,
            'user_id' => $existing->id,
        ]);

        Livewire::test(AcceptInvite::class, ['token' => $invitation->token])
            ->set('name', $existing->name)
            ->set('password', 'current-password')
            ->call('accept')
            ->assertRedirect(route('app.home'));

        $this->assertSame('coach', $existing->fresh()->role);
        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organization->id,
            'user_id' => $existing->id,
            'role' => 'athlete',
        ]);
        $this->assertDatabaseHas('coach_athlete_assignments', [
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'athlete_id' => $existing->id,
        ]);
    }

    public function test_acceptance_rejects_invite_when_coach_is_no_longer_active(): void
    {
        [$organization, , $coach] = $this->organizationWithAdmin('Inactive Coach Invite Team');
        $invitation = $this->invitation($organization, $coach, 'blocked-athlete@example.com');
        OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $coach->id)
            ->update(['status' => 'inactive']);

        Livewire::test(AcceptInvite::class, ['token' => $invitation->token])
            ->set('name', 'Blocked Athlete')
            ->set('password', 'secure-password')
            ->call('accept')
            ->assertStatus(410);

        $this->assertDatabaseMissing('users', ['email' => 'blocked-athlete@example.com']);
        $this->assertSame('pending', $invitation->fresh()->status);
        $this->assertDatabaseCount('coach_athlete_assignments', 0);
    }

    /** @return array{Organization, User, User} */
    private function organizationWithAdmin(string $name): array
    {
        $organization = Organization::create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'status' => 'active',
        ]);
        $admin = User::factory()->create(['role' => 'athlete', 'current_organization_id' => $organization->id]);
        $coach = User::factory()->create(['role' => 'coach', 'current_organization_id' => $organization->id]);

        OrganizationMembership::create(['organization_id' => $organization->id, 'user_id' => $admin->id, 'role' => 'organization_admin', 'status' => 'active', 'joined_at' => now()]);
        OrganizationMembership::create(['organization_id' => $organization->id, 'user_id' => $coach->id, 'role' => 'coach', 'status' => 'active', 'joined_at' => now()]);

        return [$organization, $admin->fresh(), $coach];
    }

    private function invitation(Organization $organization, User $coach, string $email): AthleteInvitation
    {
        return AthleteInvitation::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'email' => $email,
            'token' => str()->random(48),
            'status' => 'pending',
            'expires_at' => now()->addWeek(),
        ]);
    }
}
