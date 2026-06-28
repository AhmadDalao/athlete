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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AthleteAppController extends Controller
{
    public function __invoke(Request $request, AthleteWorkoutExecutionService $workouts): Response
    {
        /** @var User $athlete */
        $athlete = $request->user()->loadMissing(['roles', 'memberships.plan', 'deviceConnections', 'latestAthleteCheckIn']);

        abort_unless($athlete->hasRole(RoleName::Athlete), 403);

        $assignment = $athlete->athleteAssignments()
            ->where('status', CoachAthleteStatus::Active->value)
            ->with('coach')
            ->latest('started_at')
            ->first();

        $program = TrainingProgram::query()
            ->where('athlete_id', $athlete->id)
            ->whereIn('status', [TrainingProgramStatus::Active->value, TrainingProgramStatus::Draft->value])
            ->with(['coach', 'sessions.workoutLog.setLogs'])
            ->orderByRaw("case status when 'active' then 0 when 'draft' then 1 else 2 end")
            ->orderByDesc('start_date')
            ->first();

        $session = $program ? $this->nextSession($program) : null;
        $latestSnapshot = MetricSnapshot::query()
            ->where('user_id', $athlete->id)
            ->latest('metric_date')
            ->first();

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
        ]);
    }

    private function nextSession(TrainingProgram $program): ?TrainingSession
    {
        $today = now()->toDateString();

        return $program->sessions
            ->filter(fn (TrainingSession $session): bool => $session->scheduled_date?->toDateString() === $today)
            ->sortBy('sort_order')
            ->first()
            ?? $program->sessions
                ->filter(fn (TrainingSession $session): bool => $session->scheduled_date?->toDateString() >= $today)
                ->sortBy('scheduled_date')
                ->first()
            ?? $program->sessions
                ->sortByDesc('scheduled_date')
                ->first();
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
