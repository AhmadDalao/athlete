<?php

namespace App\Livewire\Coach;

use App\Models\User;
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

        $programs = $this->athlete->athletePrograms()
            ->with(['sessions.logs', 'coach'])
            ->where('coach_id', $coachId)
            ->latest()
            ->get();

        $sessionIds = $programs->flatMap(fn ($program) => $program->sessions->pluck('id'))->values();

        return view('livewire.coach.athlete-detail', [
            'assignment' => $this->athlete->athleteAssignments()->with('coach')->where('coach_id', $coachId)->first(),
            'programs' => $programs,
            'sessions' => $programs->flatMap->sessions->sortByDesc('scheduled_on')->take(30),
            'workoutLogs' => $this->athlete->workoutLogs()
                ->with('session.program')
                ->whereIn('training_session_id', $sessionIds)
                ->latest()
                ->limit(30)
                ->get(),
            'progressEntries' => $this->athlete->progressEntries()->latest('logged_on')->limit(30)->get(),
        ])->layout('layouts.app', ['title' => $this->athlete->name]);
    }
}
