<?php

namespace App\Livewire\Coach;

use App\Livewire\Forms\TrainingProgramForm;
use App\Livewire\Forms\TrainingSessionForm;
use App\Models\TrainingProgram;
use App\Services\TrainingProgramManager;
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

    public function mount(TrainingProgram $program): void
    {
        abort_unless($program->coach_id === Auth::id() || Auth::user()->isAdmin(), 403);

        $this->program = $program;
        $this->programForm->fillFrom($program);
        $this->sessionForm->initialize();
        $this->editSessionForm->initialize();
    }

    public function updateProgram(TrainingProgramManager $manager): void
    {
        $this->program = $manager->updateProgram($this->program, $this->programForm->payload());
        $this->programForm->fillFrom($this->program);
        session()->flash('status', 'Program updated.');
    }

    public function archiveProgram(TrainingProgramManager $manager)
    {
        $manager->archiveProgram($this->program);
        session()->flash('status', 'Program archived.');

        return redirect()->route('coach.programs');
    }

    public function createSession(TrainingProgramManager $manager): void
    {
        $manager->createSession($this->program, $this->sessionForm->payload());
        $this->sessionForm->clear();
        session()->flash('status', 'Session added.');
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

    public function updateSession(TrainingProgramManager $manager): void
    {
        abort_unless($this->editingSessionId !== null, 404);

        $manager->updateSession(
            $this->program,
            $this->editingSessionId,
            $this->editSessionForm->payload(),
        );

        $this->cancelEditSession();
        session()->flash('status', 'Session updated.');
    }

    public function deleteSession(int $sessionId, TrainingProgramManager $manager): void
    {
        $result = $manager->removeSession($this->program, $sessionId);

        session()->flash(
            'status',
            $result === 'cancelled'
                ? 'Session has athlete logs, so it was cancelled instead of deleted.'
                : 'Session deleted.',
        );
    }

    public function render()
    {
        return view('livewire.coach.program-detail', [
            'sessions' => $this->program->sessions()->with('logs')->paginate(10),
        ])->layout('layouts.app', ['title' => $this->program->title]);
    }
}
