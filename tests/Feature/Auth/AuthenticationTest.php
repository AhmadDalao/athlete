<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $this->get('/login')
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/login')
                ->where('signupMethods.0.value', 'email')
                ->where('signupMethods.0.enabled', true)
                ->where('signupMethods.1.value', 'google')
                ->where('signupMethods.1.enabled', false)
            );
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/settings/profile');
    }

    public function test_coaches_are_redirected_to_their_primary_workspace_after_login()
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Coach);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/coach');
    }

    public function test_athletes_are_redirected_to_the_app_after_login()
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Athlete);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/app');
    }

    public function test_admins_are_redirected_to_the_admin_dashboard_after_login()
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Admin);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/admin/dashboard');
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
