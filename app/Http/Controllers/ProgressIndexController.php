<?php

namespace App\Http\Controllers;

use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\RoleName;
use App\Models\AthleteCheckIn;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\AthleteProgressAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class ProgressIndexController extends Controller
{
    public function __invoke(AthleteProgressAnalyticsService $progressAnalytics): Response
    {
        /** @var User $viewer */
        $viewer = request()->user()->loadMissing([
            'roles',
            'latestAthleteCheckIn',
            'deviceConnections.latestSnapshot',
            'trainingProgramsAsAthlete.sessions.workoutLog',
        ]);

        return Inertia::render('progress/index', [
            'viewerRole' => $viewer->primaryRoleName(),
            'scopeLabel' => $this->scopeLabel($viewer),
            'canManageOwnCheckIns' => $viewer->hasRole(RoleName::Athlete),
            'summary' => $this->summary($viewer),
            'athleteProfile' => $viewer->hasRole(RoleName::Athlete) ? $this->athleteProfile($viewer, $progressAnalytics) : null,
            'athletes' => $viewer->hasRole(RoleName::Admin) || $viewer->hasRole(RoleName::Coach)
                ? $this->athletesPayload($viewer, $progressAnalytics)
                : null,
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
            ->sortBy(fn (TrainingProgram $program) => [($program->status->value === 'active' ? 0 : 1), -($program->start_date?->timestamp ?? 0)])
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
                ->map(fn (AthleteCheckIn $checkIn): array => $this->checkInRow($checkIn))
                ->values()
                ->all(),
            'latestCheckIn' => $athlete->latestAthleteCheckIn ? $this->checkInRow($athlete->latestAthleteCheckIn) : null,
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
            'latestSnapshot' => $latestSnapshot ? [
                'metricDate' => $latestSnapshot->metric_date?->toDateString(),
                'readinessScore' => $latestSnapshot->readiness_score,
                'sleepHours' => $latestSnapshot->sleepHours(),
                'trainingLoad' => $latestSnapshot->training_load,
            ] : null,
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
                $latestCheckIn = $athlete->latestAthleteCheckIn;
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
                    'latestCheckIn' => $latestCheckIn ? $this->checkInRow($latestCheckIn) : null,
                    'latestSnapshot' => $latestSnapshot ? [
                        'metricDate' => $latestSnapshot->metric_date?->toDateString(),
                        'readinessScore' => $latestSnapshot->readiness_score,
                        'sleepHours' => $latestSnapshot->sleepHours(),
                        'trainingLoad' => $latestSnapshot->training_load,
                    ] : null,
                    'progressOverview' => $progressReport['overview'],
                    'alerts' => $progressReport['alerts'],
                ];
            });

        return [
            'data' => $athletes->items(),
            'current_page' => $athletes->currentPage(),
            'last_page' => $athletes->lastPage(),
            'prev_page_url' => $athletes->previousPageUrl(),
            'next_page_url' => $athletes->nextPageUrl(),
            'total' => $athletes->total(),
        ];
    }

    private function visibleAthletesQuery(User $viewer)
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

    private function scopeLabel(User $viewer): string
    {
        if ($viewer->hasRole(RoleName::Admin)) {
            return 'Platform-wide athlete progress, nutrition logging, and body-composition visibility without relying on wearable data alone.';
        }

        if ($viewer->hasRole(RoleName::Coach)) {
            return 'Your roster’s manual progress layer: food, weight, hydration, energy, soreness, and how that lines up with training.';
        }

        return 'Your manual progress layer for food, body metrics, hydration, and daily readiness outside whatever the watch noticed.';
    }

    /**
     * @return array<string, mixed>
     */
    private function checkInRow(AthleteCheckIn $checkIn): array
    {
        return [
            'id' => $checkIn->id,
            'loggedDate' => $checkIn->logged_date?->toDateString(),
            'weightKg' => $checkIn->weight_kg,
            'bodyFatPercentage' => $checkIn->body_fat_percentage,
            'waistCm' => $checkIn->waist_cm,
            'caloriesConsumed' => $checkIn->calories_consumed,
            'proteinGrams' => $checkIn->protein_grams,
            'carbsGrams' => $checkIn->carbs_grams,
            'fatGrams' => $checkIn->fat_grams,
            'waterLiters' => $checkIn->water_liters,
            'mealsLoggedCount' => $checkIn->meals_logged_count,
            'energyScore' => $checkIn->energy_score,
            'sorenessScore' => $checkIn->soreness_score,
            'stressScore' => $checkIn->stress_score,
            'sleepQualityScore' => $checkIn->sleep_quality_score,
            'notes' => $checkIn->notes,
        ];
    }
}
