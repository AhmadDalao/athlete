<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CompactUserResource;
use App\Http\Resources\Api\V1\ProgramAssignmentResource;
use App\Http\Resources\Api\V1\ProgressEntryResource;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\ProgramAssignment;
use App\Models\ProgressEntry;
use App\Models\ScheduledWorkout;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoachAppController extends Controller
{
    use RespondsWithApi;

    public function home(Request $request): JsonResponse
    {
        $coach = $request->user();
        $athletes = $this->athleteQuery($coach)->limit(8)->get();
        $schedule = $this->scheduleQuery($coach)
            ->whereDate('scheduled_for', '>=', today())
            ->whereDate('scheduled_for', '<=', today()->addDays(7))
            ->limit(10)
            ->get();

        return $this->success([
            'coach' => $coach->only(['id', 'name', 'email', 'primary_goal']),
            'summary' => [
                'active_athletes' => $this->athleteQuery($coach)->count(),
                'active_programs' => TrainingProgram::query()->where('coach_id', $coach->id)->where('status', 'active')->count(),
                'workouts_this_week' => $schedule->count(),
                'pending_reviews' => ScheduledWorkout::query()->where('coach_id', $coach->id)->where('status', 'partial')->count(),
            ],
            'athletes' => CompactUserResource::collection($athletes),
            'schedule' => WorkoutResource::collection($schedule),
        ]);
    }

    public function roster(Request $request): JsonResponse
    {
        $athletes = $this->athleteQuery($request->user())
            ->when($request->string('search')->isNotEmpty(), function (Builder $query) use ($request): void {
                $search = '%'.trim((string) $request->string('search')).'%';
                $query->where(fn (Builder $query) => $query->where('name', 'like', $search)->orWhere('email', 'like', $search));
            })
            ->paginate($this->pageSize($request->query('per_page')));

        return $this->paginated($athletes, CompactUserResource::class);
    }

    public function athlete(Request $request, User $athlete): JsonResponse
    {
        abort_unless($this->athleteQuery($request->user())->whereKey($athlete->id)->exists(), 403);
        $assignments = ProgramAssignment::query()
            ->where('athlete_id', $athlete->id)
            ->whereHas('program', fn (Builder $query) => $query->where('coach_id', $request->user()->id))
            ->with(['athlete', 'program.coach', 'program.phases', 'scheduledWorkouts.session.prescribedExercises', 'scheduledWorkouts.executionLog.setLogs'])
            ->latest('starts_on')
            ->get();
        $progress = ProgressEntry::query()->where('athlete_id', $athlete->id)->latest('logged_on')->limit(30)->get();

        return $this->success([
            'athlete' => new CompactUserResource($athlete),
            'profile' => $athlete->athleteProfiles()->first(),
            'programs' => ProgramAssignmentResource::collection($assignments),
            'progress' => ProgressEntryResource::collection($progress),
        ]);
    }

    public function programs(Request $request): JsonResponse
    {
        $programs = TrainingProgram::query()
            ->where('coach_id', $request->user()->id)
            ->withCount(['sessions', 'assignments'])
            ->with(['phases'])
            ->latest()
            ->paginate($this->pageSize($request->query('per_page')));

        return $this->success(collect($programs->items())->map(fn (TrainingProgram $program): array => [
            'id' => $program->id,
            'title' => $program->title,
            'goal' => $program->goal,
            'status' => $program->status,
            'estimated_weeks' => $program->estimated_weeks,
            'sessions_count' => $program->sessions_count,
            'assignments_count' => $program->assignments_count,
            'updated_at' => $program->updated_at?->toIso8601String(),
        ]), [
            'current_page' => $programs->currentPage(),
            'last_page' => $programs->lastPage(),
            'per_page' => $programs->perPage(),
            'total' => $programs->total(),
        ], [
            'previous' => $programs->previousPageUrl(),
            'next' => $programs->nextPageUrl(),
        ]);
    }

    public function schedule(Request $request): JsonResponse
    {
        $workouts = $this->scheduleQuery($request->user())
            ->when($request->date('from'), fn (Builder $query, $from) => $query->whereDate('scheduled_for', '>=', $from))
            ->when($request->date('to'), fn (Builder $query, $to) => $query->whereDate('scheduled_for', '<=', $to))
            ->paginate($this->pageSize($request->query('per_page')));

        return $this->paginated($workouts, WorkoutResource::class);
    }

    private function athleteQuery(User $coach): Builder
    {
        return User::query()
            ->whereHas('athleteAssignments', fn (Builder $query) => $query
                ->where('coach_id', $coach->id)
                ->where('status', 'active'))
            ->orderBy('name');
    }

    private function scheduleQuery(User $coach): Builder
    {
        return ScheduledWorkout::query()
            ->where('coach_id', $coach->id)
            ->with(['coach', 'athlete', 'assignment.program', 'session.prescribedExercises', 'executionLog.setLogs'])
            ->orderBy('scheduled_for');
    }
}
