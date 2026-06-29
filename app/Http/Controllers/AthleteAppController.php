<?php

namespace App\Http\Controllers;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Models\CoachAthleteMessage;
use App\Models\MetricSnapshot;
use App\Models\SystemNotification;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\AthleteWorkoutExecutionService;
use App\Support\TrainingAppPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AthleteAppController extends Controller
{
    public function __invoke(Request $request, AthleteWorkoutExecutionService $workouts, TrainingAppPresenter $presenter): Response|RedirectResponse
    {
        /** @var User $athlete */
        $athlete = $request->user()->loadMissing(['roles', 'memberships.plan', 'deviceConnections', 'latestAthleteCheckIn']);

        if (! $athlete->hasRole(RoleName::Athlete)) {
            return redirect()->to($athlete->landingPath());
        }

        $assignments = $athlete->athleteAssignments()
            ->where('status', CoachAthleteStatus::Active->value)
            ->with('coach')
            ->latest('started_at')
            ->get();
        $assignment = $assignments
            ->first();

        $programs = TrainingProgram::query()
            ->where('athlete_id', $athlete->id)
            ->whereIn('status', [TrainingProgramStatus::Active->value, TrainingProgramStatus::Draft->value])
            ->with(['coach', 'athlete', 'sessions.workoutLog.setLogs'])
            ->orderByRaw("case status when 'active' then 0 when 'draft' then 1 else 2 end")
            ->orderByDesc('start_date')
            ->get();
        $program = $programs
            ->first();

        $session = $this->nextSession($programs);
        $latestSnapshot = MetricSnapshot::query()
            ->where('user_id', $athlete->id)
            ->latest('metric_date')
            ->first();
        $chartRange = $this->chartRange($request);
        $selectedDate = $this->selectedDate($request);
        $month = $this->selectedMonth($request, $selectedDate);
        $allSessions = $programs
            ->flatMap(fn (TrainingProgram $trainingProgram) => $trainingProgram->sessions)
            ->sortBy(fn (TrainingSession $trainingSession) => [
                $trainingSession->scheduled_date?->toDateString() ?? '9999-12-31',
                $trainingSession->sort_order,
            ])
            ->values();
        $selectedDaySessions = $allSessions
            ->filter(fn (TrainingSession $trainingSession): bool => $trainingSession->scheduled_date?->toDateString() === $selectedDate->toDateString())
            ->map(fn (TrainingSession $trainingSession): array => array_merge($presenter->session($trainingSession), [
                'program' => [
                    'id' => $trainingSession->program->id,
                    'title' => $trainingSession->program->title,
                    'goal' => $trainingSession->program->goal,
                    'status' => $trainingSession->program->status->value,
                ],
                'coach' => [
                    'id' => $trainingSession->program->coach->id,
                    'name' => $trainingSession->program->coach->name,
                    'email' => $trainingSession->program->coach->email,
                ],
            ]))
            ->values()
            ->all();

        $membership = $athlete->currentMembership();
        $messageUnreadCount = CoachAthleteMessage::query()
            ->where('recipient_id', $athlete->id)
            ->whereNull('read_at')
            ->count();

        return Inertia::render('athlete-app/index', [
            'viewer' => [
                'id' => $athlete->id,
                'name' => $athlete->name,
                'email' => $athlete->email,
                'goal' => $athlete->primary_goal,
            ],
            'coaches' => $assignments
                ->map(fn ($activeAssignment): array => [
                    'id' => $activeAssignment->coach->id,
                    'name' => $activeAssignment->coach->name,
                    'email' => $activeAssignment->coach->email,
                    'goal' => $activeAssignment->goal,
                    'status' => $activeAssignment->status->value,
                    'startedAt' => $activeAssignment->started_at?->toDateString(),
                ])
                ->values()
                ->all(),
            'coach' => $assignment ? [
                'id' => $assignment->coach->id,
                'name' => $assignment->coach->name,
                'email' => $assignment->coach->email,
                'goal' => $assignment->goal,
                'status' => $assignment->status->value,
                'startedAt' => $assignment->started_at?->toDateString(),
            ] : null,
            'training' => [
                'program' => $program ? [
                    'id' => $program->id,
                    'title' => $program->title,
                    'goal' => $program->goal,
                    'status' => $program->status->value,
                    'startDate' => $program->start_date?->toDateString(),
                    'endDate' => $program->end_date?->toDateString(),
                ] : null,
                'todaySession' => $session ? $workouts->payload($athlete, $session) : null,
            ],
            'programs' => $programs
                ->map(fn (TrainingProgram $trainingProgram): array => $presenter->program($trainingProgram))
                ->values()
                ->all(),
            'schedule' => [
                'selectedDate' => $selectedDate->toDateString(),
                'month' => $month->format('Y-m'),
                'monthLabel' => $month->format('F Y'),
                'previousMonth' => $month->subMonth()->format('Y-m'),
                'nextMonth' => $month->addMonth()->format('Y-m'),
                'days' => $this->calendarDays($month, $allSessions, $selectedDate),
            ],
            'selectedDaySessions' => $selectedDaySessions,
            'membership' => $membership ? [
                'id' => $membership->id,
                'planName' => $membership->plan?->name ?? 'Membership',
                'status' => $membership->status->value,
                'startsAt' => $membership->starts_at?->toDateString(),
                'renewsAt' => $membership->renews_at?->toDateString(),
                'endsAt' => $membership->ends_at?->toDateString(),
                'effectiveEndDate' => $membership->effectiveEndDate()?->toDateString(),
                'daysRemaining' => $membership->daysRemaining(),
                'autoRenew' => $membership->auto_renew,
            ] : null,
            'wearable' => [
                'connectedCount' => $athlete->deviceConnections
                    ->filter(fn ($connection): bool => $connection->status->value === 'connected')
                    ->count(),
                'latestSnapshot' => $latestSnapshot ? [
                    'metricDate' => $latestSnapshot->metric_date?->toDateString(),
                    'provider' => $latestSnapshot->provider->value,
                    'readinessScore' => $latestSnapshot->readiness_score,
                    'readinessBand' => $latestSnapshot->readinessBand(),
                    'strainScore' => $latestSnapshot->strain_score,
                    'sleepHours' => $latestSnapshot->sleepHours(),
                    'sleepNeedHours' => $latestSnapshot->sleepNeedHours(),
                    'caloriesBurned' => $latestSnapshot->calories_burned,
                    'steps' => $latestSnapshot->steps,
                    'heartRateVariability' => $latestSnapshot->heart_rate_variability,
                    'restingHeartRate' => $latestSnapshot->resting_heart_rate,
                ] : null,
            ],
            'progress' => $athlete->latestAthleteCheckIn ? [
                'loggedDate' => $athlete->latestAthleteCheckIn->logged_date?->toDateString(),
                'weightKg' => $athlete->latestAthleteCheckIn->weight_kg,
                'caloriesConsumed' => $athlete->latestAthleteCheckIn->calories_consumed,
                'proteinGrams' => $athlete->latestAthleteCheckIn->protein_grams,
                'waterLiters' => $athlete->latestAthleteCheckIn->water_liters,
                'energyScore' => $athlete->latestAthleteCheckIn->energy_score,
                'sorenessScore' => $athlete->latestAthleteCheckIn->soreness_score,
                'stressScore' => $athlete->latestAthleteCheckIn->stress_score,
                'sleepQualityScore' => $athlete->latestAthleteCheckIn->sleep_quality_score,
            ] : null,
            'feed' => [
                'unreadNotifications' => $this->unreadNotificationCount($athlete),
                'unreadMessages' => $messageUnreadCount,
                'items' => $this->feedItems($athlete),
            ],
            'charts' => $this->chartPayload($athlete, $chartRange),
        ]);
    }

    private function chartRange(Request $request): int
    {
        $range = (int) $request->input('range', 30);

        return in_array($range, [7, 30, 60, 90], true) ? $range : 30;
    }

    /**
     * @return array<string, mixed>
     */
    private function chartPayload(User $athlete, int $rangeDays): array
    {
        $endDate = CarbonImmutable::now()->startOfDay();
        $startDate = $endDate->subDays($rangeDays - 1);

        $snapshots = MetricSnapshot::query()
            ->where('user_id', $athlete->id)
            ->whereDate('metric_date', '>=', $startDate->toDateString())
            ->whereDate('metric_date', '<=', $endDate->toDateString())
            ->orderBy('metric_date')
            ->get();

        $checkIns = $athlete->athleteCheckIns()
            ->whereDate('logged_date', '>=', $startDate->toDateString())
            ->whereDate('logged_date', '<=', $endDate->toDateString())
            ->orderBy('logged_date')
            ->get();

        return [
            'rangeDays' => $rangeDays,
            'rangeOptions' => [7, 30, 60, 90],
            'from' => $startDate->toDateString(),
            'to' => $endDate->toDateString(),
            'wearable' => [
                'readiness' => $this->dailySeries($snapshots, 'metric_date', $startDate, $rangeDays, fn (MetricSnapshot $snapshot) => $snapshot->readiness_score),
                'strain' => $this->dailySeries($snapshots, 'metric_date', $startDate, $rangeDays, fn (MetricSnapshot $snapshot) => $snapshot->strain_score),
                'sleepHours' => $this->dailySeries($snapshots, 'metric_date', $startDate, $rangeDays, fn (MetricSnapshot $snapshot) => $snapshot->sleepHours()),
                'heartRateVariability' => $this->dailySeries($snapshots, 'metric_date', $startDate, $rangeDays, fn (MetricSnapshot $snapshot) => $snapshot->heart_rate_variability),
                'restingHeartRate' => $this->dailySeries($snapshots, 'metric_date', $startDate, $rangeDays, fn (MetricSnapshot $snapshot) => $snapshot->resting_heart_rate),
                'steps' => $this->dailySeries($snapshots, 'metric_date', $startDate, $rangeDays, fn (MetricSnapshot $snapshot) => $snapshot->steps),
                'caloriesBurned' => $this->dailySeries($snapshots, 'metric_date', $startDate, $rangeDays, fn (MetricSnapshot $snapshot) => $snapshot->calories_burned),
                'activeMinutes' => $this->dailySeries($snapshots, 'metric_date', $startDate, $rangeDays, fn (MetricSnapshot $snapshot) => $snapshot->active_minutes),
            ],
            'progress' => [
                'weightKg' => $this->dailySeries($checkIns, 'logged_date', $startDate, $rangeDays, fn ($checkIn) => $checkIn->weight_kg),
                'bodyFatPercentage' => $this->dailySeries($checkIns, 'logged_date', $startDate, $rangeDays, fn ($checkIn) => $checkIn->body_fat_percentage),
                'waistCm' => $this->dailySeries($checkIns, 'logged_date', $startDate, $rangeDays, fn ($checkIn) => $checkIn->waist_cm),
                'caloriesConsumed' => $this->dailySeries($checkIns, 'logged_date', $startDate, $rangeDays, fn ($checkIn) => $checkIn->calories_consumed),
                'proteinGrams' => $this->dailySeries($checkIns, 'logged_date', $startDate, $rangeDays, fn ($checkIn) => $checkIn->protein_grams),
                'carbsGrams' => $this->dailySeries($checkIns, 'logged_date', $startDate, $rangeDays, fn ($checkIn) => $checkIn->carbs_grams),
                'fatGrams' => $this->dailySeries($checkIns, 'logged_date', $startDate, $rangeDays, fn ($checkIn) => $checkIn->fat_grams),
                'waterLiters' => $this->dailySeries($checkIns, 'logged_date', $startDate, $rangeDays, fn ($checkIn) => $checkIn->water_liters),
                'energyScore' => $this->dailySeries($checkIns, 'logged_date', $startDate, $rangeDays, fn ($checkIn) => $checkIn->energy_score),
                'sorenessScore' => $this->dailySeries($checkIns, 'logged_date', $startDate, $rangeDays, fn ($checkIn) => $checkIn->soreness_score),
                'stressScore' => $this->dailySeries($checkIns, 'logged_date', $startDate, $rangeDays, fn ($checkIn) => $checkIn->stress_score),
                'sleepQualityScore' => $this->dailySeries($checkIns, 'logged_date', $startDate, $rangeDays, fn ($checkIn) => $checkIn->sleep_quality_score),
            ],
        ];
    }

    /**
     * @param  Collection<int, mixed>  $records
     * @return list<array{date:string,value:float|int|null}>
     */
    private function dailySeries(Collection $records, string $dateColumn, CarbonImmutable $startDate, int $rangeDays, callable $valueResolver): array
    {
        $valuesByDate = $records
            ->mapWithKeys(function ($record) use ($dateColumn, $valueResolver): array {
                $value = $valueResolver($record);
                $date = $record->{$dateColumn}?->toDateString();

                if ($date === null) {
                    return [];
                }

                return [$date => $value === null ? null : (is_float($value) ? round($value, 2) : $value)];
            })
            ->all();

        return collect(range(0, $rangeDays - 1))
            ->map(function (int $offset) use ($startDate, $valuesByDate): array {
                $date = $startDate->addDays($offset)->toDateString();

                return [
                    'date' => $date,
                    'value' => $valuesByDate[$date] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, TrainingProgram>  $programs
     */
    private function nextSession(Collection $programs): ?TrainingSession
    {
        $today = now()->toDateString();
        $sessions = $programs
            ->flatMap(fn (TrainingProgram $program) => $program->sessions)
            ->values();

        return $sessions
            ->filter(fn (TrainingSession $session): bool => $session->scheduled_date?->toDateString() === $today)
            ->sortBy('sort_order')
            ->first()
            ?? $sessions
                ->filter(fn (TrainingSession $session): bool => $session->scheduled_date?->toDateString() >= $today)
                ->sortBy('scheduled_date')
                ->first()
            ?? $sessions
                ->sortByDesc('scheduled_date')
                ->first();
    }

    private function selectedDate(Request $request): CarbonImmutable
    {
        $date = $request->string('date')->toString();

        if ($date !== '') {
            try {
                return CarbonImmutable::parse($date)->startOfDay();
            } catch (\Throwable) {
                return CarbonImmutable::now()->startOfDay();
            }
        }

        return CarbonImmutable::now()->startOfDay();
    }

    private function selectedMonth(Request $request, CarbonImmutable $selectedDate): CarbonImmutable
    {
        $month = $request->string('month')->toString();

        if ($month !== '') {
            try {
                return CarbonImmutable::parse("{$month}-01")->startOfMonth();
            } catch (\Throwable) {
                return $selectedDate->startOfMonth();
            }
        }

        return $selectedDate->startOfMonth();
    }

    /**
     * @param  Collection<int, TrainingSession>  $sessions
     * @return list<array<string, mixed>>
     */
    private function calendarDays(CarbonImmutable $month, Collection $sessions, CarbonImmutable $selectedDate): array
    {
        $monthSessions = $sessions
            ->filter(fn (TrainingSession $session): bool => $session->scheduled_date?->format('Y-m') === $month->format('Y-m'))
            ->groupBy(fn (TrainingSession $session): string => $session->scheduled_date?->toDateString() ?? 'undated');

        return collect(range(1, $month->daysInMonth))
            ->map(function (int $day) use ($month, $monthSessions, $selectedDate): array {
                $date = $month->setDay($day);
                $daySessions = $monthSessions->get($date->toDateString(), collect());

                return [
                    'date' => $date->toDateString(),
                    'dayNumber' => $date->day,
                    'weekday' => $date->format('D'),
                    'isToday' => $date->isSameDay(now()),
                    'isSelected' => $date->isSameDay($selectedDate),
                    'sessionCount' => $daySessions->count(),
                    'completedCount' => $daySessions->filter(fn (TrainingSession $session): bool => $session->workoutLog?->completion_status?->value === 'completed')->count(),
                    'hasMedia' => $daySessions->contains(fn (TrainingSession $session): bool => filled($session->video_url) || collect($session->media_items ?? [])->isNotEmpty()),
                ];
            })
            ->values()
            ->all();
    }

    private function unreadNotificationCount(User $athlete): int
    {
        return SystemNotification::query()
            ->visibleTo($athlete)
            ->whereDoesntHave('reads', fn ($query) => $query->where('user_id', $athlete->id))
            ->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function feedItems(User $athlete): array
    {
        return SystemNotification::query()
            ->visibleTo($athlete)
            ->with('reads')
            ->latest()
            ->take(4)
            ->get()
            ->map(fn (SystemNotification $notification): array => [
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'actionLabel' => $notification->action_label,
                'actionUrl' => $notification->action_url,
                'publishedAt' => $notification->created_at?->toIso8601String(),
                'read' => $notification->readBy($athlete),
            ])
            ->values()
            ->all();
    }
}
