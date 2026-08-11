<?php

namespace App\Livewire\Athlete;

use App\Models\ProgramAssignment;
use App\Queries\Athlete\AthleteWorkspaceQuery;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProgramDetail extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public ProgramAssignment $assignment;

    public function mount(ProgramAssignment $assignment): void
    {
        abort_unless(AthleteWorkspaceQuery::canOpenAssignment($assignment, (int) Auth::id()), 403);
        $this->assignment = $assignment;
    }

    public function render()
    {
        $this->assignment->load(['program.coach', 'program.phases']);

        return view('livewire.athlete.program-detail', [
            'workouts' => $this->assignment->scheduledWorkouts()
                ->with(['session.prescribedExercises', 'executionLog'])
                ->paginate(12),
            'stats' => $this->assignment->completionStats(),
        ])->layout('layouts.app', ['title' => $this->assignment->program->title]);
    }
}
