<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Enums\SignupMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_cannot_open_the_admin_user_index(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole(RoleName::Coach);

        $this->actingAs($coach)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_view_the_admin_user_index(): void
    {
        $admin = User::factory()->create();
        $coach = User::factory()->create(['registration_channel' => SignupMethod::Google->value]);
        $athlete = User::factory()->create();

        $admin->assignRole(RoleName::Admin);
        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/index')
                ->where('summary.totalUsers', 3)
                ->where('summary.coaches', 1)
                ->where('summary.athletes', 1)
                ->where('users.total', 3)
                ->where('channelOptions.1.value', SignupMethod::Google->value)
            );
    }

    public function test_admin_can_update_roles_and_onboarding_fields_from_the_ui(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'phone' => '+15550000111',
            'primary_goal' => 'Old goal',
            'preferred_contact_method' => 'email',
            'registration_channel' => SignupMethod::Email->value,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $admin->assignRole(RoleName::Admin);
        $user->assignRole(RoleName::Athlete);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => 'Updated User',
                'email' => 'new@example.com',
                'phone' => '+15550000999',
                'primary_goal' => 'New goal',
                'preferred_contact_method' => 'phone',
                'registration_channel' => SignupMethod::Google->value,
                'roles' => [RoleName::Coach->value, RoleName::Athlete->value],
            ])
            ->assertRedirect();

        $user->refresh()->load('roles');

        $this->assertSame('Updated User', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('+15550000999', $user->phone);
        $this->assertSame('New goal', $user->primary_goal);
        $this->assertSame('phone', $user->preferred_contact_method);
        $this->assertSame(SignupMethod::Google->value, $user->registration_channel);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->phone_verified_at);
        $this->assertTrue($user->hasRole(RoleName::Coach));
        $this->assertTrue($user->hasRole(RoleName::Athlete));
    }
}
