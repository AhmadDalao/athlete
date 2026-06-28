<?php

namespace App\Http\Controllers\Invitation;

use App\Http\Controllers\Controller;
use App\Models\AthleteInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcceptShowController extends Controller
{
    public function __invoke(Request $request, string $token): Response
    {
        $invitation = AthleteInvitation::query()
            ->with('coach')
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        $existingUser = User::query()->where('email', $invitation->email)->exists();
        $status = $this->publicStatus($invitation);

        return Inertia::render('invitations/accept', [
            'token' => $token,
            'status' => $status,
            'invitation' => [
                'name' => $invitation->name,
                'email' => $invitation->email,
                'phone' => $invitation->phone,
                'goal' => $invitation->goal,
                'expiresAt' => $invitation->expires_at?->toDateTimeString(),
                'coach' => [
                    'name' => $invitation->coach?->name,
                    'email' => $invitation->coach?->email,
                ],
            ],
            'existingUser' => $existingUser,
        ]);
    }

    private function publicStatus(AthleteInvitation $invitation): string
    {
        if ($invitation->status !== AthleteInvitation::STATUS_PENDING) {
            return $invitation->status;
        }

        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            return AthleteInvitation::STATUS_EXPIRED;
        }

        return AthleteInvitation::STATUS_PENDING;
    }
}
