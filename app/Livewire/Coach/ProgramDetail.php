<?php

namespace App\Livewire\Coach;

use App\Livewire\Forms\TrainingProgramForm;
use App\Livewire\Forms\TrainingSessionForm;
use App\Models\Exercise;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ProgramScheduleService;
use App\Services\TrainingProgramManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProgramDetail extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public TrainingProgram $program;

    public TrainingProgramForm $programForm;

    public TrainingSessionForm $sessionForm;

    public TrainingSessionForm $editSessionForm;

    public ?int $editingSessionId = null;

    public string $phaseTitle = '';

    public string $phaseDescription = '';

    public ?int $phaseDurationWeeks = null;

    public ?int $assignmentAthleteId = null;

    public string $assignmentStartsOn = '';

    public string $assignmentNotes = '';

    public function mount(TrainingProgram $program): void
    {
        abort_unless($program->coach_id === Auth::id() || Auth::user()->isAdmin(), 403);

        $this->program = $program;
        $this->programForm->fillFrom($program);
        $this->sessionForm->initialize();
        $this->editSessionForm->initialize();
        $this->assignmentStartsOn = today()->toDateString();
    }

    public function updateProgram(TrainingProgramManager $manager): void
    {
        $this->program = $manager->updateProgram($this->program, $this->programForm->payload());
        $this->programForm->fillFrom($this->program);
        session()->flash('status', 'Program template updated. Existing athlete schedule dates were not changed.');
    }

    public function archiveProgram(TrainingProgramManager $manager)
    {
        $manager->archiveProgram($this->program);
        session()->flash('status', 'Program archived.');

        return redirect()->route('coach.programs');
    }

    public function createPhase(AuditLogger $audit): void
    {
        $data = $this->validate([
            'phaseTitle' => ['required', 'string', 'max:120'],
            'phaseDescription' => ['nullable', 'string', 'max:1000'],
            'phaseDurationWeeks' => ['nullable', 'integer', 'min:1', 'max:52'],
        ]);

        $phase = $this->program->phases()->create([
            'organization_id' => $this->program->organization_id,
            'title' => $data['phaseTitle'],
            'description' => $data['phaseDescription'] ?: null,
            'duration_weeks' => $data['phaseDurationWeeks'],
            'sort_order' => ((int) $this->program->phases()->max('sort_order')) + 1,
        ]);
        $audit->record('program_phase.created', 'program_phase', $phase->id, "Created phase {$phase->title}.");
        $this->reset(['phaseTitle', 'phaseDescription', 'phaseDurationWeeks']);
        session()->flash('status', 'Phase added.');
    }

    public function deletePhase(int $phaseId, AuditLogger $audit): void
    {
        $phase = $this->program->phases()->whereKey($phaseId)->firstOrFail();
        $phase->sessions()->update(['program_phase_id' => null]);
        $title = $phase->title;
        $phase->delete();
        $audit->record('program_phase.deleted', 'program_phase', $phaseId, "Deleted empty phase {$title}.");
        session()->flash('status', 'Phase removed. Its sessions remain ungrouped.');
    }

    public function createSession(TrainingProgramManager $manager): void
    {
        $manager->createSession($this->program, $this->sessionForm->payload());
        $this->sessionForm->clear();
        session()->flash('status', 'Template session added and active assignments synchronized.');
    }

    public function addSessionExercise(): void
    {
        $this->sessionForm->addExercise();
    }

    public function removeSessionExercise(int $index): void
    {
        $this->sessionForm->removeExercise($index);
    }

    public function startEditSession(int $sessionId): void
    {
        $session = $this->program->sessions()->whereKey($sessionId)->firstOrFail();
        $this->editingSessionId = $session->id;
        $this->editSessionForm->fillFrom($session);
    }

    public function cancelEditSession(): void
    {
        $this->editingSessionId = null;
        $this->editSessionForm->clear();
    }

    public function addEditSessionExercise(): void
    {
        $this->editSessionForm->addExercise();
    }

    public function removeEditSessionExercise(int $index): void
    {
        $this->editSessionForm->removeExercise($index);
    }

    public function useLibraryExercise(string $form, int $index, int $exerciseId): void
    {
        abort_unless(in_array($form, ['sessionForm', 'editSessionForm'], true), 422);
        $exercise = Exercise::query()
            ->where('status', 'active')
            ->where(fn (Builder $query) => $query->where('owner_id', Auth::id())->orWhere('is_shared', true))
            ->findOrFail($exerciseId);

        $this->{$form}->exercises[$index] = [
            'exercise_id' => $exercise->id,
            'section' => $exercise->section ?: 'Main work',
            'superset_label' => '',
            'name' => $exercise->name,
            'sets' => $exercise->default_sets ?: 1,
            'reps' => $exercise->default_reps ?: '',
            'rest_seconds' => $exercise->default_rest_seconds,
            'load' => $exercise->default_load ?: '',
            'unit' => $exercise->unit ?: '',
            'note' => $exercise->instructions ?: '',
            'media_url' => $exercise->media_url ?: '',
            'movement_type' => $exercise->movement_type ?: '',
        ];
    }

    public function updateSession(TrainingProgramManager $manager): void
    {
        abort_unless($this->editingSessionId !== null, 404);
        $manager->updateSession($this->program, $this->editingSessionId, $this->editSessionForm->payload());
        $this->cancelEditSession();
        session()->flash('status', 'Template session and open athlete schedules updated.');
    }

    public function deleteSession(int $sessionId, TrainingProgramManager $manager): void
    {
        $result = $manager->removeSession($this->program, $sessionId);
        session()->flash('status', $result === 'cancelled'
            ? 'Session has execution data, so it was cancelled instead of deleted.'
            : 'Session deleted.');
    }

    public function assignProgram(ProgramScheduleService $schedule): void
    {
        $data = $this->validate([
            'assignmentAthleteId' => ['required', 'exists:users,id'],
            'assignmentStartsOn' => ['required', 'date'],
            'assignmentNotes' => ['nullable', 'string', 'max:1000'],
        ]);
        $athlete = $this->availableAthletes()->findOrFail($data['assignmentAthleteId']);
        $schedule->assign($this->program, $athlete, Auth::user(), $data['assignmentStartsOn'], $data['assignmentNotes']);
        $this->reset(['assignmentAthleteId', 'assignmentNotes']);
        $this->assignmentStartsOn = today()->toDateString();
        session()->flash('status', 'Program assigned and schedule generated.');
    }

    public function setAssignmentStatus(int $assignmentId, string $status, ProgramScheduleService $schedule): void
    {
        $assignment = $this->program->assignments()->whereKey($assignmentId)->firstOrFail();
        $schedule->setAssignmentStatus($assignment, $status);
        session()->flash('status', 'Assignment status updated.');
    }

    public function render()
    {
        return view('livewire.coach.program-detail', [
            'sessions' => $this->program->sessions()
                ->with(['phase', 'logs', 'prescribedExercises'])
                ->orderBy('day_offset')
                ->orderBy('sort_order')
                ->paginate(10, ['*'], 'sessionsPage'),
            'phases' => $this->program->phases()->withCount('sessions')->get(),
            'assignments' => $this->program->assignments()
                ->with('athlete')
                ->withCount(['scheduledWorkouts', 'workoutLogs'])
                ->paginate(10, ['*'], 'assignmentsPage'),
            'athletes' => $this->availableAthletes()->orderBy('name')->get(),
            'libraryExercises' => Exercise::query()
                ->where('status', 'active')
                ->where(fn (Builder $query) => $query->where('owner_id', Auth::id())->orWhere('is_shared', true))
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app', ['title' => $this->program->title]);
    }

    private function availableAthletes(): Builder
    {
        return User::query()
            ->whereHas('athleteAssignments', fn (Builder $query) => $query
                ->where('coach_id', Auth::id())
                ->where('status', 'active'));
    }
}
