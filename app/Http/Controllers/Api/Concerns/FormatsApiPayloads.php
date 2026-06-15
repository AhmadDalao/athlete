<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\AthleteCheckIn;
use App\Models\MetricSnapshot;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait FormatsApiPayloads
{
    /**
     * @return array<string, mixed>
     */
    protected function metaPayload(array $extra = []): array
    {
        return array_merge([
            'version' => (string) config('throughline.api.version', 'v1'),
            'generatedAt' => now()->toIso8601String(),
        ], $extra);
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewerPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'primaryGoal' => $user->primary_goal,
            'preferredContactMethod' => $user->preferred_contact_method,
            'registrationChannel' => $user->registration_channel,
            'avatarUrl' => $user->avatarUrl(),
            'roles' => $user->roles
                ->map(fn ($role) => ['name' => $role->name, 'label' => $role->label])
                ->values()
                ->all(),
            'primaryRole' => $user->primaryRoleName(),
        ];
    }

    /**
     * @template TItem
     *
     * @param  LengthAwarePaginator<TItem>  $paginator
     * @return array<string, mixed>
     */
    protected function paginationPayload(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->items(),
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function snapshotPayload(?MetricSnapshot $snapshot): ?array
    {
        if (! $snapshot) {
            return null;
        }

        return [
            'metricDate' => $snapshot->metric_date?->toDateString(),
            'provider' => $snapshot->provider->value,
            'readinessScore' => $snapshot->readiness_score,
            'readinessBand' => $snapshot->readinessBand(),
            'strainScore' => $snapshot->strain_score,
            'sleepHours' => $snapshot->sleepHours(),
            'sleepNeedHours' => $snapshot->sleepNeedHours(),
            'sleepDebtHours' => $snapshot->sleepDebtHours(),
            'sleepPerformancePercentage' => $snapshot->sleep_performance_percentage,
            'sleepConsistencyPercentage' => $snapshot->sleep_consistency_percentage,
            'sleepEfficiencyPercentage' => $snapshot->sleep_efficiency_percentage,
            'remSleepMinutes' => $snapshot->rem_sleep_minutes,
            'slowWaveSleepMinutes' => $snapshot->slow_wave_sleep_minutes,
            'steps' => $snapshot->steps,
            'distanceMeters' => $snapshot->distance_meters,
            'caloriesBurned' => $snapshot->calories_burned,
            'activeMinutes' => $snapshot->active_minutes,
            'trainingLoad' => $snapshot->training_load,
            'restingHeartRate' => $snapshot->resting_heart_rate,
            'heartRateVariability' => $snapshot->heart_rate_variability,
            'respiratoryRate' => $snapshot->respiratory_rate,
            'bloodOxygenPercent' => $snapshot->blood_oxygen_percent,
            'skinTemperatureCelsius' => $snapshot->skin_temperature_celsius,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function checkInPayload(?AthleteCheckIn $checkIn): ?array
    {
        if (! $checkIn) {
            return null;
        }

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
     * @return array<string, mixed>|null
     */
    protected function workoutLogPayload(?WorkoutLog $workoutLog): ?array
    {
        if (! $workoutLog) {
            return null;
        }

        return [
            'id' => $workoutLog->id,
            'completionStatus' => $workoutLog->completion_status->value,
            'performedAt' => $workoutLog->performed_at?->toIso8601String(),
            'durationMinutes' => $workoutLog->duration_minutes,
            'exertionRating' => $workoutLog->exertion_rating,
            'notes' => $workoutLog->notes,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $exercises
     * @return list<array<string, mixed>>
     */
    protected function normalizeExercises(array $exercises): array
    {
        return collect($exercises)
            ->filter(fn ($exercise) => is_array($exercise))
            ->map(fn (array $exercise): array => [
                'name' => (string) ($exercise['name'] ?? 'Exercise'),
                'prescription' => $exercise['prescription'] ?? null,
                'sets' => isset($exercise['sets']) ? (int) $exercise['sets'] : null,
                'reps' => $exercise['reps'] ?? null,
                'load' => $exercise['load'] ?? null,
                'restSeconds' => isset($exercise['rest_seconds']) ? (int) $exercise['rest_seconds'] : null,
                'restLabel' => $exercise['rest_label'] ?? null,
                'target' => $exercise['target'] ?? null,
                'note' => $exercise['note'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function trainingSessionPayload(TrainingSession $session): array
    {
        return [
            'id' => $session->id,
            'title' => $session->title,
            'scheduledDate' => $session->scheduled_date?->toDateString(),
            'focus' => $session->focus,
            'instructions' => $session->instructions,
            'exercises' => $this->normalizeExercises($session->exercises ?? []),
            'workoutLog' => $this->workoutLogPayload($session->workoutLog),
        ];
    }
}
