<?php

namespace App\Http\Controllers\Athlete;

use App\Enums\CoachAthleteStatus;
use App\Http\Controllers\Controller;
use App\Models\AthleteCheckIn;
use App\Models\AthleteFile;
use App\Models\CoachAthleteAssignment;
use App\Models\DeviceConnection;
use App\Models\Membership;
use App\Models\PaymentEvent;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Support\AthleteAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileShowController extends Controller
{
    public function __invoke(Request $request, User $user): Response
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless(AthleteAccess::canViewAthlete($viewer, $user), 404);

        $user->load([
            'roles',
            'memberships.plan',
            'memberships.paymentEvents',
            'paymentEvents.membership.plan',
            'deviceConnections.latestSnapshot',
            'latestAthleteCheckIn',
            'athleteCheckIns' => fn ($query) => $query->latest('logged_date')->limit(20),
            'athleteAssignments.coach.roles',
            'athleteAssignments.messages.sender',
            'trainingProgramsAsAthlete.coach.roles',
            'trainingProgramsAsAthlete.sessions.workoutLog.setLogs',
            'athleteFiles.uploadedBy',
        ]);

        $programs = $user->trainingProgramsAsAthlete
            ->when(! ($viewer->hasPermission('admin.users.view') || $viewer->hasPermission('admin.invitations.view')), fn ($collection) => $collection->where('coach_id', $viewer->id));

        $sessions = $programs
            ->flatMap(fn (TrainingProgram $program) => $program->sessions->map(fn (TrainingSession $session) => [$program, $session]))
            ->sortByDesc(fn (array $pair) => $pair[1]->scheduled_date?->timestamp ?? 0)
            ->values();

        $logs = $sessions
            ->map(fn (array $pair) => $pair[1]->workoutLog)
            ->filter()
            ->values();

        $currentMembership = $user->currentMembership();
        $activeCoachAssignment = $user->athleteAssignments
            ->filter(fn (CoachAthleteAssignment $assignment) => $assignment->status === CoachAthleteStatus::Active)
            ->sortByDesc(fn (CoachAthleteAssignment $assignment) => $assignment->started_at?->timestamp ?? 0)
            ->first();

        return Inertia::render('athletes/show', [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'primaryGoal' => $user->primary_goal,
                'preferredContactMethod' => $user->preferred_contact_method,
                'registrationChannel' => $user->registration_channel,
                'createdAt' => $user->created_at?->toDateString(),
                'latestCheckInAt' => $user->latestAthleteCheckIn?->logged_date?->toDateString(),
            ],
            'permissions' => [
                'canManageFiles' => AthleteAccess::canManageAthleteFiles($viewer, $user),
                'canViewAdminProfile' => $viewer->hasPermission('admin.users.view'),
            ],
            'summary' => [
                'currentPlan' => $currentMembership?->plan?->name ?? 'No current plan',
                'membershipStatus' => $currentMembership?->status?->value ?? 'none',
                'daysRemaining' => $currentMembership?->daysRemaining(),
                'activeCoach' => $activeCoachAssignment?->coach?->name ?? 'No active coach',
                'programs' => $programs->count(),
                'sessions' => $sessions->count(),
                'completedSessions' => $logs->where('completion_status.value', 'completed')->count(),
                'partialSessions' => $logs->where('completion_status.value', 'partial')->count(),
                'missedSessions' => $logs->where('completion_status.value', 'missed')->count(),
                'files' => $user->athleteFiles->where('status', AthleteFile::STATUS_ACTIVE)->count(),
            ],
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
                    'price' => (float) $membership->price,
                    'currency' => $membership->currency,
                ])
                ->values()
                ->all(),
            'payments' => $user->paymentEvents
                ->sortByDesc(fn (PaymentEvent $event) => $event->event_at?->timestamp ?? 0)
                ->take(10)
                ->map(fn (PaymentEvent $event): array => [
                    'id' => $event->id,
                    'type' => $event->event_type->value,
                    'status' => $event->status->value,
                    'amount' => $event->amount !== null ? (float) $event->amount : null,
                    'currency' => $event->currency,
                    'eventAt' => $event->event_at?->toDateTimeString(),
                    'reference' => $event->reference,
                ])
                ->values()
                ->all(),
            'devices' => $user->deviceConnections
                ->map(fn (DeviceConnection $connection): array => [
                    'id' => $connection->id,
                    'provider' => $connection->provider->label(),
                    'status' => $connection->status->value,
                    'lastSyncedAt' => $connection->last_synced_at?->toDateTimeString(),
                    'latestMetricDate' => $connection->latestSnapshot?->metric_date?->toDateString(),
                    'readiness' => $connection->latestSnapshot?->readiness_score,
                    'sleepHours' => $connection->latestSnapshot?->sleepHours(),
                    'strain' => $connection->latestSnapshot?->strain_score,
                ])
                ->values()
                ->all(),
            'progress' => $user->athleteCheckIns
                ->map(fn (AthleteCheckIn $checkIn): array => [
                    'id' => $checkIn->id,
                    'loggedDate' => $checkIn->logged_date?->toDateString(),
                    'weightKg' => $checkIn->weight_kg,
                    'caloriesConsumed' => $checkIn->calories_consumed,
                    'proteinGrams' => $checkIn->protein_grams,
                    'carbsGrams' => $checkIn->carbs_grams,
                    'fatGrams' => $checkIn->fat_grams,
                    'waterLiters' => $checkIn->water_liters,
                    'sleepHours' => $checkIn->sleep_hours,
                    'energyScore' => $checkIn->energy_score,
                    'sorenessScore' => $checkIn->soreness_score,
                    'notes' => $checkIn->notes,
                ])
                ->values()
                ->all(),
            'sessions' => $sessions
                ->map(fn (array $pair): array => $this->sessionRow($pair[0], $pair[1]))
                ->all(),
            'files' => $user->athleteFiles
                ->sortByDesc(fn (AthleteFile $file) => $file->created_at?->timestamp ?? 0)
                ->map(fn (AthleteFile $file): array => $this->fileRow($file))
                ->values()
                ->all(),
            'messages' => $user->athleteAssignments
                ->flatMap(fn (CoachAthleteAssignment $assignment) => $assignment->messages->map(fn ($message) => [
                    'id' => $message->id,
                    'assignmentId' => $assignment->id,
                    'coachName' => $assignment->coach->name,
                    'senderName' => $message->sender?->name,
                    'body' => $message->body,
                    'readAt' => $message->read_at?->toDateTimeString(),
                    'sentAt' => $message->created_at?->toDateTimeString(),
                ]))
                ->sortByDesc('sentAt')
                ->take(20)
                ->values()
                ->all(),
            'fileOptions' => $this->fileOptions(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionRow(TrainingProgram $program, TrainingSession $session): array
    {
        $log = $session->workoutLog;

        return [
            'id' => $session->id,
            'programTitle' => $program->title,
            'coachName' => $program->coach->name,
            'title' => $session->title,
            'scheduledDate' => $session->scheduled_date?->toDateString(),
            'focus' => $session->focus,
            'completionStatus' => $log?->completion_status?->value ?? 'not_logged',
            'performedAt' => $log?->performed_at?->toDateTimeString(),
            'durationMinutes' => $log?->duration_minutes,
            'exertionRating' => $log?->exertion_rating,
            'setCount' => $log?->setLogs?->count() ?? 0,
            'completedSets' => $log?->setLogs?->whereNotNull('completed_at')->count() ?? 0,
            'notes' => $log?->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fileRow(AthleteFile $file): array
    {
        return [
            'id' => $file->id,
            'displayName' => $file->display_name,
            'originalFilename' => $file->original_filename,
            'category' => $file->category,
            'visibility' => $file->visibility,
            'status' => $file->status,
            'mimeType' => $file->mime_type,
            'sizeBytes' => $file->size_bytes,
            'notes' => $file->notes,
            'uploadedBy' => $file->uploadedBy?->name,
            'createdAt' => $file->created_at?->toDateTimeString(),
            'archivedAt' => $file->archived_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array{categories:list<array{value:string,label:string}>,visibilities:list<array{value:string,label:string}>}
     */
    private function fileOptions(): array
    {
        return [
            'categories' => [
                ['value' => 'medical', 'label' => 'Medical'],
                ['value' => 'progress', 'label' => 'Progress'],
                ['value' => 'training', 'label' => 'Training'],
                ['value' => 'media', 'label' => 'Media'],
                ['value' => 'admin', 'label' => 'Admin'],
            ],
            'visibilities' => [
                ['value' => AthleteFile::VISIBILITY_COACH_ADMIN, 'label' => 'Coach and admin'],
                ['value' => AthleteFile::VISIBILITY_ATHLETE, 'label' => 'Athlete visible'],
                ['value' => AthleteFile::VISIBILITY_ADMIN, 'label' => 'Admin only'],
            ],
        ];
    }
}
