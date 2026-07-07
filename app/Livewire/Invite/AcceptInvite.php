<?php

namespace App\Livewire\Invite;

use App\Models\AthleteInvitation;
use App\Models\CoachAthleteAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        abort_unless(strtolower($data['email']) === strtolower($this->invitation->email), 422);

        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => Hash::make($data['password']),
                'role' => 'athlete',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        if (! $user->wasRecentlyCreated) {
            $user->update(['name' => $data['name'], 'role' => 'athlete', 'status' => 'active']);
        }

        $user->syncDefaultPermissions();

        CoachAthleteAssignment::firstOrCreate([
            'coach_id' => $this->invitation->coach_id,
            'athlete_id' => $user->id,
        ], [
            'status' => 'active',
            'started_at' => today(),
        ]);

        $this->invitation->update(['status' => 'accepted', 'accepted_at' => now()]);

        Auth::login($user);

        return redirect()->route('app.home');
    }

    public function render()
    {
        return view('livewire.invite.accept-invite')->layout('layouts.guest', ['title' => 'Accept invite']);
    }
}
