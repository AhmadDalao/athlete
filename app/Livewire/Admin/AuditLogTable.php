<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Livewire\Concerns\WithTableControls;
use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogTable extends Component
{
    use AuthorizesComponentAccess;
    use WithPagination;
    use WithTableControls;

    public string $auditAction = 'all';

    public string $auditEntity = 'all';

    public string $from = '';

    public string $to = '';

    public int $organizationId;

    public function mount(): void
    {
        $this->organizationId = (int) Auth::user()->current_organization_id;
    }

    public function updated($property): void
    {
        if (in_array($property, ['auditAction', 'auditEntity', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $this->authorizeOrganization();
        $baseQuery = fn (): Builder => AuditLog::query()->when(
            $this->organizationId > 0,
            fn (Builder $query) => $query->forOrganization($this->organizationId),
        );
        $auditQuery = $baseQuery()
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

        return view('livewire.admin.audit-log-table', [
            'auditLogs' => $this->paginateQuery($auditQuery),
            'auditActions' => $baseQuery()->whereNotNull('action')->distinct()->orderBy('action')->pluck('action'),
            'auditEntities' => $baseQuery()->whereNotNull('entity')->distinct()->orderBy('entity')->pluck('entity'),
        ])->layout('layouts.app', ['title' => 'Audit log']);
    }

    private function authorizeOrganization(): void
    {
        if ($this->organizationId === 0) {
            abort_unless(Auth::user()->isPlatformAdmin(), 403);

            return;
        }

        abort_unless(Organization::query()->whereKey($this->organizationId)->where('status', 'active')->exists(), 404);
        abort_unless(Auth::user()->isPlatformAdmin() || Auth::user()->organizationMemberships()
            ->where('organization_id', $this->organizationId)
            ->where('status', 'active')
            ->exists(), 403);
    }

    protected function componentPermissions(): array
    {
        return ['admin.audit'];
    }
}
