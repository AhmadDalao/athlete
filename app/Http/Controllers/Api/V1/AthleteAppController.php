<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProgramAssignmentResource;
use App\Http\Resources\Api\V1\ProgressEntryResource;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\ProgramAssignment;
use App\Models\ProgressEntry;
use App\Models\ScheduledWorkout;
use App\Queries\Athlete\AthleteWorkspaceQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AthleteAppController extends Controller
{
    use RespondsWithApi;

    public function home(Request $request): JsonResponse
    {
        $athlete = $request->user();
        $today = today();
        $assignments = AthleteWorkspaceQuery::assignments($athlete->id)->get();
        $todayWorkouts = AthleteWorkspaceQuery::schedule($athlete->id, $today->toDateString(), $today->toDateString())
            ->with(['coach', 'athlete'])
            ->get();
        $upcoming = AthleteWorkspaceQuery::schedule($athlete->id, $today->toDateString(), $today->copy()->addDays(7)->toDateString())
            ->with(['coach', 'athlete'])
            ->limit(10)
            ->get();
        $latestProgress = ProgressEntry::query()
            ->where('athlete_id', $athlete->id)
            ->latest('logged_on')
            ->first();

        return $this->success([
            'date' => $today->toDateString(),
            'athlete' => $athlete->only(['id', 'name', 'email', 'primary_goal']),
            'programs' => ProgramAssignmentResource::collection($assignments),
            'today_workouts' => WorkoutResource::collection($todayWorkouts),
            'upcoming_workouts' => WorkoutResource::collection($upcoming),
            'latest_progress' => $latestProgress ? new ProgressEntryResource($latestProgress) : null,
        ]);
    }

    public function calendar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $selectedDate = Carbon::createFromFormat('Y-m-d', $validated['date'] ?? today()->toDateString());
        $month = Carbon::createFromFormat('Y-m-d', ($validated['month'] ?? $selectedDate->format('Y-m')).'-01');
        $workouts = AthleteWorkspaceQuery::schedule(
            $request->user()->id,
            $month->copy()->startOfMonth()->toDateString(),
            $month->copy()->endOfMonth()->toDateString(),
        )->with(['coach', 'athlete'])->get();

        return $this->success([
            'month' => $month->format('Y-m'),
            'selected_date' => $selectedDate->toDateString(),
            'days' => collect(range(1, $month->daysInMonth))->map(function (int $day) use ($month, $workouts): array {
                $date = $month->copy()->day($day);

                return [
                    'date' => $date->toDateString(),
                    'weekday' => $date->format('D'),
                    'workout_count' => $workouts->where(fn (ScheduledWorkout $workout): bool => $workout->scheduled_for->isSameDay($date))->count(),
                ];
            }),
            'workouts' => WorkoutResource::collection($workouts),
            'selected_day_workouts' => WorkoutResource::collection(
                $workouts->where(fn (ScheduledWorkout $workout): bool => $workout->scheduled_for->isSameDay($selectedDate))->values()
            ),
        ]);
    }

    public function programs(Request $request): JsonResponse
    {
        $assignments = AthleteWorkspaceQuery::assignments($request->user()->id, (string) $request->query('status', 'current'))
            ->paginate($this->pageSize($request->query('per_page')));

        return $this->paginated($assignments, ProgramAssignmentResource::class);
    }

    public function program(Request $request, ProgramAssignment $assignment): JsonResponse
    {
        abort_unless(AthleteWorkspaceQuery::canOpenAssignment($assignment, $request->user()->id), 403);
        $assignment->load([
            'athlete',
            'program.coach',
            'program.phases',
            'scheduledWorkouts.coach',
            'scheduledWorkouts.athlete',
            'scheduledWorkouts.session.prescribedExercises',
            'scheduledWorkouts.executionLog.setLogs',
        ]);

        return $this->success(new ProgramAssignmentResource($assignment));
    }

    public function workout(Request $request, ScheduledWorkout $workout): JsonResponse
    {
        abort_unless(AthleteWorkspaceQuery::canOpenWorkout($workout, $request->user()->id), 403);
        $workout->load([
            'coach',
            'athlete',
            'assignment.program',
            'session.prescribedExercises',
            'executionLog.setLogs',
        ]);

        return $this->success(new WorkoutResource($workout));
    }
}
