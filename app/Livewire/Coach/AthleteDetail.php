<?php

namespace App\Livewire\Coach;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Models\CoachNote;
use App\Models\ProgressPhoto;
use App\Models\User;
use App\Queries\Coach\AthleteProfileQuery;
use App\Services\AthleteProgressSummaryService;
use App\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class AthleteDetail extends Component
{
    use AuthorizesComponentAccess;
    use WithFileUploads;
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public User $athlete;

    public string $tab = 'programs';

    public string $search = '';

    public string $perPage = '10';

    public array $pageSizeOptions = ['10', '25', '50', '100', 'all'];

    public string $status = 'all';

    public string $category = 'all';

    public string $recordType = 'all';

    public string $from = '';

    public string $to = '';

    public string $noteBody = '';

    public string $noteVisibility = 'private';

    public bool $notePinned = false;

    public $photo = null;

    public string $photoTakenOn = '';

    public string $photoCategory = 'progress';

    public string $photoVisibility = 'coaches';

    public string $photoNotes = '';

    public function mount(User $athlete): void
    {
        abort_unless(AthleteProfileQuery::isAssignedTo($athlete, (int) Auth::id()), 403);

        $this->athlete = $athlete;
        $this->photoTakenOn = today()->toDateString();
        $requestedTab = (string) request()->query('tab', 'programs');
        $this->tab = in_array($requestedTab, $this->tabs(), true) ? $requestedTab : 'programs';
    }

    public function selectTab(string $tab): void
    {
        abort_unless(in_array($tab, $this->tabs(), true), 422);
        $this->tab = $tab;
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'perPage', 'status', 'category', 'recordType', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function addNote(AuditLogger $audit): void
    {
        $this->authorizeAthlete();
        abort_unless(Auth::user()->can('athletes.notes'), 403);

        $data = $this->validate([
            'noteBody' => ['required', 'string', 'max:5000'],
            'noteVisibility' => ['required', Rule::in(['private', 'organization'])],
            'notePinned' => ['boolean'],
        ]);

        $note = CoachNote::create([
            'organization_id' => Auth::user()->current_organization_id,
            'coach_id' => Auth::id(),
            'athlete_id' => $this->athlete->id,
            'body' => $data['noteBody'],
            'visibility' => $data['noteVisibility'],
            'is_pinned' => $data['notePinned'],
        ]);

        $audit->record('coach_note.created', 'coach_note', $note->id, "Added a coach note for {$this->athlete->name}.");
        $this->reset(['noteBody', 'notePinned']);
        session()->flash('status', 'Private coach note saved.');
    }

    public function toggleNotePinned(int $noteId, AuditLogger $audit): void
    {
        $this->authorizeAthlete();
        abort_unless(Auth::user()->can('athletes.notes'), 403);
        $note = $this->ownedNote($noteId);
        $note->update(['is_pinned' => ! $note->is_pinned]);
        $audit->record('coach_note.pinned', 'coach_note', $note->id, "Changed pin state for {$this->athlete->name}'s note.");
    }

    public function deleteNote(int $noteId, AuditLogger $audit): void
    {
        $this->authorizeAthlete();
        abort_unless(Auth::user()->can('athletes.notes'), 403);
        $note = $this->ownedNote($noteId);
        $note->delete();
        $audit->record('coach_note.deleted', 'coach_note', $noteId, "Deleted a private coach note for {$this->athlete->name}.");
    }

    public function uploadPhoto(AuditLogger $audit): void
    {
        $this->authorizeAthlete();
        abort_unless(Auth::user()->can('progress.review'), 403);

        $data = $this->validate([
            'photo' => ['required', 'image', 'max:10240'],
            'photoTakenOn' => ['required', 'date', 'before_or_equal:today'],
            'photoCategory' => ['required', Rule::in(['progress', 'front', 'side', 'back', 'other'])],
            'photoVisibility' => ['required', Rule::in(['private', 'coaches', 'athlete'])],
            'photoNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $path = $this->photo->store("progress-photos/{$this->athlete->id}", 'public');
        $photo = ProgressPhoto::create([
            'organization_id' => Auth::user()->current_organization_id,
            'athlete_id' => $this->athlete->id,
            'uploaded_by' => Auth::id(),
            'path' => $path,
            'category' => $data['photoCategory'],
            'visibility' => $data['photoVisibility'],
            'taken_on' => $data['photoTakenOn'],
            'notes' => $data['photoNotes'] ?: null,
        ]);

        $audit->record('progress_photo.uploaded', 'progress_photo', $photo->id, "Uploaded a progress photo for {$this->athlete->name}.");
        $this->reset(['photo', 'photoNotes']);
        $this->photoTakenOn = today()->toDateString();
        session()->flash('status', 'Progress photo uploaded.');
    }

    public function deletePhoto(int $photoId, AuditLogger $audit): void
    {
        $this->authorizeAthlete();
        abort_unless(Auth::user()->can('progress.review'), 403);
        $photo = ProgressPhoto::query()
            ->where('athlete_id', $this->athlete->id)
            ->where('uploaded_by', Auth::id())
            ->findOrFail($photoId);

        Storage::disk('public')->delete(array_filter([$photo->path, $photo->thumbnail_path]));
        $photo->delete();
        $audit->record('progress_photo.deleted', 'progress_photo', $photoId, "Deleted a coach-uploaded progress photo for {$this->athlete->name}.");
    }

    public function render(AthleteProgressSummaryService $progressSummary)
    {
        $this->authorizeAthlete();
        $coachId = (int) Auth::id();
        $canReviewProgress = Auth::user()->can('progress.review');
        $canManageNotes = Auth::user()->can('athletes.notes');
        $query = $this->activeQuery($coachId);
        $records = $this->paginate($query);

        return view('livewire.coach.athlete-detail', [
            'assignment' => $this->athlete->athleteAssignments()->with('coach')->where('coach_id', $coachId)->first(),
            'records' => $records,
            'progressSummary' => $canReviewProgress
                ? $progressSummary->forCoach(
                    Auth::user(),
                    $this->athlete,
                    $this->from ?: null,
                    $this->to ?: null,
                )
                : null,
            'counts' => [
                'programs' => AthleteProfileQuery::assignments($coachId, $this->athlete->id)->count(),
                'schedule' => AthleteProfileQuery::schedule($coachId, $this->athlete->id)->count(),
                'workouts' => AthleteProfileQuery::workoutLogs($coachId, $this->athlete->id)->count(),
                'progress' => $canReviewProgress ? AthleteProfileQuery::progress($this->athlete->id)->count() : null,
                'photos' => $canReviewProgress ? AthleteProfileQuery::photos($this->athlete->id)->count() : null,
                'records' => $canReviewProgress ? AthleteProfileQuery::records($this->athlete->id)->count() : null,
                'notes' => $canManageNotes ? AthleteProfileQuery::notes($coachId, $this->athlete->id)->count() : null,
            ],
            'availableTabs' => $this->tabs(),
        ])->layout('layouts.app', ['title' => $this->athlete->name]);
    }

    private function activeQuery(int $coachId): Builder
    {
        return match ($this->tab) {
            'schedule' => AthleteProfileQuery::schedule($coachId, $this->athlete->id, $this->search, $this->status, $this->from ?: null, $this->to ?: null),
            'workouts' => AthleteProfileQuery::workoutLogs($coachId, $this->athlete->id, $this->search, $this->status, $this->from ?: null, $this->to ?: null),
            'progress' => AthleteProfileQuery::progress($this->athlete->id, $this->search, $this->from ?: null, $this->to ?: null),
            'photos' => AthleteProfileQuery::photos($this->athlete->id, $this->search, $this->category, $this->from ?: null, $this->to ?: null),
            'records' => AthleteProfileQuery::records($this->athlete->id, $this->search, $this->recordType),
            'notes' => AthleteProfileQuery::notes($coachId, $this->athlete->id, $this->search),
            default => AthleteProfileQuery::assignments($coachId, $this->athlete->id, $this->search, $this->status),
        };
    }

    private function paginate(Builder $query): LengthAwarePaginator
    {
        $perPage = $this->perPage === 'all' ? max($query->count(), 1) : (int) $this->perPage;

        return $query->paginate($perPage);
    }

    private function ownedNote(int $noteId): CoachNote
    {
        return CoachNote::query()
            ->where('athlete_id', $this->athlete->id)
            ->where('coach_id', Auth::id())
            ->findOrFail($noteId);
    }

    private function authorizeAthlete(): void
    {
        abort_unless(AthleteProfileQuery::isAssignedTo($this->athlete, (int) Auth::id()), 403);
    }

    protected function componentPermissions(): array
    {
        return ['athletes.view'];
    }

    /** @return list<string> */
    private function tabs(): array
    {
        $tabs = ['programs', 'schedule', 'workouts'];

        if (Auth::user()?->can('progress.review')) {
            array_push($tabs, 'progress', 'photos', 'records');
        }

        if (Auth::user()?->can('athletes.notes')) {
            $tabs[] = 'notes';
        }

        return $tabs;
    }
}
