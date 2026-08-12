<?php

namespace App\Livewire\Invite;

use App\Models\AthleteInvitation;
use App\Models\AthleteProfile;
use App\Models\AuditLog;
use App\Models\CoachAthleteAssignment;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AcceptInvite extends Component
{
    public AthleteInvitation $invitation;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public function mount(string $token): void
    {
        $this->invitation = AthleteInvitation::where('token', $token)->firstOrFail();
        abort_unless($this->invitation->isOpen(), 410);
        $this->name = $this->invitation->name ?: '';
        $this->email = $this->invitation->email;
    }

    public function accept()
    {
        $rateLimitKey = 'accept-invite:'.$this->invitation->id.':'.request()->ip();
        abort_if(RateLimiter::tooManyAttempts($rateLimitKey, 5), 429, 'Too many attempts. Try again in one minute.');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        abort_unless(strtolower($data['email']) === strtolower($this->invitation->email), 422);

        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($data['email'])])
            ->first();

        if ($existingUser?->isPlatformAdmin()) {
            throw ValidationException::withMessages([
                'email' => 'Platform staff accounts cannot accept athlete invitations.',
            ]);
        }

        if ($existingUser && ! Hash::check($data['password'], $existingUser->password)) {
            RateLimiter::hit($rateLimitKey, 60);

            throw ValidationException::withMessages([
                'password' => 'Use the current password for this existing account.',
            ]);
        }

        $user = DB::transaction(function () use ($data, $existingUser): User {
            $invitation = AthleteInvitation::query()
                ->lockForUpdate()
                ->findOrFail($this->invitation->id);

            abort_unless($invitation->isOpen(), 410);

            $coachIsActive = OrganizationMembership::query()
                ->where('organization_id', $invitation->organization_id)
                ->where('user_id', $invitation->coach_id)
                ->where('role', 'coach')
                ->where('status', 'active')
                ->exists();
            abort_unless($coachIsActive, 410);

            $user = $existingUser ?: User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => Hash::make($data['password']),
                'role' => 'athlete',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            abort_unless($user->status === 'active', 403);

            $membership = OrganizationMembership::query()
                ->where('organization_id', $invitation->organization_id)
                ->where('user_id', $user->id)
                ->first();

            if ($membership && $membership->role !== 'athlete') {
                throw ValidationException::withMessages([
                    'email' => 'This account already has a different role in this organization.',
                ]);
            }

            OrganizationMembership::query()->updateOrCreate(
                [
                    'organization_id' => $invitation->organization_id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => 'athlete',
                    'status' => 'active',
                    'joined_at' => $membership?->joined_at ?: now(),
                ],
            );

            AthleteProfile::query()->firstOrCreate([
                'organization_id' => $invitation->organization_id,
                'user_id' => $user->id,
            ]);

            CoachAthleteAssignment::query()->updateOrCreate([
                'organization_id' => $invitation->organization_id,
                'coach_id' => $invitation->coach_id,
                'athlete_id' => $user->id,
            ], [
                'status' => 'active',
                'started_at' => today(),
                'ended_at' => null,
            ]);

            $invitation->update(['status' => 'accepted', 'accepted_at' => now()]);
            $user->forceFill(['current_organization_id' => $invitation->organization_id])->saveQuietly();

            AuditLog::query()->create([
                'organization_id' => $invitation->organization_id,
                'user_id' => $user->id,
                'action' => 'invite.accepted',
                'entity' => 'athlete_invitation',
                'entity_id' => $invitation->id,
                'summary' => "Accepted athlete invitation for {$user->email}.",
                'ip_address' => request()->ip(),
            ]);

            if (! $existingUser) {
                $user->syncDefaultPermissions();
            }

            return $user;
        });

        RateLimiter::clear($rateLimitKey);
        Auth::login($user);

        return redirect()->route('app.home');
    }

    public function render()
    {
        return view('livewire.invite.accept-invite')->layout('layouts.guest', ['title' => 'Accept invite']);
    }
}
