<?php

namespace Tests\Feature;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Models\AthleteInvitation;
use App\Models\CoachAthleteAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AthleteInvitationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_create_and_track_an_athlete_invitation(): void
    {
        $coach = User::factory()->create(['name' => 'Coach Invite']);
        $coach->assignRole(RoleName::Coach);

        $this->actingAs($coach)
            ->post(route('roster.invitations.store', absolute: false), [
                'name' => 'Invite Athlete',
                'email' => 'invite-athlete@example.com',
                'phone' => '+15550000001',
                'goal' => 'Start a strength block',
                'notes' => 'Comes from consultation.',
            ])
            ->assertRedirect(route('roster.invitations.index', absolute: false));

        $this->assertDatabaseHas('athlete_invitations', [
            'coach_id' => $coach->id,
            'email' => 'invite-athlete@example.com',
            'status' => AthleteInvitation::STATUS_PENDING,
            'goal' => 'Start a strength block',
        ]);

        $this->assertDatabaseHas('email_delivery_logs', [
            'recipient' => 'invite-athlete@example.com',
        ]);

        $this->actingAs($coach)
            ->get(route('roster.invitations.index', absolute: false))
            ->assertInertia(fn (Assert $page) => $page
                ->component('roster/invitations')
                ->where('summary.pending', 1)
                ->where('invitations.data.0.email', 'invite-athlete@example.com')
            );
    }

    public function test_invited_new_athlete_can_accept_and_get_assigned_to_the_coach(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole(RoleName::Coach);
        $token = 'accept-new-athlete-token';

        $invitation = AthleteInvitation::query()->create([
            'coach_id' => $coach->id,
            'invited_by_user_id' => $coach->id,
            'name' => 'New Athlete',
            'email' => 'new-athlete@example.com',
            'phone' => null,
            'goal' => 'Race prep',
            'token_hash' => hash('sha256', $token),
            'status' => AthleteInvitation::STATUS_PENDING,
            'expires_at' => now()->addDays(7),
        ]);

        $this->post(route('invitations.accept.store', $token, false), [
            'name' => 'New Athlete',
            'phone' => '+15550000002',
            'primary_goal' => 'Race prep',
            'preferred_contact_method' => 'email',
            'terms_accepted' => '1',
            'password' => 'Strong-password1',
            'password_confirmation' => 'Strong-password1',
        ])->assertRedirect('/app');

        $athlete = User::query()->where('email', 'new-athlete@example.com')->firstOrFail();

        $this->assertTrue($athlete->hasRole(RoleName::Athlete));
        $this->assertDatabaseHas('coach_athlete_assignments', [
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active->value,
            'goal' => 'Race prep',
        ]);
        $this->assertSame(AthleteInvitation::STATUS_ACCEPTED, $invitation->fresh()->status);
    }

    public function test_existing_athlete_can_accept_without_creating_duplicate_user(): void
    {
        $coach = User::factory()->create();
        $athlete = User::factory()->create([
            'email' => 'existing-athlete@example.com',
            'password' => Hash::make('Existing-password1'),
        ]);
        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);
        $token = 'accept-existing-athlete-token';

        AthleteInvitation::query()->create([
            'coach_id' => $coach->id,
            'invited_by_user_id' => $coach->id,
            'name' => 'Existing Athlete',
            'email' => 'existing-athlete@example.com',
            'phone' => null,
            'goal' => 'Return to training',
            'token_hash' => hash('sha256', $token),
            'status' => AthleteInvitation::STATUS_PENDING,
            'expires_at' => now()->addDays(7),
        ]);

        $this->post(route('invitations.accept.store', $token, false), [
            'name' => 'Existing Athlete',
            'phone' => '',
            'primary_goal' => 'Return to training',
            'preferred_contact_method' => 'email',
            'terms_accepted' => '1',
            'password' => 'Existing-password1',
            'password_confirmation' => 'Existing-password1',
        ])->assertRedirect('/app');

        $this->assertSame(1, User::query()->where('email', 'existing-athlete@example.com')->count());
        $this->assertDatabaseHas('coach_athlete_assignments', [
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active->value,
        ]);
    }

    public function test_coach_cannot_invite_athlete_owned_by_another_active_coach(): void
    {
        $coachOne = User::factory()->create();
        $coachTwo = User::factory()->create();
        $athlete = User::factory()->create(['email' => 'owned-athlete@example.com']);

        $coachOne->assignRole(RoleName::Coach);
        $coachTwo->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coachTwo->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Already owned',
            'started_at' => now()->toDateString(),
        ]);

        $this->actingAs($coachOne)
            ->post(route('roster.invitations.store', absolute: false), [
                'name' => 'Owned Athlete',
                'email' => 'owned-athlete@example.com',
                'goal' => 'Try hijack',
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('athlete_invitations', [
            'coach_id' => $coachOne->id,
            'email' => 'owned-athlete@example.com',
        ]);
    }
}
