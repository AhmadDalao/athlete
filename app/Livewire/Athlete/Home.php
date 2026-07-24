<?php

namespace App\Livewire\Athlete;

use App\Models\ProgressEntry;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Home extends Component
{
    public string $selectedDate;

    public string $month;

    public function mount(): void
    {
        $this->selectedDate = request('date', today()->toDateString());
        $this->month = request('month', Carbon::parse($this->selectedDate)->format('Y-m'));
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->month = Carbon::parse($date)->format('Y-m');
    }

    public function previousMonth(): void
    {
        $month = Carbon::parse($this->month.'-01')->subMonth();
        $this->month = $month->format('Y-m');
        $this->selectedDate = $month->toDateString();
    }

    public function nextMonth(): void
    {
        $month = Carbon::parse($this->month.'-01')->addMonth();
        $this->month = $month->format('Y-m');
        $this->selectedDate = $month->toDateString();
    }

    public function render()
    {
        $athleteId = Auth::id();
        $programs = TrainingProgram::with(['coach', 'sessions.logs'])
            ->where('athlete_id', $athleteId)
            ->whereIn('status', ['active', 'draft'])
            ->latest()
            ->get();

        $start = Carbon::parse($this->month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $sessions = TrainingSession::with('program.coach', 'logs')
            ->whereHas('program', fn ($query) => $query->where('athlete_id', $athleteId))
            ->whereBetween('scheduled_on', [$start->toDateString(), $end->toDateString()])
            ->get();
        $todaySessions = TrainingSession::with('program.coach', 'logs')
            ->whereHas('program', fn ($query) => $query->where('athlete_id', $athleteId))
            ->whereDate('scheduled_on', today()->toDateString())
            ->orderBy('scheduled_on')
            ->get();
        $latestProgress = ProgressEntry::where('athlete_id', $athleteId)
            ->latest('logged_on')
            ->first();
        $programSummaries = $programs->map(function (TrainingProgram $program) use ($athleteId): array {
            $totalSessions = $program->sessions->count();
            $completedSessions = $program->sessions
                ->filter(fn (TrainingSession $session): bool => $session->logs->firstWhere('athlete_id', $athleteId)?->status === 'completed')
                ->count();
            $mediaSessions = $program->sessions
                ->filter(fn (TrainingSession $session): bool => filled($session->media_url))
                ->count();
            $nextSession = $program->sessions
                ->first(fn (TrainingSession $session): bool => $session->scheduled_on->greaterThanOrEqualTo(today()));

            return [
                'program' => $program,
                'total' => $totalSessions,
                'completed' => $completedSessions,
                'media' => $mediaSessions,
                'progress' => $totalSessions > 0 ? (int) round(($completedSessions / $totalSessions) * 100) : 0,
                'nextSession' => $nextSession,
            ];
        });

        return view('livewire.athlete.home', [
            'programs' => $programs,
            'programSummaries' => $programSummaries,
            'todaySessions' => $todaySessions,
            'latestProgress' => $latestProgress,
            'days' => collect(range(1, $end->day))->map(function (int $day) use ($start, $sessions): array {
                $date = $start->copy()->day($day)->toDateString();

                return [
                    'date' => $date,
                    'day' => $day,
                    'label' => Carbon::parse($date)->format('D'),
                    'sessions' => $sessions->filter(fn (TrainingSession $session): bool => $session->scheduled_on->toDateString() === $date)->count(),
                ];
            }),
            'selectedSessions' => $sessions->filter(fn (TrainingSession $session): bool => $session->scheduled_on->toDateString() === Carbon::parse($this->selectedDate)->toDateString()),
        ])->layout('layouts.app', ['title' => 'Athlete app']);
    }
}
