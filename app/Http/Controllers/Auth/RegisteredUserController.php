<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleName;
use App\Enums\SignupMethod;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthMethodCatalog;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(AuthMethodCatalog $authMethods): Response
    {
        return Inertia::render('auth/register', [
            'signupMethods' => $authMethods->guestMethods('register'),
            'goalSuggestions' => [
                'Improve race prep',
                'Build strength',
                'Stay consistent',
                'Return from time away',
            ],
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'phone' => $this->normalizePhone($request->input('phone')),
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone' => ['required_if:preferred_contact_method,phone', 'nullable', 'string', 'regex:/^\+?[1-9][0-9]{7,14}$/', Rule::unique(User::class)],
            'primary_goal' => 'nullable|string|max:255',
            'preferred_contact_method' => ['required', 'string', Rule::in(['email', 'phone'])],
            'account_type' => 'required|string|in:'.implode(',', RoleName::registrationValues()),
            'terms_accepted' => 'accepted',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'phone.regex' => 'Use a valid phone number with country code when needed.',
            'terms_accepted.accepted' => 'You need to accept the platform terms to create an account.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->input('phone'),
            'password' => Hash::make($request->password),
            'primary_goal' => $request->input('primary_goal'),
            'preferred_contact_method' => $request->input('preferred_contact_method', 'email'),
            'registration_channel' => SignupMethod::Email->value,
        ]);
        $user->assignRole($request->string('account_type')->toString());

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }

    private function normalizePhone(mixed $phone): ?string
    {
        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        return preg_replace('/[^0-9+]/', '', trim($phone)) ?: null;
    }
}
