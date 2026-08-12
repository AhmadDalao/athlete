<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class PasswordResetController extends Controller
{
    private const GENERIC_STATUS = 'If an active account matches that email, a password reset link has been sent.';

    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email']]);
        $user = User::query()->where('email', $credentials['email'])->where('status', 'active')->first();

        if ($user) {
            try {
                $status = Password::sendResetLink(['email' => $user->email]);

                EmailLog::query()->create([
                    'organization_id' => $user->current_organization_id,
                    'recipient' => $user->email,
                    'subject' => 'Reset your Throughline password',
                    'type' => 'password_reset',
                    'status' => $status === Password::RESET_LINK_SENT ? 'sent' : 'failed',
                    'error' => $status === Password::RESET_LINK_SENT ? null : __($status),
                ]);
            } catch (Throwable $exception) {
                EmailLog::query()->create([
                    'organization_id' => $user->current_organization_id,
                    'recipient' => $user->email,
                    'subject' => 'Reset your Throughline password',
                    'type' => 'password_reset',
                    'status' => 'failed',
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return back()->with('status', self::GENERIC_STATUS);
    }

    public function reset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($credentials, function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
            $user->tokens()->delete();

            event(new PasswordReset($user));

            AuditLog::query()->create([
                'organization_id' => $user->current_organization_id,
                'user_id' => $user->id,
                'action' => 'password.reset',
                'entity' => 'user',
                'entity_id' => $user->id,
                'summary' => "{$user->name} reset their password. Existing mobile sessions were revoked.",
                'ip_address' => request()->ip(),
            ]);
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
        }

        return redirect()->route('login')->with('status', 'Password updated. Log in with your new password.');
    }
}
