<?php

namespace App\Livewire\Coach;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Livewire\Concerns\WithTableControls;
use App\Models\Exercise;
use App\Queries\Coach\ExerciseQuery;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ExerciseLibraryTable extends Component
{
    use AuthorizesComponentAccess;
    use WithPagination;
    use WithTableControls;

    public ?int $editingId = null;

    public string $name = '';

    public string $section = '';

    public string $movementType = '';

    public string $instructions = '';

    public ?int $defaultSets = null;

    public string $defaultReps = '';

    public string $defaultLoad = '';

    public string $unit = 'kg';

    public ?int $defaultRestSeconds = null;

    public string $mediaUrl = '';

    public bool $isShared = false;

    public string $status = 'all';

    public string $ownership = 'all';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedOwnership(): void
    {
        $this->resetPage();
    }

    public function save(AuditLogger $audit): void
    {
        $data = $this->validate($this->rules());
        $exercise = $this->editingId
            ? Exercise::query()->where('owner_id', Auth::id())->findOrFail($this->editingId)
            : new Exercise;

        $exercise->fill([
            'organization_id' => Auth::user()->current_organization_id,
            'owner_id' => Auth::id(),
            'name' => $data['name'],
            'section' => $data['section'] ?: null,
            'movement_type' => $data['movementType'] ?: null,
            'instructions' => $data['instructions'] ?: null,
            'default_sets' => $data['defaultSets'],
            'default_reps' => $data['defaultReps'] ?: null,
            'default_load' => $data['defaultLoad'] ?: null,
            'unit' => $data['unit'] ?: null,
            'default_rest_seconds' => $data['defaultRestSeconds'],
            'media_url' => $data['mediaUrl'] ?: null,
            'is_shared' => $data['isShared'],
            'status' => $exercise->status ?: 'active',
        ])->save();

        $audit->record(
            $this->editingId ? 'exercise.updated' : 'exercise.created',
            'exercise',
            $exercise->id,
            ($this->editingId ? 'Updated' : 'Created')." exercise {$exercise->name}.",
        );
        $this->resetForm();
        session()->flash('status', 'Exercise saved.');
    }

    public function edit(int $exerciseId): void
    {
        $exercise = Exercise::query()->where('owner_id', Auth::id())->findOrFail($exerciseId);
        $this->editingId = $exercise->id;
        $this->name = $exercise->name;
        $this->section = $exercise->section ?: '';
        $this->movementType = $exercise->movement_type ?: '';
        $this->instructions = $exercise->instructions ?: '';
        $this->defaultSets = $exercise->default_sets;
        $this->defaultReps = $exercise->default_reps ?: '';
        $this->defaultLoad = $exercise->default_load ?: '';
        $this->unit = $exercise->unit ?: '';
        $this->defaultRestSeconds = $exercise->default_rest_seconds;
        $this->mediaUrl = $exercise->media_url ?: '';
        $this->isShared = $exercise->is_shared;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function toggleStatus(int $exerciseId, AuditLogger $audit): void
    {
        $exercise = Exercise::query()->where('owner_id', Auth::id())->findOrFail($exerciseId);
        $exercise->update(['status' => $exercise->status === 'active' ? 'archived' : 'active']);
        $audit->record('exercise.status_changed', 'exercise', $exercise->id, "Changed {$exercise->name} to {$exercise->status}.");
    }

    public function render(ExerciseQuery $exercises)
    {
        $query = $exercises->build(Auth::user(), [
            'search' => $this->search,
            'status' => $this->status,
            'ownership' => $this->ownership,
        ]);

        return view('livewire.coach.exercise-library-table', [
            'exercises' => $this->paginateQuery($this->applySorting($query, 'name')),
        ])->layout('layouts.app', ['title' => 'Exercise library']);
    }

    protected function allowedSortFields(): array
    {
        return ['name', 'section', 'movement_type', 'status', 'updated_at'];
    }

    protected function componentPermissions(): array
    {
        return ['exercises.manage'];
    }

    /** @return array<string, array<int, string>> */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'section' => ['nullable', 'string', 'max:80'],
            'movementType' => ['nullable', 'string', 'max:80'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'defaultSets' => ['nullable', 'integer', 'min:1', 'max:50'],
            'defaultReps' => ['nullable', 'string', 'max:40'],
            'defaultLoad' => ['nullable', 'string', 'max:80'],
            'unit' => ['nullable', 'string', 'max:30'],
            'defaultRestSeconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'mediaUrl' => ['nullable', 'url', 'max:255'],
            'isShared' => ['boolean'],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'section', 'movementType', 'instructions', 'defaultSets',
            'defaultReps', 'defaultLoad', 'defaultRestSeconds', 'mediaUrl', 'isShared',
        ]);
        $this->unit = 'kg';
        $this->resetValidation();
    }
}
