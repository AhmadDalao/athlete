<?php

namespace Tests\Feature;

use App\Livewire\Admin\SettingsPanel;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WebsiteControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_control_routes_require_explicit_permissions(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($owner)->get(route('admin.settings'))->assertOk();
        $this->actingAs($owner)->get(route('admin.permissions'))->assertOk();

        $this->actingAs($admin)->get(route('admin.settings'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.permissions'))->assertForbidden();

        $admin->permissions()->create(['permission' => 'admin.settings']);
        $this->actingAs($admin)->get(route('admin.settings'))->assertOk();
    }

    public function test_owner_can_update_branding_visibility_and_logo_with_an_audit_record(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(SettingsPanel::class)
            ->set('settings.app_name', 'Throughline Labs')
            ->set('settings.homepage_headline', 'Train with intent.')
            ->set('settings.public_pricing_enabled', false)
            ->set('settings.default_theme', 'light')
            ->set('logo', UploadedFile::fake()->image('throughline.png', 400, 400))
            ->call('save')
            ->assertHasNoErrors();

        $logoPath = PlatformSetting::get('logo_path');
        Storage::disk('public')->assertExists($logoPath);
        $this->assertSame('Throughline Labs', PlatformSetting::get('app_name'));
        $this->assertSame('0', PlatformSetting::get('public_pricing_enabled'));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'action' => 'settings.updated',
            'entity' => 'platform_settings',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Train with intent.')
            ->assertDontSee('id="pricing"', false)
            ->assertSee('data-theme-preference="light"', false);
        $this->get(route('pricing'))->assertNotFound();
    }

    public function test_public_features_and_contact_can_be_hidden_without_deleting_data(): void
    {
        PlatformSetting::put('public_features_enabled', '0', 'visibility');
        PlatformSetting::put('public_contact_enabled', '0', 'visibility');

        $this->get(route('features'))->assertNotFound();
        $this->get(route('contact'))->assertNotFound();
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('href="'.route('features').'"', false)
            ->assertDontSee('href="'.route('contact').'"', false);
    }

    public function test_paused_invitations_are_rejected_by_the_coach_api(): void
    {
        $organization = Organization::create([
            'name' => 'Invite Control Team',
            'slug' => 'invite-control-team',
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
        ]);
        $coach = User::factory()->create([
            'role' => 'coach',
            'current_organization_id' => $organization->id,
        ]);
        OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $coach->id,
            'role' => 'coach',
            'status' => 'active',
            'joined_at' => now(),
        ]);
        PlatformSetting::put('invitations_enabled', '0', 'invitations');

        $this->actingAs($coach, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson('/api/v1/coach/invitations', [
                'name' => 'Paused Athlete',
                'email' => 'paused@example.com',
            ])
            ->assertStatus(423)
            ->assertJsonPath('message', 'Athlete invitations are currently paused.');

        $this->assertDatabaseMissing('athlete_invitations', ['email' => 'paused@example.com']);
    }
}
