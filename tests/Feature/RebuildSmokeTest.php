<?php

namespace Tests\Feature;

use App\Livewire\Admin\ContactSubmissionsTable;
use App\Livewire\Admin\InvitationsTable;
use App\Livewire\Admin\PermissionsPanel;
use App\Livewire\Admin\SettingsPanel;
use App\Livewire\Athlete\ProgressPanel;
use App\Livewire\Athlete\WorkoutDetail;
use App\Livewire\Coach\ProgramDetail;
use App\Livewire\Coach\ProgramsTable;
use App\Models\AthleteInvitation;
use App\Models\AuditLog;
use App\Models\CoachAthleteAssignment;
use App\Models\ContactSubmission;
use App\Models\EmailLog;
use App\Models\ProgressEntry;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RebuildSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_does_not_expose_seed_credentials_outside_local_environment(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Seed accounts')
            ->assertDontSee('owner@throughline.test')
            ->assertDontSee('Password for seeded accounts');
    }

    public function test_dashboard_redirects_by_role(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);

        $this->actingAs($owner)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($coach)->get('/dashboard')->assertRedirect(route('coach.home'));
        $this->actingAs($athlete)->get('/dashboard')->assertRedirect(route('app.home'));
    }

    public function test_admin_only_dashboard_scope(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);

        $this->actingAs($coach)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($athlete)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_athlete_can_view_only_their_program(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);
        $otherAthlete = User::factory()->create(['role' => 'athlete']);

        $program = TrainingProgram::create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Assigned Plan',
            'status' => 'active',
        ]);

        $otherProgram = TrainingProgram::create([
            'coach_id' => $coach->id,
            'athlete_id' => $otherAthlete->id,
            'title' => 'Other Plan',
            'status' => 'active',
        ]);

        $this->actingAs($athlete)->get(route('app.programs.show', $program))->assertOk();
        $this->actingAs($athlete)->get(route('app.programs.show', $otherProgram))->assertForbidden();
    }

    public function test_athlete_can_mark_workout_complete(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);

        $program = TrainingProgram::create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Assigned Plan',
            'status' => 'active',
        ]);

        $session = TrainingSession::create([
            'training_program_id' => $program->id,
            'title' => 'Lower Strength',
            'scheduled_on' => now()->toDateString(),
            'status' => 'scheduled',
            'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'exercises' => [[
                'name' => 'Squat',
                'sets' => 3,
                'reps' => 5,
                'rest' => '90 sec',
                'media_url' => 'https://youtu.be/abc123XYZ_0',
            ]],
        ]);

        $this->actingAs($athlete)
            ->get(route('app.workouts.show', $session))
            ->assertOk()
            ->assertSee('Workout media')
            ->assertSee('Exercise prescription')
            ->assertSee('https://www.youtube.com/embed/abc123XYZ_0', false);

        $this->assertSame(2, $session->mediaCount());

        Livewire::test(WorkoutDetail::class, ['session' => $session])
            ->set('notes', 'Felt controlled.')
            ->set('durationMinutes', '48')
            ->set('rpe', '7')
            ->set('setLogs.0.completed', true)
            ->set('setLogs.0.actual_reps', '5')
            ->set('setLogs.0.actual_load', '80kg')
            ->call('mark', 'completed')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('workout_logs', [
            'training_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'status' => 'completed',
            'duration_minutes' => 48,
            'rpe' => 7,
            'notes' => 'Felt controlled.',
        ]);

        $this->assertSame('90 sec', WorkoutLog::firstOrFail()->set_logs[0]['target_rest']);
    }

    public function test_athlete_can_save_and_filter_progress_entries(): void
    {
        $athlete = User::factory()->create(['role' => 'athlete']);

        ProgressEntry::create([
            'athlete_id' => $athlete->id,
            'logged_on' => now()->subDay()->toDateString(),
            'weight' => 82.4,
            'protein' => 155,
            'energy' => 7,
            'notes' => 'Previous entry',
        ]);

        Livewire::actingAs($athlete)
            ->test(ProgressPanel::class)
            ->set('loggedOn', now()->toDateString())
            ->set('weight', 82.1)
            ->set('calories', 2500)
            ->set('protein', 160)
            ->set('hydration', 3000)
            ->set('sleepQuality', 8)
            ->set('soreness', 3)
            ->set('energy', 8)
            ->set('notes', 'Solid training day')
            ->call('save')
            ->set('search', 'Solid')
            ->assertSee('Solid training day')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('progress_entries', [
            'athlete_id' => $athlete->id,
            'logged_on' => today()->toDateTimeString(),
            'protein' => 160,
            'notes' => 'Solid training day',
        ]);
    }

    public function test_admin_can_open_and_export_users(): void
    {
        $admin = User::factory()->create(['role' => 'owner']);
        $athlete = User::factory()->create(['role' => 'athlete', 'email' => 'export-athlete@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $athlete))
            ->assertOk()
            ->assertSee('export-athlete@example.com');

        $response = $this->actingAs($admin)
            ->get(route('admin.users.export', ['role' => 'athlete']))
            ->assertOk();

        $this->assertStringContainsString('export-athlete@example.com', $response->streamedContent());
    }

    public function test_coach_can_open_only_assigned_athlete_profile(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);
        $otherAthlete = User::factory()->create(['role' => 'athlete']);

        CoachAthleteAssignment::create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $this->actingAs($coach)
            ->get(route('coach.athletes.show', $athlete))
            ->assertOk()
            ->assertSee($athlete->email);

        $this->actingAs($coach)
            ->get(route('coach.athletes.show', $otherAthlete))
            ->assertForbidden();
    }

    public function test_coach_can_create_program_directly_for_assigned_athlete(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);

        CoachAthleteAssignment::create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $component = Livewire::actingAs($coach)
            ->withQueryParams(['athlete' => $athlete->id])
            ->test(ProgramsTable::class)
            ->assertSet('athleteId', $athlete->id)
            ->set('title', 'Speed Foundation')
            ->set('goal', 'Build repeat sprint capacity')
            ->set('startsOn', today()->toDateString())
            ->set('endsOn', today()->addWeeks(6)->toDateString())
            ->call('createProgram')
            ->assertHasNoErrors();

        $program = TrainingProgram::where('title', 'Speed Foundation')->firstOrFail();

        $component->assertRedirect(route('coach.programs.show', $program));
        $this->assertSame($coach->id, $program->coach_id);
        $this->assertSame($athlete->id, $program->athlete_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'program.created',
            'entity' => 'training_program',
            'entity_id' => $program->id,
        ]);
    }

    public function test_admin_can_resend_invitation_and_log_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'owner']);
        $coach = User::factory()->create(['role' => 'coach']);
        $invite = AthleteInvitation::create([
            'coach_id' => $coach->id,
            'email' => 'new-athlete@example.com',
            'name' => 'New Athlete',
            'token' => 'test-token',
            'status' => 'pending',
            'expires_at' => now()->addWeek(),
        ]);

        Livewire::actingAs($admin)
            ->test(InvitationsTable::class)
            ->call('resend', $invite->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('email_logs', [
            'recipient' => 'new-athlete@example.com',
            'type' => 'athlete_invite',
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'invite.resent',
            'entity' => 'athlete_invitation',
            'entity_id' => $invite->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.invitations.export', ['search' => 'new-athlete']))
            ->assertOk();

        $this->assertStringContainsString('new-athlete@example.com', $response->streamedContent());
    }

    public function test_admin_permissions_and_settings_write_audit_logs(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($owner)
            ->test(PermissionsPanel::class)
            ->set('selectedUserId', $admin->id)
            ->set('selectedPermissions', ['admin.access', 'users.manage'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'permissions.updated',
            'entity' => 'user',
            'entity_id' => $admin->id,
        ]);

        Livewire::actingAs($owner)
            ->test(SettingsPanel::class)
            ->set('settings.app_name', 'Throughline')
            ->set('settings.tagline', 'Coach OS')
            ->set('settings.support_email', 'support@example.com')
            ->set('settings.invite_expiry_days', '7')
            ->set('settings.homepage_headline', 'Train better')
            ->set('settings.homepage_subheadline', 'Simple coaching software')
            ->set('settings.contact_headline', 'Start your setup')
            ->set('settings.contact_subheadline', 'Tell us who you coach.')
            ->set('settings.contact_button_label', 'Request setup')
            ->set('settings.invite_email_subject', 'Invite')
            ->set('settings.invite_email_body', 'Accept here: {invite_link}')
            ->set('settings.pricing_headline', 'Simple memberships')
            ->set('settings.pricing_subheadline', 'Plans for athletes, coaches, and teams.')
            ->set('settings.plan_one_name', 'Athlete')
            ->set('settings.plan_one_price', '$29/mo')
            ->set('settings.plan_one_description', 'Track your training.')
            ->set('settings.plan_one_features', "Workout calendar\nProgress log")
            ->set('settings.plan_two_name', 'Coach Plus')
            ->set('settings.plan_two_price', '$79/mo')
            ->set('settings.plan_two_description', 'Run a private roster.')
            ->set('settings.plan_two_features', "Athlete invites\nProgram builder")
            ->set('settings.plan_three_name', 'Team')
            ->set('settings.plan_three_price', 'Custom')
            ->set('settings.plan_three_description', 'Operate a whole training group.')
            ->set('settings.plan_three_features', "Admin controls\nExports")
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'settings.updated',
            'entity' => 'platform_settings',
        ]);

        $this->assertDatabaseHas('platform_settings', [
            'key' => 'pricing_headline',
            'value' => 'Simple memberships',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Simple memberships')
            ->assertSee('Coach Plus');

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Start your setup')
            ->assertSee('Request setup');
    }

    public function test_admin_can_manage_contact_submissions(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $submission = ContactSubmission::create([
            'name' => 'Lead Athlete',
            'email' => 'lead@example.com',
            'phone' => '+15550001111',
            'message' => 'I need coaching.',
            'status' => 'new',
        ]);

        $this->actingAs($owner)
            ->get(route('admin.contact-submissions'))
            ->assertOk()
            ->assertSee('lead@example.com');

        Livewire::actingAs($owner)
            ->test(ContactSubmissionsTable::class)
            ->call('markStatus', $submission->id, 'reviewed')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('contact_submissions', [
            'id' => $submission->id,
            'status' => 'reviewed',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('admin.contact-submissions.export', ['search' => 'lead']))
            ->assertOk();

        $this->assertStringContainsString('lead@example.com', $response->streamedContent());
    }

    public function test_coach_can_update_program_and_maintain_sessions(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);

        $program = TrainingProgram::create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Old Plan',
            'status' => 'draft',
        ]);

        $session = TrainingSession::create([
            'training_program_id' => $program->id,
            'title' => 'Old Session',
            'scheduled_on' => now()->toDateString(),
            'status' => 'scheduled',
            'exercises' => [['name' => 'Squat', 'sets' => 3, 'reps' => 5]],
        ]);

        $component = Livewire::actingAs($coach)
            ->test(ProgramDetail::class, ['program' => $program])
            ->set('programTitle', 'Updated Plan')
            ->set('programGoal', 'Return to sprinting')
            ->set('programStatus', 'active')
            ->call('updateProgram')
            ->call('startEditSession', $session->id)
            ->set('editTitle', 'Updated Session')
            ->set('editFocus', 'Strength')
            ->set('editScheduledOn', now()->addDay()->toDateString())
            ->set('editExercises.0.name', 'Trap bar deadlift')
            ->set('editExercises.0.sets', '4')
            ->set('editExercises.0.reps', '5')
            ->set('editExercises.0.media_url', 'https://example.com/trap-bar-demo.mp4')
            ->call('updateSession')
            ->assertHasNoErrors();

        $this->assertSame(
            'https://example.com/trap-bar-demo.mp4',
            $session->refresh()->exercises[0]['media_url']
        );

        $component->call('deleteSession', $session->id)->assertHasNoErrors();

        $this->assertDatabaseHas('training_programs', [
            'id' => $program->id,
            'title' => 'Updated Plan',
            'status' => 'active',
        ]);

        $this->assertDatabaseMissing('training_sessions', [
            'id' => $session->id,
        ]);
    }

    public function test_admin_can_filter_and_export_logs(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Log Admin']);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'settings.updated',
            'entity' => 'platform_settings',
            'summary' => 'Updated public website copy.',
            'ip_address' => '127.0.0.1',
        ]);

        EmailLog::create([
            'recipient' => 'failed@example.com',
            'subject' => 'Invite failed',
            'type' => 'athlete_invite',
            'status' => 'failed',
            'error' => 'SMTP rejected message.',
        ]);

        $this->actingAs($owner)
            ->get(route('admin.audit'))
            ->assertOk()
            ->assertSee('settings.updated');

        $auditExport = $this->actingAs($owner)
            ->get(route('admin.audit.export', [
                'tab' => 'audit',
                'audit_action' => 'settings.updated',
                'audit_entity' => 'platform_settings',
            ]))
            ->assertOk();

        $this->assertStringContainsString('Updated public website copy.', $auditExport->streamedContent());

        $emailExport = $this->actingAs($owner)
            ->get(route('admin.audit.export', [
                'tab' => 'email',
                'email_status' => 'failed',
                'email_type' => 'athlete_invite',
            ]))
            ->assertOk();

        $this->assertStringContainsString('failed@example.com', $emailExport->streamedContent());
    }
}
