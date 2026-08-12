<?php

namespace App\Livewire\Coach;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Livewire\Concerns\WithTableControls;
use App\Queries\Coach\CoachReportQuery;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Reports extends Component
{
    use AuthorizesComponentAccess;
    use WithPagination;
    use WithTableControls;

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
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
        $coachId = (int) Auth::id();

        return view('livewire.coach.reports', [
            'athletes' => $this->paginateQuery(CoachReportQuery::athletes($coachId, $this->from, $this->to, $this->search)),
            'summary' => CoachReportQuery::summary($coachId, $this->from, $this->to),
        ])->layout('layouts.app', ['title' => 'Reports']);
    }

    protected function componentPermissions(): array
    {
        return ['reports.view'];
    }
}
