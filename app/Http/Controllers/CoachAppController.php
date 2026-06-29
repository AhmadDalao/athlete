<?php

namespace App\Http\Controllers;

use App\Enums\CoachAthleteStatus;
use App\Enums\MembershipStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Models\CoachAthleteMessage;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\TrainingAppPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoachAppController extends Controller
{
    public function __invoke(Request $request, TrainingAppPresenter $presenter): Response|RedirectResponse
    {
        /** @var User $coach */
        $coach = $request->user()->loadMissing('roles');

        if (! $coach->hasRole(RoleName::Coach)) {
            return redirect()->to($coach->landingPath());
        }

        $assignments = $coach->coachAssignments()
            ->where('status', CoachAthleteStatus::Active->value)
            ->with(['athlete.memberships.plan', 'athlete.latestAthleteCheckIn', 'athlete.deviceConnections.latestSnapshot'])
            ->latest('started_at')
            ->get();

        $programs = TrainingProgram::query()
            ->where('coach_id', $coach->id)
            ->whereIn('status', [TrainingProgramStatus::Active->value, TrainingProgramStatus::Draft->value])
            ->with(['coach', 'athlete', 'sessions.workoutLog.setLogs'])
            ->orderByRaw("case status when 'active' then 0 when 'draft' then 1 else 2 end")
            ->orderByDesc('start_date')
            ->get();

        $programIds = $programs->pluck('id');

        $schedule = TrainingSession::query()
            ->whereIn('training_program_id', $programIds)
            ->whereDate('scheduled_date', '>=', now()->toDateString())
            ->whereDate('scheduled_date', '<=', now()->addDays(14)->toDateString())
            ->with(['program.coach', 'program.athlete', 'workoutLog.setLogs'])
            ->orderBy('scheduled_date')
            ->orderBy('sort_order')
            ->take(16)
            ->get()
            ->map(fn (TrainingSession $session): array => array_merge($presenter->session($session), [
                'program' => [
                    'id' => $session->program->id,
                    'title' => $session->program->title,
                ],
                'athlete' => [
                    'id' => $session->program->athlete->id,
                    'name' => $session->program->athlete->name,
                    'email' => $session->program->athlete->email,
                ],
            ]))
            ->values()
            ->all();

        $pendingLogs = TrainingSession::query()
            ->whereIn('training_program_id', $programIds)
            ->whereDate('scheduled_date', '<=', now()->toDateString())
            ->whereDoesntHave('workoutLog')
            ->with(['program.coach', 'program.athlete'])
            ->orderBy('scheduled_date')
            ->take(12)
            ->get()
            ->map(fn (TrainingSession $session): array => [
                'id' => $session->id,
                'title' => $session->title,
                'scheduledDate' => $session->scheduled_date?->toDateString(),
                'focus' => $session->focus,
                'programTitle' => $session->program->title,
                'athleteId' => $session->program->athlete->id,
                'athleteName' => $session->program->athlete->name,
            ])
            ->values()
            ->all();

        $unreadMessages = CoachAthleteMessage::query()
            ->where('recipient_id', $coach->id)
            ->whereNull('read_at')
            ->count();

        return Inertia::render('coach-app/index', [
            'viewer' => [
                'id' => $coach->id,
                'name' => $coach->name,
                'email' => $coach->email,
            ],
            'summary' => [
                'assignedAthletes' => $assignments->count(),
                'activePrograms' => $programs->filter(fn (TrainingProgram $program): bool => $program->status === TrainingProgramStatus::Active)->count(),
                'upcomingSessions' => count($schedule),
                'pendingLogs' => count($pendingLogs),
                'unreadMessages' => $unreadMessages,
            ],
            'athletes' => $assignments
                ->map(function ($assignment) use ($programs): array {
                    $athlete = $assignment->athlete;
                    $membership = $athlete->currentMembership();
                    $currentProgram = $programs
                        ->where('athlete_id', $athlete->id)
                        ->filter(fn (TrainingProgram $program): bool => $program->status === TrainingProgramStatus::Active)
                        ->first();

                    return [
                        'id' => $athlete->id,
                        'name' => $athlete->name,
                        'email' => $athlete->email,
                        'goal' => $assignment->goal ?: $athlete->primary_goal,
                        'startedAt' => $assignment->started_at?->toDateString(),
                        'membershipStatus' => $membership?->status?->value ?? 'none',
                        'membershipNeedsAttention' => $membership?->status === MembershipStatus::PastDue,
                        'latestCheckInAt' => $athlete->latestAthleteCheckIn?->logged_date?->toDateString(),
                        'connectedDevices' => $athlete->deviceConnections
                            ->filter(fn ($connection): bool => $connection->status->value === 'connected')
                            ->count(),
                        'currentProgram' => $currentProgram ? [
                            'id' => $currentProgram->id,
                            'title' => $currentProgram->title,
                            'status' => $currentProgram->status->value,
                        ] : null,
                    ];
                })
                ->values()
                ->all(),
            'programs' => $programs
                ->map(fn (TrainingProgram $program): array => $presenter->program($program))
                ->values()
                ->all(),
            'schedule' => $schedule,
            'pendingLogs' => $pendingLogs,
        ]);
    }
}
