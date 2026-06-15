<?php

namespace App\Http\Controllers\Api;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Enums\WorkoutCompletionStatus;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingIndexController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('roles');
        $baseQuery = $this->visibleProgramsQuery($user);
        $programIds = (clone $baseQuery)->pluck('id');
        $sessionQuery = TrainingSession::query()->whereIn('training_program_id', $programIds);
        $logQuery = WorkoutLog::query()->whereIn('training_session_id', $sessionQuery->pluck('id'));

        $programs = (clone $baseQuery)
            ->with([
                'coach.roles',
                'athlete.roles',
                'sessions.workoutLog',
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
                'coach' => [
                    'id' => $program->coach->id,
                    'name' => $program->coach->name,
                    'email' => $program->coach->email,
                ],
                'athlete' => [
                    'id' => $program->athlete->id,
                    'name' => $program->athlete->name,
                    'email' => $program->athlete->email,
                ],
                'sessionCount' => $program->sessions->count(),
                'nextSessionDate' => $program->sessions
                    ->where('scheduled_date', '>=', now()->startOfDay())
                    ->sortBy('scheduled_date')
                    ->first()
                    ?->scheduled_date
                    ?->toDateString(),
                'sessions' => $program->sessions
                    ->map(fn (TrainingSession $session): array => $this->trainingSessionPayload($session))
                    ->values()
                    ->all(),
            ]);

        return response()->json([
            'data' => [
                'viewerRole' => $user->primaryRoleName(),
                'summary' => $this->summary($user, $programIds, $sessionQuery, $logQuery),
                'programs' => $this->paginationPayload($programs),
                'rosterAthletes' => $this->rosterAthletes($user),
                'canCreatePrograms' => $user->hasRole(RoleName::Coach),
                'statusOptions' => collect(WorkoutCompletionStatus::cases())
                    ->map(fn (WorkoutCompletionStatus $status): array => [
                        'value' => $status->value,
                        'label' => $status->label(),
                    ])
                    ->values()
                    ->all(),
            ],
            'meta' => $this->metaPayload(),
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
}
