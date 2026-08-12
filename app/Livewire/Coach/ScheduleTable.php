<?php

namespace App\Livewire\Coach;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Livewire\Concerns\WithTableControls;
use App\Models\ScheduledWorkout;
use App\Models\User;
use App\Queries\Coach\ScheduleQuery;
use App\Services\AuditLogger;
use App\Services\ProgramScheduleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ScheduleTable extends Component
{
    use AuthorizesComponentAccess;
    use WithPagination;
    use WithTableControls;

    public string $from = '';

    public string $to = '';

    public string $status = 'all';

    public ?int $athleteId = null;

    public ?int $rescheduleId = null;

    public string $rescheduleDate = '';

    public function mount(): void
    {
        $this->from = today()->startOfMonth()->toDateString();
        $this->to = today()->addMonth()->endOfMonth()->toDateString();
    }

    public function updated($property): void
    {
        if (in_array($property, ['from', 'to', 'status', 'athleteId'], true)) {
            $this->resetPage();
        }
    }

    public function beginReschedule(int $workoutId): void
    {
        $workout = $this->coachWorkouts()->findOrFail($workoutId);
        $this->rescheduleId = $workout->id;
        $this->rescheduleDate = $workout->scheduled_for->toDateString();
    }

    public function cancelReschedule(): void
    {
        $this->reset(['rescheduleId', 'rescheduleDate']);
    }

    public function saveReschedule(ProgramScheduleService $schedule): void
    {
        $this->validate([
            'rescheduleId' => ['required', 'integer'],
            'rescheduleDate' => ['required', 'date'],
        ]);
        $workout = $this->coachWorkouts()->findOrFail($this->rescheduleId);
        $schedule->reschedule($workout, $this->rescheduleDate);
        $this->cancelReschedule();
        session()->flash('status', 'Workout rescheduled.');
    }

    public function skip(int $workoutId, AuditLogger $audit): void
    {
        $workout = $this->coachWorkouts()->whereDoesntHave('logs')->findOrFail($workoutId);
        $workout->update(['status' => 'skipped']);
        $audit->record('workout.skipped', 'scheduled_workout', $workout->id, "Skipped {$workout->session->title} for {$workout->athlete->name}.");
        session()->flash('status', 'Workout skipped.');
    }

    public function render(ScheduleQuery $schedule)
    {
        $query = $schedule->build(Auth::user(), [
            'search' => $this->search,
            'from' => $this->from,
            'to' => $this->to,
            'status' => $this->status,
            'athlete_id' => $this->athleteId,
        ]);

        return view('livewire.coach.schedule-table', [
            'workouts' => $this->paginateQuery($this->applySorting($query, 'scheduled_for')),
            'athletes' => User::query()
                ->whereHas('athleteAssignments', fn (Builder $query) => $query
                    ->where('coach_id', Auth::id())
                    ->where('status', 'active'))
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app', ['title' => 'Schedule']);
    }

    protected function allowedSortFields(): array
    {
        return ['scheduled_for', 'status', 'updated_at'];
    }

    protected function componentPermissions(): array
    {
        return ['schedule.manage'];
    }

    private function coachWorkouts(): Builder
    {
        return ScheduledWorkout::query()->where('coach_id', Auth::id());
    }
}
