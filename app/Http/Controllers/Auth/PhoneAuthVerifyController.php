<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PhoneAuthChallenge;
use App\Services\PhoneAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PhoneAuthVerifyController extends Controller
{
    public function __invoke(Request $request, PhoneAuthService $phoneAuth): RedirectResponse
    {
        $payload = $request->validate([
            'intent' => ['required', 'string', 'in:login,register'],
            'code' => ['required', 'string', 'min:4', 'max:10'],
        ]);

        $challengeId = $request->session()->get(PhoneAuthService::SESSION_CHALLENGE_KEY);
        $challenge = $challengeId
            ? PhoneAuthChallenge::query()
                ->whereKey($challengeId)
                ->where('intent', $payload['intent'])
                ->first()
            : null;

        if (! $challenge) {
            throw ValidationException::withMessages([
                'code' => 'Start again and request a fresh verification code.',
            ]);
        }

        $user = $phoneAuth->verifyChallenge($challenge, $payload['code']);

        Auth::login($user);
        $request->session()->forget(PhoneAuthService::SESSION_CHALLENGE_KEY);
        $request->session()->regenerate();

        return redirect()->intended($user->landingPath());
    }
}
