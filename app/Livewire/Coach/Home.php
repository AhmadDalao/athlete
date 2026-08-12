<?php

namespace App\Livewire\Coach;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Models\AthleteInvitation;
use App\Models\ScheduledWorkout;
use App\Models\TrainingProgram;
use App\Models\WorkoutLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Home extends Component
{
    use AuthorizesComponentAccess;

    public function render()
    {
        $coach = Auth::user();

        return view('livewire.coach.home', [
            'athletes' => $coach->coachAssignments()->with('athlete')->where('status', 'active')->limit(8)->get(),
            'stats' => [
                'athletes' => $coach->coachAssignments()->where('status', 'active')->count(),
                'programs' => TrainingProgram::where('coach_id', $coach->id)->count(),
                'sessionsToday' => ScheduledWorkout::where('coach_id', $coach->id)->whereDate('scheduled_for', today())->count(),
                'pendingInvites' => AthleteInvitation::where('coach_id', $coach->id)->where('status', 'pending')->count(),
                'completedLogs' => WorkoutLog::where('status', 'completed')
                    ->where(fn ($query) => $query
                        ->whereHas('scheduledWorkout', fn ($query) => $query->where('coach_id', $coach->id))
                        ->orWhere(fn ($query) => $query
                            ->whereNull('scheduled_workout_id')
                            ->whereHas('session.program', fn ($query) => $query->where('coach_id', $coach->id))))
                    ->count(),
            ],
            'sessions' => ScheduledWorkout::with(['athlete', 'session.program', 'assignment'])
                ->where('coach_id', $coach->id)
                ->whereDate('scheduled_for', '>=', today())
                ->where('status', 'scheduled')
                ->orderBy('scheduled_for')
                ->limit(8)
                ->get(),
        ])->layout('layouts.app', ['title' => 'Coach home']);
    }

    protected function componentPermissions(): array
    {
        return ['coach.access'];
    }
}
