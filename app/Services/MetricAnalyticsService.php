<?php

namespace App\Services;

use App\Models\DeviceConnection;
use App\Models\MetricSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class MetricAnalyticsService
{
    /**
     * @return array{latest:?array<string, mixed>,overview:array<string, mixed>,timeline:list<array<string, mixed>>,alerts:list<string>}
     */
    public function forUser(User $user, ?int $days = null): array
    {
        $windowDays = $days ?? (int) config('throughline.metrics.dashboard_window_days', 7);
        $startDate = now()->subDays($windowDays - 1)->toDateString();
        $endDate = now()->toDateString();
        $snapshots = MetricSnapshot::query()
            ->where('user_id', $user->id)
            ->whereDate('metric_date', '>=', $startDate)
            ->whereDate('metric_date', '<=', $endDate)
            ->orderBy('metric_date')
            ->get();

        return $this->buildUserReport($snapshots);
    }

    /**
     * @return array{latest:?array<string, mixed>,overview:array<string, mixed>,timeline:list<array<string, mixed>>,alerts:list<string>}
     */
    public function forConnection(DeviceConnection $connection, ?int $days = null): array
    {
        $windowDays = $days ?? (int) config('throughline.metrics.dashboard_window_days', 7);
        $startDate = now()->subDays($windowDays - 1)->toDateString();
        $endDate = now()->toDateString();
        $snapshots = $connection->metricSnapshots()
            ->whereDate('metric_date', '>=', $startDate)
            ->whereDate('metric_date', '<=', $endDate)
            ->orderBy('metric_date')
            ->get();

        return $this->buildTimelineReport($snapshots->map(fn (MetricSnapshot $snapshot): array => $this->snapshotRow($snapshot)));
    }

    /**
     * @param  EloquentCollection<int, MetricSnapshot>  $snapshots
     * @return array{latest:?array<string, mixed>,overview:array<string, mixed>,timeline:list<array<string, mixed>>,alerts:list<string>}
     */
    private function buildUserReport(EloquentCollection $snapshots): array
    {
        $dailyRows = $snapshots
            ->groupBy(fn (MetricSnapshot $snapshot) => (string) $snapshot->metric_date?->toDateString())
            ->sortKeys()
            ->map(fn (EloquentCollection $group, string $metricDate): array => $this->aggregateRows($metricDate, $group))
            ->values();

        return $this->buildTimelineReport($dailyRows);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $timeline
     * @return array{latest:?array<string, mixed>,overview:array<string, mixed>,timeline:list<array<string, mixed>>,alerts:list<string>}
     */
    private function buildTimelineReport(Collection $timeline): array
    {
        $latest = $timeline->last();
        $first = $timeline->first();

        $averageSleepHours = $this->average($timeline, 'sleepHours');
        $averageSleepDebtHours = $this->average($timeline, 'sleepDebtHours');
        $lowReadinessDays = $timeline
            ->filter(fn (array $row) => ($row['readinessScore'] ?? 101) !== null && $row['readinessScore'] <= 60)
            ->count();

        return [
            'latest' => $latest,
            'overview' => [
                'daysTracked' => $timeline->count(),
                'averageReadiness' => $this->average($timeline, 'readinessScore'),
                'averageSleepHours' => $averageSleepHours,
                'averageStrain' => $this->average($timeline, 'strainScore'),
                'totalTrainingLoad' => $this->sum($timeline, 'trainingLoad'),
                'averageRestingHeartRate' => $this->average($timeline, 'restingHeartRate'),
                'averageHrv' => $this->average($timeline, 'heartRateVariability'),
                'averageSleepPerformance' => $this->average($timeline, 'sleepPerformancePercentage'),
                'averageSleepConsistency' => $this->average($timeline, 'sleepConsistencyPercentage'),
                'averageRespiratoryRate' => $this->average($timeline, 'respiratoryRate'),
                'averageBloodOxygen' => $this->average($timeline, 'bloodOxygenPercent'),
                'averageSleepDebtHours' => $averageSleepDebtHours,
                'readinessBand' => $latest['readinessBand'] ?? null,
                'readinessDelta' => $latest && $first && $latest['readinessScore'] !== null && $first['readinessScore'] !== null
                    ? round($latest['readinessScore'] - $first['readinessScore'], 1)
                    : null,
                'lowReadinessDays' => $lowReadinessDays,
            ],
            'timeline' => $timeline->all(),
            'alerts' => $this->alerts($latest, $averageSleepHours, $averageSleepDebtHours, $lowReadinessDays, $timeline->count()),
        ];
    }

    /**
     * @param  EloquentCollection<int, MetricSnapshot>  $group
     * @return array<string, mixed>
     */
    private function aggregateRows(string $metricDate, EloquentCollection $group): array
    {
        return [
            'metricDate' => $metricDate,
            'readinessScore' => $this->aggregateAverage($group, 'readiness_score'),
            'readinessBand' => $this->readinessBand($this->aggregateAverage($group, 'readiness_score')),
            'strainScore' => $this->aggregateMax($group, 'strain_score'),
            'sleepHours' => $this->minutesToHours($this->aggregateMax($group, 'sleep_minutes')),
            'sleepNeedHours' => $this->minutesToHours($this->aggregateMax($group, 'sleep_need_minutes')),
            'sleepDebtHours' => $this->sleepDebtHours(
                $this->aggregateMax($group, 'sleep_need_minutes'),
                $this->aggregateMax($group, 'sleep_minutes'),
            ),
            'steps' => $this->aggregateMax($group, 'steps'),
            'distanceMeters' => $this->aggregateMax($group, 'distance_meters'),
            'activeMinutes' => $this->aggregateMax($group, 'active_minutes'),
            'restingHeartRate' => $this->aggregateAverage($group, 'resting_heart_rate'),
            'heartRateVariability' => $this->aggregateAverage($group, 'heart_rate_variability'),
            'trainingLoad' => $this->aggregateMax($group, 'training_load'),
            'sleepPerformancePercentage' => $this->aggregateAverage($group, 'sleep_performance_percentage'),
            'sleepConsistencyPercentage' => $this->aggregateAverage($group, 'sleep_consistency_percentage'),
            'sleepEfficiencyPercentage' => $this->aggregateAverage($group, 'sleep_efficiency_percentage'),
            'respiratoryRate' => $this->aggregateAverage($group, 'respiratory_rate'),
            'bloodOxygenPercent' => $this->aggregateAverage($group, 'blood_oxygen_percent'),
            'skinTemperatureCelsius' => $this->aggregateAverage($group, 'skin_temperature_celsius'),
            'remSleepHours' => $this->minutesToHours($this->aggregateMax($group, 'rem_sleep_minutes')),
            'slowWaveSleepHours' => $this->minutesToHours($this->aggregateMax($group, 'slow_wave_sleep_minutes')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotRow(MetricSnapshot $snapshot): array
    {
        return [
            'metricDate' => $snapshot->metric_date?->toDateString(),
            'readinessScore' => $snapshot->readiness_score,
            'readinessBand' => $snapshot->readinessBand(),
            'strainScore' => $snapshot->strain_score,
            'sleepHours' => $snapshot->sleepHours(),
            'sleepNeedHours' => $snapshot->sleepNeedHours(),
            'sleepDebtHours' => $snapshot->sleepDebtHours(),
            'steps' => $snapshot->steps,
            'distanceMeters' => $snapshot->distance_meters,
            'activeMinutes' => $snapshot->active_minutes,
            'restingHeartRate' => $snapshot->resting_heart_rate,
            'heartRateVariability' => $snapshot->heart_rate_variability,
            'trainingLoad' => $snapshot->training_load,
            'sleepPerformancePercentage' => $snapshot->sleep_performance_percentage,
            'sleepConsistencyPercentage' => $snapshot->sleep_consistency_percentage,
            'sleepEfficiencyPercentage' => $snapshot->sleep_efficiency_percentage,
            'respiratoryRate' => $snapshot->respiratory_rate,
            'bloodOxygenPercent' => $snapshot->blood_oxygen_percent,
            'skinTemperatureCelsius' => $snapshot->skin_temperature_celsius,
            'remSleepHours' => $this->minutesToHours($snapshot->rem_sleep_minutes),
            'slowWaveSleepHours' => $this->minutesToHours($snapshot->slow_wave_sleep_minutes),
        ];
    }

    private function aggregateAverage(EloquentCollection $group, string $field): ?float
    {
        $values = $group
            ->pluck($field)
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value);

        if ($values->isEmpty()) {
            return null;
        }

        return round($values->avg(), 1);
    }

    private function aggregateMax(EloquentCollection $group, string $field): ?float
    {
        $values = $group
            ->pluck($field)
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value);

        if ($values->isEmpty()) {
            return null;
        }

        return round($values->max(), 1);
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
     */
    private function sum(Collection $timeline, string $field): ?float
    {
        $values = $timeline
            ->pluck($field)
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value);

        if ($values->isEmpty()) {
            return null;
        }

        return round($values->sum(), 1);
    }

    /**
     * @return list<string>
     */
    private function alerts(?array $latest, ?float $averageSleepHours, ?float $averageSleepDebtHours, int $lowReadinessDays, int $daysTracked): array
    {
        $alerts = [];

        if (($latest['readinessScore'] ?? null) !== null && $latest['readinessScore'] <= 60) {
            $alerts[] = 'Latest readiness is in the red zone.';
        }

        if ($averageSleepHours !== null && $averageSleepHours < 7) {
            $alerts[] = 'Average sleep is below 7 hours.';
        }

        if ($averageSleepDebtHours !== null && $averageSleepDebtHours > 1) {
            $alerts[] = 'Recent sleep is trailing sleep need by more than an hour.';
        }

        if ($daysTracked > 0 && $lowReadinessDays >= 2) {
            $alerts[] = "Readiness dipped on {$lowReadinessDays} of the last {$daysTracked} tracked days.";
        }

        return $alerts;
    }

    private function minutesToHours(float|int|null $minutes): ?float
    {
        if ($minutes === null) {
            return null;
        }

        return round(((float) $minutes) / 60, 1);
    }

    private function sleepDebtHours(float|int|null $sleepNeedMinutes, float|int|null $sleepMinutes): ?float
    {
        if ($sleepNeedMinutes === null || $sleepMinutes === null) {
            return null;
        }

        return round((((float) $sleepNeedMinutes) - ((float) $sleepMinutes)) / 60, 1);
    }

    private function readinessBand(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= 80 => 'green',
            $score >= 67 => 'yellow',
            default => 'red',
        };
    }
}
