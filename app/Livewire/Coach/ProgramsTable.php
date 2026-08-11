<?php

namespace App\Livewire\Coach;

use App\Livewire\Concerns\WithTableControls;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Queries\Coach\ProgramQuery;
use App\Services\AuditLogger;
use App\Services\ProgramScheduleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProgramsTable extends Component
{
    use WithPagination;
    use WithTableControls;

    public ?int $athleteId = null;

    public string $title = '';

    public string $goal = '';

    public string $status = 'draft';

    public string $visibility = 'private';

    public ?int $estimatedWeeks = null;

    public string $notes = '';

    public string $listStatus = 'all';

    public function mount(): void
    {
        $requestedAthleteId = request()->integer('athlete');

        if ($requestedAthleteId > 0 && $this->coachCanManageAthlete($requestedAthleteId)) {
            $this->athleteId = $requestedAthleteId;
        }
    }

    public function updatedListStatus(): void
    {
        $this->resetPage();
    }

    public function createProgram(ProgramScheduleService $schedule, AuditLogger $audit)
    {
        $data = $this->validate([
            'athleteId' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:160'],
            'goal' => ['nullable', 'string', 'max:160'],
            'status' => ['required', 'in:draft,active,archived'],
            'visibility' => ['required', 'in:private,organization'],
            'estimatedWeeks' => ['nullable', 'integer', 'min:1', 'max:104'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['athleteId']) {
            abort_unless($this->coachCanManageAthlete((int) $data['athleteId']), 403);
        }

        $coach = Auth::user();
        $program = TrainingProgram::create([
            'organization_id' => $coach->current_organization_id,
            'coach_id' => $coach->id,
            'athlete_id' => null,
            'title' => $data['title'],
            'goal' => $data['goal'] ?: null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?: null,
            'is_template' => true,
            'visibility' => $data['visibility'],
            'estimated_weeks' => $data['estimatedWeeks'],
        ]);

        $program->phases()->create([
            'organization_id' => $program->organization_id,
            'title' => 'Foundation',
            'sort_order' => 1,
            'duration_weeks' => $data['estimatedWeeks'],
        ]);

        if ($data['athleteId'] && $program->organization_id) {
            $schedule->assign($program, User::findOrFail($data['athleteId']), $coach, today()->toDateString());
        }

        $audit->record('program.created', 'training_program', $program->id, "Created reusable program {$program->title}.");
        session()->flash('status', $data['athleteId'] ? 'Program created and assigned.' : 'Reusable program created.');

        return redirect()->route('coach.programs.show', $program);
    }

    public function render(ProgramQuery $programs)
    {
        $coachId = Auth::id();
        $query = $programs->build(Auth::user(), ['search' => $this->search, 'status' => $this->listStatus]);

        return view('livewire.coach.programs-table', [
            'programs' => $this->paginateQuery($this->applySorting($query, 'updated_at')),
            'athletes' => User::query()
                ->whereHas('organizationMemberships', fn (Builder $query) => $query
                    ->where('organization_id', Auth::user()->current_organization_id)
                    ->where('role', 'athlete')
                    ->where('status', 'active'))
                ->whereHas('athleteAssignments', fn (Builder $query) => $query
                    ->where('coach_id', $coachId)
                    ->where('status', 'active'))
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app', ['title' => 'Programs']);
    }

    protected function allowedSortFields(): array
    {
        return ['title', 'status', 'estimated_weeks', 'updated_at'];
    }

    private function coachCanManageAthlete(int $athleteId): bool
    {
        return Auth::user()->coachAssignments()
            ->where('athlete_id', $athleteId)
            ->where('status', 'active')
            ->exists();
    }
}
