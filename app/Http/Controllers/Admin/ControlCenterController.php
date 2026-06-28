<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingInterval;
use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\MembershipStatus;
use App\Enums\PaymentEventStatus;
use App\Enums\RoleName;
use App\Enums\SignupMethod;
use App\Enums\TrainingProgramStatus;
use App\Http\Controllers\Controller;
use App\Models\DeviceConnection;
use App\Models\Membership;
use App\Models\PaymentEvent;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Services\DeviceConnectionHealthReviewService;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class ControlCenterController extends Controller
{
    public function __invoke(DeviceConnectionHealthReviewService $healthReview): Response
    {
        /** @var User $viewer */
        $viewer = request()->user()->loadMissing('roles');

        abort_unless($viewer->hasPermission('admin.control_center.view'), 403);

        $currentMemberships = Membership::query()
            ->whereIn('status', [
                MembershipStatus::Trialing->value,
                MembershipStatus::Active->value,
                MembershipStatus::Grace->value,
                MembershipStatus::PastDue->value,
            ])
            ->with(['user.roles', 'plan'])
            ->get();

        $paymentEventsThisMonth = PaymentEvent::query()
            ->with(['user.roles', 'membership.plan'])
            ->where('event_at', '>=', now()->startOfMonth())
            ->get();

        $attentionConnections = DeviceConnection::query()
            ->whereIn('status', [DeviceConnectionStatus::Attention->value, DeviceConnectionStatus::Disconnected->value])
            ->with(['user.roles', 'latestSnapshot'])
            ->orderByRaw(
                "case status
                    when 'attention' then 0
                    when 'disconnected' then 1
                    else 2
                end"
            )
            ->orderBy('last_synced_at')
            ->get();

        $athletes = User::query()
            ->role(RoleName::Athlete)
            ->with([
                'roles',
                'memberships.plan',
                'deviceConnections',
                'athleteAssignments' => fn ($query) => $query->where('status', CoachAthleteStatus::Active->value),
            ])
            ->get();

        $coachLoad = User::query()
            ->role(RoleName::Coach)
            ->withCount([
                'coachAssignments as roster_count' => fn (Builder $query) => $query->where('status', CoachAthleteStatus::Active->value),
                'trainingProgramsAsCoach as active_programs_count' => fn (Builder $query) => $query->where('status', TrainingProgramStatus::Active->value),
            ])
            ->with([
                'coachAssignments' => fn ($query) => $query
                    ->where('status', CoachAthleteStatus::Active->value)
                    ->with(['athlete.memberships.plan', 'athlete.deviceConnections']),
            ])
            ->get()
            ->map(function (User $coach): array {
                $assignments = $coach->coachAssignments;
                $pendingLogs = TrainingSession::query()
                    ->whereHas('program', fn (Builder $query) => $query->where('coach_id', $coach->id))
                    ->where('scheduled_date', '<=', now()->toDateString())
                    ->whereDoesntHave('workoutLog')
                    ->count();

                return [
                    'name' => $coach->name,
                    'email' => $coach->email,
                    'rosterCount' => $coach->roster_count,
                    'activePrograms' => $coach->active_programs_count,
                    'pendingLogs' => $pendingLogs,
                    'athletesWithoutDevice' => $assignments
                        ->filter(fn ($assignment) => $assignment->athlete->deviceConnections->where('status', DeviceConnectionStatus::Connected)->isEmpty())
                        ->count(),
                    'membershipsAtRisk' => $assignments
                        ->filter(function ($assignment): bool {
                            $membership = $assignment->athlete->currentMembership();

                            return ! $membership || $membership->status->needsAttention();
                        })
                        ->count(),
                ];
            })
            ->sortByDesc(fn (array $entry) => $entry['pendingLogs'] + $entry['athletesWithoutDevice'] + $entry['membershipsAtRisk'])
            ->values()
            ->take(8)
            ->all();

        $athleteCoverageGaps = $athletes
            ->filter(function (User $athlete): bool {
                $hasActiveCoach = $athlete->athleteAssignments->isNotEmpty();
                $connectedDevices = $athlete->deviceConnections->where('status', DeviceConnectionStatus::Connected)->count();
                $membership = $athlete->currentMembership();

                return ! $hasActiveCoach
                    || $connectedDevices === 0
                    || ! $membership
                    || $membership->status->needsAttention();
            })
            ->map(function (User $athlete): array {
                $membership = $athlete->currentMembership();

                return [
                    'name' => $athlete->name,
                    'email' => $athlete->email,
                    'membershipStatus' => $membership?->status?->value ?? 'none',
                    'membershipPlan' => $membership?->plan?->name ?? 'No active plan',
                    'coachCount' => $athlete->athleteAssignments->count(),
                    'connectedDevices' => $athlete->deviceConnections->where('status', DeviceConnectionStatus::Connected)->count(),
                    'daysRemaining' => $membership?->daysRemaining(),
                ];
            })
            ->sortBy([
                fn (array $entry) => $entry['coachCount'] > 0 ? 1 : 0,
                fn (array $entry) => $entry['connectedDevices'] > 0 ? 1 : 0,
                fn (array $entry) => $entry['daysRemaining'] ?? 9999,
            ])
            ->values()
            ->take(8)
            ->all();

        $projectedMonthlyRevenue = round($currentMemberships->sum(fn (Membership $membership) => $this->normalizedMonthlyValue($membership)), 2);

        return Inertia::render('admin/control-center', [
            'summary' => [
                'totalUsers' => User::query()->count(),
                'admins' => User::query()->role(RoleName::Admin)->count(),
                'coaches' => User::query()->role(RoleName::Coach)->count(),
                'athletes' => User::query()->role(RoleName::Athlete)->count(),
                'activeMemberships' => $currentMemberships->count(),
                'renewalsThisWeek' => $currentMemberships
                    ->filter(fn (Membership $membership) => $this->daysRemaining($membership) !== null && $this->daysRemaining($membership) <= 7)
                    ->count(),
                'projectedMonthlyRevenue' => $projectedMonthlyRevenue,
                'paymentVolumeThisMonth' => round((float) $paymentEventsThisMonth
                    ->where('status', PaymentEventStatus::Succeeded)
                    ->sum('amount'), 2),
                'failedPaymentsThisMonth' => $paymentEventsThisMonth->where('status', PaymentEventStatus::Failed)->count(),
                'connectedDevices' => DeviceConnection::query()->where('status', DeviceConnectionStatus::Connected->value)->count(),
                'attentionConnections' => $attentionConnections->count(),
                'whoopConnections' => DeviceConnection::query()->where('provider', DeviceProvider::Whoop->value)->count(),
                'activePrograms' => TrainingProgram::query()->where('status', TrainingProgramStatus::Active->value)->count(),
                'scheduledSessionsThisWeek' => TrainingSession::query()
                    ->whereBetween('scheduled_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                    ->count(),
                'loggedSessionsThisWeek' => WorkoutLog::query()->where('performed_at', '>=', now()->startOfWeek())->count(),
                'athletesWithCoverageGaps' => count($athleteCoverageGaps),
            ],
            'queues' => [
                'membershipQueue' => $currentMemberships
                    ->filter(fn (Membership $membership) => $this->daysRemaining($membership) !== null && $this->daysRemaining($membership) <= 14)
                    ->sortBy(fn (Membership $membership) => $this->daysRemaining($membership) ?? 9999)
                    ->take(8)
                    ->map(fn (Membership $membership): array => [
                        'userName' => $membership->user->name,
                        'userRole' => $membership->user->primaryRoleName(),
                        'planName' => $membership->plan?->name ?? 'Custom plan',
                        'status' => $membership->status->value,
                        'daysRemaining' => $this->daysRemaining($membership),
                        'autoRenew' => $membership->auto_renew,
                        'endsAt' => $membership->effectiveEndDate()?->toDateString(),
                    ])
                    ->values()
                    ->all(),
                'paymentQueue' => $paymentEventsThisMonth
                    ->filter(fn (PaymentEvent $event) => in_array($event->status, [PaymentEventStatus::Pending, PaymentEventStatus::Failed], true))
                    ->sortByDesc(fn (PaymentEvent $event) => $event->event_at?->timestamp ?? 0)
                    ->take(8)
                    ->map(fn (PaymentEvent $event): array => [
                        'userName' => $event->user?->name ?? 'Unknown user',
                        'userRole' => $event->user?->primaryRoleName(),
                        'planName' => $event->membership?->plan?->name ?? 'Custom plan',
                        'status' => $event->status->value,
                        'amount' => $event->amount !== null ? (float) $event->amount : null,
                        'currency' => $event->currency,
                        'provider' => $event->provider,
                        'reference' => $event->reference,
                        'eventAt' => $event->event_at?->toDateTimeString(),
                    ])
                    ->values()
                    ->all(),
                'deviceQueue' => $attentionConnections
                    ->take(8)
                    ->map(function (DeviceConnection $connection) use ($healthReview): array {
                        return [
                            'userName' => $connection->user->name,
                            'userRole' => $connection->user->primaryRoleName(),
                            'provider' => $connection->provider->label(),
                            'status' => $connection->status->value,
                            'lastSyncedAt' => $connection->last_synced_at?->toDateTimeString(),
                            'readinessScore' => $connection->latestSnapshot?->readiness_score,
                            ...$healthReview->review($connection),
                        ];
                    })
                    ->values()
                    ->all(),
                'athleteCoverageGaps' => $athleteCoverageGaps,
                'coachLoad' => $coachLoad,
            ],
            'signupMix' => collect(SignupMethod::cases())
                ->map(fn (SignupMethod $method): array => [
                    'method' => $method->value,
                    'label' => $method->label(),
                    'enabled' => (bool) config("throughline.auth.signup_methods.{$method->value}.enabled", false),
                    'count' => User::query()->where('registration_channel', $method->value)->count(),
                ])
                ->values()
                ->all(),
            'opsPlaybook' => [
                [
                    'title' => 'Membership lifecycle audit',
                    'command' => 'php artisan throughline:memberships:audit',
                    'cadence' => 'Daily cron',
                    'reason' => 'Keeps grace, expired, and cancelled memberships from drifting out of sync.',
                ],
                [
                    'title' => 'WHOOP sync sweep',
                    'command' => 'php artisan throughline:whoop:sync',
                    'cadence' => 'Hourly cron',
                    'reason' => 'Refreshes OAuth-backed recovery data before coaches start guessing.',
                ],
                [
                    'title' => 'Ingest endpoint monitoring',
                    'command' => 'POST /api/device-connections/{public_id}/ingest',
                    'cadence' => 'Provider-driven',
                    'reason' => 'Catches failed device deliveries while the issue is still actionable.',
                ],
            ],
        ]);
    }

    private function normalizedMonthlyValue(Membership $membership): float
    {
        $price = (float) $membership->price;
        $interval = $membership->plan?->billing_interval;

        return match ($interval) {
            BillingInterval::Quarterly => $price / 3,
            BillingInterval::Yearly => $price / 12,
            default => $price,
        };
    }

    private function daysRemaining(Membership $membership): ?int
    {
        return $membership->daysRemaining();
    }
}
