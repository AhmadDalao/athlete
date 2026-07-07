<div>
    <div class="tl-hero">
        <div class="tl-eyebrow">{{ $session->scheduled_on->format('M j, Y') }}</div>
        <h2 class="h1 fw-bold">{{ $session->title }}</h2>
        <p class="tl-muted mb-0">{{ $session->program->title }} · Coach {{ $session->program->coach->name }}</p>
        @if($session->media_url)<a class="btn btn-tl mt-3" href="{{ $session->media_url }}" target="_blank"><i class="fa-solid fa-play"></i> Open media</a>@endif
    </div>

    <div class="tl-panel">
        <h3 class="h5">Exercises</h3>
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Exercise</th><th>Sets</th><th>Reps/time</th><th>Rest</th><th>Load</th><th>Note</th></tr></thead>
            <tbody>
            @forelse($session->exercises ?? [] as $exercise)
                <tr><td><strong>{{ $exercise['name'] ?? 'Exercise' }}</strong></td><td>{{ $exercise['sets'] ?? '-' }}</td><td>{{ $exercise['reps'] ?? '-' }}</td><td>{{ $exercise['rest'] ?? '-' }}</td><td>{{ $exercise['load'] ?? '-' }}</td><td>{{ $exercise['note'] ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="6" class="tl-muted">No exercises listed.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="tl-panel">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div>
                <h3 class="h5 mb-1">Log workout</h3>
                <p class="tl-muted mb-0">Record what actually happened. Your coach sees this on your profile.</p>
            </div>
            <span class="tl-badge {{ $log?->status === 'completed' ? 'green' : 'gray' }}">{{ $log?->status ?? 'not started' }}</span>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3"><label class="form-label">Duration minutes</label><input class="form-control" type="number" min="1" wire:model="durationMinutes"></div>
            <div class="col-md-3"><label class="form-label">Session RPE</label><input class="form-control" type="number" min="1" max="10" wire:model="rpe"></div>
            <div class="col-md-6"><label class="form-label">Notes for coach</label><input class="form-control" wire:model="notes" placeholder="How did it feel?"></div>
        </div>

        <div class="tl-table-wrap mb-3"><table class="table tl-table align-middle">
            <thead><tr><th>Done</th><th>Exercise</th><th>Set</th><th>Target reps</th><th>Target load</th><th>Actual reps</th><th>Actual load</th><th>Set RPE</th></tr></thead>
            <tbody>
            @forelse($setLogs as $index => $row)
                <tr>
                    <td><input class="form-check-input" type="checkbox" wire:model="setLogs.{{ $index }}.completed"></td>
                    <td><strong>{{ $row['exercise'] }}</strong></td>
                    <td>{{ $row['set'] }}</td>
                    <td>{{ $row['target_reps'] ?: '-' }}</td>
                    <td>{{ $row['target_load'] ?: '-' }}</td>
                    <td><input class="form-control" wire:model="setLogs.{{ $index }}.actual_reps"></td>
                    <td><input class="form-control" wire:model="setLogs.{{ $index }}.actual_load"></td>
                    <td><input class="form-control" type="number" min="1" max="10" wire:model="setLogs.{{ $index }}.rpe"></td>
                </tr>
            @empty
                <tr><td colspan="8" class="tl-muted">No set rows were generated for this workout.</td></tr>
            @endforelse
            </tbody>
        </table></div>

        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-tl" wire:click="mark('completed')">Save completed</button>
            <button class="btn btn-outline-tl" wire:click="mark('partial')">Save partial</button>
            <button class="btn btn-outline-danger" wire:click="mark('missed')">Mark missed</button>
        </div>
        @if($errors->any())<div class="text-danger small mt-3">{{ $errors->first() }}</div>@endif
    </div>
</div>
