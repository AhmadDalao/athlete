<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PhoneAuthChallenge;
use App\Services\PhoneAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PhoneAuthPageController extends Controller
{
    public function __invoke(Request $request, PhoneAuthService $phoneAuth): Response|RedirectResponse
    {
        $intent = $this->intentFromRequest($request);
        $draftAccountType = $request->string('account_type')->toString();
        $draftAccountType = in_array($draftAccountType, ['coach', 'athlete'], true) ? $draftAccountType : 'athlete';

        if (! $phoneAuth->isReady()) {
            return to_route($intent === 'register' ? 'register' : 'login')
                ->with('status', 'Phone authentication is not configured yet.');
        }

        $challenge = PhoneAuthChallenge::query()
            ->whereKey($request->session()->get(PhoneAuthService::SESSION_CHALLENGE_KEY))
            ->when($intent, fn ($query) => $query->where('intent', $intent))
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $challenge && $request->session()->has(PhoneAuthService::SESSION_CHALLENGE_KEY)) {
            $request->session()->forget(PhoneAuthService::SESSION_CHALLENGE_KEY);
        }

        return Inertia::render('auth/phone', [
            'intent' => $intent,
            'status' => $request->session()->get('status'),
            'challenge' => $challenge ? [
                'phoneMasked' => $phoneAuth->maskedPhone($challenge->phone),
                'expiresAt' => $challenge->expires_at->toDateTimeString(),
                'sentAt' => $challenge->sent_at->toDateTimeString(),
            ] : null,
            'draft' => [
                'phone' => $challenge?->phone ?? '',
                'email' => $challenge?->email ?? '',
                'name' => $challenge?->name ?? '',
                'accountType' => $challenge?->account_type ?? $draftAccountType,
                'primaryGoal' => $challenge?->primary_goal ?? '',
                'preferredContactMethod' => $challenge?->preferred_contact_method ?? 'phone',
            ],
        ]);
    }

    private function intentFromRequest(Request $request): string
    {
        $payload = validator($request->all(), [
            'intent' => ['nullable', 'string', Rule::in(['login', 'register'])],
        ])->validate();

        return $payload['intent'] ?? 'login';
    }
}
