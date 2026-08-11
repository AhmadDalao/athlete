<?php

namespace App\Services;

use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use Illuminate\Support\Facades\DB;

class TrainingProgramManager
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ProgramScheduleService $schedule,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateProgram(TrainingProgram $program, array $payload): TrainingProgram
    {
        return DB::transaction(function () use ($program, $payload): TrainingProgram {
            $program->update($payload);
            $program->refresh();
            $this->audit->record(
                'program.updated',
                'training_program',
                $program->id,
                "Updated program {$program->title}.",
            );

            return $program;
        });
    }

    public function archiveProgram(TrainingProgram $program): void
    {
        DB::transaction(function () use ($program): void {
            $program->update(['status' => 'archived']);
            $this->audit->record(
                'program.archived',
                'training_program',
                $program->id,
                "Archived program {$program->title}.",
            );
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createSession(TrainingProgram $program, array $payload): TrainingSession
    {
        return DB::transaction(function () use ($program, $payload): TrainingSession {
            $exercises = $payload['exercises'] ?? [];
            unset($payload['exercises']);
            $session = $program->sessions()->create($payload + [
                'organization_id' => $program->organization_id,
                'status' => 'scheduled',
            ]);
            $this->syncExercises($session, $exercises);
            $this->schedule->syncSession($session);
            $this->audit->record(
                'session.created',
                'training_session',
                $session->id,
                "Created session {$session->title}.",
            );

            return $session;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateSession(TrainingProgram $program, int $sessionId, array $payload): TrainingSession
    {
        return DB::transaction(function () use ($program, $sessionId, $payload): TrainingSession {
            $session = $program->sessions()->whereKey($sessionId)->firstOrFail();
            $exercises = $payload['exercises'] ?? [];
            unset($payload['exercises']);
            $session->update($payload);
            $this->syncExercises($session, $exercises);
            $this->schedule->syncSession($session);
            $this->audit->record(
                'session.updated',
                'training_session',
                $session->id,
                "Updated session {$session->title}.",
            );

            return $session;
        });
    }

    public function removeSession(TrainingProgram $program, int $sessionId): string
    {
        return DB::transaction(function () use ($program, $sessionId): string {
            $session = $program->sessions()->withCount('logs')->whereKey($sessionId)->firstOrFail();

            if ($session->logs_count > 0) {
                $session->update(['status' => 'cancelled']);
                $this->audit->record(
                    'session.cancelled',
                    'training_session',
                    $session->id,
                    "Cancelled logged session {$session->title}.",
                );

                return 'cancelled';
            }

            $title = $session->title;
            $session->delete();
            $this->audit->record(
                'session.deleted',
                'training_session',
                $sessionId,
                "Deleted empty session {$title}.",
            );

            return 'deleted';
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $exercises
     */
    private function syncExercises(TrainingSession $session, array $exercises): void
    {
        $legacy = collect($exercises)->values()->map(fn (array $exercise): array => [
            'name' => $exercise['name'],
            'sets' => (int) ($exercise['sets'] ?? 1),
            'reps' => $exercise['reps'] ?? '',
            'rest' => filled($exercise['rest_seconds'] ?? null) ? ($exercise['rest_seconds'].' sec') : '',
            'rest_seconds' => $exercise['rest_seconds'] ?? null,
            'load' => $exercise['load'] ?? '',
            'unit' => $exercise['unit'] ?? '',
            'note' => $exercise['note'] ?? '',
            'section' => $exercise['section'] ?? '',
            'superset_label' => $exercise['superset_label'] ?? '',
            'movement_type' => $exercise['movement_type'] ?? '',
            'media_url' => $exercise['media_url'] ?? '',
        ])->all();

        $session->update(['exercises' => $legacy]);

        if (! $session->organization_id) {
            return;
        }

        $session->prescribedExercises()->delete();
        foreach ($exercises as $index => $exercise) {
            $session->prescribedExercises()->create([
                'organization_id' => $session->organization_id,
                'exercise_id' => filled($exercise['exercise_id'] ?? null) ? $exercise['exercise_id'] : null,
                'sort_order' => $index + 1,
                'section' => filled($exercise['section'] ?? null) ? $exercise['section'] : 'Main work',
                'superset_label' => filled($exercise['superset_label'] ?? null) ? $exercise['superset_label'] : null,
                'name' => $exercise['name'],
                'target_sets' => max(1, (int) ($exercise['sets'] ?? 1)),
                'target_reps' => filled($exercise['reps'] ?? null) ? $exercise['reps'] : null,
                'target_load' => filled($exercise['load'] ?? null) ? $exercise['load'] : null,
                'unit' => filled($exercise['unit'] ?? null) ? $exercise['unit'] : null,
                'rest_seconds' => filled($exercise['rest_seconds'] ?? null) ? (int) $exercise['rest_seconds'] : null,
                'notes' => filled($exercise['note'] ?? null) ? $exercise['note'] : null,
                'media_url' => filled($exercise['media_url'] ?? null) ? $exercise['media_url'] : null,
                'movement_type' => filled($exercise['movement_type'] ?? null) ? $exercise['movement_type'] : null,
            ]);
        }
    }
}
