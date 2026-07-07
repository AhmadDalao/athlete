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
        <h3 class="h5">Log workout</h3>
        <textarea class="form-control mb-3" rows="4" placeholder="Notes for your coach" wire:model="notes"></textarea>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-tl" wire:click="mark('completed')">Completed</button>
            <button class="btn btn-outline-tl" wire:click="mark('partial')">Partial</button>
            <button class="btn btn-outline-danger" wire:click="mark('missed')">Missed</button>
        </div>
        <p class="tl-muted mt-3 mb-0">Current status: <span class="tl-badge {{ $log?->status === 'completed' ? 'green' : 'gray' }}">{{ $log?->status ?? 'not started' }}</span></p>
    </div>
</div>
