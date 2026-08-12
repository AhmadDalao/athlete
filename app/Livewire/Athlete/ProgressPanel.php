<?php

namespace App\Livewire\Athlete;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Livewire\Concerns\WithTableControls;
use App\Models\PersonalRecord;
use App\Models\ProgressEntry;
use App\Models\ProgressPhoto;
use App\Models\WorkoutLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProgressPanel extends Component
{
    use AuthorizesComponentAccess;
    use WithFileUploads;
    use WithPagination;
    use WithTableControls;

    public string $loggedOn = '';

    public ?float $weight = null;

    public ?int $calories = null;

    public ?int $protein = null;

    public ?int $hydration = null;

    public ?int $sleepQuality = null;

    public ?int $soreness = null;

    public ?int $energy = null;

    public string $notes = '';

    public string $from = '';

    public string $to = '';

    public string $range = '30';

    public $photo = null;

    public string $photoTakenOn = '';

    public string $photoCategory = 'progress';

    public string $photoNotes = '';

    public function mount(): void
    {
        $this->loggedOn = today()->toDateString();
        $this->photoTakenOn = today()->toDateString();
    }

    public function updated($property): void
    {
        if ($property === 'range' && ! in_array($this->range, ['7', '30', '90', '365'], true)) {
            $this->range = '30';
        }

        if (in_array($property, ['from', 'to', 'range'], true)) {
            $this->resetPage();
        }
    }

    public function save(): void
    {
        abort_unless(Auth::user()->can('progress.manage'), 403);
        $data = $this->validate([
            'loggedOn' => ['required', 'date', 'before_or_equal:today'],
            'weight' => ['nullable', 'numeric', 'min:20', 'max:400'],
            'calories' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'protein' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'hydration' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'sleepQuality' => ['nullable', 'integer', 'min:1', 'max:10'],
            'soreness' => ['nullable', 'integer', 'min:1', 'max:10'],
            'energy' => ['nullable', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        ProgressEntry::updateOrCreate(
            [
                'organization_id' => Auth::user()->current_organization_id,
                'athlete_id' => Auth::id(),
                'logged_on' => $data['loggedOn'],
            ],
            [
                'weight' => $data['weight'],
                'calories' => $data['calories'],
                'protein' => $data['protein'],
                'hydration' => $data['hydration'],
                'sleep_quality' => $data['sleepQuality'],
                'soreness' => $data['soreness'],
                'energy' => $data['energy'],
                'notes' => filled($data['notes']) ? trim($data['notes']) : null,
            ]
        );

        $this->reset(['weight', 'calories', 'protein', 'hydration', 'sleepQuality', 'soreness', 'energy', 'notes']);
        $this->loggedOn = today()->toDateString();
        session()->flash('status', 'Progress saved.');
    }

    public function uploadPhoto(): void
    {
        abort_unless(Auth::user()->can('photos.manage'), 403);
        $data = $this->validate([
            'photo' => ['required', 'image', 'max:10240'],
            'photoTakenOn' => ['required', 'date', 'before_or_equal:today'],
            'photoCategory' => ['required', Rule::in(['progress', 'front', 'side', 'back', 'other'])],
            'photoNotes' => ['nullable', 'string', 'max:1000'],
        ]);
        $athleteId = (int) Auth::id();
        $path = $this->photo->store("progress-photos/{$athleteId}", 'public');
        ProgressPhoto::create([
            'organization_id' => Auth::user()->current_organization_id,
            'athlete_id' => $athleteId,
            'uploaded_by' => $athleteId,
            'path' => $path,
            'category' => $data['photoCategory'],
            'visibility' => 'coaches',
            'taken_on' => $data['photoTakenOn'],
            'notes' => filled($data['photoNotes']) ? trim($data['photoNotes']) : null,
        ]);

        $this->reset(['photo', 'photoNotes']);
        $this->photoTakenOn = today()->toDateString();
        session()->flash('status', 'Progress photo uploaded.');
    }

    public function deletePhoto(int $photoId): void
    {
        abort_unless(Auth::user()->can('photos.manage'), 403);
        $photo = ProgressPhoto::query()->where('athlete_id', Auth::id())->findOrFail($photoId);
        Storage::disk('public')->delete(array_filter([$photo->path, $photo->thumbnail_path]));
        $photo->delete();
        session()->flash('status', 'Progress photo deleted.');
    }

    public function render()
    {
        $athleteId = (int) Auth::id();
        $query = $this->filteredQuery()
            ->when($this->search, fn (Builder $query) => $query->where('notes', 'like', "%{$this->search}%"))
            ->orderByDesc('logged_on');
        $rangeStart = today()->subDays(((int) $this->range) - 1)->toDateString();
        $recent = ProgressEntry::query()
            ->where('athlete_id', $athleteId)
            ->whereDate('logged_on', '>=', $rangeStart)
            ->orderBy('logged_on')
            ->limit(31)
            ->get();
        $statsQuery = $this->filteredQuery();
        $workoutLogs = WorkoutLog::query()
            ->where('athlete_id', $athleteId)
            ->whereDate('created_at', '>=', $rangeStart);

        return view('livewire.athlete.progress-panel', [
            'entries' => $this->paginateQuery($query),
            'latest' => ProgressEntry::query()->where('athlete_id', $athleteId)->latest('logged_on')->first(),
            'stats' => [
                'entries' => (clone $statsQuery)->count(),
                'avgWeight' => $this->formatNumber((clone $statsQuery)->whereNotNull('weight')->avg('weight'), 1),
                'avgProtein' => $this->formatNumber((clone $statsQuery)->whereNotNull('protein')->avg('protein'), 0),
                'avgEnergy' => $this->formatNumber((clone $statsQuery)->whereNotNull('energy')->avg('energy'), 1),
            ],
            'performance' => [
                'workouts' => (clone $workoutLogs)->where('status', 'completed')->count(),
                'duration' => (int) (clone $workoutLogs)->sum('duration_minutes'),
                'sets' => Auth::user()->workoutLogs()->whereDate('created_at', '>=', $rangeStart)->withCount(['setLogs as completed_sets_count' => fn (Builder $query) => $query->whereNotNull('completed_at')])->get()->sum('completed_sets_count'),
                'volume' => (float) Auth::user()->workoutLogs()->whereDate('created_at', '>=', $rangeStart)->with('setLogs')->get()->flatMap->setLogs->sum(fn ($set): float => (float) $set->actual_reps * (float) $set->actual_load),
            ],
            'charts' => [
                'weight' => $this->series($recent, 'weight', 'kg'),
                'protein' => $this->series($recent, 'protein', 'g'),
                'energy' => $this->series($recent, 'energy', '/10'),
            ],
            'photos' => ProgressPhoto::query()->where('athlete_id', $athleteId)->latest('taken_on')->limit(8)->get(),
            'records' => PersonalRecord::query()->where('athlete_id', $athleteId)->latest('achieved_on')->limit(8)->get(),
        ])->layout('layouts.app', ['title' => 'Progress']);
    }

    private function filteredQuery(): Builder
    {
        return ProgressEntry::query()
            ->where('athlete_id', Auth::id())
            ->when($this->from !== '', fn (Builder $query) => $query->whereDate('logged_on', '>=', $this->from))
            ->when($this->to !== '', fn (Builder $query) => $query->whereDate('logged_on', '<=', $this->to));
    }

    protected function componentPermissions(): array
    {
        return ['athlete.access'];
    }

    private function formatNumber(mixed $value, int $precision): string
    {
        return $value === null ? '-' : number_format((float) $value, $precision);
    }

    private function series(Collection $entries, string $field, string $unit): array
    {
        $max = max((float) $entries->pluck($field)->filter()->max(), 1);

        return $entries->map(function (ProgressEntry $entry) use ($field, $max, $unit): array {
            $value = $entry->{$field};

            return [
                'date' => $entry->logged_on->format('M j'),
                'value' => $value === null ? '-' : rtrim(rtrim(number_format((float) $value, 1), '0'), '.').$unit,
                'height' => $value ? max(10, min(100, (int) round(((float) $value / $max) * 100))) : 0,
            ];
        })->all();
    }
}
