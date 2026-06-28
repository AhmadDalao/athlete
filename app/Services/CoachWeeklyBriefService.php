<?php

namespace App\Services;

use App\Models\User;

class CoachWeeklyBriefService
{
    public function __construct(
        private readonly MetricAnalyticsService $metricAnalytics,
        private readonly AthleteProgressAnalyticsService $progressAnalytics,
    ) {}

    /**
     * @return array{
     *     priority:string,
     *     score:int,
     *     headline:string,
     *     summary:string,
     *     reasons:list<string>
     * }
     */
    public function forAthlete(User $athlete, ?int $days = 7): array
    {
        $metricReport = $this->metricAnalytics->forUser($athlete, $days);
        $progressReport = $this->progressAnalytics->forUser($athlete, $days);

        $metricOverview = $metricReport['overview'];
        $progressOverview = $progressReport['overview'];
        $latestProgress = $progressReport['latest'];

        $reasons = [];
        $score = 0;

        if ($metricOverview['daysTracked'] === 0) {
            $reasons[] = 'No wearable data landed in the current weekly window.';
            $score += 3;
        }

        if (($metricOverview['averageReadiness'] ?? 101) <= 60) {
            $reasons[] = 'Readiness stayed low across the week.';
            $score += 3;
        } elseif (($metricOverview['averageReadiness'] ?? 101) <= 70) {
            $reasons[] = 'Readiness softened enough to watch closely.';
            $score += 2;
        }

        if (($metricOverview['lowReadinessDays'] ?? 0) >= 2) {
            $reasons[] = 'There were multiple low-readiness days.';
            $score += 2;
        }

        if (($metricOverview['averageSleepHours'] ?? 24) < 7) {
            $reasons[] = 'Average sleep sat under seven hours.';
            $score += 2;
        }

        if (($metricOverview['averageSleepDebtHours'] ?? 0) > 1) {
            $reasons[] = 'Sleep debt is still building.';
            $score += 1;
        }

        if ($progressOverview['daysTracked'] === 0) {
            $reasons[] = 'No fresh manual check-in landed this week.';
            $score += 3;
        }

        if (($progressOverview['completionRate'] ?? 100) < 70) {
            $reasons[] = 'Training compliance slipped below 70 percent.';
            $score += 2;
        }

        if (($latestProgress['energyScore'] ?? 10) <= 4) {
            $reasons[] = 'Latest energy score came in low.';
            $score += 2;
        }

        if (($latestProgress['sorenessScore'] ?? 0) >= 7) {
            $reasons[] = 'Latest soreness score is high.';
            $score += 1;
        }

        if (($latestProgress['waterLiters'] ?? 99) < 2) {
            $reasons[] = 'Hydration is sitting below two liters.';
            $score += 1;
        }

        if (
            ($latestProgress['weightKg'] ?? null) !== null
            && ($latestProgress['proteinGrams'] ?? null) !== null
            && $latestProgress['proteinGrams'] < round($latestProgress['weightKg'] * 1.6)
        ) {
            $reasons[] = 'Protein intake is light for current bodyweight.';
            $score += 1;
        }

        $priority = match (true) {
            $score >= 6 => 'high',
            $score >= 3 => 'medium',
            default => 'stable',
        };

        $headline = match ($priority) {
            'high' => 'Direct intervention beats passive monitoring this week.',
            'medium' => 'This athlete is drifting and needs a coach glance before the week gets messy.',
            default => 'The week looks steady enough to progress without drama.',
        };

        $summaryParts = array_filter([
            $metricOverview['averageReadiness'] === null
                ? null
                : 'Readiness averaged '.round((float) $metricOverview['averageReadiness']).'/100',
            $metricOverview['averageSleepHours'] === null
                ? null
                : 'sleep averaged '.number_format((float) $metricOverview['averageSleepHours'], 1).'h',
            $progressOverview['completionRate'] === null
                ? null
                : 'compliance landed at '.round((float) $progressOverview['completionRate']).'%',
            $latestProgress && $latestProgress['energyScore'] !== null
                ? 'latest energy was '.(int) $latestProgress['energyScore'].'/10'
                : null,
            $latestProgress && $latestProgress['sorenessScore'] !== null
                ? 'latest soreness was '.(int) $latestProgress['sorenessScore'].'/10'
                : null,
        ]);

        $summary = $summaryParts === []
            ? 'There is not enough signal yet. That is a data problem, not a motivation problem.'
            : implode('. ', $summaryParts).'.';

        return [
            'priority' => $priority,
            'score' => $score,
            'headline' => $headline,
            'summary' => $summary,
            'reasons' => array_values(array_unique(array_slice($reasons, 0, 4))),
        ];
    }
}
