@props([
    'form',
    'model',
    'submitAction',
    'submitLabel',
    'addAction',
    'removeAction',
])

<form wire:submit.prevent="{{ $submitAction }}" class="vstack gap-3">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Session title</label>
            <input class="form-control" wire:model="{{ $model }}.title">
            @error($model.'.title') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Focus</label>
            <input class="form-control" wire:model="{{ $model }}.focus">
        </div>
        <div class="col-md-2">
            <label class="form-label">Date</label>
            <input class="form-control" type="date" wire:model="{{ $model }}.scheduledOn">
            @error($model.'.scheduledOn') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Video/image URL</label>
            <input
                class="form-control"
                wire:model.live.debounce.500ms="{{ $model }}.mediaUrl"
                placeholder="YouTube, Vimeo, image, or video URL"
            >
            <div class="tl-form-help mt-2">
                @if($form->mediaUrl)
                    <a href="{{ $form->mediaUrl }}" target="_blank" rel="noopener">
                        <i class="fa-solid fa-eye"></i> Preview media
                    </a>
                @else
                    This appears at the top of the athlete workout.
                @endif
            </div>
            @error($model.'.mediaUrl') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
    </div>

    <div>
        <label class="form-label">Coach notes</label>
        <textarea class="form-control" rows="2" wire:model="{{ $model }}.coachNotes"></textarea>
    </div>

    <div class="d-md-none vstack gap-2">
        @foreach($form->exercises as $index => $exercise)
            <div class="tl-mobile-record" wire:key="{{ $model }}-mobile-exercise-{{ $index }}">
                <div class="d-flex justify-content-between gap-2 align-items-center mb-3">
                    <div class="fw-bold">Exercise {{ $index + 1 }}</div>
                    <button class="btn btn-outline-danger btn-sm" type="button" wire:click="{{ $removeAction }}({{ $index }})">
                        Remove
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">Exercise</label>
                        <input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.name">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Sets</label>
                        <input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.sets">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Reps/time</label>
                        <input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.reps">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Rest</label>
                        <input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.rest">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Load</label>
                        <input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.load">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Note</label>
                        <input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.note">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Exercise demo URL</label>
                        <input
                            class="form-control"
                            wire:model.blur="{{ $model }}.exercises.{{ $index }}.media_url"
                            placeholder="Optional image, YouTube, Vimeo, or video URL"
                        >
                        @if($exercise['media_url'] ?? null)
                            <a
                                class="tl-form-help d-inline-flex mt-2"
                                href="{{ $exercise['media_url'] }}"
                                target="_blank"
                                rel="noopener"
                            >
                                <i class="fa-solid fa-eye"></i> Preview exercise demo
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="tl-table-wrap d-none d-md-block">
        <table class="table tl-table align-middle">
            <thead>
                <tr>
                    <th>Exercise</th>
                    <th>Sets</th>
                    <th>Reps/time</th>
                    <th>Rest</th>
                    <th>Load</th>
                    <th>Note</th>
                    <th>Demo URL</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($form->exercises as $index => $exercise)
                    <tr wire:key="{{ $model }}-desktop-exercise-{{ $index }}">
                        <td><input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.name"></td>
                        <td><input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.sets"></td>
                        <td><input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.reps"></td>
                        <td><input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.rest"></td>
                        <td><input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.load"></td>
                        <td><input class="form-control" wire:model="{{ $model }}.exercises.{{ $index }}.note"></td>
                        <td>
                            <input
                                class="form-control tl-media-url-input"
                                wire:model.blur="{{ $model }}.exercises.{{ $index }}.media_url"
                                placeholder="Optional URL"
                            >
                        </td>
                        <td>
                            <button class="btn btn-outline-danger btn-sm" type="button" wire:click="{{ $removeAction }}({{ $index }})">
                                Remove
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @error($model.'.exercises.*.name')
        <div class="text-danger small">{{ $message }}</div>
    @enderror

    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-tl" type="button" wire:click="{{ $addAction }}">Add exercise</button>
        <button class="btn btn-tl" type="submit">{{ $submitLabel }}</button>
    </div>
</form>
