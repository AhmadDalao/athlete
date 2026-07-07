<?php

namespace App\Livewire\Coach;

use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProgramDetail extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public TrainingProgram $program;

    public string $title = '';

    public string $focus = '';

    public string $scheduledOn = '';

    public string $coachNotes = '';

    public string $mediaUrl = '';

    public array $exercises = [
        ['name' => '', 'sets' => '', 'reps' => '', 'rest' => '', 'load' => '', 'note' => ''],
    ];

    public function mount(TrainingProgram $program): void
    {
        abort_unless($program->coach_id === Auth::id() || Auth::user()->isAdmin(), 403);
        $this->program = $program;
        $this->scheduledOn = today()->toDateString();
    }

    public function addExercise(): void
    {
        $this->exercises[] = ['name' => '', 'sets' => '', 'reps' => '', 'rest' => '', 'load' => '', 'note' => ''];
    }

    public function removeExercise(int $index): void
    {
        unset($this->exercises[$index]);
        $this->exercises = array_values($this->exercises);
    }

    public function createSession(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'focus' => ['nullable', 'string', 'max:120'],
            'scheduledOn' => ['required', 'date'],
            'coachNotes' => ['nullable', 'string', 'max:1000'],
            'mediaUrl' => ['nullable', 'url', 'max:255'],
            'exercises.*.name' => ['required', 'string', 'max:160'],
            'exercises.*.sets' => ['nullable', 'string', 'max:20'],
            'exercises.*.reps' => ['nullable', 'string', 'max:40'],
            'exercises.*.rest' => ['nullable', 'string', 'max:40'],
            'exercises.*.load' => ['nullable', 'string', 'max:80'],
            'exercises.*.note' => ['nullable', 'string', 'max:200'],
        ]);

        TrainingSession::create([
            'training_program_id' => $this->program->id,
            'title' => $this->title,
            'focus' => $this->focus ?: null,
            'scheduled_on' => $this->scheduledOn,
            'status' => 'scheduled',
            'coach_notes' => $this->coachNotes ?: null,
            'media_url' => $this->mediaUrl ?: null,
            'exercises' => collect($this->exercises)->filter(fn (array $exercise) => filled($exercise['name'] ?? null))->values()->all(),
        ]);

        $this->reset(['title', 'focus', 'coachNotes', 'mediaUrl']);
        $this->scheduledOn = today()->toDateString();
        $this->exercises = [['name' => '', 'sets' => '', 'reps' => '', 'rest' => '', 'load' => '', 'note' => '']];
        session()->flash('status', 'Session added.');
    }

    public function render()
    {
        return view('livewire.coach.program-detail', [
            'sessions' => $this->program->sessions()->with('logs')->paginate(10),
        ])->layout('layouts.app', ['title' => $this->program->title]);
    }
}
