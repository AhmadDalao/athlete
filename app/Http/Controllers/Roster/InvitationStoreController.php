<?php

namespace App\Http\Controllers\Roster;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Mail\AthleteInvitationMail;
use App\Models\AthleteInvitation;
use App\Models\CoachAthleteAssignment;
use App\Models\EmailDeliveryLog;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\AthleteInvitationService;
use App\Services\PlatformAuditLogger;
use App\Support\AthleteAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class InvitationStoreController extends Controller
{
    public function __invoke(Request $request, AthleteInvitationService $invitations, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless(AthleteAccess::canInviteAthletes($viewer), 403);

        $validated = $request->validate([
            'coach_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'goal' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $coachId = $viewer->hasRole(RoleName::Admin) || $viewer->hasRole(RoleName::Owner)
            ? (int) ($validated['coach_id'] ?? $viewer->id)
            : $viewer->id;

        /** @var User|null $coach */
        $coach = User::query()->role(RoleName::Coach)->find($coachId);

        if (! $coach) {
            throw ValidationException::withMessages(['coach_id' => 'Choose a valid coach account.']);
        }

        $email = Str::lower(trim($validated['email']));
        $existing = User::query()->where('email', $email)->with('roles')->first();

        if ($existing && ! $existing->hasRole(RoleName::Athlete)) {
            throw ValidationException::withMessages(['email' => 'This email belongs to a non-athlete account. Use admin users to change roles first.']);
        }

        if ($existing) {
            $alreadyAssigned = CoachAthleteAssignment::query()
                ->where('coach_id', $coach->id)
                ->where('athlete_id', $existing->id)
                ->whereIn('status', [CoachAthleteStatus::Active->value, CoachAthleteStatus::Paused->value])
                ->exists();

            if ($alreadyAssigned) {
                throw ValidationException::withMessages(['email' => 'This athlete is already on this coach roster.']);
            }

            if (! ($viewer->hasRole(RoleName::Admin) || $viewer->hasRole(RoleName::Owner)) && AthleteAccess::hasActiveOtherCoach($coach, $existing)) {
                throw ValidationException::withMessages(['email' => 'This athlete already has an active coach. Ask an admin to move them.']);
            }
        }

        $openInviteExists = AthleteInvitation::query()
            ->where('coach_id', $coach->id)
            ->where('email', $email)
            ->open()
            ->exists();

        if ($openInviteExists) {
            throw ValidationException::withMessages(['email' => 'An active invitation already exists for this athlete and coach. Resend it instead.']);
        }

        $created = $invitations->create([
            'coach_id' => $coach->id,
            'invited_by_user_id' => $viewer->id,
            'name' => $validated['name'],
            'email' => $email,
            'phone' => $validated['phone'] ?? null,
            'goal' => $validated['goal'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->sendSafely($created['invitation'], $created['token'], $request);

        $auditLogger->record(
            $request,
            'athlete_invitation.created',
            $created['invitation'],
            "{$viewer->name} invited {$created['invitation']->email} to {$coach->name}'s roster.",
            [
                'coach_id' => $coach->id,
                'email' => $email,
            ],
        );

        return to_route('roster.invitations.index')->with('success', 'Athlete invitation created.');
    }

    private function sendSafely(AthleteInvitation $invitation, string $token, Request $request): void
    {
        try {
            app(AthleteInvitationService::class)->send($invitation, $token);
        } catch (Throwable $exception) {
            EmailDeliveryLog::query()->create([
                'status' => 'failed',
                'mailer' => (string) config('mail.default'),
                'mailable' => class_basename(AthleteInvitationMail::class),
                'recipient' => $invitation->email,
                'subject' => PlatformSetting::valueFor('athlete_invite_subject'),
                'source' => 'athlete-invitations',
                'error' => $exception->getMessage(),
                'metadata' => [
                    'invitation_id' => $invitation->id,
                    'ip' => $request->ip(),
                ],
                'attempted_at' => now(),
                'failed_at' => now(),
            ]);
        }
    }
}
