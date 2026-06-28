<?php

namespace App\Http\Controllers;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Enums\WorkoutCompletionStatus;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class TrainingIndexController extends Controller
{
    public function __invoke(): Response
    {
        /** @var User $user */
        $user = request()->user()->loadMissing('roles');
        $baseQuery = $this->visibleProgramsQuery($user);
        $programIds = (clone $baseQuery)->pluck('id');
        $sessionQuery = TrainingSession::query()->whereIn('training_program_id', $programIds);
        $logQuery = WorkoutLog::query()->whereIn('training_session_id', $sessionQuery->pluck('id'));

        $programs = (clone $baseQuery)
            ->with([
                'coach.roles',
                'athlete.roles',
                'sessions.workoutLog.setLogs',
            ])
            ->orderByRaw(
                "case status
                    when 'active' then 0
                    when 'draft' then 1
                    when 'completed' then 2
                    when 'archived' then 3
                    else 4
                end"
            )
            ->orderByDesc('start_date')
            ->paginate(8)
            ->withQueryString()
            ->through(fn (TrainingProgram $program): array => [
                'id' => $program->id,
                'title' => $program->title,
                'status' => $program->status->value,
                'goal' => $program->goal,
                'startDate' => $program->start_date?->toDateString(),
                'endDate' => $program->end_date?->toDateString(),
                'notes' => $program->notes,
                'coachName' => $program->coach->name,
                'athleteName' => $program->athlete->name,
                'athleteId' => $program->athlete->id,
                'sessionCount' => $program->sessions->count(),
                'nextSessionDate' => $program->sessions
                    ->where('scheduled_date', '>=', now()->startOfDay())
                    ->sortBy('scheduled_date')
                    ->first()
                    ?->scheduled_date
                    ?->toDateString(),
                'sessions' => $program->sessions
                    ->map(fn (TrainingSession $session): array => [
                        'id' => $session->id,
                        'title' => $session->title,
                        'scheduledDate' => $session->scheduled_date?->toDateString(),
                        'focus' => $session->focus,
                        'instructions' => $session->instructions,
                        'videoUrl' => $session->video_url,
                        'mediaCount' => ($session->video_url ? 1 : 0) + collect($session->media_items ?? [])->count(),
                        'exercises' => $this->normalizeExercises($session->exercises ?? []),
                        'workoutLog' => $session->workoutLog ? [
                            'completionStatus' => $session->workoutLog->completion_status->value,
                            'performedAt' => $session->workoutLog->performed_at?->toDateString(),
                            'durationMinutes' => $session->workoutLog->duration_minutes,
                            'exertionRating' => $session->workoutLog->exertion_rating,
                            'energyScore' => $session->workoutLog->energy_score,
                            'sorenessScore' => $session->workoutLog->soreness_score,
                            'stressScore' => $session->workoutLog->stress_score,
                            'sleepQualityScore' => $session->workoutLog->sleep_quality_score,
                            'notes' => $session->workoutLog->notes,
                            'setLogs' => $session->workoutLog->setLogs
                                ->map(fn ($setLog): array => [
                                    'exerciseIndex' => $setLog->exercise_index,
                                    'exerciseName' => $setLog->exercise_name,
                                    'setNumber' => $setLog->set_number,
                                    'targetReps' => $setLog->target_reps,
                                    'targetLoad' => $setLog->target_load,
                                    'actualReps' => $setLog->actual_reps,
                                    'actualLoad' => $setLog->actual_load,
                                    'actualRpe' => $setLog->actual_rpe,
                                    'completedAt' => $setLog->completed_at?->toIso8601String(),
                                ])
                                ->values()
                                ->all(),
                        ] : null,
                    ])
                    ->values()
                    ->all(),
            ]);

        return Inertia::render('training/index', [
            'viewerRole' => $user->primaryRoleName(),
            'scopeLabel' => $this->scopeLabel($user),
            'summary' => $this->summary($user, $programIds, $sessionQuery, $logQuery),
            'programs' => $programs,
            'rosterAthletes' => $this->rosterAthletes($user),
            'canCreatePrograms' => $user->hasRole(RoleName::Coach),
            'statusOptions' => collect(WorkoutCompletionStatus::cases())
                ->map(fn (WorkoutCompletionStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values()
                ->all(),
            'exerciseFormatHint' => 'One line per exercise: Exercise | sets | reps or time | load | rest | target | note. Legacy "Exercise | prescription | note" still works.',
        ]);
    }

    private function visibleProgramsQuery(User $user): Builder
    {
        $query = TrainingProgram::query();

        if ($user->hasRole(RoleName::Admin)) {
            return $query;
        }

        if ($user->hasRole(RoleName::Coach)) {
            return $query->where('coach_id', $user->id);
        }

        return $query->where('athlete_id', $user->id);
    }

    /**
     * @return array<string, int>
     */
    private function summary(User $user, $programIds, Builder $sessionQuery, Builder $logQuery): array
    {
        $programQuery = TrainingProgram::query()->whereIn('id', $programIds);

        if ($user->hasRole(RoleName::Admin)) {
            return [
                'trackedPrograms' => $programQuery->count(),
                'activePrograms' => (clone $programQuery)->where('status', TrainingProgramStatus::Active->value)->count(),
                'scheduledThisWeek' => (clone $sessionQuery)
                    ->whereBetween('scheduled_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                    ->count(),
                'loggedSessions' => (clone $logQuery)->count(),
            ];
        }

        if ($user->hasRole(RoleName::Coach)) {
            return [
                'trackedPrograms' => $programQuery->count(),
                'activePrograms' => (clone $programQuery)->where('status', TrainingProgramStatus::Active->value)->count(),
                'scheduledThisWeek' => (clone $sessionQuery)
                    ->whereBetween('scheduled_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                    ->count(),
                'pendingLogs' => (clone $sessionQuery)
                    ->where('scheduled_date', '<=', now()->toDateString())
                    ->whereDoesntHave('workoutLog')
                    ->count(),
            ];
        }

        return [
            'trackedPrograms' => $programQuery->count(),
            'activePrograms' => (clone $programQuery)->where('status', TrainingProgramStatus::Active->value)->count(),
            'scheduledThisWeek' => (clone $sessionQuery)
                ->whereBetween('scheduled_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->count(),
            'completedThisWeek' => (clone $logQuery)
                ->where('completion_status', WorkoutCompletionStatus::Completed->value)
                ->where('performed_at', '>=', now()->subDays(6)->startOfDay())
                ->count(),
        ];
    }

    private function scopeLabel(User $user): string
    {
        if ($user->hasRole(RoleName::Admin)) {
            return 'All training programs, assigned sessions, and workout logs across the platform';
        }

        if ($user->hasRole(RoleName::Coach)) {
            return 'Programs you assigned, upcoming sessions, and the workout logs your roster is producing';
        }

        return 'Your assigned training blocks and session log history';
    }

    /**
     * @return list<array{id:int,name:string,email:string}>
     */
    private function rosterAthletes(User $user): array
    {
        if (! $user->hasRole(RoleName::Coach)) {
            return [];
        }

        return $user->coachAssignments()
            ->where('status', CoachAthleteStatus::Active->value)
            ->with('athlete')
            ->get()
            ->map(fn ($assignment): array => [
                'id' => $assignment->athlete->id,
                'name' => $assignment->athlete->name,
                'email' => $assignment->athlete->email,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $exercises
     * @return list<array<string, mixed>>
     */
    private function normalizeExercises(array $exercises): array
    {
        return collect($exercises)
            ->filter(fn ($exercise) => is_array($exercise))
            ->map(fn (array $exercise): array => [
                'name' => (string) ($exercise['name'] ?? 'Exercise'),
                'prescription' => $exercise['prescription'] ?? null,
                'sets' => isset($exercise['sets']) ? (int) $exercise['sets'] : null,
                'reps' => $exercise['reps'] ?? null,
                'load' => $exercise['load'] ?? null,
                'rest_seconds' => isset($exercise['rest_seconds']) ? (int) $exercise['rest_seconds'] : null,
                'rest_label' => $exercise['rest_label'] ?? null,
                'target' => $exercise['target'] ?? null,
                'note' => $exercise['note'] ?? null,
            ])
            ->values()
            ->all();
    }
}
