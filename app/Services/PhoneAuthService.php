<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\SignupMethod;
use App\Models\PhoneAuthChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PhoneAuthService
{
    public const SESSION_CHALLENGE_KEY = 'phone_auth.challenge_id';

    public function isReady(): bool
    {
        if (! (bool) config('throughline.auth.phone.enabled', false)) {
            return false;
        }

        return match ($this->driver()) {
            'log' => ! app()->environment('production'),
            'twilio' => $this->twilioConfigured(),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function startChallenge(string $intent, array $payload): PhoneAuthChallenge
    {
        $this->guardReady();

        $phone = $this->normalizePhone($payload['phone'] ?? null);

        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => 'Use a valid phone number with country code when needed.',
            ]);
        }

        if ($intent === 'login') {
            if (! User::query()->where('phone', $phone)->exists()) {
                throw ValidationException::withMessages([
                    'phone' => 'No Throughline account exists for this phone number yet.',
                ]);
            }
        } elseif ($intent === 'register') {
            $email = Str::lower(trim((string) ($payload['email'] ?? '')));

            if (User::query()->where('phone', $phone)->exists()) {
                throw ValidationException::withMessages([
                    'phone' => 'A Throughline account already uses this phone number.',
                ]);
            }

            if ($email !== '' && User::query()->where('email', $email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'A Throughline account already uses this email address.',
                ]);
            }
        } else {
            throw new RuntimeException('Phone auth intent is invalid.');
        }

        PhoneAuthChallenge::query()
            ->where('intent', $intent)
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->update([
                'consumed_at' => now(),
            ]);

        $code = $this->generateCode();

        $challenge = PhoneAuthChallenge::query()->create([
            'intent' => $intent,
            'phone' => $phone,
            'email' => $intent === 'register' ? Str::lower(trim((string) ($payload['email'] ?? ''))) : null,
            'name' => $intent === 'register' ? trim((string) ($payload['name'] ?? '')) : null,
            'account_type' => $intent === 'register' ? trim((string) ($payload['account_type'] ?? '')) : null,
            'preferred_contact_method' => $intent === 'register'
                ? trim((string) ($payload['preferred_contact_method'] ?? 'phone'))
                : 'phone',
            'delivery_driver' => $this->driver(),
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'sent_at' => now(),
            'expires_at' => now()->addMinutes(max((int) config('throughline.auth.phone.ttl_minutes', 10), 1)),
            'primary_goal' => $intent === 'register' ? $this->nullableString($payload['primary_goal'] ?? null) : null,
        ]);

        $this->deliverCode($challenge->phone, $code);

        return $challenge;
    }

    public function verifyChallenge(PhoneAuthChallenge $challenge, string $code): User
    {
        $this->guardReady();
        $this->guardChallengeState($challenge);

        $challenge->increment('attempts');
        $challenge->refresh();

        if (! Hash::check(trim($code), $challenge->code_hash)) {
            if ($challenge->attempts >= max((int) config('throughline.auth.phone.max_attempts', 5), 1)) {
                $challenge->forceFill([
                    'consumed_at' => now(),
                ])->save();

                throw ValidationException::withMessages([
                    'code' => 'That code was wrong too many times. Start again and request a fresh one.',
                ]);
            }

            throw ValidationException::withMessages([
                'code' => 'That verification code is wrong.',
            ]);
        }

        $user = $challenge->intent === 'register'
            ? $this->registerUserFromChallenge($challenge)
            : $this->loginUserFromChallenge($challenge);

        $challenge->forceFill([
            'consumed_at' => now(),
        ])->save();

        return $user;
    }

    public function maskedPhone(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return $phone;
        }

        return str_repeat('•', max(strlen($phone) - 4, 0)).substr($phone, -4);
    }

    public function normalizePhone(mixed $phone): ?string
    {
        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        return preg_replace('/[^0-9+]/', '', trim($phone)) ?: null;
    }

    private function registerUserFromChallenge(PhoneAuthChallenge $challenge): User
    {
        if (! is_string($challenge->account_type) || ! in_array($challenge->account_type, RoleName::registrationValues(), true)) {
            throw ValidationException::withMessages([
                'phone' => 'That phone sign-up request is missing the account type. Start again.',
            ]);
        }

        if (User::query()->where('phone', $challenge->phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'A Throughline account already uses this phone number.',
            ]);
        }

        if ($challenge->email && User::query()->where('email', $challenge->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'A Throughline account already uses this email address.',
            ]);
        }

        return DB::transaction(function () use ($challenge): User {
            $user = User::query()->create([
                'name' => $challenge->name ?: 'Phone user',
                'email' => $challenge->email,
                'phone' => $challenge->phone,
                'password' => Hash::make(Str::password(40)),
                'primary_goal' => $challenge->primary_goal,
                'preferred_contact_method' => $challenge->preferred_contact_method ?: 'phone',
                'registration_channel' => SignupMethod::Phone->value,
            ]);

            $user->forceFill([
                'phone_verified_at' => now(),
            ])->save();

            $user->assignRole($challenge->account_type);

            return $user->fresh(['roles', 'socialAccounts']);
        });
    }

    private function loginUserFromChallenge(PhoneAuthChallenge $challenge): User
    {
        $user = User::query()->where('phone', $challenge->phone)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => 'No Throughline account exists for this phone number yet.',
            ]);
        }

        if (! $user->phone_verified_at) {
            $user->forceFill([
                'phone_verified_at' => now(),
            ])->save();
        }

        return $user->fresh(['roles', 'socialAccounts']);
    }

    private function guardReady(): void
    {
        if (! $this->isReady()) {
            throw new RuntimeException('Phone authentication is not configured yet.');
        }
    }

    private function guardChallengeState(PhoneAuthChallenge $challenge): void
    {
        if ($challenge->consumed_at) {
            throw ValidationException::withMessages([
                'code' => 'That verification code was already used. Start again.',
            ]);
        }

        if ($challenge->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'That verification code expired. Request a fresh one.',
            ]);
        }

        if ($challenge->attempts >= max((int) config('throughline.auth.phone.max_attempts', 5), 1)) {
            throw ValidationException::withMessages([
                'code' => 'That verification code already hit the attempt limit. Start again.',
            ]);
        }
    }

    private function deliverCode(string $phone, string $code): void
    {
        $message = sprintf(
            'Throughline verification code: %s. It expires in %d minute(s).',
            $code,
            max((int) config('throughline.auth.phone.ttl_minutes', 10), 1),
        );

        match ($this->driver()) {
            'log' => Log::info('Throughline phone verification code issued.', [
                'phone' => $phone,
                'code' => $code,
            ]),
            'twilio' => $this->sendViaTwilio($phone, $message),
            default => throw new RuntimeException('Phone authentication driver is not supported.'),
        };
    }

    private function sendViaTwilio(string $phone, string $message): void
    {
        if (! $this->twilioConfigured()) {
            throw new RuntimeException('Twilio SMS delivery is not configured yet.');
        }

        $accountSid = (string) config('throughline.auth.phone.twilio.account_sid');
        $authToken = (string) config('throughline.auth.phone.twilio.auth_token');

        $payload = [
            'To' => $phone,
            'Body' => $message,
        ];

        if (filled(config('throughline.auth.phone.twilio.messaging_service_sid'))) {
            $payload['MessagingServiceSid'] = (string) config('throughline.auth.phone.twilio.messaging_service_sid');
        } else {
            $payload['From'] = (string) config('throughline.auth.phone.twilio.from');
        }

        Http::asForm()
            ->acceptJson()
            ->withBasicAuth($accountSid, $authToken)
            ->post(sprintf(
                'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json',
                $accountSid,
            ), $payload)
            ->throw();
    }

    private function twilioConfigured(): bool
    {
        return filled(config('throughline.auth.phone.twilio.account_sid'))
            && filled(config('throughline.auth.phone.twilio.auth_token'))
            && (
                filled(config('throughline.auth.phone.twilio.from'))
                || filled(config('throughline.auth.phone.twilio.messaging_service_sid'))
            );
    }

    private function driver(): string
    {
        return Str::lower(trim((string) config('throughline.auth.phone.driver', 'log')));
    }

    private function generateCode(): string
    {
        $digits = max((int) config('throughline.auth.phone.otp_digits', 6), 4);
        $max = (10 ** $digits) - 1;

        return str_pad((string) random_int(0, $max), $digits, '0', STR_PAD_LEFT);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
