<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_cannot_manage_system_settings(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole(RoleName::Coach);

        $this->actingAs($coach)
            ->get(route('admin.system-settings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_update_system_settings_and_homepage_text(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        $settings = PlatformSetting::values();
        $settings['home_eyebrow'] = 'Built for serious coaching';
        $settings['home_headline'] = 'One dashboard for athletes, coaches, and operations.';
        $settings['home_subheadline'] = 'A custom public message controlled by admins.';
        $settings['support_email'] = 'team@example.com';

        $this->actingAs($admin)
            ->patch(route('admin.system-settings.update'), ['settings' => $settings])
            ->assertRedirect();

        $this->assertDatabaseHas('platform_settings', [
            'key' => 'home_headline',
            'value' => 'One dashboard for athletes, coaches, and operations.',
            'updated_by_user_id' => $admin->id,
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('welcome')
                ->where('content.eyebrow', 'Built for serious coaching')
                ->where('content.headline', 'One dashboard for athletes, coaches, and operations.')
                ->where('content.subheadline', 'A custom public message controlled by admins.')
            );
    }
}
