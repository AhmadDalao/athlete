<?php

namespace App\Services;

use App\Models\ProgramAssignment;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgramPersonalizationService
{
    public function __construct(
        private readonly ProgramScheduleService $schedule,
        private readonly AuditLogger $audit,
    ) {}

    public function duplicatePreset(TrainingProgram $source, User $coach, ?string $title = null): TrainingProgram
    {
        $this->assertPresetOwner($source, $coach);

        return DB::transaction(function () use ($source, $coach, $title): TrainingProgram {
            $result = $this->cloneProgram($source, [
                'coach_id' => $coach->id,
                'athlete_id' => null,
                'source_program_id' => $source->id,
                'title' => filled($title) ? trim((string) $title) : 'Copy of '.$source->title,
                'status' => 'draft',
                'is_template' => true,
            ]);
            $copy = $result['program'];
            $this->audit->record('program.duplicated', 'training_program', $copy->id, "Duplicated preset {$source->title}.");

            return $copy->load(['phases', 'sessions.prescribedExercises']);
        });
    }

    public function personalize(
        TrainingProgram $source,
        User $athlete,
        User $coach,
        string $startsOn,
        ?string $notes = null,
        bool $publish = true,
    ): ProgramAssignment {
        $this->assertPresetOwner($source, $coach);
        $this->assertAthleteAvailable($source, $athlete, $coach);

        return DB::transaction(function () use ($source, $athlete, $coach, $startsOn, $notes, $publish): ProgramAssignment {
            $result = $this->cloneProgram($source, [
                'coach_id' => $coach->id,
                'athlete_id' => $athlete->id,
                'source_program_id' => $source->id,
                'status' => 'draft',
                'is_template' => false,
            ]);
            $copy = $result['program'];
            $assignment = $this->schedule->createDraftAssignment($copy, $athlete, $coach, $startsOn, $notes);
            $this->audit->record(
                'program.personalized',
                'training_program',
                $copy->id,
                "Created a personalized copy of {$source->title} for {$athlete->name}.",
            );

            return $publish ? $this->schedule->publish($assignment, $coach) : $assignment->refresh();
        });
    }

    public function isolateExistingAssignment(ProgramAssignment $assignment): ProgramAssignment
    {
        $assignment->loadMissing(['program.phases', 'program.sessions.prescribedExercises', 'athlete', 'assigner']);
        if (! $assignment->program->is_template) {
            return $assignment;
        }

        return DB::transaction(function () use ($assignment): ProgramAssignment {
            $result = $this->cloneProgram($assignment->program, [
                'athlete_id' => $assignment->athlete_id,
                'source_program_id' => $assignment->program->id,
                'status' => $assignment->status === 'draft' ? 'draft' : 'active',
                'is_template' => false,
            ]);

            $sessionMap = $result['sessions'];
            $exerciseMap = $result['exercises'];
            $assignment->update([
                'training_program_id' => $result['program']->id,
                'published_at' => $assignment->status === 'draft'
                    ? null
                    : ($assignment->published_at ?: $assignment->created_at),
            ]);

            foreach ($assignment->scheduledWorkouts()->with(['logs.setLogs'])->get() as $workout) {
                $oldSessionId = $workout->training_session_id;
                $newSessionId = $sessionMap[$oldSessionId] ?? null;
                if (! $newSessionId) {
                    continue;
                }
                $workout->update(['training_session_id' => $newSessionId]);
                foreach ($workout->logs as $log) {
                    $log->update(['training_session_id' => $newSessionId]);
                    foreach ($log->setLogs as $setLog) {
                        if ($setLog->training_session_exercise_id && isset($exerciseMap[$setLog->training_session_exercise_id])) {
                            $setLog->update([
                                'training_session_exercise_id' => $exerciseMap[$setLog->training_session_exercise_id],
                            ]);
                        }
                    }
                }
            }

            return $assignment->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{program: TrainingProgram, sessions: array<int, int>, exercises: array<int, int>}
     */
    private function cloneProgram(TrainingProgram $source, array $overrides): array
    {
        $source->loadMissing(['phases', 'sessions.prescribedExercises']);
        $copy = $source->replicate();
        $copy->fill($overrides + ['organization_id' => $source->organization_id]);
        $copy->save();

        $phaseMap = [];
        foreach ($source->phases as $phase) {
            $phaseCopy = $phase->replicate();
            $phaseCopy->training_program_id = $copy->id;
            $phaseCopy->organization_id = $copy->organization_id;
            $phaseCopy->save();
            $phaseMap[$phase->id] = $phaseCopy->id;
        }

        $sessionMap = [];
        $exerciseMap = [];
        foreach ($source->sessions as $session) {
            $sessionCopy = $session->replicate();
            $sessionCopy->training_program_id = $copy->id;
            $sessionCopy->organization_id = $copy->organization_id;
            $sessionCopy->program_phase_id = $session->program_phase_id
                ? ($phaseMap[$session->program_phase_id] ?? null)
                : null;
            $sessionCopy->save();
            $sessionMap[$session->id] = $sessionCopy->id;

            foreach ($session->prescribedExercises as $exercise) {
                $exerciseCopy = $exercise->replicate();
                $exerciseCopy->training_session_id = $sessionCopy->id;
                $exerciseCopy->organization_id = $copy->organization_id;
                $exerciseCopy->save();
                $exerciseMap[$exercise->id] = $exerciseCopy->id;
            }
        }

        return ['program' => $copy, 'sessions' => $sessionMap, 'exercises' => $exerciseMap];
    }

    private function assertPresetOwner(TrainingProgram $source, User $coach): void
    {
        if (! $source->is_template || $source->coach_id !== $coach->id) {
            throw ValidationException::withMessages(['program' => 'Choose one of your reusable program presets.']);
        }
    }

    private function assertAthleteAvailable(TrainingProgram $source, User $athlete, User $coach): void
    {
        $assigned = $coach->coachAssignments()
            ->where('organization_id', $source->organization_id)
            ->where('athlete_id', $athlete->id)
            ->where('status', 'active')
            ->exists();
        if (! $assigned) {
            throw ValidationException::withMessages(['athlete_id' => 'This athlete is not active on your roster.']);
        }

        $alreadyActive = ProgramAssignment::query()
            ->where('athlete_id', $athlete->id)
            ->whereIn('status', ['draft', 'active', 'paused'])
            ->whereHas('program', fn ($query) => $query
                ->where('id', $source->id)
                ->orWhere('source_program_id', $source->id))
            ->exists();
        if ($alreadyActive) {
            throw ValidationException::withMessages(['athlete_id' => 'This athlete already has a current plan from this preset.']);
        }
    }
}
