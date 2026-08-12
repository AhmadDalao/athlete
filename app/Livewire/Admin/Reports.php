<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableControls;
use App\Models\Organization;
use App\Queries\Admin\OperationsReportQuery;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Reports extends Component
{
    use WithPagination;
    use WithTableControls;

    public string $from = '';

    public string $to = '';

    public int $organizationId;

    public function mount(): void
    {
        $this->organizationId = (int) Auth::user()->current_organization_id;
        abort_unless($this->organizationId > 0, 409, 'Select an organization before opening reports.');
        $this->from = today()->subDays(29)->toDateString();
        $this->to = today()->toDateString();
    }

    public function updated($property): void
    {
        if (in_array($property, ['from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $organization = Organization::query()->whereKey($this->organizationId)->where('status', 'active')->firstOrFail();
        abort_unless(Auth::user()->isPlatformAdmin() || Auth::user()->organizationMemberships()
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->exists(), 403);

        return view('livewire.admin.reports', [
            'organization' => $organization,
            'coaches' => $this->paginateQuery(OperationsReportQuery::coaches($organization->id, $this->from, $this->to, $this->search)),
            'summary' => OperationsReportQuery::summary($organization->id, $this->from, $this->to),
        ])->layout('layouts.app', ['title' => 'Operations reports']);
    }
}
