<?php

namespace App\Livewire\Athlete;

use App\Livewire\Concerns\WithTableControls;
use App\Models\ProgressEntry;
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

    public function mount(): void
    {
        $this->loggedOn = today()->toDateString();
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

        session()->flash('status', 'Progress saved.');
    }

    public function render()
    {
        $query = ProgressEntry::where('athlete_id', Auth::id())
            ->when($this->search, fn ($query) => $query->where('notes', 'like', "%{$this->search}%"))
            ->orderByDesc('logged_on');

        return view('livewire.athlete.progress-panel', [
            'entries' => $this->paginateQuery($query),
            'latest' => ProgressEntry::where('athlete_id', Auth::id())->latest('logged_on')->first(),
        ])->layout('layouts.app', ['title' => 'Progress']);
    }
}
