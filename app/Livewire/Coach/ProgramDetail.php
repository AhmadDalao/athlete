<?php

namespace App\Livewire\Coach;

use App\Models\AuditLog;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProgramDetail extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public TrainingProgram $program;

    public string $programTitle = '';

    public string $programGoal = '';

    public string $programStatus = 'active';

    public ?string $programStartsOn = null;

    public ?string $programEndsOn = null;

    public string $programNotes = '';

    public string $title = '';

    public string $focus = '';

    public string $scheduledOn = '';

    public string $coachNotes = '';

    public string $mediaUrl = '';

    public array $exercises = [
        ['name' => '', 'sets' => '', 'reps' => '', 'rest' => '', 'load' => '', 'note' => '', 'media_url' => ''],
    ];

    public ?int $editingSessionId = null;

    public string $editTitle = '';

    public string $editFocus = '';

    public string $editScheduledOn = '';

    public string $editCoachNotes = '';

    public string $editMediaUrl = '';

    public array $editExercises = [
        ['name' => '', 'sets' => '', 'reps' => '', 'rest' => '', 'load' => '', 'note' => '', 'media_url' => ''],
    ];

    public function mount(TrainingProgram $program): void
    {
        abort_unless($program->coach_id === Auth::id() || Auth::user()->isAdmin(), 403);
        $this->program = $program;
        $this->programTitle = $program->title;
        $this->programGoal = $program->goal ?: '';
        $this->programStatus = $program->status;
        $this->programStartsOn = $program->starts_on?->toDateString();
        $this->programEndsOn = $program->ends_on?->toDateString();
        $this->programNotes = $program->notes ?: '';
        $this->scheduledOn = today()->toDateString();
    }

    public function addExercise(): void
    {
        $this->exercises[] = $this->emptyExercise();
    }

    public function removeExercise(int $index): void
    {
        unset($this->exercises[$index]);
        $this->exercises = array_values($this->exercises);
    }

    public function updateProgram(): void
    {
        $data = $this->validate([
            'programTitle' => ['required', 'string', 'max:160'],
            'programGoal' => ['nullable', 'string', 'max:160'],
            'programStatus' => ['required', 'in:draft,active,archived'],
            'programStartsOn' => ['nullable', 'date'],
            'programEndsOn' => ['nullable', 'date', 'after_or_equal:programStartsOn'],
            'programNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->program->update([
            'title' => $data['programTitle'],
            'goal' => $data['programGoal'] ?: null,
            'status' => $data['programStatus'],
            'starts_on' => $data['programStartsOn'],
            'ends_on' => $data['programEndsOn'],
            'notes' => $data['programNotes'] ?: null,
        ]);

        $this->program->refresh();
        $this->writeAudit('program.updated', $this->program->id, "Updated program {$this->program->title}.");
        session()->flash('status', 'Program updated.');
    }

    public function archiveProgram()
    {
        $this->program->update(['status' => 'archived']);
        $this->program->refresh();
        $this->programStatus = 'archived';
        $this->writeAudit('program.archived', $this->program->id, "Archived program {$this->program->title}.");
        session()->flash('status', 'Program archived.');

        return redirect()->route('coach.programs');
    }

    public function createSession(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'focus' => ['nullable', 'string', 'max:120'],
            'scheduledOn' => ['required', 'date'],
            'coachNotes' => ['nullable', 'string', 'max:1000'],
            'mediaUrl' => ['nullable', 'url', 'max:255'],
            'exercises.*.name' => ['required', 'string', 'max:160'],
            'exercises.*.sets' => ['nullable', 'string', 'max:20'],
            'exercises.*.reps' => ['nullable', 'string', 'max:40'],
            'exercises.*.rest' => ['nullable', 'string', 'max:40'],
            'exercises.*.load' => ['nullable', 'string', 'max:80'],
            'exercises.*.note' => ['nullable', 'string', 'max:200'],
            'exercises.*.media_url' => ['nullable', 'url', 'max:255'],
        ]);

        $session = TrainingSession::create([
            'training_program_id' => $this->program->id,
            'title' => $this->title,
            'focus' => $this->focus ?: null,
            'scheduled_on' => $this->scheduledOn,
            'status' => 'scheduled',
            'coach_notes' => $this->coachNotes ?: null,
            'media_url' => $this->mediaUrl ?: null,
            'exercises' => $this->normalizeExercises($this->exercises),
        ]);

        $this->reset(['title', 'focus', 'coachNotes', 'mediaUrl']);
        $this->scheduledOn = today()->toDateString();
        $this->exercises = [$this->emptyExercise()];
        $this->writeAudit('session.created', $session->id, "Created session {$session->title}.");
        session()->flash('status', 'Session added.');
    }

    public function startEditSession(int $sessionId): void
    {
        $session = $this->program->sessions()->whereKey($sessionId)->firstOrFail();

        $this->editingSessionId = $session->id;
        $this->editTitle = $session->title;
        $this->editFocus = $session->focus ?: '';
        $this->editScheduledOn = $session->scheduled_on->toDateString();
        $this->editCoachNotes = $session->coach_notes ?: '';
        $this->editMediaUrl = $session->media_url ?: '';
        $this->editExercises = collect($session->exercises ?: [])
            ->map(fn (array $exercise): array => [
                'name' => $exercise['name'] ?? '',
                'sets' => (string) ($exercise['sets'] ?? ''),
                'reps' => (string) ($exercise['reps'] ?? ''),
                'rest' => (string) ($exercise['rest'] ?? ''),
                'load' => (string) ($exercise['load'] ?? ''),
                'note' => (string) ($exercise['note'] ?? ''),
                'media_url' => (string) ($exercise['media_url'] ?? ''),
            ])
            ->values()
            ->all() ?: [$this->emptyExercise()];
    }

    public function cancelEditSession(): void
    {
        $this->editingSessionId = null;
        $this->editTitle = '';
        $this->editFocus = '';
        $this->editScheduledOn = '';
        $this->editCoachNotes = '';
        $this->editMediaUrl = '';
        $this->editExercises = [$this->emptyExercise()];
    }

    public function addEditExercise(): void
    {
        $this->editExercises[] = $this->emptyExercise();
    }

    public function removeEditExercise(int $index): void
    {
        unset($this->editExercises[$index]);
        $this->editExercises = array_values($this->editExercises);
    }

    public function updateSession(): void
    {
        $data = $this->validate([
            'editingSessionId' => ['required', 'integer'],
            'editTitle' => ['required', 'string', 'max:160'],
            'editFocus' => ['nullable', 'string', 'max:120'],
            'editScheduledOn' => ['required', 'date'],
            'editCoachNotes' => ['nullable', 'string', 'max:1000'],
            'editMediaUrl' => ['nullable', 'url', 'max:255'],
            'editExercises.*.name' => ['required', 'string', 'max:160'],
            'editExercises.*.sets' => ['nullable', 'string', 'max:20'],
            'editExercises.*.reps' => ['nullable', 'string', 'max:40'],
            'editExercises.*.rest' => ['nullable', 'string', 'max:40'],
            'editExercises.*.load' => ['nullable', 'string', 'max:80'],
            'editExercises.*.note' => ['nullable', 'string', 'max:200'],
            'editExercises.*.media_url' => ['nullable', 'url', 'max:255'],
        ]);

        $session = $this->program->sessions()->whereKey($data['editingSessionId'])->firstOrFail();
        $session->update([
            'title' => $data['editTitle'],
            'focus' => $data['editFocus'] ?: null,
            'scheduled_on' => $data['editScheduledOn'],
            'coach_notes' => $data['editCoachNotes'] ?: null,
            'media_url' => $data['editMediaUrl'] ?: null,
            'exercises' => $this->normalizeExercises($this->editExercises),
        ]);

        $this->writeAudit('session.updated', $session->id, "Updated session {$session->title}.");
        $this->cancelEditSession();
        session()->flash('status', 'Session updated.');
    }

    public function deleteSession(int $sessionId): void
    {
        $session = $this->program->sessions()->withCount('logs')->whereKey($sessionId)->firstOrFail();

        if ($session->logs_count > 0) {
            $session->update(['status' => 'cancelled']);
            $this->writeAudit('session.cancelled', $session->id, "Cancelled logged session {$session->title}.");
            session()->flash('status', 'Session has athlete logs, so it was cancelled instead of deleted.');

            return;
        }

        $title = $session->title;
        $session->delete();
        $this->writeAudit('session.deleted', $sessionId, "Deleted empty session {$title}.");
        session()->flash('status', 'Session deleted.');
    }

    public function render()
    {
        return view('livewire.coach.program-detail', [
            'sessions' => $this->program->sessions()->with('logs')->paginate(10),
        ])->layout('layouts.app', ['title' => $this->program->title]);
    }

    private function writeAudit(string $action, int $entityId, string $summary): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity' => str_starts_with($action, 'program.') ? 'training_program' : 'training_session',
            'entity_id' => $entityId,
            'summary' => $summary,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $exercises
     * @return array<int, array<string, string>>
     */
    private function normalizeExercises(array $exercises): array
    {
        return collect($exercises)
            ->filter(fn (array $exercise): bool => filled($exercise['name'] ?? null))
            ->map(fn (array $exercise): array => [
                'name' => trim((string) ($exercise['name'] ?? '')),
                'sets' => trim((string) ($exercise['sets'] ?? '')),
                'reps' => trim((string) ($exercise['reps'] ?? '')),
                'rest' => trim((string) ($exercise['rest'] ?? '')),
                'load' => trim((string) ($exercise['load'] ?? '')),
                'note' => trim((string) ($exercise['note'] ?? '')),
                'media_url' => trim((string) ($exercise['media_url'] ?? '')),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{name: string, sets: string, reps: string, rest: string, load: string, note: string, media_url: string}
     */
    private function emptyExercise(): array
    {
        return [
            'name' => '',
            'sets' => '',
            'reps' => '',
            'rest' => '',
            'load' => '',
            'note' => '',
            'media_url' => '',
        ];
    }
}
