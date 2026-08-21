<?php

namespace App\Services;

use App\Models\PersonalRecord;
use App\Models\ScheduledWorkout;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Models\WorkoutSetLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkoutExecutionService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly UserNotificationService $notifications,
    ) {}

    public function save(ScheduledWorkout $workout, User $athlete, array $data, string $status): WorkoutLog
    {
        if (! in_array($status, ['completed', 'partial', 'missed', 'skipped'], true)) {
            throw ValidationException::withMessages(['status' => 'Invalid workout status.']);
        }

        if ($status === 'completed' && ! ($data['confirmedComplete'] ?? false)) {
            throw ValidationException::withMessages(['confirmedComplete' => 'Confirm that today’s training is finished.']);
        }

        $rows = collect($data['setLogs'] ?? [])->values();
        if ($status === 'completed') {
            $workout->loadMissing('session.prescribedExercises');
            $expectedSets = $workout->session->prescribedExercises->flatMap(
                fn ($exercise) => collect(range(1, max(1, (int) $exercise->target_sets)))
                    ->map(fn (int $set): string => "{$exercise->id}:{$set}")
            )->sort()->values();
            $completedSets = $rows
                ->filter(fn (array $row): bool => (bool) ($row['completed'] ?? false))
                ->map(fn (array $row): string => ((int) ($row['exercise_id'] ?? 0)).':'.((int) ($row['set'] ?? 0)))
                ->sort()
                ->values();

            if ($expectedSets->all() !== $completedSets->all()) {
                throw ValidationException::withMessages(['setLogs' => 'Complete every prescribed set, or save the workout as partial.']);
            }
        }

        return DB::transaction(function () use ($workout, $athlete, $data, $status, $rows): WorkoutLog {
            $log = WorkoutLog::firstOrNew([
                'scheduled_workout_id' => $workout->id,
                'athlete_id' => $athlete->id,
            ]);
            $previousStatus = $log->exists ? $log->status : null;
            $log->fill([
                'organization_id' => $workout->organization_id,
                'training_session_id' => $workout->training_session_id,
                'program_assignment_id' => $workout->program_assignment_id,
                'status' => $status,
                'duration_minutes' => $data['durationMinutes'] ?: null,
                'rpe' => $data['rpe'] ?: null,
                'set_logs' => $rows->map(fn (array $row): array => [
                    'exercise' => $row['exercise'],
                    'set' => (int) $row['set'],
                    'target_reps' => $row['target_reps'] ?: null,
                    'target_load' => $row['target_load'] ?: null,
                    'target_rest' => $row['target_rest_seconds'] ?: null,
                    'actual_reps' => $row['actual_reps'] ?: null,
                    'actual_load' => $row['actual_load'] ?: null,
                    'rpe' => $row['rpe'] ?: null,
                    'completed' => (bool) ($row['completed'] ?? false),
                ])->all(),
                'completed_at' => $status === 'completed' ? now() : null,
                'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
                'sync_version' => $log->exists ? $log->sync_version + 1 : 1,
            ]);
            $log->save();

            $log->setLogs()->delete();
            foreach ($rows as $row) {
                $setLog = $log->setLogs()->create([
                    'organization_id' => $workout->organization_id,
                    'scheduled_workout_id' => $workout->id,
                    'training_session_exercise_id' => $row['exercise_id'] ?: null,
                    'athlete_id' => $athlete->id,
                    'exercise_index' => (int) $row['exercise_index'],
                    'exercise_name' => $row['exercise'],
                    'set_number' => (int) $row['set'],
                    'target_reps' => $row['target_reps'] ?: null,
                    'target_load' => $row['target_load'] ?: null,
                    'target_rest_seconds' => $row['target_rest_seconds'] ?: null,
                    'actual_reps' => $row['actual_reps'] ?: null,
                    'actual_load' => $row['actual_load'] ?: null,
                    'actual_rpe' => $row['rpe'] ?: null,
                    'completed_at' => ($row['completed'] ?? false) ? now() : null,
                    'notes' => filled($row['notes'] ?? null) ? trim((string) $row['notes']) : null,
                ]);
                $this->captureLoadRecord($workout, $athlete, $setLog);
            }

            $workout->update([
                'status' => $status,
                'athlete_notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
            ]);
            $this->audit->record(
                'workout.execution_saved',
                'scheduled_workout',
                $workout->id,
                "Saved {$workout->session->title} as {$status}.",
                $athlete->id,
            );
            if ($previousStatus !== $status) {
                $this->notifications->workoutSaved($workout, $status);
            }

            return $log->fresh(['setLogs']);
        });
    }

    private function captureLoadRecord(ScheduledWorkout $workout, User $athlete, WorkoutSetLog $setLog): void
    {
        if (! $setLog->completed_at || ! $setLog->actual_load) {
            return;
        }

        $best = (float) PersonalRecord::query()
            ->where('athlete_id', $athlete->id)
            ->where('exercise_name', $setLog->exercise_name)
            ->where('record_type', 'load')
            ->max('value');
        if ((float) $setLog->actual_load <= $best) {
            return;
        }

        PersonalRecord::create([
            'organization_id' => $workout->organization_id,
            'athlete_id' => $athlete->id,
            'workout_set_log_id' => $setLog->id,
            'exercise_name' => $setLog->exercise_name,
            'record_type' => 'load',
            'value' => $setLog->actual_load,
            'unit' => $setLog->sessionExercise?->unit ?: 'kg',
            'achieved_on' => $workout->scheduled_for->toDateString(),
        ]);
    }
}
