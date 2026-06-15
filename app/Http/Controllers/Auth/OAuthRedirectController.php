<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleName;
use App\Exceptions\SocialAuthException;
use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class OAuthRedirectController extends Controller
{
    public function __invoke(
        Request $request,
        string $provider,
        SocialAuthService $socialAuth,
    ): RedirectResponse|SymfonyRedirectResponse {
        $fallbackRoute = $request->string('intent')->toString() === 'register' ? 'register' : 'login';

        try {
            $signupMethod = $socialAuth->resolveProvider($provider);
        } catch (SocialAuthException $exception) {
            return to_route($fallbackRoute)->with('status', $exception->getMessage());
        }

        $payload = Validator::validate($request->all(), [
            'intent' => ['required', 'string', Rule::in(['login', 'register'])],
            'account_type' => [
                Rule::requiredIf(fn () => $request->string('intent')->toString() === 'register'),
                'nullable',
                'string',
                Rule::in(RoleName::registrationValues()),
            ],
        ]);

        $request->session()->put(SocialAuthService::SESSION_CONTEXT_KEY, [
            'provider' => $signupMethod->value,
            'intent' => $payload['intent'],
            'account_type' => $payload['account_type'] ?? null,
        ]);

        return Socialite::driver($signupMethod->value)
            ->redirectUrl($socialAuth->redirectUrlFor($signupMethod))
            ->scopes($socialAuth->scopesFor($signupMethod))
            ->redirect();
    }
}
