<?php

namespace App\Http\Controllers;

use App\Enums\BillingInterval;
use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\MembershipStatus;
use App\Enums\PaymentEventStatus;
use App\Enums\RoleName;
use App\Enums\SignupMethod;
use App\Enums\TrainingProgramStatus;
use App\Enums\WorkoutCompletionStatus;
use App\Models\DeviceConnection;
use App\Models\Membership;
use App\Models\MetricSnapshot;
use App\Models\PaymentEvent;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Services\AthleteProgressAnalyticsService;
use App\Services\CoachWeeklyBriefService;
use App\Services\MetricAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        MetricAnalyticsService $metricAnalytics,
        AthleteProgressAnalyticsService $progressAnalytics,
        CoachWeeklyBriefService $coachWeeklyBriefs
    ): Response {
        /** @var User $user */
        $user = request()->user()->load([
            'roles',
            'memberships.plan',
            'latestAthleteCheckIn',
            'deviceConnections.latestSnapshot',
            'coachAssignments.athlete.roles',
            'coachAssignments.athlete.memberships.plan',
            'coachAssignments.athlete.deviceConnections.latestSnapshot',
            'coachAssignments.athlete.latestAthleteCheckIn',
            'coachAssignments.athlete.trainingProgramsAsAthlete.sessions.workoutLog',
            'athleteAssignments.coach.roles',
            'trainingProgramsAsAthlete.coach.roles',
            'trainingProgramsAsAthlete.sessions.workoutLog',
        ]);

        return Inertia::render('dashboard', [
            'viewer' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'primaryGoal' => $user->primary_goal,
                'preferredContactMethod' => $user->preferred_contact_method,
                'registrationChannel' => $user->registration_channel,
                'roles' => $user->roles
                    ->map(fn ($role) => ['name' => $role->name, 'label' => $role->label])
                    ->values()
                    ->all(),
                'primaryRole' => $user->primaryRoleName(),
            ],
            'admin' => $user->hasRole(RoleName::Admin) ? $this->adminOverview() : null,
            'coach' => $user->hasRole(RoleName::Coach) ? $this->coachOverview($user, $coachWeeklyBriefs) : null,
            'athlete' => $user->hasRole(RoleName::Athlete) ? $this->athleteOverview($user, $metricAnalytics, $progressAnalytics) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminOverview(): array
    {
        $activeMemberships = Membership::query()
            ->whereIn('status', MembershipStatus::activeValues())
            ->with(['plan', 'user'])
            ->get();
        $paymentEventsThisMonth = PaymentEvent::query()
            ->with(['user', 'membership.plan'])
            ->where('event_at', '>=', now()->startOfMonth())
            ->get();

        $projectedMonthlyRevenue = round($activeMemberships->sum(fn (Membership $membership) => $this->normalizedMonthlyValue($membership)), 2);

        return [
            'metrics' => [
                'totalUsers' => User::query()->count(),
                'totalCoaches' => User::query()->role(RoleName::Coach)->count(),
                'totalAthletes' => User::query()->role(RoleName::Athlete)->count(),
                'activeMemberships' => $activeMemberships->count(),
                'expiringMemberships' => $activeMemberships
                    ->filter(fn (Membership $membership) => $this->daysRemaining($membership) !== null && $this->daysRemaining($membership) <= 7)
                    ->count(),
                'connectedDevices' => DeviceConnection::query()
                    ->where('status', DeviceConnectionStatus::Connected->value)
                    ->count(),
                'attentionConnections' => DeviceConnection::query()
                    ->whereIn('status', [DeviceConnectionStatus::Attention->value, DeviceConnectionStatus::Disconnected->value])
                    ->count(),
                'projectedMonthlyRevenue' => $projectedMonthlyRevenue,
                'coachPayoutLiability' => round($projectedMonthlyRevenue * 0.85, 2),
                'activePrograms' => TrainingProgram::query()
                    ->where('status', TrainingProgramStatus::Active->value)
                    ->count(),
                'scheduledSessionsThisWeek' => TrainingSession::query()
                    ->whereBetween('scheduled_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                    ->count(),
                'loggedSessionsThisWeek' => WorkoutLog::query()
                    ->where('performed_at', '>=', now()->startOfWeek())
                    ->count(),
                'paymentVolumeThisMonth' => round((float) $paymentEventsThisMonth
                    ->where('status', PaymentEventStatus::Succeeded)
                    ->sum('amount'), 2),
                'failedPaymentsThisMonth' => $paymentEventsThisMonth
                    ->where('status', PaymentEventStatus::Failed)
                    ->count(),
                'newUsersThisMonth' => User::query()
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count(),
            ],
            'expiringMembershipWatchlist' => $activeMemberships
                ->sortBy(fn (Membership $membership) => $this->daysRemaining($membership) ?? 10_000)
                ->take(5)
                ->map(fn (Membership $membership): array => [
                    'userName' => $membership->user->name,
                    'planName' => $membership->plan?->name ?? 'Custom plan',
                    'status' => $membership->status->value,
                    'daysRemaining' => $this->daysRemaining($membership),
                    'endsAt' => $membership->effectiveEndDate()?->toDateString(),
                ])
                ->values()
                ->all(),
            'deviceAttentionQueue' => DeviceConnection::query()
                ->whereIn('status', [DeviceConnectionStatus::Attention->value, DeviceConnectionStatus::Disconnected->value])
                ->with('user')
                ->orderByRaw(
                    "case status
                        when 'attention' then 0
                        when 'disconnected' then 1
                        else 2
                    end"
                )
                ->orderBy('last_synced_at')
                ->take(5)
                ->get()
                ->map(fn (DeviceConnection $connection): array => [
                    'userName' => $connection->user->name,
                    'provider' => $connection->provider->label(),
                    'status' => $connection->status->value,
                    'lastSyncedAt' => $connection->last_synced_at?->toDateTimeString(),
                ])
                ->values()
                ->all(),
            'paymentAttentionQueue' => $paymentEventsThisMonth
                ->filter(fn (PaymentEvent $event) => in_array($event->status, [PaymentEventStatus::Pending, PaymentEventStatus::Failed], true))
                ->sortByDesc(fn (PaymentEvent $event) => $event->event_at?->timestamp ?? 0)
                ->take(5)
                ->map(fn (PaymentEvent $event): array => [
                    'userName' => $event->user?->name ?? 'Unknown user',
                    'planName' => $event->membership?->plan?->name ?? 'Custom plan',
                    'status' => $event->status->value,
                    'amount' => $event->amount !== null ? (float) $event->amount : null,
                    'currency' => $event->currency,
                    'eventAt' => $event->event_at?->toDateTimeString(),
                    'reference' => $event->reference,
                    'notes' => $event->notes,
                ])
                ->values()
                ->all(),
            'signupMix' => collect(SignupMethod::cases())
                ->map(fn (SignupMethod $method): array => [
                    'method' => $method->value,
                    'label' => $method->label(),
                    'enabled' => (bool) config("throughline.auth.signup_methods.{$method->value}.enabled", false),
                    'count' => User::query()->where('registration_channel', $method->value)->count(),
                ])
                ->values()
                ->all(),
            'recentUsers' => $this->adminRecentUsers(),
            'athleteTable' => $this->adminAthleteRows(),
            'subscriptionTable' => $this->adminSubscriptionRows(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function adminRecentUsers(): array
    {
        return User::query()
            ->with(['roles', 'memberships.plan'])
            ->withCount(['memberships', 'deviceConnections'])
            ->latest()
            ->take(8)
            ->get()
            ->map(function (User $user): array {
                $membership = $user->currentMembership();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'primaryRole' => $user->primaryRoleName(),
                    'registrationChannel' => $user->registration_channel,
                    'createdAt' => $user->created_at?->toDateString(),
                    'membershipsCount' => $user->memberships_count,
                    'deviceConnectionsCount' => $user->device_connections_count,
                    'currentMembership' => $membership ? [
                        'status' => $membership->status->value,
                        'planName' => $membership->plan?->name ?? 'Custom plan',
                        'subscribedAt' => $membership->starts_at?->toDateString(),
                        'daysRemaining' => $membership->daysRemaining(),
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function adminAthleteRows(): array
    {
        return User::query()
            ->role(RoleName::Athlete)
            ->with([
                'roles',
                'memberships.plan',
                'latestAthleteCheckIn',
                'deviceConnections',
                'athleteAssignments.coach.roles',
            ])
            ->withCount(['memberships', 'deviceConnections', 'trainingProgramsAsAthlete'])
            ->latest()
            ->take(8)
            ->get()
            ->map(function (User $athlete): array {
                $membership = $athlete->currentMembership();
                $activeCoachNames = $athlete->athleteAssignments
                    ->filter(fn ($assignment) => $assignment->status === CoachAthleteStatus::Active)
                    ->map(fn ($assignment) => $assignment->coach->name)
                    ->values()
                    ->all();

                return [
                    'id' => $athlete->id,
                    'name' => $athlete->name,
                    'email' => $athlete->email,
                    'goal' => $athlete->primary_goal,
                    'createdAt' => $athlete->created_at?->toDateString(),
                    'coachNames' => $activeCoachNames,
                    'deviceConnectionsCount' => $athlete->device_connections_count,
                    'trainingProgramsCount' => $athlete->training_programs_as_athlete_count,
                    'latestCheckInAt' => $athlete->latestAthleteCheckIn?->logged_date?->toDateString(),
                    'currentMembership' => $membership ? [
                        'status' => $membership->status->value,
                        'planName' => $membership->plan?->name ?? 'Custom plan',
                        'subscribedAt' => $membership->starts_at?->toDateString(),
                        'daysRemaining' => $membership->daysRemaining(),
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function adminSubscriptionRows(): array
    {
        return Membership::query()
            ->with(['user.roles', 'plan'])
            ->latest('starts_at')
            ->take(8)
            ->get()
            ->map(fn (Membership $membership): array => [
                'id' => $membership->id,
                'userId' => $membership->user->id,
                'userName' => $membership->user->name,
                'userEmail' => $membership->user->email,
                'planName' => $membership->plan?->name ?? 'Custom plan',
                'status' => $membership->status->value,
                'startsAt' => $membership->starts_at?->toDateString(),
                'renewsAt' => $membership->renews_at?->toDateString(),
                'endsAt' => $membership->effectiveEndDate()?->toDateString(),
                'daysRemaining' => $membership->daysRemaining(),
                'price' => (float) $membership->price,
                'currency' => $membership->currency,
                'billingProvider' => $membership->billing_provider,
                'autoRenew' => $membership->auto_renew,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function coachOverview(User $coach, CoachWeeklyBriefService $coachWeeklyBriefs): array
    {
        $coachProgramIds = TrainingProgram::query()
            ->where('coach_id', $coach->id)
            ->pluck('id');

        $roster = $coach->coachAssignments
            ->filter(fn ($assignment) => $assignment->status === CoachAthleteStatus::Active)
            ->map(function ($assignment) use ($coachWeeklyBriefs): array {
                $athlete = $assignment->athlete;
                $membership = $athlete->currentMembership();
                $daysRemaining = $this->daysRemaining($membership);
                $connectedDevices = $athlete->deviceConnections
                    ->where('status', DeviceConnectionStatus::Connected)
                    ->count();
                $latestSnapshot = $this->latestSnapshotFor($athlete);
                $latestCheckIn = $athlete->latestAthleteCheckIn;
                $currentProgram = $this->currentProgramFor($athlete);
                $nextSession = $currentProgram ? $this->nextSessionFor($currentProgram) : null;
                $pendingLogs = $currentProgram ? $this->pendingWorkoutLogsFor($currentProgram) : 0;
                $latestWorkoutLog = $currentProgram ? $this->latestWorkoutLogFor($currentProgram) : null;

                $flags = [];

                if (! $membership) {
                    $flags[] = 'No active membership';
                } elseif ($membership->status->needsAttention()) {
                    $flags[] = $membership->status->label();
                } elseif ($daysRemaining !== null && $daysRemaining <= 7) {
                    $flags[] = 'Renewal soon';
                }

                if (! $currentProgram) {
                    $flags[] = 'No active program';
                }

                if ($pendingLogs > 0) {
                    $flags[] = 'Workout log pending';
                }

                if ($connectedDevices === 0) {
                    $flags[] = 'No device connected';
                }

                if (($latestSnapshot?->readiness_score ?? 101) <= 60) {
                    $flags[] = 'Low readiness';
                }

                if (! $latestCheckIn || $latestCheckIn->logged_date?->lt(now()->subDays(3)->startOfDay())) {
                    $flags[] = 'No recent check-in';
                } elseif (($latestCheckIn->energy_score ?? 10) <= 4) {
                    $flags[] = 'Low energy';
                }

                if (
                    ($latestCheckIn?->weight_kg ?? null) !== null
                    && ($latestCheckIn?->protein_grams ?? null) !== null
                    && $latestCheckIn->protein_grams < round($latestCheckIn->weight_kg * 1.6)
                ) {
                    $flags[] = 'Protein low';
                }

                $weeklyBrief = $coachWeeklyBriefs->forAthlete($athlete);

                return [
                    'id' => $athlete->id,
                    'name' => $athlete->name,
                    'email' => $athlete->email,
                    'goal' => $assignment->goal ?: ($athlete->primary_goal ?: 'General strength block'),
                    'membershipStatus' => $membership?->status?->value ?? 'none',
                    'membershipPlan' => $membership?->plan?->name ?? 'No active plan',
                    'daysRemaining' => $daysRemaining,
                    'connectedDevices' => $connectedDevices,
                    'latestSnapshot' => $latestSnapshot ? [
                        'metricDate' => $latestSnapshot->metric_date?->toDateString(),
                        'readinessScore' => $latestSnapshot->readiness_score,
                        'sleepHours' => $latestSnapshot->sleepHours(),
                        'strainScore' => $latestSnapshot->strain_score,
                    ] : null,
                    'latestCheckIn' => $latestCheckIn ? [
                        'loggedDate' => $latestCheckIn->logged_date?->toDateString(),
                        'weightKg' => $latestCheckIn->weight_kg,
                        'caloriesConsumed' => $latestCheckIn->calories_consumed,
                        'proteinGrams' => $latestCheckIn->protein_grams,
                        'waterLiters' => $latestCheckIn->water_liters,
                        'energyScore' => $latestCheckIn->energy_score,
                        'sorenessScore' => $latestCheckIn->soreness_score,
                    ] : null,
                    'currentProgram' => $currentProgram ? [
                        'title' => $currentProgram->title,
                        'status' => $currentProgram->status->value,
                        'nextSessionDate' => $nextSession?->scheduled_date?->toDateString(),
                        'pendingLogs' => $pendingLogs,
                        'lastWorkoutStatus' => $latestWorkoutLog?->completion_status?->value,
                    ] : null,
                    'weeklyBrief' => $weeklyBrief,
                    'flags' => $flags,
                ];
            })
            ->values();

        return [
            'metrics' => [
                'rosterCount' => $roster->count(),
                'athletesNeedingAttention' => $roster->filter(fn (array $athlete) => count($athlete['flags']) > 0)->count(),
                'activePrograms' => TrainingProgram::query()
                    ->where('coach_id', $coach->id)
                    ->where('status', TrainingProgramStatus::Active->value)
                    ->count(),
                'upcomingSessions' => TrainingSession::query()
                    ->whereIn('training_program_id', $coachProgramIds)
                    ->whereBetween('scheduled_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                    ->count(),
                'pendingWorkoutLogs' => TrainingSession::query()
                    ->whereIn('training_program_id', $coachProgramIds)
                    ->where('scheduled_date', '<=', now()->toDateString())
                    ->whereDoesntHave('workoutLog')
                    ->count(),
            ],
            'roster' => $roster->all(),
            'attentionQueue' => $roster
                ->filter(fn (array $athlete) => count($athlete['flags']) > 0)
                ->take(5)
                ->values()
                ->all(),
            'weeklyBriefs' => $roster
                ->sortByDesc(fn (array $athlete) => $athlete['weeklyBrief']['score'])
                ->take(3)
                ->values()
                ->map(fn (array $athlete): array => [
                    'id' => $athlete['id'],
                    'name' => $athlete['name'],
                    'goal' => $athlete['goal'],
                    'weeklyBrief' => $athlete['weeklyBrief'],
                ])
                ->all(),
            'upcomingSessions' => TrainingSession::query()
                ->whereIn('training_program_id', $coachProgramIds)
                ->with(['program.athlete'])
                ->whereBetween('scheduled_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->orderBy('scheduled_date')
                ->take(6)
                ->get()
                ->map(fn (TrainingSession $session): array => [
                    'athleteName' => $session->program->athlete->name,
                    'programTitle' => $session->program->title,
                    'sessionTitle' => $session->title,
                    'scheduledDate' => $session->scheduled_date?->toDateString(),
                    'focus' => $session->focus,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function athleteOverview(User $athlete, MetricAnalyticsService $metricAnalytics, AthleteProgressAnalyticsService $progressAnalytics): array
    {
        $membership = $athlete->currentMembership();
        $coaches = $athlete->athleteAssignments
            ->filter(fn ($assignment) => $assignment->status === CoachAthleteStatus::Active)
            ->map(fn ($assignment): array => [
                'id' => $assignment->coach->id,
                'name' => $assignment->coach->name,
                'email' => $assignment->coach->email,
                'relationshipStatus' => $assignment->status->value,
                'startDate' => $assignment->started_at?->toDateString(),
                'goal' => $assignment->goal,
            ])
            ->values();

        $deviceConnections = $athlete->deviceConnections
            ->map(fn ($connection): array => [
                'provider' => $connection->provider->value,
                'providerLabel' => $connection->provider->label(),
                'status' => $connection->status->value,
                'lastSyncedAt' => $connection->last_synced_at?->toDateTimeString(),
                'latestSnapshot' => $connection->latestSnapshot ? [
                    'metricDate' => $connection->latestSnapshot->metric_date?->toDateString(),
                    'readinessScore' => $connection->latestSnapshot->readiness_score,
                    'sleepHours' => $connection->latestSnapshot->sleepHours(),
                    'strainScore' => $connection->latestSnapshot->strain_score,
                ] : null,
            ])
            ->values();
        $latestSnapshot = $this->latestSnapshotFor($athlete);
        $currentProgram = $this->currentProgramFor($athlete);
        $programIds = $athlete->trainingProgramsAsAthlete->pluck('id');
        $metricReport = $metricAnalytics->forUser($athlete);
        $progressReport = $progressAnalytics->forUser($athlete);
        $latestCheckIn = $athlete->latestAthleteCheckIn;

        return [
            'metrics' => [
                'coachCount' => $coaches->count(),
                'connectedDevices' => $deviceConnections->where('status', DeviceConnectionStatus::Connected->value)->count(),
                'membershipDaysRemaining' => $this->daysRemaining($membership),
                'latestReadinessScore' => $latestSnapshot?->readiness_score,
                'latestWeightKg' => $progressReport['overview']['latestWeightKg'],
                'weightDeltaKg' => $progressReport['overview']['weightDeltaKg'],
                'averageCaloriesConsumed' => $progressReport['overview']['averageCaloriesConsumed'],
                'averageProteinGrams' => $progressReport['overview']['averageProteinGrams'],
                'checkInsThisWeek' => $progressReport['overview']['checkInsThisWeek'],
                'upcomingSessionsCount' => TrainingSession::query()
                    ->whereIn('training_program_id', $programIds)
                    ->whereBetween('scheduled_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                    ->count(),
                'completedSessionsThisWeek' => WorkoutLog::query()
                    ->where('athlete_id', $athlete->id)
                    ->where('completion_status', WorkoutCompletionStatus::Completed->value)
                    ->where('performed_at', '>=', now()->startOfWeek())
                    ->count(),
            ],
            'membership' => [
                'planName' => $membership?->plan?->name ?? 'No active plan',
                'status' => $membership?->status?->value ?? 'none',
                'daysRemaining' => $this->daysRemaining($membership),
                'renewsAt' => $membership?->renews_at?->toDateString(),
                'endsAt' => $membership?->effectiveEndDate()?->toDateString(),
                'autoRenew' => $membership?->auto_renew ?? false,
            ],
            'training' => $currentProgram ? [
                'title' => $currentProgram->title,
                'goal' => $currentProgram->goal,
                'status' => $currentProgram->status->value,
                'coachName' => $currentProgram->coach->name,
                'startDate' => $currentProgram->start_date?->toDateString(),
                'endDate' => $currentProgram->end_date?->toDateString(),
                'nextSession' => ($nextSession = $this->nextSessionFor($currentProgram)) ? [
                    'title' => $nextSession->title,
                    'scheduledDate' => $nextSession->scheduled_date?->toDateString(),
                    'focus' => $nextSession->focus,
                    'instructions' => $nextSession->instructions,
                    'videoUrl' => $nextSession->video_url,
                    'exercises' => $this->normalizeExercises($nextSession->exercises ?? []),
                ] : null,
                'upcomingSessions' => $currentProgram->sessions
                    ->filter(fn (TrainingSession $session) => $session->scheduled_date && $session->scheduled_date->gte(now()->startOfDay()))
                    ->sortBy(fn (TrainingSession $session) => $session->scheduled_date?->timestamp ?? 0)
                    ->take(4)
                    ->map(fn (TrainingSession $session): array => [
                        'title' => $session->title,
                        'scheduledDate' => $session->scheduled_date?->toDateString(),
                        'focus' => $session->focus,
                        'videoUrl' => $session->video_url,
                        'exerciseCount' => count($session->exercises ?? []),
                    ])
                    ->values()
                    ->all(),
                'recentLogs' => $currentProgram->sessions
                    ->filter(fn (TrainingSession $session) => (bool) $session->workoutLog)
                    ->sortByDesc(fn (TrainingSession $session) => $session->workoutLog?->performed_at?->timestamp ?? 0)
                    ->take(4)
                    ->map(fn (TrainingSession $session): array => [
                        'sessionTitle' => $session->title,
                        'completionStatus' => $session->workoutLog->completion_status->value,
                        'performedAt' => $session->workoutLog->performed_at?->toDateString(),
                        'durationMinutes' => $session->workoutLog->duration_minutes,
                        'exertionRating' => $session->workoutLog->exertion_rating,
                    ])
                    ->values()
                    ->all(),
            ] : null,
            'latestSnapshot' => $latestSnapshot ? [
                'metricDate' => $latestSnapshot->metric_date?->toDateString(),
                'readinessScore' => $latestSnapshot->readiness_score,
                'strainScore' => $latestSnapshot->strain_score,
                'sleepHours' => $latestSnapshot->sleepHours(),
                'steps' => $latestSnapshot->steps,
                'restingHeartRate' => $latestSnapshot->resting_heart_rate,
                'heartRateVariability' => $latestSnapshot->heart_rate_variability,
                'sleepNeedHours' => $latestSnapshot->sleepNeedHours(),
                'sleepDebtHours' => $latestSnapshot->sleepDebtHours(),
                'sleepPerformancePercentage' => $latestSnapshot->sleep_performance_percentage,
                'sleepConsistencyPercentage' => $latestSnapshot->sleep_consistency_percentage,
                'respiratoryRate' => $latestSnapshot->respiratory_rate,
                'bloodOxygenPercent' => $latestSnapshot->blood_oxygen_percent,
                'skinTemperatureCelsius' => $latestSnapshot->skin_temperature_celsius,
                'trainingLoad' => $latestSnapshot->training_load,
            ] : null,
            'latestCheckIn' => $latestCheckIn ? [
                'loggedDate' => $latestCheckIn->logged_date?->toDateString(),
                'weightKg' => $latestCheckIn->weight_kg,
                'bodyFatPercentage' => $latestCheckIn->body_fat_percentage,
                'waistCm' => $latestCheckIn->waist_cm,
                'caloriesConsumed' => $latestCheckIn->calories_consumed,
                'proteinGrams' => $latestCheckIn->protein_grams,
                'carbsGrams' => $latestCheckIn->carbs_grams,
                'fatGrams' => $latestCheckIn->fat_grams,
                'waterLiters' => $latestCheckIn->water_liters,
                'mealsLoggedCount' => $latestCheckIn->meals_logged_count,
                'energyScore' => $latestCheckIn->energy_score,
                'sorenessScore' => $latestCheckIn->soreness_score,
                'stressScore' => $latestCheckIn->stress_score,
                'sleepQualityScore' => $latestCheckIn->sleep_quality_score,
                'notes' => $latestCheckIn->notes,
            ] : null,
            'metricReport' => $metricReport,
            'progressReport' => $progressReport,
            'coaches' => $coaches->all(),
            'deviceConnections' => $deviceConnections->all(),
        ];
    }

    private function normalizedMonthlyValue(Membership $membership): float
    {
        $amount = (float) $membership->price;
        $interval = $membership->plan?->billing_interval;

        return match ($interval) {
            BillingInterval::Quarterly => round($amount / 3, 2),
            BillingInterval::Yearly => round($amount / 12, 2),
            default => $amount,
        };
    }

    private function daysRemaining(?Membership $membership): ?int
    {
        return $membership?->daysRemaining();
    }

    private function latestSnapshotFor(User $athlete): ?MetricSnapshot
    {
        return $athlete->deviceConnections
            ->pluck('latestSnapshot')
            ->filter()
            ->sortByDesc(fn (MetricSnapshot $snapshot) => $snapshot->metric_date?->timestamp ?? 0)
            ->first();
    }

    private function currentProgramFor(User $athlete): ?TrainingProgram
    {
        return $athlete->trainingProgramsAsAthlete
            ->sortBy(fn (TrainingProgram $program) => (match ($program->status) {
                TrainingProgramStatus::Active => 0,
                TrainingProgramStatus::Draft => 1,
                TrainingProgramStatus::Completed => 2,
                TrainingProgramStatus::Archived => 3,
            }) * 10_000_000_000 - ($program->start_date?->timestamp ?? 0))
            ->first();
    }

    private function nextSessionFor(TrainingProgram $program): ?TrainingSession
    {
        return $program->sessions
            ->filter(fn (TrainingSession $session) => $session->scheduled_date && $session->scheduled_date->gte(now()->startOfDay()))
            ->sortBy(fn (TrainingSession $session) => $session->scheduled_date?->timestamp ?? 0)
            ->first();
    }

    private function pendingWorkoutLogsFor(TrainingProgram $program): int
    {
        return $program->sessions
            ->filter(fn (TrainingSession $session) => $session->scheduled_date && $session->scheduled_date->lte(now()->startOfDay()) && ! $session->workoutLog)
            ->count();
    }

    private function latestWorkoutLogFor(TrainingProgram $program): ?WorkoutLog
    {
        return $program->sessions
            ->pluck('workoutLog')
            ->filter()
            ->sortByDesc(fn (WorkoutLog $log) => $log->performed_at?->timestamp ?? 0)
            ->first();
    }

    /**
     * @param  list<array<string, mixed>>  $exercises
     * @return list<array<string, mixed>>
     */
    private function normalizeExercises(array $exercises): array
    {
        return collect($exercises)
            ->filter(fn ($exercise) => is_array($exercise))
            ->map(fn (array $exercise): array => [
                'name' => (string) ($exercise['name'] ?? 'Exercise'),
                'prescription' => $exercise['prescription'] ?? null,
                'sets' => isset($exercise['sets']) ? (int) $exercise['sets'] : null,
                'reps' => $exercise['reps'] ?? null,
                'load' => $exercise['load'] ?? null,
                'rest_seconds' => isset($exercise['rest_seconds']) ? (int) $exercise['rest_seconds'] : null,
                'rest_label' => $exercise['rest_label'] ?? null,
                'target' => $exercise['target'] ?? null,
                'note' => $exercise['note'] ?? null,
            ])
            ->values()
            ->all();
    }
}
