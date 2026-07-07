<?php

namespace App\Livewire\Coach;

use App\Livewire\Concerns\WithTableControls;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AthletesTable extends Component
{
    use WithPagination;
    use WithTableControls;

    public function render()
    {
        $coachId = Auth::id();
        $query = User::query()
            ->where('role', 'athlete')
            ->whereHas('athleteAssignments', fn ($query) => $query->where('coach_id', $coachId)->where('status', 'active'))
            ->withCount(['progressEntries', 'workoutLogs'])
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('primary_goal', 'like', "%{$this->search}%")))
            ->orderBy('name');

        return view('livewire.coach.athletes-table', [
            'athletes' => $this->paginateQuery($query),
        ])->layout('layouts.app', ['title' => 'My athletes']);
    }
}
