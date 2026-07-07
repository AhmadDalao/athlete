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

    public string $durationMinutes = '';

    public string $rpe = '';

    public array $setLogs = [];

    public function mount(TrainingSession $session): void
    {
        $session->load('program.coach');
        abort_unless($session->program->athlete_id === Auth::id(), 403);
        $this->session = $session;
        $log = $session->logs()->where('athlete_id', Auth::id())->first();

        $this->notes = $log?->notes ?? '';
        $this->durationMinutes = (string) ($log?->duration_minutes ?? '');
        $this->rpe = (string) ($log?->rpe ?? '');
        $this->setLogs = $log?->set_logs ?: $this->defaultSetLogs();
    }

    public function mark(string $status): void
    {
        abort_unless(in_array($status, ['completed', 'partial', 'missed'], true), 422);

        $data = $this->validate([
            'notes' => ['nullable', 'string', 'max:1500'],
            'durationMinutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'rpe' => ['nullable', 'integer', 'min:1', 'max:10'],
            'setLogs' => ['array'],
            'setLogs.*.exercise' => ['required', 'string', 'max:160'],
            'setLogs.*.set' => ['required', 'integer', 'min:1'],
            'setLogs.*.target_reps' => ['nullable', 'string', 'max:60'],
            'setLogs.*.target_load' => ['nullable', 'string', 'max:80'],
            'setLogs.*.actual_reps' => ['nullable', 'string', 'max:60'],
            'setLogs.*.actual_load' => ['nullable', 'string', 'max:80'],
            'setLogs.*.rpe' => ['nullable', 'integer', 'min:1', 'max:10'],
            'setLogs.*.completed' => ['boolean'],
        ]);

        WorkoutLog::updateOrCreate(
            ['training_session_id' => $this->session->id, 'athlete_id' => Auth::id()],
            [
                'status' => $status,
                'duration_minutes' => $data['durationMinutes'] ?: null,
                'rpe' => $data['rpe'] ?: null,
                'set_logs' => collect($data['setLogs'])->map(fn (array $row): array => [
                    'exercise' => $row['exercise'],
                    'set' => (int) $row['set'],
                    'target_reps' => $row['target_reps'] ?? null,
                    'target_load' => $row['target_load'] ?? null,
                    'actual_reps' => $row['actual_reps'] ?? null,
                    'actual_load' => $row['actual_load'] ?? null,
                    'rpe' => $row['rpe'] ?? null,
                    'completed' => (bool) ($row['completed'] ?? false),
                ])->values()->all(),
                'completed_at' => $status === 'completed' ? now() : null,
                'notes' => $data['notes'] ?: null,
            ]
        );

        session()->flash('status', 'Workout saved.');
    }

    private function defaultSetLogs(): array
    {
        return collect($this->session->exercises ?? [])->flatMap(function (array $exercise): array {
            $setCount = max((int) ($exercise['sets'] ?? 1), 1);

            return collect(range(1, $setCount))->map(fn (int $set): array => [
                'exercise' => $exercise['name'] ?? 'Exercise',
                'set' => $set,
                'target_reps' => (string) ($exercise['reps'] ?? ''),
                'target_load' => (string) ($exercise['load'] ?? ''),
                'actual_reps' => '',
                'actual_load' => '',
                'rpe' => '',
                'completed' => false,
            ])->all();
        })->values()->all();
    }

    public function render()
    {
        return view('livewire.athlete.workout-detail', [
            'log' => $this->session->logs()->where('athlete_id', Auth::id())->first(),
        ])->layout('layouts.app', ['title' => $this->session->title]);
    }
}
