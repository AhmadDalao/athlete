<?php

namespace App\Http\Controllers\Invitation;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\AthleteInvitation;
use App\Models\CoachAthleteAssignment;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use App\Support\AthleteAccess;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AcceptStoreController extends Controller
{
    public function __invoke(Request $request, string $token, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        $invitation = AthleteInvitation::query()
            ->with(['coach.roles', 'invitedBy.roles'])
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        if (! $invitation->isOpen()) {
            throw ValidationException::withMessages(['invitation' => 'This invitation is no longer active. Ask your coach to resend it.']);
        }

        $existingUser = User::query()->where('email', $invitation->email)->with('roles')->first();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'primary_goal' => ['nullable', 'string', 'max:255'],
            'preferred_contact_method' => ['required', 'string', Rule::in(['email', 'phone'])],
            'terms_accepted' => ['accepted'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'terms_accepted.accepted' => 'You need to accept the platform terms to join this coach roster.',
        ]);

        if ($existingUser) {
            if (! $existingUser->hasRole(RoleName::Athlete)) {
                throw ValidationException::withMessages(['email' => 'This invitation email belongs to a non-athlete account. Contact support.']);
            }

            if (! Auth::attempt(['email' => $invitation->email, 'password' => $validated['password']])) {
                throw ValidationException::withMessages(['password' => 'The password does not match the existing athlete account.']);
            }

            /** @var User $athlete */
            $athlete = $existingUser;
            $athlete->forceFill([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? $athlete->phone,
                'primary_goal' => $validated['primary_goal'] ?? $athlete->primary_goal,
                'preferred_contact_method' => $validated['preferred_contact_method'],
            ])->save();
        } else {
            $athlete = User::query()->create([
                'name' => $validated['name'],
                'email' => Str::lower($invitation->email),
                'phone' => $validated['phone'] ?? $invitation->phone,
                'email_verified_at' => now(),
                'password' => Hash::make($validated['password']),
                'primary_goal' => $validated['primary_goal'] ?? $invitation->goal,
                'preferred_contact_method' => $validated['preferred_contact_method'],
                'registration_channel' => 'coach_invite',
            ]);
            $athlete->assignRole(RoleName::Athlete);

            event(new Registered($athlete));
            Auth::login($athlete);
        }

        $this->handleOtherActiveCoach($invitation, $athlete);

        $assignment = CoachAthleteAssignment::query()->firstOrNew([
            'coach_id' => $invitation->coach_id,
            'athlete_id' => $athlete->id,
        ]);

        $assignment->fill([
            'status' => CoachAthleteStatus::Active,
            'goal' => $invitation->goal ?? $assignment->goal,
            'notes' => $invitation->notes ?? $assignment->notes,
            'started_at' => now()->toDateString(),
            'ended_at' => null,
        ]);
        $assignment->syncLifecycleDates();
        $assignment->save();

        $invitation->forceFill([
            'status' => AthleteInvitation::STATUS_ACCEPTED,
            'accepted_user_id' => $athlete->id,
            'accepted_at' => now(),
        ])->save();

        $auditLogger->record(
            $request,
            'athlete_invitation.accepted',
            $invitation,
            "{$athlete->name} accepted invitation to {$invitation->coach->name}'s roster.",
            [
                'coach_id' => $invitation->coach_id,
                'athlete_id' => $athlete->id,
                'assignment_id' => $assignment->id,
            ],
        );

        return redirect()->intended($athlete->landingPath())->with('success', 'Invitation accepted. Your coach roster is connected.');
    }

    private function handleOtherActiveCoach(AthleteInvitation $invitation, User $athlete): void
    {
        if (! AthleteAccess::hasActiveOtherCoach($invitation->coach, $athlete)) {
            return;
        }

        $inviter = $invitation->invitedBy;
        $adminOverride = $inviter && ($inviter->hasRole(RoleName::Admin) || $inviter->hasRole(RoleName::Owner));

        if (! $adminOverride) {
            throw ValidationException::withMessages(['invitation' => 'This athlete already has an active coach. Ask an admin to move the roster relationship.']);
        }

        CoachAthleteAssignment::query()
            ->where('athlete_id', $athlete->id)
            ->where('coach_id', '!=', $invitation->coach_id)
            ->where('status', CoachAthleteStatus::Active->value)
            ->update([
                'status' => CoachAthleteStatus::Archived->value,
                'ended_at' => now()->toDateString(),
                'updated_at' => now(),
            ]);
    }
}
