<?php

namespace App\Services;

use App\Enums\WorkoutCompletionStatus;
use App\Models\AthleteCheckIn;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class AthleteProgressAnalyticsService
{
    /**
     * @return array{latest:?array<string, mixed>,overview:array<string, mixed>,timeline:list<array<string, mixed>>,alerts:list<string>}
     */
    public function forUser(User $user, ?int $days = null): array
    {
        $windowDays = $days ?? (int) config('throughline.progress.dashboard_window_days', 14);
        $startDate = now()->subDays($windowDays - 1)->toDateString();
        $endDate = now()->toDateString();
        $checkIns = AthleteCheckIn::query()
            ->where('user_id', $user->id)
            ->whereDate('logged_date', '>=', $startDate)
            ->whereDate('logged_date', '<=', $endDate)
            ->orderBy('logged_date')
            ->get();

        return $this->buildUserReport($user, $checkIns, $startDate, $endDate);
    }

    /**
     * @param  EloquentCollection<int, AthleteCheckIn>  $checkIns
     * @return array{latest:?array<string, mixed>,overview:array<string, mixed>,timeline:list<array<string, mixed>>,alerts:list<string>}
     */
    private function buildUserReport(User $user, EloquentCollection $checkIns, string $startDate, string $endDate): array
    {
        $timeline = $checkIns
            ->map(fn (AthleteCheckIn $checkIn): array => $this->checkInRow($checkIn))
            ->values();

        $latest = $timeline->last();
        $first = $timeline->first();
        $programIds = $user->trainingProgramsAsAthlete()->pluck('id');
        $scheduledSessions = TrainingSession::query()
            ->whereIn('training_program_id', $programIds)
            ->whereBetween('scheduled_date', [$startDate, $endDate])
            ->whereDate('scheduled_date', '<=', now()->toDateString())
            ->count();
        $completedSessions = WorkoutLog::query()
            ->where('athlete_id', $user->id)
            ->where('completion_status', WorkoutCompletionStatus::Completed->value)
            ->whereBetween('performed_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->count();

        return [
            'latest' => $latest,
            'overview' => [
                'daysTracked' => $timeline->count(),
                'checkInsThisWeek' => $checkIns
                    ->filter(fn (AthleteCheckIn $checkIn) => $checkIn->logged_date?->gte(now()->startOfWeek()))
                    ->count(),
                'lastLoggedDate' => $latest['loggedDate'] ?? null,
                'latestWeightKg' => $latest['weightKg'] ?? null,
                'weightDeltaKg' => $latest && $first && $latest['weightKg'] !== null && $first['weightKg'] !== null
                    ? round($latest['weightKg'] - $first['weightKg'], 1)
                    : null,
                'averageCaloriesConsumed' => $this->average($timeline, 'caloriesConsumed'),
                'averageProteinGrams' => $this->average($timeline, 'proteinGrams'),
                'averageCarbsGrams' => $this->average($timeline, 'carbsGrams'),
                'averageFatGrams' => $this->average($timeline, 'fatGrams'),
                'averageWaterLiters' => $this->average($timeline, 'waterLiters'),
                'averageEnergyScore' => $this->average($timeline, 'energyScore'),
                'averageSorenessScore' => $this->average($timeline, 'sorenessScore'),
                'averageStressScore' => $this->average($timeline, 'stressScore'),
                'averageSleepQualityScore' => $this->average($timeline, 'sleepQualityScore'),
                'averageBodyFatPercentage' => $this->average($timeline, 'bodyFatPercentage'),
                'averageWaistCm' => $this->average($timeline, 'waistCm'),
                'scheduledSessions' => $scheduledSessions,
                'completedSessions' => $completedSessions,
                'completionRate' => $scheduledSessions > 0
                    ? round(($completedSessions / $scheduledSessions) * 100, 1)
                    : null,
            ],
            'timeline' => $timeline->all(),
            'alerts' => $this->alerts($timeline, $latest),
        ];
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

    /**
     * @param  Collection<int, array<string, mixed>>  $timeline
     */
    private function average(Collection $timeline, string $field): ?float
    {
        $values = $timeline
            ->pluck($field)
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value);

        if ($values->isEmpty()) {
            return null;
        }

        return round($values->avg(), 1);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $timeline
     * @return list<string>
     */
    private function alerts(Collection $timeline, ?array $latest): array
    {
        $alerts = [];

        if (! $latest) {
            return ['No athlete check-ins have been logged in this tracking window.'];
        }

        $latestDate = $latest['loggedDate'] ?? null;

        if ($latestDate && now()->diffInDays($latestDate) >= 3) {
            $alerts[] = 'No fresh athlete check-in has been logged in the last 72 hours.';
        }

        if (($latest['energyScore'] ?? 10) <= 4) {
            $alerts[] = 'Latest energy score is low.';
        }

        if (($latest['sorenessScore'] ?? 0) >= 7) {
            $alerts[] = 'Latest soreness score is high.';
        }

        if (($latest['waterLiters'] ?? 99) < 2) {
            $alerts[] = 'Latest hydration entry is below 2 liters.';
        }

        if (
            ($latest['weightKg'] ?? null) !== null
            && ($latest['proteinGrams'] ?? null) !== null
            && $latest['proteinGrams'] < round($latest['weightKg'] * 1.6)
        ) {
            $alerts[] = 'Latest protein intake is below a simple 1.6 g/kg recovery target.';
        }

        $weightValues = $timeline
            ->pluck('weightKg')
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value);

        if ($weightValues->count() >= 2) {
            $delta = round($weightValues->last() - $weightValues->first(), 1);

            if (abs($delta) >= 1.5) {
                $alerts[] = sprintf('Weight moved %s kg across the tracked window.', $delta > 0 ? "+{$delta}" : (string) $delta);
            }
        }

        return $alerts;
    }
}
