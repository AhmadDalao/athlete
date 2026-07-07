<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableControls;
use App\Models\AthleteInvitation;
use App\Models\AuditLog;
use App\Services\InvitationDeliveryService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class InvitationsTable extends Component
{
    use WithPagination;
    use WithTableControls;

    public string $status = 'all';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function cancel(int $inviteId): void
    {
        $invite = AthleteInvitation::findOrFail($inviteId);
        $invite->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'invite.cancelled',
            'entity' => 'athlete_invitation',
            'entity_id' => $invite->id,
            'summary' => "Admin cancelled invite for {$invite->email}.",
            'ip_address' => request()->ip(),
        ]);
    }

    public function resend(int $inviteId, InvitationDeliveryService $delivery): void
    {
        $invite = AthleteInvitation::where('status', 'pending')->findOrFail($inviteId);
        $sent = $delivery->send($invite);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'invite.resent',
            'entity' => 'athlete_invitation',
            'entity_id' => $invite->id,
            'summary' => "Admin resent invite for {$invite->email}. Email ".($sent ? 'sent' : 'failed').'.',
            'ip_address' => request()->ip(),
        ]);

        session()->flash('status', $sent ? 'Invitation resent.' : 'Invitation resend logged as failed. Check mail settings.');
    }

    public function render()
    {
        $query = AthleteInvitation::with('coach')
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('email', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")
                ->orWhereHas('coach', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))))
            ->latest();

        return view('livewire.admin.invitations-table', [
            'invitations' => $this->paginateQuery($query),
        ])->layout('layouts.app', ['title' => 'Invitations']);
    }
}
