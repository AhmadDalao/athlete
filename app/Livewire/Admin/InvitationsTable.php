<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableControls;
use App\Models\AuditLog;
use App\Queries\Admin\ManagedInvitationQuery;
use App\Services\InvitationDeliveryService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class InvitationsTable extends Component
{
    use WithPagination;
    use WithTableControls;

    public string $status = 'all';

    public function boot(): void
    {
        $this->authorizeAccess();
    }

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function cancel(int $inviteId): void
    {
        $this->authorizeAccess();
        $invite = ManagedInvitationQuery::findVisibleOrFail(Auth::user(), $inviteId);
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
            'summary' => "Admin cancelled invite for {$invite->email}.",
            'ip_address' => request()->ip(),
        ]);
    }

    public function resend(int $inviteId, InvitationDeliveryService $delivery): void
    {
        $this->authorizeAccess();
        $invite = ManagedInvitationQuery::visibleTo(Auth::user())
            ->where('status', 'pending')
            ->findOrFail($inviteId);
        $sent = $delivery->send($invite);

        AuditLog::create([
            'organization_id' => $invite->organization_id,
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
        $this->authorizeAccess();
        $query = ManagedInvitationQuery::visibleTo(Auth::user())
            ->with('coach')
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

    private function authorizeAccess(): void
    {
        abort_unless(Auth::user()?->can('invitations.manage'), 403);
    }
}
