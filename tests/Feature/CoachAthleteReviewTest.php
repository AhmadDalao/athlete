<?php

namespace Tests\Feature;

use App\Livewire\Coach\AthleteDetail;
use App\Models\CoachAthleteAssignment;
use App\Models\CoachNote;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\ProgressEntry;
use App\Models\ProgressPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CoachAthleteReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_profile_filters_and_exports_only_the_assigned_athlete(): void
    {
        [$organization, $coach, $athlete] = $this->coachTeam();
        $otherAthlete = User::factory()->create(['role' => 'athlete']);
        $this->join($organization, $otherAthlete, 'athlete');

        ProgressEntry::create([
            'organization_id' => $organization->id,
            'athlete_id' => $athlete->id,
            'logged_on' => '2026-08-10',
            'weight' => 82.4,
            'notes' => 'Visible recovery note',
        ]);
        ProgressEntry::create([
            'organization_id' => $organization->id,
            'athlete_id' => $otherAthlete->id,
            'logged_on' => '2026-08-10',
            'weight' => 91.2,
            'notes' => 'Private other athlete note',
        ]);

        Livewire::actingAs($coach)
            ->test(AthleteDetail::class, ['athlete' => $athlete])
            ->call('selectTab', 'progress')
            ->set('search', 'Visible recovery')
            ->assertSee('Visible recovery note')
            ->assertDontSee('Private other athlete note');

        $csv = $this->actingAs($coach)
            ->get(route('coach.athletes.export', [
                'athlete' => $athlete,
                'section' => 'progress',
                'search' => 'Visible recovery',
            ]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Visible recovery note', $csv);
        $this->assertStringNotContainsString('Private other athlete note', $csv);
        $this->actingAs($coach)->get(route('coach.athletes.show', $otherAthlete))->assertForbidden();
    }

    public function test_private_notes_are_owned_and_organization_notes_are_shared(): void
    {
        [$organization, $coach, $athlete] = $this->coachTeam();
        $otherCoach = User::factory()->create(['role' => 'coach']);
        $this->join($organization, $otherCoach, 'coach');
        CoachAthleteAssignment::create([
            'organization_id' => $organization->id,
            'coach_id' => $otherCoach->id,
            'athlete_id' => $athlete->id,
            'status' => 'active',
            'started_at' => today(),
        ]);
        CoachNote::create([
            'organization_id' => $organization->id,
            'coach_id' => $otherCoach->id,
            'athlete_id' => $athlete->id,
            'body' => 'Other coach private note',
            'visibility' => 'private',
        ]);
        CoachNote::create([
            'organization_id' => $organization->id,
            'coach_id' => $otherCoach->id,
            'athlete_id' => $athlete->id,
            'body' => 'Shared organization note',
            'visibility' => 'organization',
        ]);

        Livewire::actingAs($coach)
            ->test(AthleteDetail::class, ['athlete' => $athlete])
            ->call('selectTab', 'notes')
            ->set('noteBody', 'Coach-owned private note')
            ->set('noteVisibility', 'private')
            ->call('addNote')
            ->assertHasNoErrors()
            ->assertSee('Coach-owned private note')
            ->assertSee('Shared organization note')
            ->assertDontSee('Other coach private note');

        $this->assertDatabaseHas('coach_notes', [
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'body' => 'Coach-owned private note',
        ]);
    }

    public function test_progress_photo_upload_and_inline_access_are_assignment_scoped(): void
    {
        Storage::fake('public');
        [$organization, $coach, $athlete] = $this->coachTeam();
        $otherCoach = User::factory()->create(['role' => 'coach']);
        $this->join($organization, $otherCoach, 'coach');

        Livewire::actingAs($coach)
            ->test(AthleteDetail::class, ['athlete' => $athlete])
            ->call('selectTab', 'photos')
            ->set('photo', UploadedFile::fake()->image('front.jpg'))
            ->set('photoTakenOn', today()->toDateString())
            ->set('photoCategory', 'front')
            ->set('photoVisibility', 'coaches')
            ->call('uploadPhoto')
            ->assertHasNoErrors();

        $photo = ProgressPhoto::firstOrFail();
        Storage::disk('public')->assertExists($photo->path);
        $this->actingAs($coach)
            ->get(route('coach.athletes.photos.view', [$athlete, $photo]))
            ->assertOk();
        $this->actingAs($otherCoach)
            ->get(route('coach.athletes.photos.view', [$athlete, $photo]))
            ->assertForbidden();
    }

    /** @return array{Organization, User, User} */
    private function coachTeam(): array
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);
        $organization = Organization::create([
            'name' => 'Athlete Review Team',
            'slug' => 'athlete-review-team',
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
        ]);
        $this->join($organization, $coach, 'coach');
        $this->join($organization, $athlete, 'athlete');
        CoachAthleteAssignment::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => 'active',
            'started_at' => today(),
        ]);

        return [$organization, $coach->fresh(), $athlete->fresh()];
    }

    private function join(Organization $organization, User $user, string $role): void
    {
        OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $user->forceFill(['current_organization_id' => $organization->id])->saveQuietly();
    }
}
