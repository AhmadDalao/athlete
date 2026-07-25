<?php

namespace App\Services;

use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use Illuminate\Support\Facades\DB;

class TrainingProgramManager
{
    public function __construct(private readonly AuditLogger $audit) {}

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
            $session = $program->sessions()->create($payload + ['status' => 'scheduled']);
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
            $session->update($payload);
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
}
