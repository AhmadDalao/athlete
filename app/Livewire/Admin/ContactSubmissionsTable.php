<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableControls;
use App\Models\AuditLog;
use App\Models\ContactSubmission;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ContactSubmissionsTable extends Component
{
    use WithPagination;
    use WithTableControls;

    public string $status = 'all';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function markStatus(int $submissionId, string $status): void
    {
        abort_unless(in_array($status, ['new', 'reviewed', 'closed'], true), 422);

        $submission = ContactSubmission::findOrFail($submissionId);
        $submission->update(['status' => $status]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'contact.status_updated',
            'entity' => 'contact_submission',
            'entity_id' => $submission->id,
            'summary' => "Marked contact from {$submission->email} as {$status}.",
            'ip_address' => request()->ip(),
        ]);

        session()->flash('status', 'Contact submission updated.');
    }

    public function render()
    {
        $query = ContactSubmission::query()
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
                ->orWhere('message', 'like', "%{$this->search}%")))
            ->latest();

        return view('livewire.admin.contact-submissions-table', [
            'submissions' => $this->paginateQuery($query),
        ])->layout('layouts.app', ['title' => 'Contact submissions']);
    }
}
