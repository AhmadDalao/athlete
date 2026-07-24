<div>
    <x-tl.page-hero
        :eyebrow="$session->scheduled_on->format('M j, Y')"
        :title="$session->title"
        :subtitle="$session->program->title.' · Coach '.$session->program->coach->name"
    >
        @if($session->media_url)
            <x-slot:actions>
                <a class="btn btn-tl" href="{{ $session->media_url }}" target="_blank"><i class="fa-solid fa-play"></i> Open media</a>
            </x-slot:actions>
        @endif
    </x-tl.page-hero>

    @if($media['type'] !== 'none')
        <x-tl.section-card title="Workout media" subtitle="Video or image assigned by your coach for this session.">
            <div class="tl-media-panel">
                @if($media['type'] === 'image')
                    <img src="{{ $media['url'] }}" alt="{{ $session->title }} media">
                @elseif($media['type'] === 'video')
                    <video src="{{ $media['url'] }}" controls playsinline></video>
                @elseif($media['type'] === 'embed' && $media['embedUrl'])
                    <iframe src="{{ $media['embedUrl'] }}" title="{{ $session->title }} media" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                @else
                    <div class="tl-media-link">
                        <i class="fa-solid fa-up-right-from-square"></i>
                        <div>
                            <strong>Open coach media</strong>
                            <p class="tl-muted mb-0">This source cannot be embedded safely, so open it in a new tab.</p>
                        </div>
                        <a class="btn btn-tl ms-auto" href="{{ $media['url'] }}" target="_blank">Open</a>
                    </div>
                @endif
            </div>
        </x-tl.section-card>
    @endif

    <x-tl.table-card
        title="Exercise prescription"
        subtitle="What your coach assigned: sets, reps/time, rest, load, and notes."
        :count="count($session->exercises ?? [])"
        icon="fa-solid fa-dumbbell"
    >
        <div class="d-md-none vstack gap-2">
            @forelse($session->exercises ?? [] as $exercise)
                <div class="tl-mobile-record">
                    <div class="fw-bold">{{ $exercise['name'] ?? 'Exercise' }}</div>
                    <div class="tl-mobile-record-grid mt-3">
                        <div><span class="tl-muted small d-block">Sets</span><span>{{ $exercise['sets'] ?? '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Reps/time</span><span>{{ $exercise['reps'] ?? '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Rest</span><span>{{ $exercise['rest'] ?? '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Load</span><span>{{ $exercise['load'] ?? '-' }}</span></div>
                    </div>
                    @if($exercise['note'] ?? null)
                        <div class="tl-muted small mt-3">{{ $exercise['note'] }}</div>
                    @endif
                </div>
            @empty
                <div class="tl-mobile-record tl-muted">No exercises listed.</div>
            @endforelse
        </div>

        <div class="tl-table-wrap d-none d-md-block"><table class="table tl-table align-middle">
            <thead><tr><th>Exercise</th><th>Sets</th><th>Reps/time</th><th>Rest</th><th>Load</th><th>Note</th></tr></thead>
            <tbody>
            @forelse($session->exercises ?? [] as $exercise)
                <tr><td><strong>{{ $exercise['name'] ?? 'Exercise' }}</strong></td><td>{{ $exercise['sets'] ?? '-' }}</td><td>{{ $exercise['reps'] ?? '-' }}</td><td>{{ $exercise['rest'] ?? '-' }}</td><td>{{ $exercise['load'] ?? '-' }}</td><td>{{ $exercise['note'] ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="6" class="tl-muted">No exercises listed.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-tl.table-card>

    <x-tl.section-card title="Log workout" subtitle="Record what actually happened. Your coach sees this on your profile.">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div></div>
            <span class="tl-badge {{ $log?->status === 'completed' ? 'green' : 'gray' }}">{{ $log?->status ?? 'not started' }}</span>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3"><label class="form-label">Duration minutes</label><input class="form-control" type="number" min="1" wire:model="durationMinutes"></div>
            <div class="col-md-3"><label class="form-label">Session RPE</label><input class="form-control" type="number" min="1" max="10" wire:model="rpe"></div>
            <div class="col-md-6"><label class="form-label">Notes for coach</label><input class="form-control" wire:model="notes" placeholder="How did it feel?"></div>
        </div>

        <div class="d-md-none vstack gap-2 mb-3">
            @forelse($setLogs as $index => $row)
                <div class="tl-mobile-record">
                    <div class="d-flex justify-content-between gap-2 align-items-start">
                        <div>
                            <div class="fw-bold">{{ $row['exercise'] }}</div>
                            <div class="tl-muted small">Set {{ $row['set'] }} · Target {{ $row['target_reps'] ?: '-' }} reps · {{ $row['target_load'] ?: 'bodyweight' }} · Rest {{ $row['target_rest'] ?? '-' }}</div>
                        </div>
                        <input class="form-check-input mt-1" type="checkbox" wire:model="setLogs.{{ $index }}.completed" aria-label="Complete set {{ $row['set'] }}">
                    </div>
                    <div class="tl-mobile-record-grid mt-3">
                        <div class="tl-mobile-field">
                            <label class="form-label small">Actual reps</label>
                            <input class="form-control" wire:model="setLogs.{{ $index }}.actual_reps">
                        </div>
                        <div class="tl-mobile-field">
                            <label class="form-label small">Actual load</label>
                            <input class="form-control" wire:model="setLogs.{{ $index }}.actual_load">
                        </div>
                        <div class="tl-mobile-field">
                            <label class="form-label small">Set RPE</label>
                            <input class="form-control" type="number" min="1" max="10" wire:model="setLogs.{{ $index }}.rpe">
                        </div>
                    </div>
                </div>
            @empty
                <div class="tl-mobile-record tl-muted">No set rows were generated for this workout.</div>
            @endforelse
        </div>

        <div class="tl-table-wrap d-none d-md-block mb-3"><table class="table tl-table align-middle">
            <thead><tr><th>Done</th><th>Exercise</th><th>Set</th><th>Target reps</th><th>Target load</th><th>Rest</th><th>Actual reps</th><th>Actual load</th><th>Set RPE</th></tr></thead>
            <tbody>
            @forelse($setLogs as $index => $row)
                <tr>
                    <td><input class="form-check-input" type="checkbox" wire:model="setLogs.{{ $index }}.completed"></td>
                    <td><strong>{{ $row['exercise'] }}</strong></td>
                    <td>{{ $row['set'] }}</td>
                    <td>{{ $row['target_reps'] ?: '-' }}</td>
                    <td>{{ $row['target_load'] ?: '-' }}</td>
                    <td>{{ $row['target_rest'] ?? '-' }}</td>
                    <td><input class="form-control" wire:model="setLogs.{{ $index }}.actual_reps"></td>
                    <td><input class="form-control" wire:model="setLogs.{{ $index }}.actual_load"></td>
                    <td><input class="form-control" type="number" min="1" max="10" wire:model="setLogs.{{ $index }}.rpe"></td>
                </tr>
            @empty
                <tr><td colspan="9" class="tl-muted">No set rows were generated for this workout.</td></tr>
            @endforelse
            </tbody>
        </table></div>

        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-tl" wire:click="mark('completed')">Save completed</button>
            <button class="btn btn-outline-tl" wire:click="mark('partial')">Save partial</button>
            <button class="btn btn-outline-danger" wire:click="mark('missed')">Mark missed</button>
        </div>
        @if($errors->any())<div class="text-danger small mt-3">{{ $errors->first() }}</div>@endif
    </x-tl.section-card>
</div>
