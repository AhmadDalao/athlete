<?php

namespace Tests\Feature;

use App\Livewire\Admin\OrganizationsTable;
use App\Livewire\Athlete\ProgressPanel;
use App\Livewire\Coach\AthleteDetail;
use App\Livewire\Coach\ProgramDetail;
use App\Models\CoachAthleteAssignment;
use App\Models\CoachNote;
use App\Models\MembershipPermissionOverride;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\ProgressEntry;
use App\Models\ProgressPhoto;
use App\Models\TrainingProgram;
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

    public function test_program_assignment_permission_is_enforced_in_api_and_livewire(): void
    {
        [$organization, $coach, $membership] = $this->member('coach', globalRole: 'coach');
        [, $athlete] = $this->member('athlete', $organization);
        CoachAthleteAssignment::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => 'active',
            'started_at' => today(),
        ]);
        $program = TrainingProgram::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'title' => 'Protected preset',
            'status' => 'active',
            'is_template' => true,
        ]);
        MembershipPermissionOverride::create([
            'organization_membership_id' => $membership->id,
            'permission' => 'programs.assign',
            'allowed' => false,
        ]);

        $permissions = $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->json('data.user.permissions');

        $this->assertContains('programs.manage', $permissions);
        $this->assertNotContains('programs.assign', $permissions);

        $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson("/api/v1/coach/programs/{$program->id}/assignments", [
                'athlete_id' => $athlete->id,
                'starts_on' => today()->toDateString(),
            ])
            ->assertForbidden();

        Livewire::actingAs($coach)
            ->test(ProgramDetail::class, ['program' => $program])
            ->set('assignmentAthleteId', $athlete->id)
            ->set('assignmentStartsOn', today()->toDateString())
            ->call('assignProgram')
            ->assertForbidden();
    }

    public function test_denied_review_permissions_remove_private_athlete_data_from_api_and_web_tabs(): void
    {
        [$organization, $coach, $membership] = $this->member('coach', globalRole: 'coach');
        [, $athlete] = $this->member('athlete', $organization);
        CoachAthleteAssignment::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => 'active',
            'started_at' => today(),
        ]);
        ProgressEntry::create([
            'organization_id' => $organization->id,
            'athlete_id' => $athlete->id,
            'logged_on' => today(),
            'weight_kg' => 82,
        ]);
        CoachNote::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'body' => 'Private coaching note.',
            'visibility' => 'private',
        ]);
        ProgressPhoto::create([
            'organization_id' => $organization->id,
            'athlete_id' => $athlete->id,
            'uploaded_by' => $coach->id,
            'path' => 'progress-photos/private.jpg',
            'category' => 'progress',
            'visibility' => 'coaches',
            'taken_on' => today(),
        ]);
        foreach (['progress.review', 'athletes.notes'] as $permission) {
            MembershipPermissionOverride::create([
                'organization_membership_id' => $membership->id,
                'permission' => $permission,
                'allowed' => false,
            ]);
        }

        $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson("/api/v1/coach/athletes/{$athlete->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data.progress')
            ->assertJsonCount(0, 'data.photos')
            ->assertJsonCount(0, 'data.records')
            ->assertJsonCount(0, 'data.notes')
            ->assertJsonPath('data.progress_summary', null)
            ->assertJsonPath('data.capabilities.review_progress', false)
            ->assertJsonPath('data.capabilities.manage_notes', false);

        Livewire::actingAs($coach)
            ->test(AthleteDetail::class, ['athlete' => $athlete])
            ->assertDontSee('Progress media')
            ->assertDontSee('Coach notes')
            ->assertDontSee('Private coaching note.');
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
