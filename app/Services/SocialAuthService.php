<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\SignupMethod;
use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialAuthService
{
    public const SESSION_CONTEXT_KEY = 'social_auth.context';

    public function isProviderEnabled(SignupMethod|string $provider): bool
    {
        $method = $provider instanceof SignupMethod ? $provider : SignupMethod::from($provider);

        if (! $method->isOauthProvider()) {
            return false;
        }

        if (! config("throughline.auth.signup_methods.{$method->value}.enabled", false)) {
            return false;
        }

        /** @var array<string, mixed> $service */
        $service = config("services.{$method->value}", []);

        return filled($service['client_id'] ?? null)
            && filled($service['client_secret'] ?? null);
    }

    public function redirectUrlFor(SignupMethod $provider): string
    {
        $configuredRedirect = config("services.{$provider->value}.redirect");

        if (is_string($configuredRedirect) && $configuredRedirect !== '') {
            return $configuredRedirect;
        }

        return route('auth.oauth.callback', ['provider' => $provider->value], absolute: true);
    }

    public function resolveProvider(string $provider): SignupMethod
    {
        $method = SignupMethod::tryFrom(Str::lower(trim($provider)));

        if (! $method || ! $method->isOauthProvider()) {
            throw new SocialAuthException('That social sign-in provider is not supported.');
        }

        if (! $this->isProviderEnabled($method)) {
            throw new SocialAuthException(sprintf('%s sign-in is not configured yet.', $method->label()));
        }

        return $method;
    }

    public function scopesFor(SignupMethod $provider): array
    {
        /** @var list<string> $scopes */
        $scopes = config("services.{$provider->value}.scopes", []);

        return $scopes;
    }

    public function syncUserFromProvider(
        SignupMethod $provider,
        ProviderUser $providerUser,
        string $intent,
        ?string $accountType = null,
    ): User {
        $providerUserId = trim((string) $providerUser->getId());
        $email = Str::lower(trim((string) $providerUser->getEmail()));

        if ($providerUserId === '') {
            throw new SocialAuthException(sprintf('%s did not return a stable account ID.', $provider->label()));
        }

        if ($email === '') {
            throw new SocialAuthException(sprintf('%s did not return an email address, so we cannot safely finish sign-in.', $provider->label()));
        }

        $socialAccount = SocialAccount::query()
            ->with('user.roles')
            ->where('provider', $provider->value)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($socialAccount) {
            $this->refreshSocialAccount($socialAccount, $providerUser, $email);

            return $socialAccount->user->loadMissing('roles', 'socialAccounts');
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user && $intent !== 'register') {
            throw new SocialAuthException('No Throughline account exists for this Google email yet. Start from sign up so we can assign the right role.');
        }

        if (! $user) {
            $role = $this->resolveRegistrationRole($accountType);

            $user = User::query()->create([
                'name' => trim((string) ($providerUser->getName() ?: Str::before($email, '@'))),
                'email' => $email,
                'password' => Hash::make(Str::password(32)),
                'primary_goal' => null,
                'preferred_contact_method' => 'email',
                'registration_channel' => $provider->value,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole($role);
        } else {
            if ($intent === 'register' && $accountType && ! array_intersect($user->roleNames(), RoleName::registrationValues())) {
                $user->assignRole($this->resolveRegistrationRole($accountType));
            }

            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }

        SocialAccount::query()->updateOrCreate(
            [
                'provider' => $provider->value,
                'provider_user_id' => $providerUserId,
            ],
            $this->socialAccountAttributes($user->id, $providerUser, $email),
        );

        return $user->fresh(['roles', 'socialAccounts']);
    }

    private function resolveRegistrationRole(?string $accountType): RoleName
    {
        if (! is_string($accountType) || ! in_array($accountType, RoleName::registrationValues(), true)) {
            throw new SocialAuthException('Choose whether the Google account should register as a coach or athlete before continuing.');
        }

        return RoleName::from($accountType);
    }

    private function refreshSocialAccount(SocialAccount $socialAccount, ProviderUser $providerUser, string $email): void
    {
        $socialAccount->fill($this->socialAccountAttributes($socialAccount->user_id, $providerUser, $email));
        $socialAccount->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function socialAccountAttributes(int $userId, ProviderUser $providerUser, string $email): array
    {
        $expiresIn = $providerUser->expiresIn ?? null;

        return [
            'user_id' => $userId,
            'provider_email' => $email,
            'provider_avatar' => $providerUser->getAvatar(),
            'access_token' => $providerUser->token ?? null,
            'refresh_token' => $providerUser->refreshToken ?? null,
            'token_expires_at' => is_numeric($expiresIn) ? now()->addSeconds((int) $expiresIn) : null,
            'provider_payload' => [
                'id' => $providerUser->getId(),
                'nickname' => $providerUser->getNickname(),
                'name' => $providerUser->getName(),
                'email' => $providerUser->getEmail(),
                'avatar' => $providerUser->getAvatar(),
            ],
        ];
    }
}
