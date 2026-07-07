<?php

namespace App\Livewire\Athlete;

use App\Models\TrainingProgram;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProgramDetail extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public TrainingProgram $program;

    public function mount(TrainingProgram $program): void
    {
        abort_unless($program->athlete_id === Auth::id(), 403);
        $this->program = $program;
    }

    public function render()
    {
        return view('livewire.athlete.program-detail', [
            'sessions' => $this->program->sessions()->with('logs')->paginate(12),
        ])->layout('layouts.app', ['title' => $this->program->title]);
    }
}
