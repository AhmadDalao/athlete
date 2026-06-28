<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_role_notifications_and_users_can_mark_them_read(): void
    {
        $admin = User::factory()->create();
        $coach = User::factory()->create();
        $athlete = User::factory()->create();

        $admin->assignRole(RoleName::Admin);
        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        $this->actingAs($admin)
            ->post(route('notifications.store'), [
                'title' => 'Training update',
                'body' => 'New workouts are ready for this week.',
                'target_type' => 'role',
                'target_role' => RoleName::Coach->value,
                'target_user_id' => null,
                'action_label' => 'Open training',
                'action_url' => '/training',
                'starts_at' => null,
                'expires_at' => null,
            ])
            ->assertRedirect();

        $notification = SystemNotification::query()->firstOrFail();

        $this->actingAs($coach)
            ->get(route('notifications.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('notifications/index')
                ->where('notifications.total', 1)
                ->where('notifications.data.0.title', 'Training update')
                ->where('notifications.data.0.readAt', null)
            );

        $this->actingAs($athlete)
            ->get(route('notifications.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('notifications/index')
                ->where('notifications.total', 0)
            );

        $this->actingAs($coach)
            ->post(route('notifications.read', $notification))
            ->assertRedirect();

        $this->assertDatabaseHas('system_notification_reads', [
            'system_notification_id' => $notification->id,
            'user_id' => $coach->id,
        ]);
    }
}
