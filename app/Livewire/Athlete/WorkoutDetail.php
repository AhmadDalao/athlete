<?php

namespace App\Livewire\Athlete;

use App\Models\ScheduledWorkout;
use App\Models\TrainingSessionExercise;
use App\Queries\Athlete\AthleteWorkspaceQuery;
use App\Services\WorkoutExecutionService;
use App\Support\TrainingMedia;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WorkoutDetail extends Component
{
    public ScheduledWorkout $workout;

    public string $notes = '';

    public string $durationMinutes = '';

    public string $rpe = '';

    public array $setLogs = [];

    public function mount(ScheduledWorkout $workout): void
    {
        abort_unless(AthleteWorkspaceQuery::canOpenWorkout($workout, (int) Auth::id()), 403);
        $this->workout = $workout;
        $this->workout->load([
            'assignment.program.coach',
            'session.prescribedExercises',
            'executionLog.setLogs',
        ]);
        $log = $this->workout->executionLog;
        $this->notes = $log?->notes ?? '';
        $this->durationMinutes = (string) ($log?->duration_minutes ?? '');
        $this->rpe = (string) ($log?->rpe ?? '');
        $this->setLogs = $log && $log->setLogs->isNotEmpty()
            ? $log->setLogs->map(fn ($row): array => [
                'exercise_id' => $row->training_session_exercise_id,
                'exercise_index' => $row->exercise_index,
                'exercise' => $row->exercise_name,
                'set' => $row->set_number,
                'target_reps' => $row->target_reps ?? '',
                'target_load' => $row->target_load ?? '',
                'target_rest_seconds' => $row->target_rest_seconds ?? '',
                'actual_reps' => $row->actual_reps ?? '',
                'actual_load' => $row->actual_load ?? '',
                'rpe' => $row->actual_rpe ?? '',
                'notes' => $row->notes ?? '',
                'completed' => $row->completed_at !== null,
            ])->all()
            : $this->defaultSetLogs();
    }

    public function mark(string $status, WorkoutExecutionService $execution): void
    {
        $data = $this->validate([
            'notes' => ['nullable', 'string', 'max:1500'],
            'durationMinutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'rpe' => ['nullable', 'integer', 'min:1', 'max:10'],
            'setLogs' => ['array'],
            'setLogs.*.exercise_id' => ['nullable', 'integer'],
            'setLogs.*.exercise_index' => ['required', 'integer', 'min:0'],
            'setLogs.*.exercise' => ['required', 'string', 'max:160'],
            'setLogs.*.set' => ['required', 'integer', 'min:1'],
            'setLogs.*.target_reps' => ['nullable', 'string', 'max:60'],
            'setLogs.*.target_load' => ['nullable', 'string', 'max:80'],
            'setLogs.*.target_rest_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'setLogs.*.actual_reps' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'setLogs.*.actual_load' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'setLogs.*.rpe' => ['nullable', 'integer', 'min:1', 'max:10'],
            'setLogs.*.notes' => ['nullable', 'string', 'max:500'],
            'setLogs.*.completed' => ['boolean'],
        ]);

        $execution->save($this->workout, Auth::user(), $data, $status);
        $this->workout->refresh()->load('executionLog.setLogs');
        session()->flash('status', 'Workout saved as '.$status.'.');
    }

    public function render()
    {
        $session = $this->workout->session;
        $exerciseSource = $session->prescribedExercises->isNotEmpty()
            ? $session->prescribedExercises
            : collect($session->exercises ?? []);

        return view('livewire.athlete.workout-detail', [
            'session' => $session,
            'log' => $this->workout->executionLog,
            'sessionMedia' => TrainingMedia::fromUrl($session->media_url),
            'exerciseMedia' => $exerciseSource->map(fn ($exercise): array => TrainingMedia::fromUrl(
                $exercise instanceof TrainingSessionExercise ? $exercise->media_url : ($exercise['media_url'] ?? null)
            ))->all(),
        ])->layout('layouts.app', ['title' => $session->title]);
    }

    private function defaultSetLogs(): array
    {
        $exercises = $this->workout->session->prescribedExercises;
        if ($exercises->isNotEmpty()) {
            return $exercises->flatMap(function (TrainingSessionExercise $exercise, int $index): array {
                return collect(range(1, max($exercise->target_sets, 1)))->map(fn (int $set): array => [
                    'exercise_id' => $exercise->id,
                    'exercise_index' => $index,
                    'exercise' => $exercise->name,
                    'set' => $set,
                    'target_reps' => (string) ($exercise->target_reps ?? ''),
                    'target_load' => (string) ($exercise->target_load ?? ''),
                    'target_rest_seconds' => $exercise->rest_seconds ?? '',
                    'actual_reps' => '',
                    'actual_load' => '',
                    'rpe' => '',
                    'notes' => '',
                    'completed' => false,
                ])->all();
            })->values()->all();
        }

        return collect($this->workout->session->exercises ?? [])->flatMap(function (array $exercise, int $index): array {
            return collect(range(1, max((int) ($exercise['sets'] ?? 1), 1)))->map(fn (int $set): array => [
                'exercise_id' => null,
                'exercise_index' => $index,
                'exercise' => $exercise['name'] ?? 'Exercise',
                'set' => $set,
                'target_reps' => (string) ($exercise['reps'] ?? ''),
                'target_load' => (string) ($exercise['load'] ?? ''),
                'target_rest_seconds' => (int) filter_var((string) ($exercise['rest'] ?? 0), FILTER_SANITIZE_NUMBER_INT),
                'actual_reps' => '',
                'actual_load' => '',
                'rpe' => '',
                'notes' => '',
                'completed' => false,
            ])->all();
        })->values()->all();
    }
}
