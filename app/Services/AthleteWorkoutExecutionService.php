<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\WorkoutCompletionStatus;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Models\WorkoutSetLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AthleteWorkoutExecutionService
{
    /**
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function payload(User $athlete, TrainingSession $session): array
    {
        $session = $this->authorizedSession($athlete, $session);
        $workoutLog = $this->existingWorkoutLog($athlete, $session);

        return [
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'scheduledDate' => $session->scheduled_date?->toDateString(),
                'focus' => $session->focus,
                'instructions' => $session->instructions,
                'videoUrl' => $session->video_url,
            ],
            'program' => [
                'id' => $session->program->id,
                'title' => $session->program->title,
                'goal' => $session->program->goal,
                'status' => $session->program->status->value,
            ],
            'coach' => [
                'id' => $session->program->coach->id,
                'name' => $session->program->coach->name,
                'email' => $session->program->coach->email,
            ],
            'athlete' => [
                'id' => $athlete->id,
                'name' => $athlete->name,
                'email' => $athlete->email,
            ],
            'exercises' => $this->normalizedExercises($session),
            'setLogs' => $this->setRows($athlete, $session, $workoutLog),
            'workoutLog' => $workoutLog ? $this->workoutLogPayload($workoutLog) : null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sets
     *
     * @throws AuthorizationException
     */
    public function saveSetLogs(User $athlete, TrainingSession $session, array $sets): WorkoutLog
    {
        $session = $this->authorizedSession($athlete, $session);
        $targetRows = $this->targetRows($session);
        $targetLookup = $this->targetLookup($targetRows);

        return DB::transaction(function () use ($athlete, $session, $sets, $targetRows, $targetLookup): WorkoutLog {
            $log = $this->ensureWorkoutLog($athlete, $session);

            foreach ($sets as $set) {
                $key = $this->setKey((int) ($set['exercise_index'] ?? -1), (int) ($set['set_number'] ?? -1));
                $target = $targetLookup->get($key);

                if (! $target) {
                    continue;
                }

                $completed = (bool) ($set['completed'] ?? false);

                WorkoutSetLog::query()->updateOrCreate(
                    [
                        'training_session_id' => $session->id,
                        'athlete_id' => $athlete->id,
                        'exercise_index' => $target['exerciseIndex'],
                        'set_number' => $target['setNumber'],
                    ],
                    [
                        'workout_log_id' => $log->id,
                        'exercise_name' => $target['exerciseName'],
                        'target_reps' => $target['targetReps'],
                        'target_load' => $target['targetLoad'],
                        'target_rest_seconds' => $target['targetRestSeconds'],
                        'actual_reps' => $this->blankToNull($set['actual_reps'] ?? null),
                        'actual_load' => $this->blankToNull($set['actual_load'] ?? null),
                        'actual_rpe' => $set['actual_rpe'] ?? null,
                        'completed_at' => $completed ? now() : null,
                        'notes' => $this->blankToNull($set['notes'] ?? null),
                    ],
                );
            }

            $this->syncCompletionFromSets($log, $targetRows);

            return $log->refresh()->load('setLogs');
        });
    }

    /**
     * @param  array<string, mixed>  $journal
     *
     * @throws AuthorizationException
     */
    public function complete(User $athlete, TrainingSession $session, WorkoutCompletionStatus $status, array $journal = []): WorkoutLog
    {
        $session = $this->authorizedSession($athlete, $session);

        return DB::transaction(function () use ($athlete, $session, $status, $journal): WorkoutLog {
            $log = $this->ensureWorkoutLog($athlete, $session);

            if ($status === WorkoutCompletionStatus::Completed) {
                $this->completeMissingTargetSets($athlete, $session, $log);
            }

            $log->forceFill([
                'completion_status' => $status,
                'performed_at' => $journal['performed_at'] ?? $log->performed_at ?? now(),
                'duration_minutes' => $journal['duration_minutes'] ?? $log->duration_minutes,
                'exertion_rating' => $journal['exertion_rating'] ?? $log->exertion_rating,
                'energy_score' => $journal['energy_score'] ?? $log->energy_score,
                'soreness_score' => $journal['soreness_score'] ?? $log->soreness_score,
                'stress_score' => $journal['stress_score'] ?? $log->stress_score,
                'sleep_quality_score' => $journal['sleep_quality_score'] ?? $log->sleep_quality_score,
                'notes' => $journal['notes'] ?? $log->notes,
            ])->save();

            return $log->refresh()->load('setLogs');
        });
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws AuthorizationException
     */
    public function targetRowsFor(User $athlete, TrainingSession $session): array
    {
        return $this->targetRows($this->authorizedSession($athlete, $session));
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizedSession(User $athlete, TrainingSession $session): TrainingSession
    {
        $athlete->loadMissing('roles');
        $session->load(['program.coach', 'program.athlete', 'workoutLog.setLogs']);

        if (! $athlete->hasRole(RoleName::Athlete) || $session->program->athlete_id !== $athlete->id) {
            throw new AuthorizationException('This workout is not assigned to this athlete.');
        }

        return $session;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function setRows(User $athlete, TrainingSession $session, ?WorkoutLog $workoutLog): array
    {
        $targetRows = $this->targetRows($session);
        $existingLogs = $workoutLog
            ? $workoutLog->setLogs->keyBy(fn (WorkoutSetLog $setLog): string => $this->setKey($setLog->exercise_index, $setLog->set_number))
            : collect();

        return collect($targetRows)
            ->map(function (array $target) use ($existingLogs): array {
                /** @var WorkoutSetLog|null $existing */
                $existing = $existingLogs->get($this->setKey($target['exerciseIndex'], $target['setNumber']));

                return array_merge($target, [
                    'id' => $existing?->id,
                    'actualReps' => $existing?->actual_reps,
                    'actualLoad' => $existing?->actual_load,
                    'actualRpe' => $existing?->actual_rpe,
                    'completedAt' => $existing?->completed_at?->toIso8601String(),
                    'notes' => $existing?->notes,
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function targetRows(TrainingSession $session): array
    {
        return collect($this->normalizedExercises($session))
            ->flatMap(function (array $exercise, int $exerciseIndex): array {
                $setCount = max(1, (int) ($exercise['sets'] ?? 1));

                return collect(range(1, $setCount))
                    ->map(fn (int $setNumber): array => [
                        'exerciseIndex' => $exerciseIndex,
                        'exerciseName' => $exercise['name'],
                        'setNumber' => $setNumber,
                        'targetReps' => $exercise['reps'] ?? $exercise['prescription'],
                        'targetLoad' => $exercise['load'],
                        'targetRestSeconds' => $exercise['restSeconds'],
                    ])
                    ->all();
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizedExercises(TrainingSession $session): array
    {
        return collect($session->exercises ?? [])
            ->filter(fn ($exercise): bool => is_array($exercise))
            ->map(fn (array $exercise): array => [
                'name' => (string) ($exercise['name'] ?? 'Exercise'),
                'prescription' => $exercise['prescription'] ?? null,
                'sets' => isset($exercise['sets']) ? (int) $exercise['sets'] : null,
                'reps' => $exercise['reps'] ?? null,
                'load' => $exercise['load'] ?? null,
                'restSeconds' => isset($exercise['rest_seconds']) ? (int) $exercise['rest_seconds'] : null,
                'restLabel' => $exercise['rest_label'] ?? null,
                'target' => $exercise['target'] ?? null,
                'note' => $exercise['note'] ?? null,
            ])
            ->values()
            ->all();
    }

    private function existingWorkoutLog(User $athlete, TrainingSession $session): ?WorkoutLog
    {
        if ($session->relationLoaded('workoutLog') && $session->workoutLog?->athlete_id === $athlete->id) {
            return $session->workoutLog->loadMissing('setLogs');
        }

        return WorkoutLog::query()
            ->where('training_session_id', $session->id)
            ->where('athlete_id', $athlete->id)
            ->with('setLogs')
            ->first();
    }

    private function ensureWorkoutLog(User $athlete, TrainingSession $session): WorkoutLog
    {
        return WorkoutLog::query()->firstOrCreate(
            [
                'training_session_id' => $session->id,
                'athlete_id' => $athlete->id,
            ],
            [
                'completion_status' => WorkoutCompletionStatus::Partial,
                'performed_at' => now(),
            ],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $targetRows
     */
    private function syncCompletionFromSets(WorkoutLog $log, array $targetRows): void
    {
        $targetKeys = collect($targetRows)->map(fn (array $row): string => $this->setKey($row['exerciseIndex'], $row['setNumber']));
        $completedKeys = $log->setLogs()
            ->whereNotNull('completed_at')
            ->get()
            ->map(fn (WorkoutSetLog $setLog): string => $this->setKey($setLog->exercise_index, $setLog->set_number))
            ->intersect($targetKeys);

        $log->forceFill([
            'completion_status' => $targetKeys->isNotEmpty() && $completedKeys->count() === $targetKeys->count()
                ? WorkoutCompletionStatus::Completed
                : WorkoutCompletionStatus::Partial,
            'performed_at' => $log->performed_at ?? now(),
        ])->save();
    }

    private function completeMissingTargetSets(User $athlete, TrainingSession $session, WorkoutLog $log): void
    {
        $existingKeys = $log->setLogs()
            ->whereNotNull('completed_at')
            ->get()
            ->keyBy(fn (WorkoutSetLog $setLog): string => $this->setKey($setLog->exercise_index, $setLog->set_number));

        foreach ($this->targetRows($session) as $target) {
            if ($existingKeys->has($this->setKey($target['exerciseIndex'], $target['setNumber']))) {
                continue;
            }

            WorkoutSetLog::query()->updateOrCreate(
                [
                    'training_session_id' => $session->id,
                    'athlete_id' => $athlete->id,
                    'exercise_index' => $target['exerciseIndex'],
                    'set_number' => $target['setNumber'],
                ],
                [
                    'workout_log_id' => $log->id,
                    'exercise_name' => $target['exerciseName'],
                    'target_reps' => $target['targetReps'],
                    'target_load' => $target['targetLoad'],
                    'target_rest_seconds' => $target['targetRestSeconds'],
                    'completed_at' => now(),
                ],
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $targetRows
     * @return Collection<string, array<string, mixed>>
     */
    private function targetLookup(array $targetRows): Collection
    {
        return collect($targetRows)->keyBy(fn (array $target): string => $this->setKey($target['exerciseIndex'], $target['setNumber']));
    }

    /**
     * @return array<string, mixed>
     */
    private function workoutLogPayload(WorkoutLog $workoutLog): array
    {
        return [
            'id' => $workoutLog->id,
            'completionStatus' => $workoutLog->completion_status->value,
            'performedAt' => $workoutLog->performed_at?->toIso8601String(),
            'durationMinutes' => $workoutLog->duration_minutes,
            'exertionRating' => $workoutLog->exertion_rating,
            'energyScore' => $workoutLog->energy_score,
            'sorenessScore' => $workoutLog->soreness_score,
            'stressScore' => $workoutLog->stress_score,
            'sleepQualityScore' => $workoutLog->sleep_quality_score,
            'notes' => $workoutLog->notes,
        ];
    }

    private function setKey(int $exerciseIndex, int $setNumber): string
    {
        return "{$exerciseIndex}:{$setNumber}";
    }

    private function blankToNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
