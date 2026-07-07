<?php

namespace App\Livewire\Athlete;

use App\Models\TrainingSession;
use App\Models\WorkoutLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WorkoutDetail extends Component
{
    public TrainingSession $session;

    public string $notes = '';

    public function mount(TrainingSession $session): void
    {
        $session->load('program.coach');
        abort_unless($session->program->athlete_id === Auth::id(), 403);
        $this->session = $session;
        $this->notes = $session->logs()->where('athlete_id', Auth::id())->value('notes') ?? '';
    }

    public function mark(string $status): void
    {
        abort_unless(in_array($status, ['completed', 'partial', 'missed'], true), 422);

        WorkoutLog::updateOrCreate(
            ['training_session_id' => $this->session->id, 'athlete_id' => Auth::id()],
            [
                'status' => $status,
                'completed_at' => $status === 'completed' ? now() : null,
                'notes' => $this->notes ?: null,
            ]
        );

        session()->flash('status', 'Workout saved.');
    }

    public function render()
    {
        return view('livewire.athlete.workout-detail', [
            'log' => $this->session->logs()->where('athlete_id', Auth::id())->first(),
        ])->layout('layouts.app', ['title' => $this->session->title]);
    }
}
