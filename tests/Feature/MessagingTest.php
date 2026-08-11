<?php

namespace Tests\Feature;

use App\Livewire\Messaging\Inbox;
use App\Models\CoachAthleteAssignment;
use App\Models\MediaAsset;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_coach_and_athlete_can_exchange_messages(): void
    {
        [, $coach, $athlete] = $this->coachTeam();

        Livewire::actingAs($coach)
            ->test(Inbox::class)
            ->call('startConversation', $athlete->id)
            ->set('body', 'Training starts at 6 PM.')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'sender_id' => $coach->id,
            'body' => 'Training starts at 6 PM.',
        ]);

        Livewire::actingAs($athlete)
            ->test(Inbox::class)
            ->assertSee('Training starts at 6 PM')
            ->set('body', 'Understood, coach.')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'sender_id' => $athlete->id,
            'body' => 'Understood, coach.',
        ]);
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_unassigned_users_cannot_start_a_conversation(): void
    {
        [$organization, $coach] = $this->coachTeam();
        $unassignedAthlete = User::factory()->create(['role' => 'athlete']);
        $this->join($organization, $unassignedAthlete, 'athlete');

        $this->actingAs($coach);
        $this->expectException(AuthorizationException::class);
        app(ConversationService::class)->direct($coach, $unassignedAthlete);
    }

    public function test_message_attachments_are_available_only_to_participants(): void
    {
        Storage::fake('public');
        [$organization, $coach, $athlete] = $this->coachTeam();
        $outsider = User::factory()->create(['role' => 'coach']);
        $this->join($organization, $outsider, 'coach');

        Livewire::actingAs($coach)
            ->test(Inbox::class)
            ->call('startConversation', $athlete->id)
            ->set('attachment', UploadedFile::fake()->create('plan.pdf', 32, 'application/pdf'))
            ->call('send')
            ->assertHasNoErrors();

        $media = MediaAsset::firstOrFail();
        Storage::disk('public')->assertExists($media->path);
        $this->actingAs($athlete)
            ->get(route('messages.attachments', $media))
            ->assertDownload('plan.pdf');
        $this->actingAs($outsider)
            ->get(route('messages.attachments', $media))
            ->assertForbidden();
    }

    /** @return array{Organization, User, User} */
    private function coachTeam(): array
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $athlete = User::factory()->create(['role' => 'athlete']);
        $organization = Organization::create([
            'name' => 'Messaging Team',
            'slug' => 'messaging-team',
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
