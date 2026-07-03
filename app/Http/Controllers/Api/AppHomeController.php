<?php

namespace App\Http\Controllers\Api;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Http\Controllers\Api\Concerns\BuildsMobileAppPayloads;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\CoachAthleteAssignment;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\TrainingAppPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppHomeController extends Controller
{
    use BuildsMobileAppPayloads;
    use FormatsApiPayloads;

    public function __invoke(Request $request, TrainingAppPresenter $presenter): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('roles');
        $this->abortUnlessMobileUser($user);

        return response()->json([
            'data' => $user->hasRole(RoleName::Coach)
                ? $this->coachPayload($user, $presenter)
                : $this->athletePayload($user, $presenter),
            'meta' => $this->metaPayload(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function athletePayload(User $user, TrainingAppPresenter $presenter): array
    {
        $programs = $this->mobileProgramsQuery($user)
            ->with(['coach', 'athlete', 'sessions.workoutLog'])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $programIds = $programs->pluck('id');
        $today = now()->toDateString();

        $todaySessions = TrainingSession::query()
            ->whereIn('training_program_id', $programIds)
            ->whereDate('scheduled_date', $today)
            ->with(['program.coach', 'program.athlete', 'workoutLog'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (TrainingSession $session): array => $this->mobileSessionPayload($session, $presenter))
            ->values()
            ->all();

        $upcomingSessions = TrainingSession::query()
            ->whereIn('training_program_id', $programIds)
            ->whereDate('scheduled_date', '>=', $today)
            ->with(['program.coach', 'program.athlete', 'workoutLog'])
            ->orderBy('scheduled_date')
            ->orderBy('sort_order')
            ->limit(8)
            ->get()
            ->map(fn (TrainingSession $session): array => $this->mobileSessionPayload($session, $presenter))
            ->values()
            ->all();

        $assignments = CoachAthleteAssignment::query()
            ->where('athlete_id', $user->id)
            ->where('status', CoachAthleteStatus::Active->value)
            ->with('coach')
            ->latest('started_at')
            ->get();

        return [
            'role' => RoleName::Athlete->value,
            'viewer' => $this->viewerPayload($user),
            'coaches' => $assignments
                ->map(fn (CoachAthleteAssignment $assignment): array => [
                    'id' => $assignment->coach->id,
                    'name' => $assignment->coach->name,
                    'email' => $assignment->coach->email,
                    'goal' => $assignment->goal,
                    'startedAt' => $assignment->started_at?->toDateString(),
                ])
                ->values()
                ->all(),
            'programs' => $programs
                ->map(fn (TrainingProgram $program): array => $presenter->program($program))
                ->values()
                ->all(),
            'todaySessions' => $todaySessions,
            'upcomingSessions' => $upcomingSessions,
            'membership' => $this->mobileMembershipPayload($user),
            'wearable' => [
                'latestSnapshot' => $this->snapshotPayload($user->metricSnapshots()->latest('metric_date')->first()),
                'connectedCount' => $user->deviceConnections()->where('status', 'connected')->count(),
            ],
            'progress' => [
                'latestCheckIn' => $this->checkInPayload($user->athleteCheckIns()->latest('logged_date')->first()),
            ],
            'messages' => [
                'unreadCount' => $this->unreadMessageCount($user),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function coachPayload(User $user, TrainingAppPresenter $presenter): array
    {
        $programs = $this->mobileProgramsQuery($user)
            ->with(['coach', 'athlete', 'sessions.workoutLog'])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $programIds = $programs->pluck('id');

        $athletes = CoachAthleteAssignment::query()
            ->where('coach_id', $user->id)
            ->where('status', CoachAthleteStatus::Active->value)
            ->with(['athlete.latestAthleteCheckIn', 'athlete.deviceConnections.latestSnapshot'])
            ->latest('started_at')
            ->limit(20)
            ->get();

        $schedule = TrainingSession::query()
            ->whereIn('training_program_id', $programIds)
            ->whereBetween('scheduled_date', [now()->toDateString(), now()->addDays(14)->toDateString()])
            ->with(['program.coach', 'program.athlete', 'workoutLog'])
            ->orderBy('scheduled_date')
            ->orderBy('sort_order')
            ->limit(16)
            ->get()
            ->map(fn (TrainingSession $session): array => $this->mobileSessionPayload($session, $presenter))
            ->values()
            ->all();

        $pendingLogs = TrainingSession::query()
            ->whereIn('training_program_id', $programIds)
            ->whereDate('scheduled_date', '<=', now()->toDateString())
            ->whereDoesntHave('workoutLog')
            ->with(['program.coach', 'program.athlete'])
            ->orderByDesc('scheduled_date')
            ->limit(10)
            ->get()
            ->map(fn (TrainingSession $session): array => $this->mobileSessionPayload($session, $presenter))
            ->values()
            ->all();

        return [
            'role' => RoleName::Coach->value,
            'viewer' => $this->viewerPayload($user),
            'summary' => [
                'assignedAthletes' => $athletes->count(),
                'activePrograms' => $programs
                    ->filter(fn (TrainingProgram $program): bool => $program->status === TrainingProgramStatus::Active)
                    ->count(),
                'upcomingSessions' => count($schedule),
                'pendingLogs' => count($pendingLogs),
                'unreadMessages' => $this->unreadMessageCount($user),
            ],
            'athletes' => $athletes
                ->map(fn (CoachAthleteAssignment $assignment): array => [
                    'assignmentId' => $assignment->id,
                    'id' => $assignment->athlete->id,
                    'name' => $assignment->athlete->name,
                    'email' => $assignment->athlete->email,
                    'goal' => $assignment->goal,
                    'startedAt' => $assignment->started_at?->toDateString(),
                    'latestCheckIn' => $this->checkInPayload($assignment->athlete->latestAthleteCheckIn),
                    'latestSnapshot' => $this->snapshotPayload(
                        $assignment->athlete->deviceConnections
                            ->pluck('latestSnapshot')
                            ->filter()
                            ->sortByDesc(fn ($snapshot) => $snapshot->metric_date?->timestamp ?? 0)
                            ->first()
                    ),
                ])
                ->values()
                ->all(),
            'programs' => $programs
                ->map(fn (TrainingProgram $program): array => $presenter->program($program))
                ->values()
                ->all(),
            'schedule' => $schedule,
            'pendingLogs' => $pendingLogs,
            'messages' => [
                'unreadCount' => $this->unreadMessageCount($user),
            ],
        ];
    }
}
