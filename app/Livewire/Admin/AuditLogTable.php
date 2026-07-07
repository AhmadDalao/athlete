<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableControls;
use App\Models\AuditLog;
use App\Models\EmailLog;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogTable extends Component
{
    use WithPagination;
    use WithTableControls;

    public string $tab = 'audit';

    public function render()
    {
        $auditQuery = AuditLog::with('user')
            ->when($this->search, fn ($query) => $query->where('summary', 'like', "%{$this->search}%")
                ->orWhere('action', 'like', "%{$this->search}%"))
            ->latest();

        $emailQuery = EmailLog::query()
            ->when($this->search, fn ($query) => $query->where('recipient', 'like', "%{$this->search}%")
                ->orWhere('subject', 'like', "%{$this->search}%"))
            ->latest();

        return view('livewire.admin.audit-log-table', [
            'auditLogs' => $this->tab === 'audit' ? $this->paginateQuery($auditQuery) : collect(),
            'emailLogs' => $this->tab === 'email' ? $this->paginateQuery($emailQuery) : collect(),
        ])->layout('layouts.app', ['title' => 'Logs']);
    }
}
