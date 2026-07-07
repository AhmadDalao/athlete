<?php

namespace App\Livewire\Coach;

use App\Livewire\Concerns\WithTableControls;
use App\Models\AthleteInvitation;
use App\Models\EmailLog;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class InvitationsPanel extends Component
{
    use WithPagination;
    use WithTableControls;

    public string $name = '';

    public string $email = '';

    public function invite(): void
    {
        $data = $this->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
        ]);

        $token = Str::random(48);
        $invite = AthleteInvitation::create([
            'coach_id' => Auth::id(),
            'name' => $data['name'] ?: null,
            'email' => $data['email'],
            'token' => $token,
            'status' => 'pending',
            'expires_at' => now()->addDays((int) PlatformSetting::get('invite_expiry_days', '7')),
        ]);

        $link = route('invites.accept', $invite->token);
        $subject = PlatformSetting::get('app_name', 'Throughline').' athlete invitation';

        try {
            Mail::raw('You were invited to Throughline by '.Auth::user()->name.". Accept here: {$link}", function ($message) use ($invite, $subject): void {
                $message->to($invite->email)->subject($subject);
            });
            EmailLog::create(['recipient' => $invite->email, 'subject' => $subject, 'type' => 'athlete_invite', 'status' => 'sent']);
        } catch (\Throwable $exception) {
            EmailLog::create(['recipient' => $invite->email, 'subject' => $subject, 'type' => 'athlete_invite', 'status' => 'failed', 'error' => $exception->getMessage()]);
        }

        $this->reset(['name', 'email']);
        session()->flash('status', 'Invitation created. If mail is configured, the athlete will receive it.');
    }

    public function cancel(int $inviteId): void
    {
        AthleteInvitation::where('coach_id', Auth::id())->findOrFail($inviteId)->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function render()
    {
        $query = AthleteInvitation::where('coach_id', Auth::id())
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('email', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")))
            ->latest();

        return view('livewire.coach.invitations-panel', [
            'invitations' => $this->paginateQuery($query),
        ])->layout('layouts.app', ['title' => 'Invitations']);
    }
}
