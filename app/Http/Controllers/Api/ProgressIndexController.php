<?php

namespace App\Http\Controllers\Api;

use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\AthleteCheckIn;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\AthleteProgressAnalyticsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressIndexController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(Request $request, AthleteProgressAnalyticsService $progressAnalytics): JsonResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing([
            'roles',
            'latestAthleteCheckIn',
            'deviceConnections.latestSnapshot',
            'trainingProgramsAsAthlete.sessions.workoutLog',
        ]);

        return response()->json([
            'data' => [
                'viewerRole' => $viewer->primaryRoleName(),
                'canManageOwnCheckIns' => $viewer->hasRole(RoleName::Athlete),
                'summary' => $this->summary($viewer),
                'athleteProfile' => $viewer->hasRole(RoleName::Athlete) ? $this->athleteProfile($viewer, $progressAnalytics) : null,
                'athletes' => $viewer->hasRole(RoleName::Admin) || $viewer->hasRole(RoleName::Coach)
                    ? $this->athletesPayload($viewer, $progressAnalytics)
                    : null,
            ],
            'meta' => $this->metaPayload(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(User $viewer): array
    {
        $baseAthleteQuery = $this->visibleAthletesQuery($viewer);

        return [
            'visibleAthletes' => (clone $baseAthleteQuery)->count(),
            'checkInsThisWeek' => AthleteCheckIn::query()
                ->whereIn('user_id', (clone $baseAthleteQuery)->pluck('id'))
                ->where('logged_date', '>=', now()->startOfWeek()->toDateString())
                ->count(),
            'athletesMissingRecentCheckIn' => (clone $baseAthleteQuery)
                ->get()
                ->filter(function (User $athlete): bool {
                    $latest = $athlete->latestAthleteCheckIn;

                    return ! $latest || $latest->logged_date?->lt(now()->subDays(3)->startOfDay());
                })
                ->count(),
            'connectedDevices' => (clone $baseAthleteQuery)
                ->withCount([
                    'deviceConnections as connected_devices_count' => fn ($query) => $query->where('status', DeviceConnectionStatus::Connected->value),
                ])
                ->get()
                ->sum('connected_devices_count'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function athleteProfile(User $athlete, AthleteProgressAnalyticsService $progressAnalytics): array
    {
        $athlete->loadMissing([
            'latestAthleteCheckIn',
            'athleteCheckIns' => fn ($query) => $query->orderByDesc('logged_date'),
            'athleteAssignments.coach.roles',
            'deviceConnections.latestSnapshot',
            'trainingProgramsAsAthlete.coach.roles',
            'trainingProgramsAsAthlete.sessions.workoutLog',
        ]);

        $progressReport = $progressAnalytics->forUser($athlete);
        $currentProgram = $athlete->trainingProgramsAsAthlete
            ->sortBy(function (TrainingProgram $program): array {
                $weight = $program->status === TrainingProgramStatus::Active ? 0 : 1;

                return [$weight, -($program->start_date?->timestamp ?? 0)];
            })
            ->first();
        $latestSnapshot = $athlete->deviceConnections
            ->pluck('latestSnapshot')
            ->filter()
            ->sortByDesc(fn ($snapshot) => $snapshot->metric_date?->timestamp ?? 0)
            ->first();

        return [
            'metrics' => [
                'latestWeightKg' => $progressReport['overview']['latestWeightKg'],
                'weightDeltaKg' => $progressReport['overview']['weightDeltaKg'],
                'averageCaloriesConsumed' => $progressReport['overview']['averageCaloriesConsumed'],
                'averageProteinGrams' => $progressReport['overview']['averageProteinGrams'],
                'averageWaterLiters' => $progressReport['overview']['averageWaterLiters'],
                'checkInsThisWeek' => $progressReport['overview']['checkInsThisWeek'],
                'completionRate' => $progressReport['overview']['completionRate'],
                'scheduledSessions' => $progressReport['overview']['scheduledSessions'],
                'completedSessions' => $progressReport['overview']['completedSessions'],
            ],
            'progressReport' => $progressReport,
            'recentCheckIns' => $athlete->athleteCheckIns
                ->take(8)
                ->map(fn (AthleteCheckIn $checkIn): array => $this->checkInPayload($checkIn))
                ->values()
                ->all(),
            'latestCheckIn' => $this->checkInPayload($athlete->latestAthleteCheckIn),
            'defaults' => [
                'loggedDate' => now()->toDateString(),
            ],
            'coaches' => $athlete->athleteAssignments
                ->filter(fn ($assignment) => $assignment->status === CoachAthleteStatus::Active)
                ->map(fn ($assignment): array => [
                    'name' => $assignment->coach->name,
                    'email' => $assignment->coach->email,
                    'goal' => $assignment->goal,
                ])
                ->values()
                ->all(),
            'currentProgram' => $currentProgram ? [
                'title' => $currentProgram->title,
                'goal' => $currentProgram->goal,
                'coachName' => $currentProgram->coach->name,
            ] : null,
            'latestSnapshot' => $this->snapshotPayload($latestSnapshot),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function athletesPayload(User $viewer, AthleteProgressAnalyticsService $progressAnalytics): array
    {
        $athletes = $this->visibleAthletesQuery($viewer)
            ->with([
                'latestAthleteCheckIn',
                'deviceConnections.latestSnapshot',
                'athleteAssignments.coach.roles',
                'trainingProgramsAsAthlete.coach.roles',
            ])
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(function (User $athlete) use ($progressAnalytics): array {
                $progressReport = $progressAnalytics->forUser($athlete);
                $latestSnapshot = $athlete->deviceConnections
                    ->pluck('latestSnapshot')
                    ->filter()
                    ->sortByDesc(fn ($snapshot) => $snapshot->metric_date?->timestamp ?? 0)
                    ->first();
                $primaryCoach = $athlete->athleteAssignments
                    ->filter(fn ($assignment) => $assignment->status === CoachAthleteStatus::Active)
                    ->first()
                    ?->coach;

                return [
                    'id' => $athlete->id,
                    'name' => $athlete->name,
                    'email' => $athlete->email,
                    'primaryGoal' => $athlete->primary_goal,
                    'coachName' => $primaryCoach?->name,
                    'latestCheckIn' => $this->checkInPayload($athlete->latestAthleteCheckIn),
                    'latestSnapshot' => $this->snapshotPayload($latestSnapshot),
                    'progressOverview' => $progressReport['overview'],
                    'alerts' => $progressReport['alerts'],
                ];
            });

        return $this->paginationPayload($athletes);
    }

    private function visibleAthletesQuery(User $viewer): Builder
    {
        $query = User::query()->role(RoleName::Athlete);

        if ($viewer->hasRole(RoleName::Admin)) {
            return $query;
        }

        if ($viewer->hasRole(RoleName::Coach)) {
            $athleteIds = $viewer->coachAssignments()
                ->where('status', CoachAthleteStatus::Active->value)
                ->pluck('athlete_id');

            return $query->whereIn('id', $athleteIds);
        }

        return $query->whereKey($viewer->id);
    }
}
