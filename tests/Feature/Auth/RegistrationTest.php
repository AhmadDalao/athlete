<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Enums\SignupMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $this->get('/register')
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/register')
                ->where('signupMethods.0.value', SignupMethod::Email->value)
                ->where('signupMethods.0.enabled', true)
                ->where('signupMethods.1.value', SignupMethod::Google->value)
                ->where('signupMethods.1.enabled', false)
                ->has('goalSuggestions', 4)
            );
    }

    public function test_new_users_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+966500000000',
            'account_type' => RoleName::Athlete->value,
            'primary_goal' => 'Build consistency',
            'preferred_contact_method' => 'phone',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
        ]);

        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole(RoleName::Athlete));
        $this->assertSame('+966500000000', auth()->user()->phone);
        $this->assertSame('Build consistency', auth()->user()->primary_goal);
        $this->assertSame('phone', auth()->user()->preferred_contact_method);
        $this->assertSame(SignupMethod::Email->value, auth()->user()->registration_channel);
        $response->assertRedirect(route('athlete.app.index', absolute: false));
    }

    public function test_phone_contact_preference_requires_a_phone_number()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Phone First',
            'email' => 'phone-first@example.com',
            'phone' => '',
            'account_type' => RoleName::Coach->value,
            'primary_goal' => 'Coach more athletes',
            'preferred_contact_method' => 'phone',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_new_coaches_land_in_roster_after_registration()
    {
        $response = $this->post('/register', [
            'name' => 'Coach User',
            'email' => 'coach-user@example.com',
            'phone' => '+966511111111',
            'account_type' => RoleName::Coach->value,
            'primary_goal' => 'Build a better roster',
            'preferred_contact_method' => 'email',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
        ]);

        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole(RoleName::Coach));
        $response->assertRedirect('/roster');
    }
}
