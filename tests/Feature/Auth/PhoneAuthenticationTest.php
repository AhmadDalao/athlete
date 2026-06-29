<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Enums\SignupMethod;
use App\Models\PhoneAuthChallenge;
use App\Models\User;
use App\Services\PhoneAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhoneAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_exposes_phone_when_enabled(): void
    {
        $this->enablePhoneAuth();

        $this->get('/login')
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/login')
                ->where('signupMethods.3.value', SignupMethod::Phone->value)
                ->where('signupMethods.3.enabled', true)
                ->where(
                    'signupMethods.3.authorizationUrl',
                    route('auth.phone.create', ['intent' => 'login'], absolute: false),
                )
            );
    }

    public function test_new_user_can_register_with_phone_and_otp(): void
    {
        $this->enablePhoneAuth();

        $this->post(route('auth.phone.challenges.store', absolute: false), [
            'intent' => 'register',
            'name' => 'Phone Athlete',
            'email' => 'phone-athlete@example.com',
            'phone' => '+966500000000',
            'account_type' => RoleName::Athlete->value,
            'primary_goal' => 'Stay consistent',
            'preferred_contact_method' => 'phone',
            'terms_accepted' => true,
        ])->assertRedirect(route('auth.phone.create', ['intent' => 'register'], absolute: false));

        $challenge = PhoneAuthChallenge::query()->latest('id')->firstOrFail();
        $challenge->forceFill([
            'code_hash' => Hash::make('123456'),
        ])->save();

        $response = $this
            ->withSession([PhoneAuthService::SESSION_CHALLENGE_KEY => $challenge->id])
            ->post(route('auth.phone.verify', absolute: false), [
                'intent' => 'register',
                'code' => '123456',
            ]);

        $response->assertRedirect(route('athlete.app.index', absolute: false));
        $this->assertAuthenticated();
        $this->assertSame(SignupMethod::Phone->value, auth()->user()->registration_channel);
        $this->assertTrue(auth()->user()->hasRole(RoleName::Athlete));
        $this->assertNotNull(auth()->user()->phone_verified_at);
        $this->assertDatabaseHas('users', [
            'email' => 'phone-athlete@example.com',
            'phone' => '+966500000000',
            'registration_channel' => SignupMethod::Phone->value,
        ]);
    }

    public function test_existing_user_can_log_in_with_phone_and_otp(): void
    {
        $this->enablePhoneAuth();

        $user = User::factory()->create([
            'phone' => '+966511111111',
            'phone_verified_at' => null,
        ]);
        $user->assignRole(RoleName::Coach);

        $this->post(route('auth.phone.challenges.store', absolute: false), [
            'intent' => 'login',
            'phone' => '+966511111111',
        ])->assertRedirect(route('auth.phone.create', ['intent' => 'login'], absolute: false));

        $challenge = PhoneAuthChallenge::query()->latest('id')->firstOrFail();
        $challenge->forceFill([
            'code_hash' => Hash::make('654321'),
        ])->save();

        $response = $this
            ->withSession([PhoneAuthService::SESSION_CHALLENGE_KEY => $challenge->id])
            ->post(route('auth.phone.verify', absolute: false), [
                'intent' => 'login',
                'code' => '654321',
            ]);

        $response->assertRedirect('/coach');
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_phone_login_requires_existing_phone_account(): void
    {
        $this->enablePhoneAuth();

        $response = $this->from(route('auth.phone.create', ['intent' => 'login'], absolute: false))
            ->post(route('auth.phone.challenges.store', absolute: false), [
                'intent' => 'login',
                'phone' => '+966599999999',
            ]);

        $response
            ->assertRedirect(route('auth.phone.create', ['intent' => 'login'], absolute: false))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('phone_auth_challenges', 0);
    }

    private function enablePhoneAuth(): void
    {
        config()->set('throughline.auth.signup_methods.phone.enabled', true);
        config()->set('throughline.auth.phone.enabled', true);
        config()->set('throughline.auth.phone.driver', 'log');
        config()->set('throughline.auth.phone.otp_digits', 6);
        config()->set('throughline.auth.phone.ttl_minutes', 10);
        config()->set('throughline.auth.phone.max_attempts', 5);
    }
}
