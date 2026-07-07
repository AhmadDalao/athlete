<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableControls;
use App\Models\AuditLog;
use App\Models\EmailLog;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogTable extends Component
{
    use WithPagination;
    use WithTableControls;

    public string $tab = 'audit';

    public string $auditAction = 'all';

    public string $auditEntity = 'all';

    public string $emailStatus = 'all';

    public string $emailType = 'all';

    public string $from = '';

    public string $to = '';

    public function setTab(string $tab): void
    {
        abort_unless(in_array($tab, ['audit', 'email'], true), 422);

        $this->tab = $tab;
        $this->resetPage();
    }

    public function updated($property): void
    {
        if (in_array($property, ['auditAction', 'auditEntity', 'emailStatus', 'emailType', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $auditQuery = AuditLog::query()
            ->with('user')
            ->when($this->auditAction !== 'all', fn (Builder $query) => $query->where('action', $this->auditAction))
            ->when($this->auditEntity !== 'all', fn (Builder $query) => $query->where('entity', $this->auditEntity))
            ->when($this->from !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $this->to))
            ->when($this->search, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('summary', 'like', "%{$this->search}%")
                ->orWhere('action', 'like', "%{$this->search}%")
                ->orWhere('entity', 'like', "%{$this->search}%")
                ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$this->search}%"))))
            ->latest();

        $emailQuery = EmailLog::query()
            ->when($this->emailStatus !== 'all', fn (Builder $query) => $query->where('status', $this->emailStatus))
            ->when($this->emailType !== 'all', fn (Builder $query) => $query->where('type', $this->emailType))
            ->when($this->from !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $this->to))
            ->when($this->search, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('recipient', 'like', "%{$this->search}%")
                ->orWhere('subject', 'like', "%{$this->search}%")
                ->orWhere('type', 'like', "%{$this->search}%")
                ->orWhere('status', 'like', "%{$this->search}%")
                ->orWhere('error', 'like', "%{$this->search}%")))
            ->latest();

        return view('livewire.admin.audit-log-table', [
            'auditLogs' => $this->tab === 'audit' ? $this->paginateQuery($auditQuery) : collect(),
            'emailLogs' => $this->tab === 'email' ? $this->paginateQuery($emailQuery) : collect(),
            'auditActions' => AuditLog::query()->whereNotNull('action')->distinct()->orderBy('action')->pluck('action'),
            'auditEntities' => AuditLog::query()->whereNotNull('entity')->distinct()->orderBy('entity')->pluck('entity'),
            'emailStatuses' => EmailLog::query()->whereNotNull('status')->distinct()->orderBy('status')->pluck('status'),
            'emailTypes' => EmailLog::query()->whereNotNull('type')->distinct()->orderBy('type')->pluck('type'),
        ])->layout('layouts.app', ['title' => 'Logs']);
    }
}
