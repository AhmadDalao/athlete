<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableControls;
use App\Models\AthleteInvitation;
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
        AthleteInvitation::findOrFail($inviteId)->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function render()
    {
        $query = AthleteInvitation::with('coach')
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('email', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")))
            ->latest();

        return view('livewire.admin.invitations-table', [
            'invitations' => $this->paginateQuery($query),
        ])->layout('layouts.app', ['title' => 'Invitations']);
    }
}
