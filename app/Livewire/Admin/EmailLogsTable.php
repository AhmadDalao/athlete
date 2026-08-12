<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableControls;
use App\Models\EmailLog;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class EmailLogsTable extends Component
{
    use WithPagination;
    use WithTableControls;

    public string $status = 'all';

    public string $type = 'all';

    public string $from = '';

    public string $to = '';

    public int $organizationId;

    public function mount(): void
    {
        $this->organizationId = (int) Auth::user()->current_organization_id;
    }

    public function updated($property): void
    {
        if (in_array($property, ['status', 'type', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $this->authorizeOrganization();
        $baseQuery = fn (): Builder => EmailLog::query()->when(
            $this->organizationId > 0,
            fn (Builder $query) => $query->forOrganization($this->organizationId),
        );
        $query = $baseQuery()
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->type !== 'all', fn (Builder $query) => $query->where('type', $this->type))
            ->when($this->from !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $this->to))
            ->when($this->search, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('recipient', 'like', "%{$this->search}%")
                ->orWhere('subject', 'like', "%{$this->search}%")
                ->orWhere('type', 'like', "%{$this->search}%")
                ->orWhere('status', 'like', "%{$this->search}%")
                ->orWhere('error', 'like', "%{$this->search}%")))
            ->latest();

        return view('livewire.admin.email-logs-table', [
            'emailLogs' => $this->paginateQuery($query),
            'statuses' => $baseQuery()->whereNotNull('status')->distinct()->orderBy('status')->pluck('status'),
            'types' => $baseQuery()->whereNotNull('type')->distinct()->orderBy('type')->pluck('type'),
            'summary' => [
                'total' => $baseQuery()->count(),
                'sent' => $baseQuery()->where('status', 'sent')->count(),
                'failed' => $baseQuery()->where('status', 'failed')->count(),
            ],
        ])->layout('layouts.app', ['title' => 'Email logs']);
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
}
