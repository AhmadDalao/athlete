<?php

namespace App\Livewire\Coach;

use App\Livewire\Concerns\WithTableControls;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProgramsTable extends Component
{
    use WithPagination;
    use WithTableControls;

    public ?int $athleteId = null;

    public string $title = '';

    public string $goal = '';

    public string $status = 'active';

    public ?string $startsOn = null;

    public ?string $endsOn = null;

    public string $notes = '';

    public function createProgram(): void
    {
        $data = $this->validate([
            'athleteId' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:160'],
            'goal' => ['nullable', 'string', 'max:160'],
            'status' => ['required', 'in:draft,active,archived'],
            'startsOn' => ['nullable', 'date'],
            'endsOn' => ['nullable', 'date', 'after_or_equal:startsOn'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $allowed = Auth::user()->coachAssignments()->where('athlete_id', $data['athleteId'])->where('status', 'active')->exists();
        abort_unless($allowed, 403);

        TrainingProgram::create([
            'coach_id' => Auth::id(),
            'athlete_id' => $data['athleteId'],
            'title' => $data['title'],
            'goal' => $data['goal'] ?: null,
            'status' => $data['status'],
            'starts_on' => $data['startsOn'],
            'ends_on' => $data['endsOn'],
            'notes' => $data['notes'] ?: null,
        ]);

        $this->reset(['athleteId', 'title', 'goal', 'startsOn', 'endsOn', 'notes']);
        $this->status = 'active';
        session()->flash('status', 'Program created.');
    }

    public function render()
    {
        $coachId = Auth::id();
        $query = TrainingProgram::with(['athlete', 'sessions'])
            ->where('coach_id', $coachId)
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('goal', 'like', "%{$this->search}%")
                ->orWhereHas('athlete', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))))
            ->latest();

        return view('livewire.coach.programs-table', [
            'programs' => $this->paginateQuery($query),
            'athletes' => User::where('role', 'athlete')
                ->whereHas('athleteAssignments', fn ($query) => $query->where('coach_id', $coachId)->where('status', 'active'))
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app', ['title' => 'Programs']);
    }
}
