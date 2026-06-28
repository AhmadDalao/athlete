<?php

namespace App\Services;

use App\Mail\AthleteInvitationMail;
use App\Models\AthleteInvitation;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AthleteInvitationService
{
    /**
     * @param  array{name:string,email:string,phone?:string|null,goal?:string|null,notes?:string|null,coach_id:int,invited_by_user_id:int|null}  $payload
     * @return array{invitation:AthleteInvitation,token:string}
     */
    public function create(array $payload): array
    {
        $token = AthleteInvitation::tokenPair();
        $expiryDays = $this->expiryDays();

        $invitation = AthleteInvitation::query()->create([
            'coach_id' => $payload['coach_id'],
            'invited_by_user_id' => $payload['invited_by_user_id'] ?? null,
            'name' => trim($payload['name']),
            'email' => Str::lower(trim($payload['email'])),
            'phone' => $payload['phone'] ?? null,
            'goal' => $payload['goal'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'token_hash' => $token['hash'],
            'status' => AthleteInvitation::STATUS_PENDING,
            'expires_at' => now()->addDays($expiryDays),
        ]);

        return [
            'invitation' => $invitation,
            'token' => $token['plain'],
        ];
    }

    public function resend(AthleteInvitation $invitation): string
    {
        return $invitation->refreshToken($this->expiryDays());
    }

    public function send(AthleteInvitation $invitation, string $token): void
    {
        $invitation->loadMissing('coach');

        Mail::to($invitation->email)->send(new AthleteInvitationMail(
            invitation: $invitation,
            acceptUrl: route('invitations.accept.show', ['token' => $token]),
        ));
    }

    private function expiryDays(): int
    {
        $value = (int) PlatformSetting::valueFor('athlete_invite_expiry_days');

        return max(1, $value > 0 ? $value : 14);
    }
}
