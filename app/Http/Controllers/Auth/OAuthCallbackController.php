<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\SocialAuthException;
use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class OAuthCallbackController extends Controller
{
    public function __invoke(
        Request $request,
        string $provider,
        SocialAuthService $socialAuth,
    ): RedirectResponse {
        $context = $request->session()->pull(SocialAuthService::SESSION_CONTEXT_KEY, []);
        $intent = $context['intent'] ?? 'login';
        $accountType = $context['account_type'] ?? null;
        $fallbackRoute = $intent === 'register' ? 'register' : 'login';

        try {
            $signupMethod = $socialAuth->resolveProvider($provider);
        } catch (SocialAuthException $exception) {
            return to_route($fallbackRoute)->with('status', $exception->getMessage());
        }

        if ($request->filled('error')) {
            return to_route($fallbackRoute)->with('status', sprintf('%s sign-in was canceled before it finished.', $signupMethod->label()));
        }

        try {
            $providerUser = Socialite::driver($signupMethod->value)->user();
            $user = $socialAuth->syncUserFromProvider($signupMethod, $providerUser, $intent, $accountType);

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (SocialAuthException $exception) {
            return to_route($fallbackRoute)->with('status', $exception->getMessage());
        } catch (Throwable $exception) {
            Log::warning('Social authentication callback failed.', [
                'provider' => $signupMethod->value,
                'intent' => $intent,
                'error' => $exception->getMessage(),
            ]);

            return to_route($fallbackRoute)->with('status', sprintf('%s sign-in could not be completed. Check the credentials and callback URL, then try again.', $signupMethod->label()));
        }
    }
}
