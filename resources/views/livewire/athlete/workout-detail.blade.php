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

    @if($sessionMedia['type'] !== 'none')
        <x-tl.section-card title="Workout media" subtitle="Video or image assigned by your coach for this session.">
            <x-tl.training-media :media="$sessionMedia" :title="$session->title.' workout media'" />
        </x-tl.section-card>
    @endif

    <x-tl.section-card title="Exercise prescription" subtitle="Open each exercise to see its targets, coach cue, and demonstration.">
        <div class="tl-exercise-list">
            @forelse($session->exercises ?? [] as $index => $exercise)
                @php($media = $exerciseMedia[$index] ?? ['type' => 'none', 'url' => null, 'embedUrl' => null])
                <details class="tl-exercise-card" @if($loop->first) open @endif>
                    <summary>
                        <span class="tl-exercise-index">{{ $index + 1 }}</span>
                        <span>
                            <strong>{{ $exercise['name'] ?? 'Exercise' }}</strong>
                            <small>{{ $exercise['sets'] ?? '-' }} sets · {{ $exercise['reps'] ?? '-' }} reps/time · {{ $exercise['rest'] ?? '-' }} rest</small>
                        </span>
                        @if($media['type'] !== 'none')
                            <span class="tl-badge green"><i class="fa-solid fa-circle-play"></i> Demo</span>
                        @endif
                        <i class="fa-solid fa-chevron-down tl-exercise-chevron"></i>
                    </summary>
                    <div class="tl-exercise-body">
                        <div class="tl-exercise-targets">
                            <div><span>Sets</span><strong>{{ $exercise['sets'] ?? '-' }}</strong></div>
                            <div><span>Reps / time</span><strong>{{ $exercise['reps'] ?? '-' }}</strong></div>
                            <div><span>Rest</span><strong>{{ $exercise['rest'] ?? '-' }}</strong></div>
                            <div><span>Load</span><strong>{{ $exercise['load'] ?? '-' }}</strong></div>
                        </div>
                        @if($exercise['note'] ?? null)
                            <div class="tl-coach-cue"><i class="fa-solid fa-comment-dots"></i><span>{{ $exercise['note'] }}</span></div>
                        @endif
                        <x-tl.training-media :media="$media" :title="($exercise['name'] ?? 'Exercise').' demonstration'" :compact="true" />
                    </div>
                </details>
            @empty
                <div class="tl-mobile-record tl-muted">No exercises listed.</div>
            @endforelse
        </div>
    </x-tl.section-card>

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
