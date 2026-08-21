<?php

namespace App\Services;

use App\Models\ProgramAssignment;
use App\Models\ScheduledWorkout;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgramScheduleService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly UserNotificationService $notifications,
    ) {}

    public function assign(
        TrainingProgram $program,
        User $athlete,
        User $assigner,
        string $startsOn,
        ?string $notes = null,
    ): ProgramAssignment {
        if ($program->is_template) {
            return app(ProgramPersonalizationService::class)->personalize(
                $program,
                $athlete,
                $assigner,
                $startsOn,
                $notes,
                true,
            );
        }

        $assignment = $this->createDraftAssignment($program, $athlete, $assigner, $startsOn, $notes);

        return $this->publish($assignment, $assigner);
    }

    public function createDraftAssignment(
        TrainingProgram $program,
        User $athlete,
        User $assigner,
        string $startsOn,
        ?string $notes = null,
    ): ProgramAssignment {
        if (! $program->organization_id || ! $athlete->organizationMemberships()
            ->where('organization_id', $program->organization_id)
            ->where('role', 'athlete')
            ->where('status', 'active')
            ->exists()) {
            throw ValidationException::withMessages(['athlete_id' => 'The athlete is outside the active organization.']);
        }

        if ($program->assignments()->where('athlete_id', $athlete->id)->whereIn('status', ['draft', 'active', 'paused'])->exists()) {
            throw ValidationException::withMessages(['athlete_id' => 'This athlete already has a current assignment for this plan.']);
        }

        return DB::transaction(function () use ($program, $athlete, $assigner, $startsOn, $notes): ProgramAssignment {
            $lastOffset = (int) $program->sessions()->where('status', '!=', 'cancelled')->max('day_offset');
            $start = CarbonImmutable::parse($startsOn);
            $timezone = $athlete->athleteProfiles()
                ->where('organization_id', $program->organization_id)
                ->value('timezone') ?: $program->organization->timezone;

            $assignment = $program->assignments()->create([
                'organization_id' => $program->organization_id,
                'athlete_id' => $athlete->id,
                'assigned_by' => $assigner->id,
                'status' => 'draft',
                'starts_on' => $start->toDateString(),
                'ends_on' => $start->addDays($lastOffset)->toDateString(),
                'timezone' => $timezone,
                'notes' => filled($notes) ? trim((string) $notes) : null,
            ]);

            $this->audit->record(
                'program.assignment_drafted',
                'program_assignment',
                $assignment->id,
                "Prepared {$program->title} for {$athlete->name}.",
            );

            return $assignment->refresh();
        });
    }

    public function publish(ProgramAssignment $assignment, User $publisher): ProgramAssignment
    {
        $assignment->loadMissing(['program', 'athlete']);
        if ($assignment->program->coach_id !== $publisher->id) {
            abort(403);
        }
        if ($assignment->published_at) {
            throw ValidationException::withMessages(['assignment' => 'This athlete plan is already published.']);
        }

        return DB::transaction(function () use ($assignment): ProgramAssignment {
            $assignment->update(['status' => 'active', 'published_at' => now()]);
            $assignment->program->update(['status' => 'active']);
            $this->generate($assignment);
            $this->audit->record(
                'program.assigned',
                'program_assignment',
                $assignment->id,
                "Published {$assignment->program->title} for {$assignment->athlete->name}.",
            );
            $this->notifications->programAssigned($assignment);

            return $assignment->refresh();
        });
    }

    public function generate(ProgramAssignment $assignment): int
    {
        $assignment->loadMissing('program.sessions');
        $created = 0;

        foreach ($assignment->program->sessions->where('status', '!=', 'cancelled') as $session) {
            $scheduledFor = CarbonImmutable::parse($assignment->starts_on, $assignment->timezone)
                ->addDays((int) $session->day_offset)
                ->setTime(9, 0)
                ->utc();

            $workout = $assignment->scheduledWorkouts()->firstOrCreate(
                [
                    'training_session_id' => $session->id,
                    'scheduled_for' => $scheduledFor,
                ],
                [
                    'organization_id' => $assignment->organization_id,
                    'athlete_id' => $assignment->athlete_id,
                    'coach_id' => $assignment->program->coach_id,
                    'status' => 'scheduled',
                    'coach_notes' => $session->coach_notes,
                ]
            );

            $created += $workout->wasRecentlyCreated ? 1 : 0;
        }

        return $created;
    }

    public function syncSession(TrainingSession $session, bool $createMissing = true): void
    {
        if (! $session->organization_id) {
            return;
        }

        $session->loadMissing('program.assignments');

        foreach ($session->program->assignments->whereIn('status', ['active', 'paused']) as $assignment) {
            $scheduledFor = CarbonImmutable::parse($assignment->starts_on, $assignment->timezone)
                ->addDays((int) $session->day_offset)
                ->setTime(9, 0)
                ->utc();

            $existing = $assignment->scheduledWorkouts()
                ->where('training_session_id', $session->id)
                ->first();

            if ($existing?->logs()->exists()) {
                continue;
            }

            if ($existing) {
                $existing->update([
                    'scheduled_for' => $scheduledFor,
                    'coach_notes' => $session->coach_notes,
                ]);

            } elseif ($createMissing) {
                $assignment->scheduledWorkouts()->create([
                    'organization_id' => $assignment->organization_id,
                    'training_session_id' => $session->id,
                    'athlete_id' => $assignment->athlete_id,
                    'coach_id' => $session->program->coach_id,
                    'scheduled_for' => $scheduledFor,
                    'status' => 'scheduled',
                    'coach_notes' => $session->coach_notes,
                ]);
            }

            $lastOffset = (int) $session->program->sessions()->where('status', '!=', 'cancelled')->max('day_offset');
            $assignment->update([
                'ends_on' => CarbonImmutable::parse($assignment->starts_on)->addDays($lastOffset)->toDateString(),
            ]);
        }
    }

    public function reschedule(ScheduledWorkout $workout, string $scheduledFor): ScheduledWorkout
    {
        if ($workout->logs()->exists()) {
            throw ValidationException::withMessages(['rescheduleDate' => 'A workout with execution data cannot be rescheduled.']);
        }

        $timezone = $workout->assignment->timezone;
        $localTime = $workout->scheduled_for->timezone($timezone);
        $workout->update([
            'scheduled_for' => CarbonImmutable::parse($scheduledFor, $timezone)
                ->setTime($localTime->hour, $localTime->minute)
                ->utc(),
        ]);
        $this->audit->record(
            'workout.rescheduled',
            'scheduled_workout',
            $workout->id,
            "Rescheduled {$workout->session->title} for {$workout->scheduled_for->toDateString()}.",
        );
        $this->notifications->workoutRescheduled($workout);

        return $workout->refresh();
    }

    public function setAssignmentStatus(ProgramAssignment $assignment, string $status): void
    {
        if (! in_array($status, ['active', 'paused', 'completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['assignmentStatus' => 'Invalid assignment status.']);
        }

        DB::transaction(function () use ($assignment, $status): void {
            if ($assignment->status === 'draft') {
                throw ValidationException::withMessages(['status' => 'Publish this athlete plan before changing its status.']);
            }
            $assignment->update(['status' => $status]);

            if (in_array($status, ['cancelled', 'completed'], true)) {
                $assignment->scheduledWorkouts()
                    ->whereDoesntHave('logs')
                    ->where('status', 'scheduled')
                    ->update(['status' => $status === 'cancelled' ? 'skipped' : 'cancelled']);
            }

            $this->audit->record(
                'program_assignment.status_changed',
                'program_assignment',
                $assignment->id,
                "Changed assignment status to {$status}.",
            );
            $this->notifications->assignmentStatusChanged($assignment);
        });
    }
}
