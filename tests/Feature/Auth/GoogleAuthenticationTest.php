<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Enums\SignupMethod;
use App\Models\User;
use App\Services\SocialAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_exposes_google_when_enabled(): void
    {
        $this->enableGoogleAuth();

        $this->get('/login')
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/login')
                ->where('signupMethods.1.value', SignupMethod::Google->value)
                ->where('signupMethods.1.enabled', true)
                ->where(
                    'signupMethods.1.authorizationUrl',
                    route('auth.oauth.redirect', ['provider' => SignupMethod::Google->value, 'intent' => 'login'], absolute: false),
                )
            );
    }

    public function test_google_redirect_url_falls_back_to_the_real_callback_route_for_subdirectory_deploys(): void
    {
        URL::forceRootUrl('https://ahmaddalao.com/athlete');
        URL::forceScheme('https');

        config()->set('throughline.auth.signup_methods.google.enabled', true);
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', null);

        $service = app(SocialAuthService::class);

        $this->assertTrue($service->isProviderEnabled(SignupMethod::Google));
        $this->assertSame(
            'https://ahmaddalao.com/athlete/auth/google/callback',
            $service->redirectUrlFor(SignupMethod::Google),
        );
    }

    public function test_google_redirect_stores_register_context_and_redirects_to_provider(): void
    {
        $this->enableGoogleAuth();
        Socialite::fake(SignupMethod::Google->value);

        $this->get(route('auth.oauth.redirect', [
            'provider' => SignupMethod::Google->value,
            'intent' => 'register',
            'account_type' => RoleName::Athlete->value,
        ], absolute: false))
            ->assertRedirect()
            ->assertSessionHas(SocialAuthService::SESSION_CONTEXT_KEY, [
                'provider' => SignupMethod::Google->value,
                'intent' => 'register',
                'account_type' => RoleName::Athlete->value,
            ]);
    }

    public function test_new_user_can_register_with_google_as_an_athlete(): void
    {
        $this->enableGoogleAuth();
        Socialite::fake(SignupMethod::Google->value, $this->googleUser(
            id: 'google-athlete-1',
            name: 'Google Athlete',
            email: 'google-athlete@example.com',
            avatar: 'https://example.com/avatar-athlete.png',
        ));

        $response = $this
            ->withSession([
                SocialAuthService::SESSION_CONTEXT_KEY => [
                    'provider' => SignupMethod::Google->value,
                    'intent' => 'register',
                    'account_type' => RoleName::Athlete->value,
                ],
            ])
            ->get(route('auth.oauth.callback', ['provider' => SignupMethod::Google->value], absolute: false));

        $response->assertRedirect(route('athlete.app.index', absolute: false));
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole(RoleName::Athlete));
        $this->assertSame(SignupMethod::Google->value, auth()->user()->registration_channel);
        $this->assertDatabaseHas('social_accounts', [
            'provider' => SignupMethod::Google->value,
            'provider_user_id' => 'google-athlete-1',
            'provider_email' => 'google-athlete@example.com',
            'user_id' => auth()->id(),
        ]);
    }

    public function test_existing_email_user_can_link_and_login_with_google(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'linked-user@example.com',
        ]);
        $user->assignRole(RoleName::Coach);

        $this->enableGoogleAuth();
        Socialite::fake(SignupMethod::Google->value, $this->googleUser(
            id: 'google-coach-1',
            name: 'Linked Coach',
            email: 'linked-user@example.com',
            avatar: 'https://example.com/avatar-coach.png',
        ));

        $response = $this
            ->withSession([
                SocialAuthService::SESSION_CONTEXT_KEY => [
                    'provider' => SignupMethod::Google->value,
                    'intent' => 'login',
                    'account_type' => null,
                ],
            ])
            ->get(route('auth.oauth.callback', ['provider' => SignupMethod::Google->value], absolute: false));

        $response->assertRedirect('/roster');
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertDatabaseHas('social_accounts', [
            'provider' => SignupMethod::Google->value,
            'provider_user_id' => 'google-coach-1',
            'provider_email' => 'linked-user@example.com',
            'user_id' => $user->id,
        ]);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_google_login_without_existing_account_redirects_back_to_login(): void
    {
        $this->enableGoogleAuth();
        Socialite::fake(SignupMethod::Google->value, $this->googleUser(
            id: 'google-new-user',
            name: 'New User',
            email: 'new-user@example.com',
            avatar: 'https://example.com/avatar-new.png',
        ));

        $response = $this
            ->withSession([
                SocialAuthService::SESSION_CONTEXT_KEY => [
                    'provider' => SignupMethod::Google->value,
                    'intent' => 'login',
                    'account_type' => null,
                ],
            ])
            ->get(route('auth.oauth.callback', ['provider' => SignupMethod::Google->value], absolute: false));

        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionHas('status', 'No Throughline account exists for this Google email yet. Start from sign up so we can assign the right role.');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'new-user@example.com',
        ]);
    }

    private function enableGoogleAuth(): void
    {
        config()->set('throughline.auth.signup_methods.google.enabled', true);
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');
        config()->set('services.google.scopes', ['openid', 'profile', 'email']);
    }

    private function googleUser(string $id, string $name, string $email, string $avatar): SocialiteUser
    {
        return (new SocialiteUser)->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
        ]);
    }
}
