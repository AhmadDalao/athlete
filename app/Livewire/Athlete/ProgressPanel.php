<?php

namespace App\Livewire\Athlete;

use App\Livewire\Concerns\WithTableControls;
use App\Models\ProgressEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProgressPanel extends Component
{
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

    public function mount(): void
    {
        $this->loggedOn = today()->toDateString();
    }

    public function updated($property): void
    {
        if (in_array($property, ['from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function save(): void
    {
        $this->validate([
            'loggedOn' => ['required', 'date'],
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
            ['athlete_id' => Auth::id(), 'logged_on' => $this->loggedOn],
            [
                'weight' => $this->weight,
                'calories' => $this->calories,
                'protein' => $this->protein,
                'hydration' => $this->hydration,
                'sleep_quality' => $this->sleepQuality,
                'soreness' => $this->soreness,
                'energy' => $this->energy,
                'notes' => $this->notes ?: null,
            ]
        );

        $this->reset(['weight', 'calories', 'protein', 'hydration', 'sleepQuality', 'soreness', 'energy', 'notes']);
        $this->loggedOn = today()->toDateString();
        session()->flash('status', 'Progress saved.');
    }

    public function render()
    {
        $query = $this->filteredQuery()
            ->when($this->search, fn ($query) => $query->where('notes', 'like', "%{$this->search}%"))
            ->orderByDesc('logged_on');

        $recent = ProgressEntry::where('athlete_id', Auth::id())
            ->orderByDesc('logged_on')
            ->limit(7)
            ->get()
            ->reverse()
            ->values();

        $statsQuery = $this->filteredQuery();

        return view('livewire.athlete.progress-panel', [
            'entries' => $this->paginateQuery($query),
            'latest' => ProgressEntry::where('athlete_id', Auth::id())->latest('logged_on')->first(),
            'stats' => [
                'entries' => (clone $statsQuery)->count(),
                'avgWeight' => $this->formatNumber((clone $statsQuery)->whereNotNull('weight')->avg('weight'), 1),
                'avgProtein' => $this->formatNumber((clone $statsQuery)->whereNotNull('protein')->avg('protein'), 0),
                'avgEnergy' => $this->formatNumber((clone $statsQuery)->whereNotNull('energy')->avg('energy'), 1),
            ],
            'charts' => [
                'weight' => $this->series($recent, 'weight', 'kg'),
                'protein' => $this->series($recent, 'protein', 'g'),
                'energy' => $this->series($recent, 'energy', '/10'),
            ],
        ])->layout('layouts.app', ['title' => 'Progress']);
    }

    private function filteredQuery(): Builder
    {
        return ProgressEntry::where('athlete_id', Auth::id())
            ->when($this->from !== '', fn (Builder $query) => $query->whereDate('logged_on', '>=', $this->from))
            ->when($this->to !== '', fn (Builder $query) => $query->whereDate('logged_on', '<=', $this->to));
    }

    private function formatNumber(mixed $value, int $precision): string
    {
        if ($value === null) {
            return '-';
        }

        return number_format((float) $value, $precision);
    }

    private function series(Collection $entries, string $field, string $unit): array
    {
        $max = (float) $entries->pluck($field)->filter()->max();
        $max = $max > 0 ? $max : 1;

        return $entries->map(function (ProgressEntry $entry) use ($field, $max, $unit): array {
            $value = $entry->{$field};
            $height = $value ? max(10, min(100, (int) round(((float) $value / $max) * 100))) : 0;

            return [
                'date' => $entry->logged_on->format('M j'),
                'value' => $value === null ? '-' : rtrim(rtrim(number_format((float) $value, 1), '0'), '.').$unit,
                'height' => $height,
            ];
        })->all();
    }
}
