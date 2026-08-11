<?php

namespace App\Livewire\Athlete;

use App\Models\ProgramAssignment;
use App\Models\ProgressEntry;
use App\Models\ScheduledWorkout;
use App\Queries\Athlete\AthleteWorkspaceQuery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

class Home extends Component
{
    #[Url(as: 'date', history: true)]
    public string $selectedDate;

    #[Url(history: true)]
    public string $month;

    public function mount(): void
    {
        $requestedDate = (string) request('date', today()->toDateString());
        $this->selectedDate = $this->validDate($requestedDate) ? $requestedDate : today()->toDateString();
        $requestedMonth = (string) request('month', Carbon::parse($this->selectedDate)->format('Y-m'));
        $this->month = preg_match('/^\d{4}-\d{2}$/', $requestedMonth) ? $requestedMonth : Carbon::parse($this->selectedDate)->format('Y-m');
    }

    public function selectDate(string $date): void
    {
        abort_unless($this->validDate($date), 422);
        $this->selectedDate = $date;
        $this->month = Carbon::parse($date)->format('Y-m');
    }

    public function previousMonth(): void
    {
        $this->moveMonth(-1);
    }

    public function nextMonth(): void
    {
        $this->moveMonth(1);
    }

    public function goToday(): void
    {
        $this->selectedDate = today()->toDateString();
        $this->month = today()->format('Y-m');
    }

    public function render()
    {
        $athleteId = (int) Auth::id();
        $assignments = AthleteWorkspaceQuery::assignments($athleteId)->get();
        $monthStart = Carbon::parse($this->month.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $monthSchedule = AthleteWorkspaceQuery::schedule(
            $athleteId,
            $monthStart->toDateString(),
            $monthEnd->toDateString(),
        )->get();
        $todayWorkouts = AthleteWorkspaceQuery::schedule(
            $athleteId,
            today()->toDateString(),
            today()->toDateString(),
        )->get();

        return view('livewire.athlete.home', [
            'assignments' => $assignments,
            'programSummaries' => $assignments->map(fn (ProgramAssignment $assignment): array => $this->programSummary($assignment)),
            'todayWorkouts' => $todayWorkouts,
            'latestProgress' => ProgressEntry::query()->where('athlete_id', $athleteId)->latest('logged_on')->first(),
            'days' => $this->calendarDays($monthStart, $monthEnd, $monthSchedule),
            'selectedWorkouts' => $monthSchedule->filter(
                fn (ScheduledWorkout $workout): bool => $workout->scheduled_for->toDateString() === $this->selectedDate
            ),
        ])->layout('layouts.app', ['title' => 'Home']);
    }

    private function programSummary(ProgramAssignment $assignment): array
    {
        $stats = $assignment->completionStats();
        $next = $assignment->scheduledWorkouts
            ->where('scheduled_for', '>=', now())
            ->whereNotIn('status', ['completed', 'missed', 'skipped', 'cancelled'])
            ->sortBy('scheduled_for')
            ->first();

        return [
            'assignment' => $assignment,
            ...$stats,
            'media' => $assignment->scheduledWorkouts->sum(fn (ScheduledWorkout $workout): int => $workout->session->mediaCount()),
            'nextWorkout' => $next,
        ];
    }

    private function calendarDays(Carbon $start, Carbon $end, $schedule): array
    {
        $days = [];
        for ($offset = 0; $offset < $start->dayOfWeekIso - 1; $offset++) {
            $days[] = ['date' => null, 'day' => null, 'label' => null, 'workouts' => 0];
        }
        for ($day = 1; $day <= $end->day; $day++) {
            $date = $start->copy()->day($day)->toDateString();
            $days[] = [
                'date' => $date,
                'day' => $day,
                'label' => Carbon::parse($date)->format('D'),
                'workouts' => $schedule->filter(fn (ScheduledWorkout $workout): bool => $workout->scheduled_for->toDateString() === $date)->count(),
            ];
        }

        return $days;
    }

    private function moveMonth(int $direction): void
    {
        $month = Carbon::parse($this->month.'-01')->addMonths($direction);
        $this->month = $month->format('Y-m');
        $this->selectedDate = $month->toDateString();
    }

    private function validDate(string $date): bool
    {
        try {
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
                && Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d') === $date;
        } catch (Throwable) {
            return false;
        }
    }
}
