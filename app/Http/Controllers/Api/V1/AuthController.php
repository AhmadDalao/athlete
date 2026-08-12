<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\AuditLog;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    private const GENERIC_RESET_STATUS = 'If an active account matches that email, a password reset link has been sent.';

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', (string) $request->string('email'))->first();

        if (! $user || $user->status !== 'active' || ! Hash::check((string) $request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match an active account.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken((string) $request->string('device_name'), ['mobile'])->plainTextToken;

        AuditLog::query()->create([
            'organization_id' => $user->current_organization_id,
            'user_id' => $user->id,
            'action' => 'api.login',
            'entity' => 'user',
            'entity_id' => $user->id,
            'summary' => "{$user->name} signed in from a mobile client.",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => UserResource::make($user),
                'organizations' => OrganizationResource::collection($user->organizations()->wherePivot('status', 'active')->get()),
            ],
            'meta' => (object) [],
            'links' => (object) [],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => UserResource::make($request->user()),
                'organizations' => OrganizationResource::collection($request->user()->organizations()->wherePivot('status', 'active')->get()),
            ],
            'meta' => (object) [],
            'links' => (object) [],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['data' => ['logged_out' => true], 'meta' => (object) [], 'links' => (object) []]);
    }

    public function revokeAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['data' => ['revoked' => true], 'meta' => (object) [], 'links' => (object) []]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = (string) $request->string('email');
        $user = User::query()->where('email', $email)->where('status', 'active')->first();

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

        return response()->json([
            'data' => ['message' => self::GENERIC_RESET_STATUS],
            'meta' => (object) [],
            'links' => (object) [],
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset($request->validated(), function (User $user, string $password): void {
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
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json([
            'data' => ['message' => 'Password updated. Log in with your new password.'],
            'meta' => (object) [],
            'links' => (object) [],
        ]);
    }
}
