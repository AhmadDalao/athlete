@props([
    'form',
    'model',
    'submitAction',
    'submitLabel',
    'addAction',
    'removeAction',
    'phases' => collect(),
    'libraryExercises' => collect(),
])

<form wire:submit.prevent="{{ $submitAction }}" class="vstack gap-3">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Session title</label>
            <input class="form-control" wire:model="{{ $model }}.title" placeholder="Lower-body strength">
            @error($model.'.title') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Phase</label>
            <select class="form-select" wire:model="{{ $model }}.phaseId">
                <option value="">Ungrouped</option>
                @foreach($phases as $phase)<option value="{{ $phase->id }}">{{ $phase->title }}</option>@endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label">Day offset</label>
            <input class="form-control" type="number" min="0" wire:model="{{ $model }}.dayOffset">
            <div class="tl-form-help mt-1">Day 0 is assignment start.</div>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Estimated minutes</label>
            <input class="form-control" type="number" min="1" wire:model="{{ $model }}.estimatedMinutes">
        </div>
        <div class="col-md-3">
            <label class="form-label">Focus</label>
            <input class="form-control" wire:model="{{ $model }}.focus" placeholder="Squat and hinge">
        </div>
        <div class="col-md-2">
            <label class="form-label">Order</label>
            <input class="form-control" type="number" min="0" wire:model="{{ $model }}.sortOrder">
        </div>
        <div class="col-md-7">
            <label class="form-label">Session media URL</label>
            <input class="form-control" wire:model.blur="{{ $model }}.mediaUrl" placeholder="YouTube, Vimeo, image, or direct video URL">
            @error($model.'.mediaUrl') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
    </div>

    <div>
        <label class="form-label">Coach notes</label>
        <textarea class="form-control" rows="2" wire:model="{{ $model }}.coachNotes" placeholder="Instructions the athlete sees before starting."></textarea>
    </div>

    <div class="tl-table-wrap">
        <table class="table tl-table align-middle tl-builder-table">
            <thead>
                <tr><th>#</th><th>Library / exercise</th><th>Section</th><th>Sets</th><th>Reps</th><th>Load</th><th>Rest</th><th>Media and notes</th><th></th></tr>
            </thead>
            <tbody>
            @foreach($form->exercises as $index => $exercise)
                <tr wire:key="{{ $model }}-exercise-{{ $index }}">
                    <td><strong>{{ $index + 1 }}</strong></td>
                    <td class="tl-builder-name-cell">
                        @if($libraryExercises->isNotEmpty())
                            <select class="form-select form-select-sm mb-2" wire:change="useLibraryExercise('{{ $model }}', {{ $index }}, $event.target.value)">
                                <option value="">Load from library...</option>
                                @foreach($libraryExercises as $libraryExercise)
                                    <option value="{{ $libraryExercise->id }}">{{ $libraryExercise->name }}</option>
                                @endforeach
                            </select>
                        @endif
                        <input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.name" placeholder="Exercise name">
                        <input class="form-control form-control-sm mt-2" wire:model="{{ $model }}.exercises.{{ $index }}.movement_type" placeholder="Movement type">
                    </td>
                    <td>
                        <input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.section" placeholder="Main work">
                        <input class="form-control form-control-sm mt-2" wire:model="{{ $model }}.exercises.{{ $index }}.superset_label" placeholder="Superset A">
                    </td>
                    <td><input class="form-control tl-number-input" type="number" min="1" wire:model="{{ $model }}.exercises.{{ $index }}.sets"></td>
                    <td><input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.reps" placeholder="5 or 30 sec"></td>
                    <td>
                        <input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.load" placeholder="80">
                        <input class="form-control form-control-sm mt-2" wire:model="{{ $model }}.exercises.{{ $index }}.unit" placeholder="kg">
                    </td>
                    <td><input class="form-control tl-number-input" type="number" min="0" wire:model="{{ $model }}.exercises.{{ $index }}.rest_seconds" placeholder="sec"></td>
                    <td class="tl-builder-notes-cell">
                        <input class="form-control" wire:model.blur="{{ $model }}.exercises.{{ $index }}.media_url" placeholder="Media URL">
                        <textarea class="form-control form-control-sm mt-2" rows="2" wire:model="{{ $model }}.exercises.{{ $index }}.note" placeholder="Technique note"></textarea>
                    </td>
                    <td><button class="btn btn-outline-danger btn-sm" type="button" wire:click="{{ $removeAction }}({{ $index }})" aria-label="Remove exercise"><i class="fa-solid fa-trash"></i></button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @error($model.'.exercises.*.name')<div class="text-danger small">{{ $message }}</div>@enderror
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-tl" type="button" wire:click="{{ $addAction }}"><i class="fa-solid fa-plus"></i> Add exercise</button>
        <button class="btn btn-tl" type="submit"><i class="fa-solid fa-floppy-disk"></i> {{ $submitLabel }}</button>
    </div>
</form>
