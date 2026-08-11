<div>
    <x-tl.page-hero eyebrow="Coaching tools" title="Exercise library" subtitle="Save trusted prescriptions once, then load them directly into any program session.">
        <x-slot:actions><a class="btn btn-outline-tl" href="{{ route('coach.programs') }}"><i class="fa-solid fa-dumbbell"></i> Program library</a></x-slot:actions>
    </x-tl.page-hero>

    <x-tl.section-card :eyebrow="$editingId ? 'Editing exercise' : 'New exercise'" :title="$editingId ? 'Update library item' : 'Create library item'" subtitle="Defaults speed up program building; every prescription remains editable inside a session.">
        <form class="row g-3 align-items-end" wire:submit.prevent="save">
            <div class="col-md-4"><label class="form-label">Exercise name</label><input class="form-control" wire:model="name" placeholder="Trap bar deadlift"></div>
            <div class="col-md-2"><label class="form-label">Section</label><input class="form-control" wire:model="section" placeholder="Main work"></div>
            <div class="col-md-2"><label class="form-label">Movement type</label><input class="form-control" wire:model="movementType" placeholder="Strength"></div>
            <div class="col-4 col-md-1"><label class="form-label">Sets</label><input class="form-control" type="number" wire:model="defaultSets"></div>
            <div class="col-4 col-md-1"><label class="form-label">Reps</label><input class="form-control" wire:model="defaultReps"></div>
            <div class="col-4 col-md-2"><label class="form-label">Rest sec</label><input class="form-control" type="number" wire:model="defaultRestSeconds"></div>
            <div class="col-md-2"><label class="form-label">Default load</label><input class="form-control" wire:model="defaultLoad" placeholder="80"></div>
            <div class="col-md-2"><label class="form-label">Unit</label><input class="form-control" wire:model="unit" placeholder="kg"></div>
            <div class="col-md-5"><label class="form-label">Media URL</label><input class="form-control" wire:model="mediaUrl" placeholder="YouTube, Vimeo, image, or direct video"></div>
            <div class="col-md-3"><div class="form-check form-switch tl-switch"><input id="exercise-shared" class="form-check-input" type="checkbox" wire:model="isShared"><label class="form-check-label" for="exercise-shared">Share with organization coaches</label></div></div>
            <div class="col-md-9"><label class="form-label">Instructions</label><textarea class="form-control" rows="2" wire:model="instructions" placeholder="Technique cues and safety notes"></textarea></div>
            <div class="col-md-3"><div class="d-flex gap-2"><button class="btn btn-tl flex-grow-1" type="submit"><i class="fa-solid fa-floppy-disk"></i> {{ $editingId ? 'Update' : 'Save' }}</button>@if($editingId)<button class="btn btn-outline-tl" type="button" wire:click="cancelEdit">Cancel</button>@endif</div></div>
        </form>
        @if($errors->any())<div class="text-danger small mt-3">{{ $errors->first() }}</div>@endif
    </x-tl.section-card>

    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search exercise, section, or movement type'])

    <x-tl.table-card title="Exercise directory" subtitle="Shared records are readable by organization coaches; only the owner can edit or archive them." :count="$exercises->total()" icon="fa-solid fa-list-check">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" wire:model.live="status"><option value="all">All statuses</option><option value="active">Active</option><option value="archived">Archived</option></select></div>
            <div class="col-md-3"><label class="form-label">Ownership</label><select class="form-select" wire:model.live="ownership"><option value="all">Mine and shared</option><option value="mine">Created by me</option><option value="shared">Shared library</option></select></div>
            <div class="col-md-auto ms-md-auto"><a class="btn btn-outline-tl" href="{{ route('coach.exercises.export', ['search' => $search, 'status' => $status, 'ownership' => $ownership]) }}"><i class="fa-solid fa-download"></i> Export CSV</a></div>
        </div>
        <div class="tl-table-wrap"><table class="table tl-table align-middle"><thead><tr><th><button class="tl-sort-button" wire:click="sortBy('name')">Exercise <i class="fa-solid fa-sort"></i></button></th><th>Section</th><th>Prescription</th><th>Rest</th><th>Owner</th><th>Media</th><th>Status</th><th>Actions</th></tr></thead><tbody>
            @forelse($exercises as $exercise)<tr wire:key="exercise-{{ $exercise->id }}"><td><strong>{{ $exercise->name }}</strong><span class="d-block tl-muted">{{ $exercise->movement_type ?: 'Unclassified' }}</span></td><td>{{ $exercise->section ?: '-' }}</td><td>{{ $exercise->default_sets ?: '-' }} × {{ $exercise->default_reps ?: '-' }} @if($exercise->default_load)<span class="d-block tl-muted">{{ $exercise->default_load }} {{ $exercise->unit }}</span>@endif</td><td>{{ $exercise->default_rest_seconds !== null ? $exercise->default_rest_seconds.' sec' : '-' }}</td><td>{{ $exercise->owner?->name ?: 'Organization' }}<span class="d-block tl-muted">{{ $exercise->is_shared ? 'Shared' : 'Private' }}</span></td><td>@if($exercise->media_url)<a href="{{ $exercise->media_url }}" target="_blank" rel="noopener">Open</a>@else<span class="tl-muted">None</span>@endif</td><td><span class="tl-badge {{ $exercise->status === 'active' ? 'green' : 'gray' }}">{{ $exercise->status }}</span></td><td>@if($exercise->owner_id === auth()->id())<div class="d-flex gap-2"><button class="btn btn-outline-tl btn-sm" wire:click="edit({{ $exercise->id }})">Edit</button><button class="btn btn-outline-danger btn-sm" wire:click="toggleStatus({{ $exercise->id }})">{{ $exercise->status === 'active' ? 'Archive' : 'Restore' }}</button></div>@else<span class="tl-muted">Read only</span>@endif</td></tr>
            @empty<tr><td colspan="8" class="tl-muted">No exercises match the current filters.</td></tr>@endforelse
        </tbody></table></div><div class="mt-3">{{ $exercises->links() }}</div>
    </x-tl.table-card>
</div>
