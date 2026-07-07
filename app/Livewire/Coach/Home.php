<?php

namespace App\Livewire\Coach;

use App\Models\AthleteInvitation;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\WorkoutLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Home extends Component
{
    public function render()
    {
        $coach = Auth::user();

        return view('livewire.coach.home', [
            'athletes' => $coach->coachAssignments()->with('athlete')->where('status', 'active')->limit(8)->get(),
            'stats' => [
                'athletes' => $coach->coachAssignments()->where('status', 'active')->count(),
                'programs' => TrainingProgram::where('coach_id', $coach->id)->count(),
                'sessionsToday' => TrainingSession::whereHas('program', fn ($query) => $query->where('coach_id', $coach->id))->whereDate('scheduled_on', today())->count(),
                'pendingInvites' => AthleteInvitation::where('coach_id', $coach->id)->where('status', 'pending')->count(),
                'completedLogs' => WorkoutLog::whereHas('session.program', fn ($query) => $query->where('coach_id', $coach->id))->where('status', 'completed')->count(),
            ],
            'sessions' => TrainingSession::with('program.athlete')
                ->whereHas('program', fn ($query) => $query->where('coach_id', $coach->id))
                ->whereDate('scheduled_on', '>=', today())
                ->orderBy('scheduled_on')
                ->limit(8)
                ->get(),
        ])->layout('layouts.app', ['title' => 'Coach home']);
    }
}
