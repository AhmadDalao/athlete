<?php

namespace Tests\Feature;

use App\Livewire\Athlete\Home;
use App\Livewire\Athlete\Profile;
use App\Livewire\Athlete\ProgressPanel;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\ProgramAssignment;
use App\Models\ProgressPhoto;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\ProgramScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AthleteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_athlete_home_combines_multiple_coach_assignments_and_changes_month_without_redirecting(): void
    {
        [$organization, $athlete, $coachOne, $coachTwo] = $this->team();
        $first = $this->assignedProgram($organization, $coachOne, $athlete, 'Strength block', '2026-08-11');
        $second = $this->assignedProgram($organization, $coachTwo, $athlete, 'Speed block', '2026-08-18');

        Livewire::actingAs($athlete)
            ->test(Home::class)
            ->assertSee($first->program->title)
            ->assertSee($second->program->title)
            ->set('month', '2026-08')
            ->call('nextMonth')
            ->assertSet('month', '2026-09')
            ->assertSet('selectedDate', '2026-09-01');
    }

    public function test_athlete_cannot_open_another_athletes_scheduled_workout(): void
    {
        [$organization, $athlete, $coach] = $this->team();
        $other = User::factory()->create(['role' => 'athlete']);
        $this->join($organization, $other, 'athlete');
        $assignment = $this->assignedProgram($organization, $coach, $other, 'Private plan', today()->toDateString());

        $this->actingAs($athlete)
            ->get(route('app.workouts.show', $assignment->scheduledWorkouts()->firstOrFail()))
            ->assertForbidden();
    }

    public function test_athlete_can_manage_profile_and_private_progress_photo(): void
    {
        Storage::fake('public');
        [$organization, $athlete] = $this->team();

        Livewire::actingAs($athlete)
            ->test(Profile::class)
            ->set('name', 'Updated Athlete')
            ->set('sport', 'Rowing')
            ->set('position', 'Single scull')
            ->set('heightCm', '184')
            ->set('timezone', 'Asia/Riyadh')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($athlete)
            ->test(ProgressPanel::class)
            ->set('photo', UploadedFile::fake()->image('progress.jpg'))
            ->set('photoTakenOn', today()->toDateString())
            ->set('photoCategory', 'front')
            ->call('uploadPhoto')
            ->assertHasNoErrors();

        $photo = ProgressPhoto::firstOrFail();
        $this->assertDatabaseHas('athlete_profiles', [
            'organization_id' => $organization->id,
            'user_id' => $athlete->id,
            'sport' => 'Rowing',
        ]);
        Storage::disk('public')->assertExists($photo->path);
        $this->actingAs($athlete)->get(route('app.progress.photos.view', $photo))->assertOk();

        $other = User::factory()->create(['role' => 'athlete']);
        $this->join($organization, $other, 'athlete');
        $this->actingAs($other)->get(route('app.progress.photos.view', $photo))->assertForbidden();
    }

    /** @return array{Organization, User, User, User} */
    private function team(): array
    {
        $organization = Organization::create([
            'name' => 'Athlete Workflow Team',
            'slug' => 'athlete-workflow-team',
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
        ]);
        $athlete = User::factory()->create(['role' => 'athlete']);
        $coachOne = User::factory()->create(['role' => 'coach']);
        $coachTwo = User::factory()->create(['role' => 'coach']);
        $this->join($organization, $athlete, 'athlete');
        $this->join($organization, $coachOne, 'coach');
        $this->join($organization, $coachTwo, 'coach');

        return [$organization, $athlete->fresh(), $coachOne->fresh(), $coachTwo->fresh()];
    }

    private function assignedProgram(Organization $organization, User $coach, User $athlete, string $title, string $startsOn): ProgramAssignment
    {
        $program = TrainingProgram::create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'title' => $title,
            'status' => 'active',
            'is_template' => true,
        ]);
        $program->sessions()->create([
            'organization_id' => $organization->id,
            'title' => $title.' session',
            'status' => 'scheduled',
            'day_offset' => 0,
        ]);

        return app(ProgramScheduleService::class)->assign($program, $athlete, $coach, $startsOn);
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
