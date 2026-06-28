<?php

namespace App\Http\Controllers;

use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Models\CoachAthleteAssignment;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\CoachWeeklyBriefService;
use App\Support\TablePageSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RosterIndexController extends Controller
{
    public function __invoke(Request $request, CoachWeeklyBriefService $coachWeeklyBriefs): Response
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
            ->paginate(TablePageSize::resolve($request, $baseQuery))
            ->withQueryString()
            ->through(fn (CoachAthleteAssignment $assignment): array => $this->assignmentPayload($assignment, $coachWeeklyBriefs));

        return Inertia::render('roster/index', [
            'viewerRole' => $viewer->primaryRoleName(),
            'scopeLabel' => $this->scopeLabel($viewer),
            'summary' => $this->summary($viewer),
            'assignments' => $assignments,
            'coachOptions' => $viewer->hasRole(RoleName::Admin) ? $this->coachOptions() : [],
            'athleteOptions' => $this->athleteOptions(),
            'statusOptions' => collect(CoachAthleteStatus::cases())
                ->map(fn (CoachAthleteStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values()
                ->all(),
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

    private function scopeLabel(User $viewer): string
    {
        if ($viewer->hasRole(RoleName::Admin)) {
            return 'All coach-athlete assignments, their operating status, and the athletes still sitting without clean coverage.';
        }

        return 'Your roster, their current coaching status, and the athletes you can still bring under management.';
    }

    /**
     * @return array<string, mixed>
     */
    private function assignmentPayload(CoachAthleteAssignment $assignment, CoachWeeklyBriefService $coachWeeklyBriefs): array
    {
        $athlete = $assignment->athlete;
        $membership = $athlete->currentMembership();
        $daysRemaining = $this->daysRemaining($membership);
        $connectedDevices = $athlete->deviceConnections
            ->where('status', DeviceConnectionStatus::Connected)
            ->count();
        $latestSnapshot = $athlete->deviceConnections
            ->pluck('latestSnapshot')
            ->filter()
            ->sortByDesc(fn ($snapshot) => $snapshot->metric_date?->timestamp ?? 0)
            ->first();
        $latestCheckIn = $athlete->latestAthleteCheckIn;
        $currentProgram = $this->currentProgramFor($assignment);
        $nextSession = $currentProgram ? $this->nextSessionFor($currentProgram) : null;
        $weeklyBrief = $coachWeeklyBriefs->forAthlete($athlete);

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
                'daysRemaining' => $daysRemaining,
            ] : null,
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
                'pendingLogs' => $currentProgram->sessions
                    ->where('scheduled_date', '<=', now()->toDateString())
                    ->whereNull('workoutLog')
                    ->count(),
            ] : null,
            'weeklyBrief' => $weeklyBrief,
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

    private function daysRemaining($membership): ?int
    {
        if (! $membership || ! $membership->effectiveEndDate()) {
            return null;
        }

        return now()->startOfDay()->diffInDays($membership->effectiveEndDate()->startOfDay(), false);
    }
}
