<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'status' => 'active'], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'These credentials do not match an active account.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'login',
            'entity' => 'user',
            'entity_id' => $user->id,
            'summary' => "{$user->name} signed in.",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->intended($user->landingPath());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
