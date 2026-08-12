<?php

namespace App\Livewire\Coach;

use App\Livewire\Concerns\WithTableControls;
use App\Models\AthleteInvitation;
use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Services\InvitationDeliveryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class InvitationsPanel extends Component
{
    use WithPagination;
    use WithTableControls;

    public string $name = '';

    public string $email = '';

    public function boot(): void
    {
        $this->authorizeAccess();
    }

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function invite(InvitationDeliveryService $delivery): void
    {
        $this->authorizeAccess();
        abort_unless(PlatformSetting::enabled('invitations_enabled', true), 423, 'Athlete invitations are currently paused.');

        $data = $this->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
        ]);

        $token = Str::random(48);
        $invite = AthleteInvitation::create([
            'organization_id' => Auth::user()->current_organization_id,
            'coach_id' => Auth::id(),
            'name' => $data['name'] ?: null,
            'email' => $data['email'],
            'token' => $token,
            'status' => 'pending',
            'expires_at' => now()->addDays((int) PlatformSetting::get('invite_expiry_days', '7')),
        ]);

        $sent = $delivery->send($invite);

        AuditLog::create([
            'organization_id' => $invite->organization_id,
            'user_id' => Auth::id(),
            'action' => 'invite.created',
            'entity' => 'athlete_invitation',
            'entity_id' => $invite->id,
            'summary' => "Invited {$invite->email}. Email ".($sent ? 'sent' : 'failed').'.',
            'ip_address' => request()->ip(),
        ]);

        $this->reset(['name', 'email']);
        session()->flash('status', 'Invitation created. If mail is configured, the athlete will receive it.');
    }

    public function cancel(int $inviteId): void
    {
        $this->authorizeAccess();
        $invite = $this->invitations()->findOrFail($inviteId);
        $invite->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        AuditLog::create([
            'organization_id' => $invite->organization_id,
            'user_id' => Auth::id(),
            'action' => 'invite.cancelled',
            'entity' => 'athlete_invitation',
            'entity_id' => $invite->id,
            'summary' => "Cancelled invite for {$invite->email}.",
            'ip_address' => request()->ip(),
        ]);
    }

    public function resend(int $inviteId, InvitationDeliveryService $delivery): void
    {
        $this->authorizeAccess();
        abort_unless(PlatformSetting::enabled('invitations_enabled', true), 423, 'Athlete invitations are currently paused.');

        $invite = $this->invitations()->where('status', 'pending')->findOrFail($inviteId);
        $sent = $delivery->send($invite);

        AuditLog::create([
            'organization_id' => $invite->organization_id,
            'user_id' => Auth::id(),
            'action' => 'invite.resent',
            'entity' => 'athlete_invitation',
            'entity_id' => $invite->id,
            'summary' => "Resent invite for {$invite->email}. Email ".($sent ? 'sent' : 'failed').'.',
            'ip_address' => request()->ip(),
        ]);

        session()->flash('status', $sent ? 'Invitation resent.' : 'Invitation resend logged as failed. Check mail settings.');
    }

    public function render()
    {
        $this->authorizeAccess();
        $query = $this->invitations()
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('email', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")))
            ->latest();

        return view('livewire.coach.invitations-panel', [
            'invitations' => $this->paginateQuery($query),
        ])->layout('layouts.app', ['title' => 'Invitations']);
    }

    private function authorizeAccess(): void
    {
        abort_unless(Auth::user()?->can('invitations.manage'), 403);
    }

    private function invitations()
    {
        return AthleteInvitation::query()
            ->withoutGlobalScope('organization')
            ->where('organization_id', Auth::user()->current_organization_id)
            ->where('coach_id', Auth::id());
    }
}
