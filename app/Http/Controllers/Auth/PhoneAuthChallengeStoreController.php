<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Services\PhoneAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PhoneAuthChallengeStoreController extends Controller
{
    public function __invoke(Request $request, PhoneAuthService $phoneAuth): RedirectResponse
    {
        $intent = $request->string('intent')->toString() === 'register' ? 'register' : 'login';

        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'phone' => $phoneAuth->normalizePhone($request->input('phone')),
        ]);

        $payload = $request->validate([
            'intent' => ['required', 'string', Rule::in(['login', 'register'])],
            'phone' => ['required', 'string', 'regex:/^\+?[1-9][0-9]{7,14}$/'],
            'name' => [Rule::requiredIf($intent === 'register'), 'nullable', 'string', 'max:255'],
            'email' => [Rule::requiredIf($intent === 'register'), 'nullable', 'email', 'max:255'],
            'account_type' => [Rule::requiredIf($intent === 'register'), 'nullable', 'string', Rule::in(RoleName::registrationValues())],
            'primary_goal' => ['nullable', 'string', 'max:255'],
            'preferred_contact_method' => ['nullable', 'string', Rule::in(['email', 'phone'])],
            'terms_accepted' => $intent === 'register' ? ['accepted'] : ['nullable'],
        ], [
            'phone.regex' => 'Use a valid phone number with country code when needed.',
            'terms_accepted.accepted' => 'You need to accept the platform terms to create an account.',
        ]);

        $challenge = $phoneAuth->startChallenge($intent, $payload);

        $request->session()->put(PhoneAuthService::SESSION_CHALLENGE_KEY, $challenge->id);

        return to_route('auth.phone.create', ['intent' => $intent])
            ->with('status', sprintf(
                'Verification code sent to %s. Enter it below before it expires.',
                $phoneAuth->maskedPhone($challenge->phone),
            ));
    }
}
