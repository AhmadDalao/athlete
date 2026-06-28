<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Enums\SignupMethod;
use App\Services\SocialAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AppleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_exposes_apple_when_enabled(): void
    {
        $this->enableAppleAuth();

        $this->get('/login')
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/login')
                ->where('signupMethods.2.value', SignupMethod::Apple->value)
                ->where('signupMethods.2.enabled', true)
                ->where(
                    'signupMethods.2.authorizationUrl',
                    route('auth.oauth.redirect', ['provider' => SignupMethod::Apple->value, 'intent' => 'login'], absolute: false),
                )
            );
    }

    public function test_apple_provider_is_considered_configured_with_private_key_settings(): void
    {
        config()->set('throughline.auth.signup_methods.apple.enabled', true);
        config()->set('services.apple.client_id', 'com.throughline.web');
        config()->set('services.apple.client_secret', '');
        config()->set('services.apple.key_id', 'APPLEKEY123');
        config()->set('services.apple.team_id', 'TEAM123456');
        config()->set('services.apple.private_key', '/tmp/AuthKey_APPLEKEY123.p8');

        $service = app(SocialAuthService::class);

        $this->assertTrue($service->isProviderEnabled(SignupMethod::Apple));
    }

    public function test_new_user_can_register_with_apple_as_a_coach(): void
    {
        $this->enableAppleAuth();

        Socialite::fake(SignupMethod::Apple->value, $this->appleUser(
            id: 'apple-coach-1',
            name: 'Apple Coach',
            email: 'apple-coach@example.com',
        ));

        $response = $this
            ->withSession([
                SocialAuthService::SESSION_CONTEXT_KEY => [
                    'provider' => SignupMethod::Apple->value,
                    'intent' => 'register',
                    'account_type' => RoleName::Coach->value,
                ],
            ])
            ->post(route('auth.oauth.callback', ['provider' => SignupMethod::Apple->value], absolute: false));

        $response->assertRedirect('/roster');
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole(RoleName::Coach));
        $this->assertSame(SignupMethod::Apple->value, auth()->user()->registration_channel);
        $this->assertDatabaseHas('social_accounts', [
            'provider' => SignupMethod::Apple->value,
            'provider_user_id' => 'apple-coach-1',
            'provider_email' => 'apple-coach@example.com',
            'user_id' => auth()->id(),
        ]);
    }

    private function enableAppleAuth(): void
    {
        config()->set('throughline.auth.signup_methods.apple.enabled', true);
        config()->set('services.apple.client_id', 'com.throughline.web');
        config()->set('services.apple.client_secret', 'apple-client-secret');
        config()->set('services.apple.redirect', 'http://localhost/auth/apple/callback');
        config()->set('services.apple.scopes', ['name', 'email']);
    }

    private function appleUser(string $id, string $name, string $email): SocialiteUser
    {
        return (new SocialiteUser)->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
        ]);
    }
}
