<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\CoachAthleteAssignment;
use App\Models\DeviceConnection;
use App\Models\Membership;
use App\Models\PaymentEvent;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Support\PermissionCatalog;
use Inertia\Inertia;
use Inertia\Response;

class UserShowController extends Controller
{
    public function __invoke(User $user): Response
    {
        /** @var User $viewer */
        $viewer = request()->user()->loadMissing('roles');

        abort_unless($viewer->hasPermission('admin.users.view'), 403);

        $user->load([
            'roles',
            'permissions',
            'memberships.plan',
            'memberships.paymentEvents',
            'paymentEvents.membership.plan',
            'deviceConnections.latestSnapshot',
            'latestAthleteCheckIn',
            'athleteAssignments.coach.roles',
            'coachAssignments.athlete.roles',
            'trainingProgramsAsAthlete.coach.roles',
            'trainingProgramsAsAthlete.sessions.workoutLog',
            'trainingProgramsAsCoach.athlete.roles',
            'trainingProgramsAsCoach.sessions.workoutLog',
        ]);

        $currentMembership = $user->currentMembership();

        return Inertia::render('admin/users/show', [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'primaryGoal' => $user->primary_goal,
                'preferredContactMethod' => $user->preferred_contact_method,
                'registrationChannel' => $user->registration_channel,
                'position' => $user->position,
                'emailVerifiedAt' => $user->email_verified_at?->toDateTimeString(),
                'phoneVerifiedAt' => $user->phone_verified_at?->toDateTimeString(),
                'createdAt' => $user->created_at?->toDateString(),
                'updatedAt' => $user->updated_at?->toDateTimeString(),
                'roles' => $user->roleNames(),
                'primaryRole' => $user->primaryRoleName(),
                'permissions' => $user->permissionKeys(),
                'permissionCount' => count($user->permissionKeys()),
                'stripeCustomerId' => $user->stripe_customer_id,
            ],
            'permissionGroups' => collect(PermissionCatalog::groups())
                ->map(fn (array $group, string $key): array => [
                    'key' => $key,
                    'label' => $group['label'],
                    'permissions' => collect($group['permissions'])
                        ->map(fn (string $description, string $permissionKey): array => [
                            'key' => $permissionKey,
                            'description' => $description,
                            'enabled' => in_array($permissionKey, $user->permissionKeys(), true),
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'summary' => [
                'currentPlan' => $currentMembership?->plan?->name ?? 'No current plan',
                'currentStatus' => $currentMembership?->status?->value ?? 'none',
                'subscribedAt' => $currentMembership?->starts_at?->toDateString(),
                'daysRemaining' => $currentMembership?->daysRemaining(),
                'memberships' => $user->memberships->count(),
                'paymentEvents' => $user->paymentEvents->count(),
                'deviceConnections' => $user->deviceConnections->count(),
                'activeCoachAssignments' => $this->activeAssignmentCount($user->athleteAssignments),
                'activeAthleteAssignments' => $this->activeAssignmentCount($user->coachAssignments),
                'trainingProgramsAsAthlete' => $user->trainingProgramsAsAthlete->count(),
                'trainingProgramsAsCoach' => $user->trainingProgramsAsCoach->count(),
                'latestCheckInAt' => $user->latestAthleteCheckIn?->logged_date?->toDateString(),
            ],
            'memberships' => $user->memberships
                ->sortByDesc(fn (Membership $membership) => $membership->starts_at?->timestamp ?? 0)
                ->map(fn (Membership $membership): array => [
                    'id' => $membership->id,
                    'planName' => $membership->plan?->name ?? 'Custom plan',
                    'status' => $membership->status->value,
                    'startsAt' => $membership->starts_at?->toDateString(),
                    'renewsAt' => $membership->renews_at?->toDateString(),
                    'endsAt' => $membership->effectiveEndDate()?->toDateString(),
                    'daysRemaining' => $membership->daysRemaining(),
                    'autoRenew' => $membership->auto_renew,
                    'price' => (float) $membership->price,
                    'currency' => $membership->currency,
                    'billingProvider' => $membership->billing_provider,
                    'providerSubscriptionId' => $membership->provider_subscription_id,
                    'createdAt' => $membership->created_at?->toDateString(),
                ])
                ->values()
                ->all(),
            'payments' => $user->paymentEvents
                ->sortByDesc(fn (PaymentEvent $event) => $event->event_at?->timestamp ?? 0)
                ->take(12)
                ->map(fn (PaymentEvent $event): array => [
                    'id' => $event->id,
                    'eventType' => $event->event_type->value,
                    'status' => $event->status->value,
                    'provider' => $event->provider,
                    'reference' => $event->reference,
                    'amount' => $event->amount !== null ? (float) $event->amount : null,
                    'currency' => $event->currency,
                    'eventAt' => $event->event_at?->toDateTimeString(),
                    'planName' => $event->membership?->plan?->name ?? 'Custom plan',
                ])
                ->values()
                ->all(),
            'devices' => $user->deviceConnections
                ->map(fn (DeviceConnection $connection): array => [
                    'id' => $connection->id,
                    'provider' => $connection->provider->label(),
                    'status' => $connection->status->value,
                    'authType' => $connection->auth_type,
                    'lastSyncedAt' => $connection->last_synced_at?->toDateTimeString(),
                    'lastErrorAt' => $connection->last_error_at?->toDateTimeString(),
                    'lastErrorMessage' => $connection->last_error_message,
                    'latestMetricDate' => $connection->latestSnapshot?->metric_date?->toDateString(),
                    'latestReadiness' => $connection->latestSnapshot?->readiness_score,
                ])
                ->values()
                ->all(),
            'coachAssignments' => $user->athleteAssignments
                ->map(fn (CoachAthleteAssignment $assignment): array => [
                    'id' => $assignment->id,
                    'coachName' => $assignment->coach->name,
                    'coachEmail' => $assignment->coach->email,
                    'status' => $assignment->status->value,
                    'goal' => $assignment->goal,
                    'startedAt' => $assignment->started_at?->toDateString(),
                    'endedAt' => $assignment->ended_at?->toDateString(),
                ])
                ->values()
                ->all(),
            'athleteAssignments' => $user->coachAssignments
                ->map(fn (CoachAthleteAssignment $assignment): array => [
                    'id' => $assignment->id,
                    'athleteId' => $assignment->athlete->id,
                    'athleteName' => $assignment->athlete->name,
                    'athleteEmail' => $assignment->athlete->email,
                    'status' => $assignment->status->value,
                    'goal' => $assignment->goal,
                    'startedAt' => $assignment->started_at?->toDateString(),
                    'endedAt' => $assignment->ended_at?->toDateString(),
                ])
                ->values()
                ->all(),
            'programs' => collect()
                ->merge($user->trainingProgramsAsAthlete->map(fn (TrainingProgram $program): array => [
                    'id' => $program->id,
                    'title' => $program->title,
                    'role' => 'athlete',
                    'counterparty' => $program->coach->name,
                    'status' => $program->status->value,
                    'startDate' => $program->start_date?->toDateString(),
                    'endDate' => $program->end_date?->toDateString(),
                    'sessionCount' => $program->sessions->count(),
                ]))
                ->merge($user->trainingProgramsAsCoach->map(fn (TrainingProgram $program): array => [
                    'id' => $program->id,
                    'title' => $program->title,
                    'role' => 'coach',
                    'counterparty' => $program->athlete->name,
                    'status' => $program->status->value,
                    'startDate' => $program->start_date?->toDateString(),
                    'endDate' => $program->end_date?->toDateString(),
                    'sessionCount' => $program->sessions->count(),
                ]))
                ->values()
                ->all(),
        ]);
    }

    private function activeAssignmentCount($assignments): int
    {
        return $assignments
            ->filter(fn (CoachAthleteAssignment $assignment) => $assignment->status === CoachAthleteStatus::Active)
            ->count();
    }
}
