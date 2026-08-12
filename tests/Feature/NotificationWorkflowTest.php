<?php

namespace Tests\Feature;

use App\Livewire\NotificationCenter;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Notifications\ThroughlineNotification;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_inboxes_are_scoped_to_the_active_organization(): void
    {
        $athlete = User::factory()->create(['role' => 'athlete']);
        $primary = $this->organization('Primary Team', 'primary-team');
        $secondary = $this->organization('Secondary Team', 'secondary-team');
        $this->join($primary, $athlete);
        $this->join($secondary, $athlete);
        $athlete->forceFill(['current_organization_id' => $primary->id])->saveQuietly();

        $athlete->notify(new ThroughlineNotification(
            $primary->id,
            'program',
            'Primary program assigned',
            'This belongs to the primary team.',
            'program_assignment',
            101,
        ));
        $athlete->notify(new ThroughlineNotification(
            $secondary->id,
            'schedule',
            'Secondary schedule changed',
            'This must not leak into the primary inbox.',
            'scheduled_workout',
            202,
        ));

        $primaryNotification = $athlete->notifications()->where('data->organization_id', $primary->id)->firstOrFail();
        $secondaryNotification = $athlete->notifications()->where('data->organization_id', $secondary->id)->firstOrFail();

        $this->actingAs($athlete)
            ->get(route('notifications'))
            ->assertOk()
            ->assertSee('Primary program assigned')
            ->assertDontSee('Secondary schedule changed');

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $primary->id)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $primaryNotification->id)
            ->assertJsonPath('meta.unread', 1);

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $primary->id)
            ->patchJson("/api/v1/notifications/{$secondaryNotification->id}/read")
            ->assertNotFound();

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $primary->id)
            ->patchJson("/api/v1/notifications/{$primaryNotification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $primaryNotification->id)
            ->assertJsonPath('data.read_at', fn (mixed $value): bool => filled($value));

        $this->assertNull($secondaryNotification->fresh()->read_at);
    }

    public function test_web_inbox_can_filter_and_mark_organization_notifications_read(): void
    {
        $athlete = User::factory()->create(['role' => 'athlete']);
        $organization = $this->organization('Livewire Team', 'livewire-team');
        $this->join($organization, $athlete);
        $athlete->notify(new ThroughlineNotification(
            $organization->id,
            'message',
            'Coach sent a message',
            'Review today’s training note.',
            'conversation',
            41,
        ));

        app(OrganizationContext::class)->set($organization);

        Livewire::actingAs($athlete)
            ->test(NotificationCenter::class)
            ->assertSee('Coach sent a message')
            ->assertSee('1 unread')
            ->set('filter', 'unread')
            ->call('markAllRead')
            ->assertSee('0 unread');

        $this->assertNotNull($athlete->notifications()->firstOrFail()->fresh()->read_at);
    }

    private function organization(string $name, string $slug): Organization
    {
        return Organization::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
        ]);
    }

    private function join(Organization $organization, User $athlete): void
    {
        OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $athlete->id,
            'role' => 'athlete',
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }
}
