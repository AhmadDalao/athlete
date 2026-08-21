<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Livewire\Concerns\WithTableControls;
use App\Models\CoachAthleteAssignment;
use App\Models\User;
use App\Services\RosterAssignmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class RosterAssignments extends Component
{
    use AuthorizesComponentAccess;
    use WithPagination;
    use WithTableControls;

    public ?int $coachId = null;

    public ?int $athleteId = null;

    public string $status = 'active';

    public function assign(RosterAssignmentService $roster): void
    {
        $data = $this->validate([
            'coachId' => ['required', 'integer'],
            'athleteId' => ['required', 'integer', 'different:coachId'],
        ]);
        $roster->assign(Auth::user(), $data['coachId'], $data['athleteId']);
        $this->reset(['coachId', 'athleteId']);
        session()->flash('status', 'Coach and athlete assigned.');
    }

    public function end(int $assignmentId, RosterAssignmentService $roster): void
    {
        $assignment = CoachAthleteAssignment::query()->findOrFail($assignmentId);
        $roster->end(Auth::user(), $assignment);
        session()->flash('status', 'Roster assignment ended.');
    }

    public function reactivate(int $assignmentId, RosterAssignmentService $roster): void
    {
        $assignment = CoachAthleteAssignment::query()->findOrFail($assignmentId);
        abort_unless($assignment->organization_id === Auth::user()->current_organization_id, 403);
        $roster->assign(Auth::user(), $assignment->coach_id, $assignment->athlete_id);
        session()->flash('status', 'Roster assignment reactivated.');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $organizationId = Auth::user()->current_organization_id;
        $members = fn (string $role): Builder => User::query()
            ->whereHas('organizationMemberships', fn (Builder $query) => $query
                ->where('organization_id', $organizationId)
                ->where('role', $role)
                ->where('status', 'active'));
        $assignments = CoachAthleteAssignment::query()
            ->with(['coach', 'athlete'])
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereHas('coach', fn (Builder $query) => $query->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
                ->orWhereHas('athlete', fn (Builder $query) => $query->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))))
            ->latest('started_at');

        return view('livewire.admin.roster-assignments', [
            'assignments' => $this->paginateQuery($assignments),
            'coaches' => $members('coach')->orderBy('name')->get(),
            'athletes' => $members('athlete')->orderBy('name')->get(),
        ])->layout('layouts.app', ['title' => 'Roster assignments']);
    }

    protected function componentPermissions(): array
    {
        return ['roster.assign'];
    }
}
