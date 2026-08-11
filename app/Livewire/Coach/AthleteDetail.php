<?php

namespace App\Livewire\Coach;

use App\Models\ProgramAssignment;
use App\Models\ScheduledWorkout;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AthleteDetail extends Component
{
    public User $athlete;

    public function mount(User $athlete): void
    {
        abort_unless($athlete->isAthlete(), 404);
        abort_unless(
            $athlete->athleteAssignments()
                ->where('coach_id', Auth::id())
                ->where('status', 'active')
                ->exists(),
            403
        );

        $this->athlete = $athlete;
    }

    public function render()
    {
        $coachId = Auth::id();

        $assignments = ProgramAssignment::query()
            ->where('athlete_id', $this->athlete->id)
            ->whereHas('program', fn ($query) => $query->where('coach_id', $coachId))
            ->with(['program.sessions', 'scheduledWorkouts.logs'])
            ->latest('starts_on')
            ->get();

        $sessions = ScheduledWorkout::query()
            ->where('athlete_id', $this->athlete->id)
            ->where('coach_id', $coachId)
            ->with(['session.program', 'logs', 'assignment'])
            ->latest('scheduled_for')
            ->limit(30)
            ->get();

        return view('livewire.coach.athlete-detail', [
            'assignment' => $this->athlete->athleteAssignments()->with('coach')->where('coach_id', $coachId)->first(),
            'assignments' => $assignments,
            'sessions' => $sessions,
            'workoutLogs' => WorkoutLog::query()
                ->where('athlete_id', $this->athlete->id)
                ->with('session.program')
                ->where(fn ($query) => $query
                    ->whereHas('scheduledWorkout', fn ($query) => $query->where('coach_id', $coachId))
                    ->orWhere(fn ($query) => $query
                        ->whereNull('scheduled_workout_id')
                        ->whereHas('session.program', fn ($query) => $query->where('coach_id', $coachId))))
                ->latest()
                ->limit(30)
                ->get(),
            'progressEntries' => $this->athlete->progressEntries()->latest('logged_on')->limit(30)->get(),
        ])->layout('layouts.app', ['title' => $this->athlete->name]);
    }
}
