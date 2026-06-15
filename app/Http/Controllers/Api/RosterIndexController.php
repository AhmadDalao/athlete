<?php

namespace App\Http\Controllers\Api;

use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\CoachAthleteAssignment;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RosterIndexController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()?->loadMissing('roles');

        abort_unless($viewer && ($viewer->hasRole(RoleName::Admin) || $viewer->hasRole(RoleName::Coach)), 403);

        $baseQuery = $this->visibleAssignmentsQuery($viewer);
        $assignments = (clone $baseQuery)
            ->with([
                'coach.roles',
                'athlete.roles',
                'athlete.latestAthleteCheckIn',
                'athlete.memberships.plan',
                'athlete.deviceConnections.latestSnapshot',
                'athlete.trainingProgramsAsAthlete.sessions.workoutLog',
            ])
            ->orderByRaw(
                "case status
                    when 'active' then 0
                    when 'paused' then 1
                    when 'archived' then 2
                    else 3
                end"
            )
            ->orderByDesc('started_at')
            ->paginate(8)
            ->withQueryString()
            ->through(fn (CoachAthleteAssignment $assignment): array => $this->assignmentPayload($assignment));

        return response()->json([
            'data' => [
                'viewerRole' => $viewer->primaryRoleName(),
                'summary' => $this->summary($viewer),
                'assignments' => $this->paginationPayload($assignments),
                'coachOptions' => $viewer->hasRole(RoleName::Admin) ? $this->coachOptions() : [],
                'athleteOptions' => $this->athleteOptions(),
                'statusOptions' => collect(CoachAthleteStatus::cases())
                    ->map(fn (CoachAthleteStatus $status): array => [
                        'value' => $status->value,
                        'label' => $status->label(),
                    ])
                    ->values()
                    ->all(),
            ],
            'meta' => $this->metaPayload(),
        ]);
    }

    private function visibleAssignmentsQuery(User $viewer): Builder
    {
        $query = CoachAthleteAssignment::query();

        if ($viewer->hasRole(RoleName::Admin)) {
            return $query;
        }

        return $query->where('coach_id', $viewer->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(User $viewer): array
    {
        $assignmentQuery = $this->visibleAssignmentsQuery($viewer);
        $activeQuery = (clone $assignmentQuery)->where('status', CoachAthleteStatus::Active->value);

        $availableAthleteQuery = User::query()->role(RoleName::Athlete);

        if ($viewer->hasRole(RoleName::Admin)) {
            $availableAthleteQuery->whereDoesntHave('athleteAssignments', fn (Builder $query) => $query->where('status', CoachAthleteStatus::Active->value));
        } else {
            $availableAthleteQuery->whereDoesntHave('athleteAssignments', function (Builder $query) use ($viewer): void {
                $query
                    ->where('status', CoachAthleteStatus::Active->value)
                    ->where('coach_id', $viewer->id);
            });
        }

        return [
            'totalAssignments' => (clone $assignmentQuery)->count(),
            'activeAssignments' => (clone $assignmentQuery)->where('status', CoachAthleteStatus::Active->value)->count(),
            'pausedAssignments' => (clone $assignmentQuery)->where('status', CoachAthleteStatus::Paused->value)->count(),
            'archivedAssignments' => (clone $assignmentQuery)->where('status', CoachAthleteStatus::Archived->value)->count(),
            'activeAthletes' => (clone $activeQuery)->distinct('athlete_id')->count('athlete_id'),
            'availableAthletes' => $availableAthleteQuery->count(),
            'athletesMissingRecentCheckIns' => (clone $activeQuery)
                ->with('athlete.latestAthleteCheckIn')
                ->get()
                ->filter(fn (CoachAthleteAssignment $assignment) => ! $assignment->athlete->latestAthleteCheckIn || $assignment->athlete->latestAthleteCheckIn->logged_date?->lt(now()->subDays(3)->startOfDay()))
                ->count(),
            'representedCoaches' => $viewer->hasRole(RoleName::Admin)
                ? (clone $assignmentQuery)->distinct('coach_id')->count('coach_id')
                : 1,
        ];
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    private function coachOptions(): array
    {
        return User::query()
            ->role(RoleName::Coach)
            ->orderBy('name')
            ->get()
            ->map(fn (User $coach): array => [
                'value' => (string) $coach->id,
                'label' => $coach->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    private function athleteOptions(): array
    {
        return User::query()
            ->role(RoleName::Athlete)
            ->orderBy('name')
            ->get()
            ->map(fn (User $athlete): array => [
                'value' => (string) $athlete->id,
                'label' => $athlete->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function assignmentPayload(CoachAthleteAssignment $assignment): array
    {
        $athlete = $assignment->athlete;
        $membership = $athlete->currentMembership();
        $connectedDevices = $athlete->deviceConnections
            ->where('status', DeviceConnectionStatus::Connected)
            ->count();
        $latestSnapshot = $athlete->deviceConnections
            ->pluck('latestSnapshot')
            ->filter()
            ->sortByDesc(fn ($snapshot) => $snapshot->metric_date?->timestamp ?? 0)
            ->first();
        $currentProgram = $this->currentProgramFor($assignment);
        $nextSession = $currentProgram ? $this->nextSessionFor($currentProgram) : null;

        return [
            'id' => $assignment->id,
            'status' => $assignment->status->value,
            'goal' => $assignment->goal,
            'notes' => $assignment->notes,
            'startedAt' => $assignment->started_at?->toDateString(),
            'endedAt' => $assignment->ended_at?->toDateString(),
            'coach' => [
                'id' => $assignment->coach->id,
                'name' => $assignment->coach->name,
                'email' => $assignment->coach->email,
            ],
            'athlete' => [
                'id' => $athlete->id,
                'name' => $athlete->name,
                'email' => $athlete->email,
                'phone' => $athlete->phone,
                'primaryGoal' => $athlete->primary_goal,
                'preferredContactMethod' => $athlete->preferred_contact_method,
            ],
            'membership' => $membership ? [
                'status' => $membership->status->value,
                'planName' => $membership->plan?->name ?? 'Custom plan',
                'daysRemaining' => $membership->daysRemaining(),
            ] : null,
            'connectedDevices' => $connectedDevices,
            'latestSnapshot' => $this->snapshotPayload($latestSnapshot),
            'latestCheckIn' => $this->checkInPayload($athlete->latestAthleteCheckIn),
            'currentProgram' => $currentProgram ? [
                'title' => $currentProgram->title,
                'status' => $currentProgram->status->value,
                'nextSessionDate' => $nextSession?->scheduled_date?->toDateString(),
                'pendingLogs' => $currentProgram->sessions
                    ->where('scheduled_date', '<=', now()->toDateString())
                    ->whereNull('workoutLog')
                    ->count(),
            ] : null,
        ];
    }

    private function currentProgramFor(CoachAthleteAssignment $assignment): ?TrainingProgram
    {
        return $assignment->athlete->trainingProgramsAsAthlete
            ->where('coach_id', $assignment->coach_id)
            ->sortBy(function (TrainingProgram $program): array {
                $statusWeight = match ($program->status) {
                    TrainingProgramStatus::Active => 0,
                    TrainingProgramStatus::Draft => 1,
                    TrainingProgramStatus::Completed => 2,
                    TrainingProgramStatus::Archived => 3,
                };

                return [$statusWeight, -($program->start_date?->timestamp ?? 0)];
            })
            ->first();
    }

    private function nextSessionFor(TrainingProgram $program): ?TrainingSession
    {
        return $program->sessions
            ->where('scheduled_date', '>=', now()->startOfDay())
            ->sortBy('scheduled_date')
            ->first();
    }
}
