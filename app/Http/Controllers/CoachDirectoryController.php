<?php

namespace App\Http\Controllers;

use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class CoachDirectoryController extends Controller
{
    public function __invoke(): Response
    {
        $search = trim(request()->string('q')->value());

        $coaches = User::query()
            ->role(RoleName::Coach)
            ->withCount([
                'coachAssignments as active_roster_count' => fn (Builder $query) => $query->where('status', CoachAthleteStatus::Active->value),
                'trainingProgramsAsCoach as active_programs_count' => fn (Builder $query) => $query->where('status', TrainingProgramStatus::Active->value),
            ])
            ->with([
                'coachAssignments' => fn ($query) => $query
                    ->where('status', CoachAthleteStatus::Active->value)
                    ->with(['athlete.deviceConnections', 'athlete.latestAthleteCheckIn']),
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('primary_goal', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('active_roster_count')
            ->orderByDesc('active_programs_count')
            ->orderBy('name')
            ->get()
            ->map(function (User $coach): array {
                $assignments = $coach->coachAssignments;
                $connectedAthletesCount = $assignments
                    ->filter(fn ($assignment) => $assignment->athlete->deviceConnections->where('status', DeviceConnectionStatus::Connected)->isNotEmpty())
                    ->count();
                $whoopRosterCount = $assignments
                    ->filter(fn ($assignment) => $assignment->athlete->deviceConnections
                        ->where('provider', DeviceProvider::Whoop)
                        ->where('status', DeviceConnectionStatus::Connected)
                        ->isNotEmpty())
                    ->count();
                $recentCheckInCount = $assignments
                    ->filter(fn ($assignment) => $assignment->athlete->latestAthleteCheckIn?->logged_date?->gte(now()->subDays(7)->startOfDay()))
                    ->count();

                $signals = collect([
                    $coach->primary_goal ? $coach->primary_goal : null,
                    $coach->active_programs_count > 0 ? "{$coach->active_programs_count} active program(s)" : null,
                    $connectedAthletesCount > 0 ? "{$connectedAthletesCount} athlete(s) with live device data" : null,
                    $whoopRosterCount > 0 ? 'WHOOP-connected roster' : null,
                ])
                    ->filter()
                    ->values()
                    ->take(4)
                    ->all();

                return [
                    'id' => $coach->id,
                    'name' => $coach->name,
                    'email' => $coach->email,
                    'headline' => $coach->primary_goal ?: 'Strength and performance coaching',
                    'rosterCount' => (int) $coach->active_roster_count,
                    'activePrograms' => (int) $coach->active_programs_count,
                    'connectedAthletesCount' => $connectedAthletesCount,
                    'whoopRosterCount' => $whoopRosterCount,
                    'recentCheckInCount' => $recentCheckInCount,
                    'signals' => $signals,
                    'contactUrl' => route('contact.show', [
                        'coach' => $coach->name,
                        'goal' => $coach->primary_goal,
                    ], false),
                ];
            })
            ->values();

        return Inertia::render('coaches/index', [
            'filters' => [
                'q' => $search === '' ? null : $search,
            ],
            'summary' => [
                'totalCoaches' => $coaches->count(),
                'activeRosterSeats' => $coaches->sum('rosterCount'),
                'activePrograms' => $coaches->sum('activePrograms'),
                'wearableAwareCoaches' => $coaches->filter(fn (array $coach) => $coach['connectedAthletesCount'] > 0)->count(),
            ],
            'coaches' => $coaches->all(),
        ]);
    }
}
