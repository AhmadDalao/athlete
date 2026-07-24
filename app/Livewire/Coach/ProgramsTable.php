<?php

namespace App\Livewire\Coach;

use App\Livewire\Concerns\WithTableControls;
use App\Models\AuditLog;
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

    public function mount(): void
    {
        $requestedAthleteId = request()->integer('athlete');

        if ($requestedAthleteId > 0 && $this->coachCanManageAthlete($requestedAthleteId)) {
            $this->athleteId = $requestedAthleteId;
        }
    }

    public function createProgram()
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

        abort_unless($this->coachCanManageAthlete((int) $data['athleteId']), 403);

        $program = TrainingProgram::create([
            'coach_id' => Auth::id(),
            'athlete_id' => $data['athleteId'],
            'title' => $data['title'],
            'goal' => $data['goal'] ?: null,
            'status' => $data['status'],
            'starts_on' => $data['startsOn'],
            'ends_on' => $data['endsOn'],
            'notes' => $data['notes'] ?: null,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'program.created',
            'entity' => 'training_program',
            'entity_id' => $program->id,
            'summary' => "Created program {$program->title}.",
            'ip_address' => request()->ip(),
        ]);

        session()->flash('status', 'Program created.');

        return redirect()->route('coach.programs.show', $program);
    }

    public function render()
    {
        $coachId = Auth::id();
        $query = TrainingProgram::with(['athlete', 'sessions.logs'])
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

    private function coachCanManageAthlete(int $athleteId): bool
    {
        return Auth::user()->coachAssignments()
            ->where('athlete_id', $athleteId)
            ->where('status', 'active')
            ->exists();
    }
}
