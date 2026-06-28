<?php

namespace App\Support;

use App\Enums\SignupMethod;
use App\Services\PhoneAuthService;
use App\Services\SocialAuthService;

class AuthMethodCatalog
{
    public function __construct(
        private readonly SocialAuthService $socialAuth,
        private readonly PhoneAuthService $phoneAuth,
    ) {}

    /**
     * @return list<array{value:string,label:string,headline:string,description:string,enabled:bool,authorizationUrl:?string}>
     */
    public function guestMethods(string $intent): array
    {
        return collect(SignupMethod::cases())
            ->map(function (SignupMethod $method) use ($intent): array {
                /** @var array{headline?:string,description?:string}|null $config */
                $config = config("throughline.auth.signup_methods.{$method->value}");
                $enabled = match (true) {
                    $method === SignupMethod::Email => true,
                    $method->isOauthProvider() => $this->socialAuth->isProviderEnabled($method),
                    $method === SignupMethod::Phone => $this->phoneAuth->isReady(),
                    default => (bool) ($config['enabled'] ?? false),
                };

                return [
                    'value' => $method->value,
                    'label' => $method->label(),
                    'headline' => $config['headline'] ?? $method->label(),
                    'description' => $config['description'] ?? '',
                    'enabled' => $enabled,
                    'authorizationUrl' => match (true) {
                        $method->isOauthProvider() && $enabled
                            => route('auth.oauth.redirect', ['provider' => $method->value, 'intent' => $intent], absolute: false),
                        $method === SignupMethod::Phone && $enabled
                            => route('auth.phone.create', ['intent' => $intent], absolute: false),
                        default => null,
                    },
                ];
            })
            ->values()
            ->all();
    }
}
