<?php

namespace App\Support;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Enums\WorkoutCompletionStatus;
use App\Models\AthleteCheckIn;
use App\Models\AthleteFile;
use App\Models\CoachAthleteAssignment;
use App\Models\CoachAthleteMessage;
use App\Models\DeviceConnection;
use App\Models\Membership;
use App\Models\PaymentEvent;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Models\WorkoutSetLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AthleteProfileTables
{
    public const EXPORT_SECTIONS = [
        'assignments',
        'memberships',
        'payments',
        'devices',
        'sessions',
        'set-logs',
        'progress',
        'files',
        'messages',
    ];

    /**
     * @return array<string, array<string, string|null>>
     */
    public function filters(Request $request): array
    {
        return [
            'assignments' => [
                'q' => $this->clean($request, 'assignments_q'),
                'status' => $this->clean($request, 'assignments_status'),
                'per_page' => TablePageSize::queryValue($request, 'assignments_per_page'),
            ],
            'memberships' => [
                'q' => $this->clean($request, 'memberships_q'),
                'status' => $this->clean($request, 'memberships_status'),
                'per_page' => TablePageSize::queryValue($request, 'memberships_per_page'),
            ],
            'payments' => [
                'q' => $this->clean($request, 'payments_q'),
                'status' => $this->clean($request, 'payments_status'),
                'type' => $this->clean($request, 'payments_type'),
                'from' => $this->clean($request, 'payments_from'),
                'to' => $this->clean($request, 'payments_to'),
                'per_page' => TablePageSize::queryValue($request, 'payments_per_page'),
            ],
            'devices' => [
                'q' => $this->clean($request, 'devices_q'),
                'status' => $this->clean($request, 'devices_status'),
                'provider' => $this->clean($request, 'devices_provider'),
                'per_page' => TablePageSize::queryValue($request, 'devices_per_page'),
            ],
            'sessions' => [
                'q' => $this->clean($request, 'sessions_q'),
                'status' => $this->clean($request, 'sessions_status'),
                'from' => $this->clean($request, 'sessions_from'),
                'to' => $this->clean($request, 'sessions_to'),
                'per_page' => TablePageSize::queryValue($request, 'sessions_per_page'),
            ],
            'setLogs' => [
                'q' => $this->clean($request, 'sets_q'),
                'completed' => $this->clean($request, 'sets_completed'),
                'from' => $this->clean($request, 'sets_from'),
                'to' => $this->clean($request, 'sets_to'),
                'per_page' => TablePageSize::queryValue($request, 'sets_per_page'),
            ],
            'progress' => [
                'q' => $this->clean($request, 'progress_q'),
                'from' => $this->clean($request, 'progress_from'),
                'to' => $this->clean($request, 'progress_to'),
                'per_page' => TablePageSize::queryValue($request, 'progress_per_page'),
            ],
            'files' => [
                'q' => $this->clean($request, 'files_q'),
                'status' => $this->clean($request, 'files_status'),
                'category' => $this->clean($request, 'files_category'),
                'visibility' => $this->clean($request, 'files_visibility'),
                'per_page' => TablePageSize::queryValue($request, 'files_per_page'),
            ],
            'messages' => [
                'q' => $this->clean($request, 'messages_q'),
                'read' => $this->clean($request, 'messages_read'),
                'per_page' => TablePageSize::queryValue($request, 'messages_per_page'),
            ],
        ];
    }

    /**
     * @param  array<string, array<string, string|null>>  $filters
     * @return array<string, mixed>
     */
    public function tables(User $viewer, User $athlete, array $filters): array
    {
        $assignmentsQuery = $this->assignmentsQuery($viewer, $athlete, $filters['assignments'])
            ->latest('started_at');
        $membershipsQuery = $this->membershipsQuery($athlete, $filters['memberships'])
            ->with('plan')
            ->latest('starts_at');
        $paymentsQuery = $this->paymentsQuery($athlete, $filters['payments'])
            ->latest('event_at');
        $devicesQuery = $this->devicesQuery($athlete, $filters['devices'])
            ->with('latestSnapshot')
            ->latest();
        $sessionsQuery = $this->sessionsQuery($viewer, $athlete, $filters['sessions'])
            ->with(['program.coach', 'workoutLog.setLogs'])
            ->orderByDesc('scheduled_date')
            ->orderByDesc('id');
        $setLogsQuery = $this->setLogsQuery($viewer, $athlete, $filters['setLogs'])
            ->with(['session.program.coach'])
            ->orderByDesc('id');
        $progressQuery = $this->progressQuery($athlete, $filters['progress'])
            ->latest('logged_date');
        $filesQuery = $this->filesQuery($viewer, $athlete, $filters['files'])
            ->with('uploadedBy')
            ->latest();
        $messagesQuery = $this->messagesQuery($viewer, $athlete, $filters['messages'])
            ->with(['assignment.coach', 'sender'])
            ->latest();

        return [
            'assignments' => $assignmentsQuery
                ->paginate(TablePageSize::resolveValue($filters['assignments']['per_page'], $assignmentsQuery), ['*'], 'assignments_page')
                ->withQueryString()
                ->through(fn (CoachAthleteAssignment $assignment): array => $this->assignmentRow($assignment)),
            'memberships' => $membershipsQuery
                ->paginate(TablePageSize::resolveValue($filters['memberships']['per_page'], $membershipsQuery), ['*'], 'memberships_page')
                ->withQueryString()
                ->through(fn (Membership $membership): array => $this->membershipRow($membership)),
            'payments' => $paymentsQuery
                ->paginate(TablePageSize::resolveValue($filters['payments']['per_page'], $paymentsQuery), ['*'], 'payments_page')
                ->withQueryString()
                ->through(fn (PaymentEvent $event): array => $this->paymentRow($event)),
            'devices' => $devicesQuery
                ->paginate(TablePageSize::resolveValue($filters['devices']['per_page'], $devicesQuery), ['*'], 'devices_page')
                ->withQueryString()
                ->through(fn (DeviceConnection $connection): array => $this->deviceRow($connection)),
            'sessions' => $sessionsQuery
                ->paginate(TablePageSize::resolveValue($filters['sessions']['per_page'], $sessionsQuery), ['*'], 'sessions_page')
                ->withQueryString()
                ->through(fn (TrainingSession $session): array => $this->sessionRow($session)),
            'setLogs' => $setLogsQuery
                ->paginate(TablePageSize::resolveValue($filters['setLogs']['per_page'], $setLogsQuery), ['*'], 'sets_page')
                ->withQueryString()
                ->through(fn (WorkoutSetLog $setLog): array => $this->setLogRow($setLog)),
            'progress' => $progressQuery
                ->paginate(TablePageSize::resolveValue($filters['progress']['per_page'], $progressQuery), ['*'], 'progress_page')
                ->withQueryString()
                ->through(fn (AthleteCheckIn $checkIn): array => $this->progressRow($checkIn)),
            'files' => $filesQuery
                ->paginate(TablePageSize::resolveValue($filters['files']['per_page'], $filesQuery), ['*'], 'files_page')
                ->withQueryString()
                ->through(fn (AthleteFile $file): array => $this->fileRow($file)),
            'messages' => $messagesQuery
                ->paginate(TablePageSize::resolveValue($filters['messages']['per_page'], $messagesQuery), ['*'], 'messages_page')
                ->withQueryString()
                ->through(fn (CoachAthleteMessage $message): array => $this->messageRow($message)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(User $viewer, User $athlete): array
    {
        $athlete->loadMissing(['memberships.plan', 'latestAthleteCheckIn']);

        $currentMembership = $athlete->currentMembership();
        $activeCoachAssignment = $athlete->athleteAssignments()
            ->where('status', CoachAthleteStatus::Active->value)
            ->with('coach')
            ->latest('started_at')
            ->first();

        $sessionQuery = $this->baseSessionsQuery($viewer, $athlete);
        $workoutLogQuery = $this->baseWorkoutLogQuery($viewer, $athlete);

        return [
            'currentPlan' => $currentMembership?->plan?->name ?? 'No current plan',
            'membershipStatus' => $currentMembership?->status?->value ?? 'none',
            'daysRemaining' => $currentMembership?->daysRemaining(),
            'activeCoach' => $activeCoachAssignment?->coach?->name ?? 'No active coach',
            'programs' => $this->baseProgramQuery($viewer, $athlete)->count(),
            'sessions' => (clone $sessionQuery)->count(),
            'completedSessions' => (clone $workoutLogQuery)->where('completion_status', WorkoutCompletionStatus::Completed->value)->count(),
            'partialSessions' => (clone $workoutLogQuery)->where('completion_status', WorkoutCompletionStatus::Partial->value)->count(),
            'missedSessions' => (clone $workoutLogQuery)->where('completion_status', WorkoutCompletionStatus::Missed->value)->count(),
            'files' => $this->filesQuery($viewer, $athlete, ['q' => null, 'status' => AthleteFile::STATUS_ACTIVE, 'category' => null, 'visibility' => null])->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'fileOptions' => [
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
                'statuses' => [
                    ['value' => AthleteFile::STATUS_ACTIVE, 'label' => 'Active'],
                    ['value' => AthleteFile::STATUS_ARCHIVED, 'label' => 'Archived'],
                ],
            ],
            'completionStatuses' => collect(WorkoutCompletionStatus::cases())
                ->map(fn (WorkoutCompletionStatus $status): array => ['value' => $status->value, 'label' => $status->label()])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, array<string, string|null>>  $filters
     * @return array{filename:string,headers:list<string>,rows:list<list<mixed>>}
     */
    public function export(User $viewer, User $athlete, string $section, array $filters): array
    {
        return match ($section) {
            'assignments' => $this->exportPayload($section, ['Coach', 'Email', 'Status', 'Goal', 'Started', 'Ended'], $this->assignmentsQuery($viewer, $athlete, $filters['assignments'])->with('coach')->latest('started_at')->limit(5000)->get()->map(fn ($row) => $this->assignmentRow($row))->map(fn ($row) => [$row['coachName'], $row['coachEmail'], $row['status'], $row['goal'], $row['startedAt'], $row['endedAt']])->all()),
            'memberships' => $this->exportPayload($section, ['Plan', 'Status', 'Starts', 'Renews', 'Ends', 'Days remaining', 'Price', 'Currency'], $this->membershipsQuery($athlete, $filters['memberships'])->with('plan')->latest('starts_at')->limit(5000)->get()->map(fn ($row) => $this->membershipRow($row))->map(fn ($row) => [$row['planName'], $row['status'], $row['startsAt'], $row['renewsAt'], $row['endsAt'], $row['daysRemaining'], $row['price'], $row['currency']])->all()),
            'payments' => $this->exportPayload($section, ['Time', 'Type', 'Status', 'Amount', 'Currency', 'Provider', 'Reference', 'Notes'], $this->paymentsQuery($athlete, $filters['payments'])->latest('event_at')->limit(5000)->get()->map(fn ($row) => $this->paymentRow($row))->map(fn ($row) => [$row['eventAt'], $row['type'], $row['status'], $row['amount'], $row['currency'], $row['provider'], $row['reference'], $row['notes']])->all()),
            'devices' => $this->exportPayload($section, ['Provider', 'Status', 'External user', 'Last sync', 'Metric date', 'Readiness', 'Sleep hours', 'Strain', 'Error'], $this->devicesQuery($athlete, $filters['devices'])->with('latestSnapshot')->latest()->limit(5000)->get()->map(fn ($row) => $this->deviceRow($row))->map(fn ($row) => [$row['provider'], $row['status'], $row['externalUserId'], $row['lastSyncedAt'], $row['latestMetricDate'], $row['readiness'], $row['sleepHours'], $row['strain'], $row['lastErrorMessage']])->all()),
            'sessions' => $this->exportPayload($section, ['Date', 'Program', 'Coach', 'Session', 'Focus', 'Status', 'Completed sets', 'Set count', 'Duration', 'RPE', 'Notes'], $this->sessionsQuery($viewer, $athlete, $filters['sessions'])->with(['program.coach', 'workoutLog.setLogs'])->orderByDesc('scheduled_date')->limit(5000)->get()->map(fn ($row) => $this->sessionRow($row))->map(fn ($row) => [$row['scheduledDate'], $row['programTitle'], $row['coachName'], $row['title'], $row['focus'], $row['completionStatus'], $row['completedSets'], $row['setCount'], $row['durationMinutes'], $row['exertionRating'], $row['notes']])->all()),
            'set-logs' => $this->exportPayload($section, ['Date', 'Program', 'Session', 'Exercise', 'Set', 'Target reps', 'Target load', 'Target rest', 'Actual reps', 'Actual load', 'RPE', 'Completed', 'Notes'], $this->setLogsQuery($viewer, $athlete, $filters['setLogs'])->with(['session.program.coach'])->latest()->limit(5000)->get()->map(fn ($row) => $this->setLogRow($row))->map(fn ($row) => [$row['scheduledDate'], $row['programTitle'], $row['sessionTitle'], $row['exerciseName'], $row['setNumber'], $row['targetReps'], $row['targetLoad'], $row['targetRestSeconds'], $row['actualReps'], $row['actualLoad'], $row['actualRpe'], $row['completedAt'], $row['notes']])->all()),
            'progress' => $this->exportPayload($section, ['Date', 'Weight kg', 'Body fat', 'Waist cm', 'Calories', 'Protein', 'Carbs', 'Fat', 'Water L', 'Meals', 'Energy', 'Soreness', 'Stress', 'Sleep quality', 'Notes'], $this->progressQuery($athlete, $filters['progress'])->latest('logged_date')->limit(5000)->get()->map(fn ($row) => $this->progressRow($row))->map(fn ($row) => [$row['loggedDate'], $row['weightKg'], $row['bodyFatPercentage'], $row['waistCm'], $row['caloriesConsumed'], $row['proteinGrams'], $row['carbsGrams'], $row['fatGrams'], $row['waterLiters'], $row['mealsLoggedCount'], $row['energyScore'], $row['sorenessScore'], $row['stressScore'], $row['sleepQualityScore'], $row['notes']])->all()),
            'files' => $this->exportPayload($section, ['Created', 'File', 'Original filename', 'Category', 'Visibility', 'Status', 'Size bytes', 'Uploaded by', 'Archived', 'Notes'], $this->filesQuery($viewer, $athlete, $filters['files'])->with('uploadedBy')->latest()->limit(5000)->get()->map(fn ($row) => $this->fileRow($row))->map(fn ($row) => [$row['createdAt'], $row['displayName'], $row['originalFilename'], $row['category'], $row['visibility'], $row['status'], $row['sizeBytes'], $row['uploadedBy'], $row['archivedAt'], $row['notes']])->all()),
            'messages' => $this->exportPayload($section, ['Sent', 'Coach', 'Sender', 'Recipient', 'Read at', 'Message'], $this->messagesQuery($viewer, $athlete, $filters['messages'])->with(['assignment.coach', 'sender', 'recipient'])->latest()->limit(5000)->get()->map(fn ($row) => $this->messageRow($row))->map(fn ($row) => [$row['sentAt'], $row['coachName'], $row['senderName'], $row['recipientName'], $row['readAt'], $row['body']])->all()),
            default => abort(404),
        };
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return Builder<CoachAthleteAssignment>
     */
    private function assignmentsQuery(User $viewer, User $athlete, array $filters): Builder
    {
        return CoachAthleteAssignment::query()
            ->with('coach')
            ->where('athlete_id', $athlete->id)
            ->when($this->isAssignedCoachOnly($viewer, $athlete), fn (Builder $query) => $query->where('coach_id', $viewer->id))
            ->when($filters['q'] ?? null, function (Builder $query, string $value): void {
                $query->where(function (Builder $nested) use ($value): void {
                    $nested->where('goal', 'like', "%{$value}%")
                        ->orWhereHas('coach', fn (Builder $coach) => $coach->where('name', 'like', "%{$value}%")->orWhere('email', 'like', "%{$value}%"));
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $value) => $query->where('status', $value));
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return Builder<Membership>
     */
    private function membershipsQuery(User $athlete, array $filters): Builder
    {
        return Membership::query()
            ->where('user_id', $athlete->id)
            ->when($filters['q'] ?? null, function (Builder $query, string $value): void {
                $query->where(function (Builder $nested) use ($value): void {
                    $nested->where('billing_provider', 'like', "%{$value}%")
                        ->orWhere('provider_subscription_id', 'like', "%{$value}%")
                        ->orWhereHas('plan', fn (Builder $plan) => $plan->where('name', 'like', "%{$value}%"));
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $value) => $query->where('status', $value));
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return Builder<PaymentEvent>
     */
    private function paymentsQuery(User $athlete, array $filters): Builder
    {
        return PaymentEvent::query()
            ->where('user_id', $athlete->id)
            ->when($filters['q'] ?? null, function (Builder $query, string $value): void {
                $query->where(function (Builder $nested) use ($value): void {
                    $nested->where('reference', 'like', "%{$value}%")
                        ->orWhere('provider', 'like', "%{$value}%")
                        ->orWhere('notes', 'like', "%{$value}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $value) => $query->where('status', $value))
            ->when($filters['type'] ?? null, fn (Builder $query, string $value) => $query->where('event_type', $value))
            ->when($filters['from'] ?? null, fn (Builder $query, string $value) => $query->whereDate('event_at', '>=', $value))
            ->when($filters['to'] ?? null, fn (Builder $query, string $value) => $query->whereDate('event_at', '<=', $value));
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return Builder<DeviceConnection>
     */
    private function devicesQuery(User $athlete, array $filters): Builder
    {
        return DeviceConnection::query()
            ->where('user_id', $athlete->id)
            ->when($filters['q'] ?? null, function (Builder $query, string $value): void {
                $query->where(function (Builder $nested) use ($value): void {
                    $nested->where('external_user_id', 'like', "%{$value}%")
                        ->orWhere('last_error_message', 'like', "%{$value}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $value) => $query->where('status', $value))
            ->when($filters['provider'] ?? null, fn (Builder $query, string $value) => $query->where('provider', $value));
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return Builder<TrainingSession>
     */
    private function sessionsQuery(User $viewer, User $athlete, array $filters): Builder
    {
        return $this->baseSessionsQuery($viewer, $athlete)
            ->when($filters['q'] ?? null, function (Builder $query, string $value): void {
                $query->where(function (Builder $nested) use ($value): void {
                    $nested->where('title', 'like', "%{$value}%")
                        ->orWhere('focus', 'like', "%{$value}%")
                        ->orWhereHas('program', fn (Builder $program) => $program->where('title', 'like', "%{$value}%")->orWhere('goal', 'like', "%{$value}%"));
                });
            })
            ->when($filters['status'] ?? null, function (Builder $query, string $value): void {
                if ($value === 'not_logged') {
                    $query->whereDoesntHave('workoutLog');

                    return;
                }

                $query->whereHas('workoutLog', fn (Builder $log) => $log->where('completion_status', $value));
            })
            ->when($filters['from'] ?? null, fn (Builder $query, string $value) => $query->whereDate('scheduled_date', '>=', $value))
            ->when($filters['to'] ?? null, fn (Builder $query, string $value) => $query->whereDate('scheduled_date', '<=', $value));
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return Builder<WorkoutSetLog>
     */
    private function setLogsQuery(User $viewer, User $athlete, array $filters): Builder
    {
        return WorkoutSetLog::query()
            ->where('athlete_id', $athlete->id)
            ->whereHas('session.program', fn (Builder $program) => $this->scopeProgramVisibility($program, $viewer, $athlete))
            ->when($filters['q'] ?? null, function (Builder $query, string $value): void {
                $query->where(function (Builder $nested) use ($value): void {
                    $nested->where('exercise_name', 'like', "%{$value}%")
                        ->orWhere('actual_load', 'like', "%{$value}%")
                        ->orWhere('notes', 'like', "%{$value}%")
                        ->orWhereHas('session', fn (Builder $session) => $session->where('title', 'like', "%{$value}%"));
                });
            })
            ->when($filters['completed'] ?? null, function (Builder $query, string $value): void {
                $value === 'completed'
                    ? $query->whereNotNull('completed_at')
                    : $query->whereNull('completed_at');
            })
            ->when($filters['from'] ?? null, fn (Builder $query, string $value) => $query->whereHas('session', fn (Builder $session) => $session->whereDate('scheduled_date', '>=', $value)))
            ->when($filters['to'] ?? null, fn (Builder $query, string $value) => $query->whereHas('session', fn (Builder $session) => $session->whereDate('scheduled_date', '<=', $value)));
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return Builder<AthleteCheckIn>
     */
    private function progressQuery(User $athlete, array $filters): Builder
    {
        return AthleteCheckIn::query()
            ->where('user_id', $athlete->id)
            ->when($filters['q'] ?? null, fn (Builder $query, string $value) => $query->where('notes', 'like', "%{$value}%"))
            ->when($filters['from'] ?? null, fn (Builder $query, string $value) => $query->whereDate('logged_date', '>=', $value))
            ->when($filters['to'] ?? null, fn (Builder $query, string $value) => $query->whereDate('logged_date', '<=', $value));
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return Builder<AthleteFile>
     */
    private function filesQuery(User $viewer, User $athlete, array $filters): Builder
    {
        return AthleteFile::query()
            ->where('athlete_id', $athlete->id)
            ->when($viewer->id === $athlete->id, fn (Builder $query) => $query->where('visibility', AthleteFile::VISIBILITY_ATHLETE)->where('status', AthleteFile::STATUS_ACTIVE))
            ->when($this->isAssignedCoachOnly($viewer, $athlete), fn (Builder $query) => $query->where('visibility', '!=', AthleteFile::VISIBILITY_ADMIN)->where('status', AthleteFile::STATUS_ACTIVE))
            ->when($filters['q'] ?? null, function (Builder $query, string $value): void {
                $query->where(function (Builder $nested) use ($value): void {
                    $nested->where('display_name', 'like', "%{$value}%")
                        ->orWhere('original_filename', 'like', "%{$value}%")
                        ->orWhere('notes', 'like', "%{$value}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $value) => $query->where('status', $value))
            ->when($filters['category'] ?? null, fn (Builder $query, string $value) => $query->where('category', $value))
            ->when($filters['visibility'] ?? null, fn (Builder $query, string $value) => $query->where('visibility', $value));
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return Builder<CoachAthleteMessage>
     */
    private function messagesQuery(User $viewer, User $athlete, array $filters): Builder
    {
        return CoachAthleteMessage::query()
            ->whereHas('assignment', function (Builder $query) use ($viewer, $athlete): void {
                $query->where('athlete_id', $athlete->id)
                    ->when($this->isAssignedCoachOnly($viewer, $athlete), fn (Builder $assignment) => $assignment->where('coach_id', $viewer->id));
            })
            ->when($filters['q'] ?? null, function (Builder $query, string $value): void {
                $query->where(function (Builder $nested) use ($value): void {
                    $nested->where('body', 'like', "%{$value}%")
                        ->orWhereHas('sender', fn (Builder $sender) => $sender->where('name', 'like', "%{$value}%")->orWhere('email', 'like', "%{$value}%"));
                });
            })
            ->when($filters['read'] ?? null, function (Builder $query, string $value): void {
                $value === 'read' ? $query->whereNotNull('read_at') : $query->whereNull('read_at');
            });
    }

    /**
     * @return Builder<TrainingProgram>
     */
    private function baseProgramQuery(User $viewer, User $athlete): Builder
    {
        $query = TrainingProgram::query()
            ->where('athlete_id', $athlete->id);

        return $this->scopeProgramVisibility($query, $viewer, $athlete);
    }

    /**
     * @return Builder<TrainingSession>
     */
    private function baseSessionsQuery(User $viewer, User $athlete): Builder
    {
        return TrainingSession::query()
            ->whereHas('program', function (Builder $program) use ($viewer, $athlete): void {
                $program->where('athlete_id', $athlete->id);
                $this->scopeProgramVisibility($program, $viewer, $athlete);
            });
    }

    /**
     * @return Builder<WorkoutLog>
     */
    private function baseWorkoutLogQuery(User $viewer, User $athlete): Builder
    {
        return WorkoutLog::query()
            ->where('athlete_id', $athlete->id)
            ->whereHas('session.program', fn (Builder $program) => $this->scopeProgramVisibility($program, $viewer, $athlete));
    }

    private function scopeProgramVisibility(Builder $query, User $viewer, User $athlete): Builder
    {
        if ($this->isAssignedCoachOnly($viewer, $athlete)) {
            return $query->where('coach_id', $viewer->id);
        }

        return $query;
    }

    private function isAssignedCoachOnly(User $viewer, User $athlete): bool
    {
        $viewer->loadMissing('roles');

        return $viewer->id !== $athlete->id
            && ! $viewer->hasRole(RoleName::Owner)
            && ! $viewer->hasRole(RoleName::Admin)
            && $viewer->hasRole(RoleName::Coach);
    }

    /**
     * @return array<string, mixed>
     */
    private function assignmentRow(CoachAthleteAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'coachName' => $assignment->coach?->name ?? 'Unknown coach',
            'coachEmail' => $assignment->coach?->email,
            'status' => $assignment->status->value,
            'goal' => $assignment->goal,
            'startedAt' => $assignment->started_at?->toDateString(),
            'endedAt' => $assignment->ended_at?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function membershipRow(Membership $membership): array
    {
        return [
            'id' => $membership->id,
            'planName' => $membership->plan?->name ?? 'Custom plan',
            'status' => $membership->status->value,
            'startsAt' => $membership->starts_at?->toDateString(),
            'renewsAt' => $membership->renews_at?->toDateString(),
            'endsAt' => $membership->effectiveEndDate()?->toDateString(),
            'daysRemaining' => $membership->daysRemaining(),
            'price' => (float) $membership->price,
            'currency' => $membership->currency,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentRow(PaymentEvent $event): array
    {
        return [
            'id' => $event->id,
            'type' => $event->event_type->value,
            'status' => $event->status->value,
            'provider' => $event->provider,
            'amount' => $event->amount !== null ? (float) $event->amount : null,
            'currency' => $event->currency,
            'eventAt' => $event->event_at?->toDateTimeString(),
            'reference' => $event->reference,
            'notes' => $event->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function deviceRow(DeviceConnection $connection): array
    {
        return [
            'id' => $connection->id,
            'provider' => $connection->provider->label(),
            'providerValue' => $connection->provider->value,
            'status' => $connection->status->value,
            'externalUserId' => $connection->external_user_id,
            'lastSyncedAt' => $connection->last_synced_at?->toDateTimeString(),
            'latestMetricDate' => $connection->latestSnapshot?->metric_date?->toDateString(),
            'readiness' => $connection->latestSnapshot?->readiness_score,
            'sleepHours' => $connection->latestSnapshot?->sleepHours(),
            'strain' => $connection->latestSnapshot?->strain_score,
            'lastErrorMessage' => $connection->last_error_message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionRow(TrainingSession $session): array
    {
        $log = $session->workoutLog;

        return [
            'id' => $session->id,
            'programTitle' => $session->program?->title ?? 'No program',
            'coachName' => $session->program?->coach?->name ?? 'No coach',
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
    private function setLogRow(WorkoutSetLog $setLog): array
    {
        return [
            'id' => $setLog->id,
            'sessionId' => $setLog->training_session_id,
            'scheduledDate' => $setLog->session?->scheduled_date?->toDateString(),
            'programTitle' => $setLog->session?->program?->title ?? 'No program',
            'sessionTitle' => $setLog->session?->title ?? 'No session',
            'exerciseName' => $setLog->exercise_name,
            'exerciseIndex' => $setLog->exercise_index,
            'setNumber' => $setLog->set_number,
            'targetReps' => $setLog->target_reps,
            'targetLoad' => $setLog->target_load,
            'targetRestSeconds' => $setLog->target_rest_seconds,
            'actualReps' => $setLog->actual_reps,
            'actualLoad' => $setLog->actual_load,
            'actualRpe' => $setLog->actual_rpe,
            'completedAt' => $setLog->completed_at?->toDateTimeString(),
            'notes' => $setLog->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function progressRow(AthleteCheckIn $checkIn): array
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
            'previewable' => $this->isPreviewable($file->mime_type),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function messageRow(CoachAthleteMessage $message): array
    {
        return [
            'id' => $message->id,
            'assignmentId' => $message->coach_athlete_assignment_id,
            'coachName' => $message->assignment?->coach?->name ?? 'No coach',
            'senderName' => $message->sender?->name,
            'recipientName' => $message->recipient?->name,
            'body' => $message->body,
            'readAt' => $message->read_at?->toDateTimeString(),
            'sentAt' => $message->created_at?->toDateTimeString(),
        ];
    }

    private function isPreviewable(?string $mime): bool
    {
        return in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'], true);
    }

    private function clean(Request $request, string $key): ?string
    {
        $value = $request->string($key)->trim()->value();

        return $value === '' || $value === 'all' ? null : $value;
    }

    /**
     * @param  list<list<mixed>>  $rows
     * @return array{filename:string,headers:list<string>,rows:list<list<mixed>>}
     */
    private function exportPayload(string $section, array $headers, array $rows): array
    {
        return [
            'filename' => "throughline-athlete-{$section}.csv",
            'headers' => $headers,
            'rows' => $rows,
        ];
    }
}
