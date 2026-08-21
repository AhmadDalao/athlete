<div>
    @php
        $assignment = $workout->assignment;
        $timezone = $assignment->timezone ?: 'UTC';
        $prescription = $session->prescribedExercises->isNotEmpty()
            ? $session->prescribedExercises->map(fn ($exercise) => [
                'name' => $exercise->name,
                'section' => $exercise->section,
                'sets' => $exercise->target_sets,
                'reps' => $exercise->target_reps,
                'load' => trim(($exercise->target_load ?? '').' '.($exercise->unit ?? '')),
                'rest' => $exercise->rest_seconds ? $exercise->rest_seconds.' sec' : null,
                'note' => $exercise->notes,
            ])
            : collect($session->exercises ?? []);
    @endphp

    <x-tl.page-hero :eyebrow="$workout->scheduled_for->timezone($timezone)->format('M j, Y')" :title="$session->title" :subtitle="$assignment->program->title.' · Coach '.$assignment->program->coach->name">
        <x-slot:actions>
            <a class="btn btn-outline-tl" href="{{ route('app.programs.show', $assignment) }}"><i class="fa-solid fa-arrow-left"></i> Program</a>
            @if($session->media_url)<a class="btn btn-tl" href="{{ $session->media_url }}" target="_blank" rel="noopener"><i class="fa-solid fa-play"></i> Open media</a>@endif
        </x-slot:actions>
    </x-tl.page-hero>

    @if($sessionMedia['type'] !== 'none')
        <x-tl.section-card title="Workout media" subtitle="Demonstration assigned by your coach.">
            <x-tl.training-media :media="$sessionMedia" :title="$session->title.' workout media'" />
        </x-tl.section-card>
    @endif

    <x-tl.section-card title="Prescription" subtitle="Targets are guidance. Record what you actually complete below.">
        <div class="tl-exercise-list">
            @forelse($prescription as $index => $exercise)
                @php($media = $exerciseMedia[$index] ?? ['type' => 'none', 'url' => null, 'embedUrl' => null])
                <details class="tl-exercise-card" @if($loop->first) open @endif>
                    <summary>
                        <span class="tl-exercise-index">{{ $index + 1 }}</span>
                        <span><strong>{{ $exercise['name'] ?? 'Exercise' }}</strong><small>{{ $exercise['sets'] ?? '-' }} sets · {{ $exercise['reps'] ?? '-' }} reps/time · {{ $exercise['rest'] ?? 'No rest target' }}</small></span>
                        @if($media['type'] !== 'none')<span class="tl-badge green"><i class="fa-solid fa-circle-play"></i> Demo</span>@endif
                        <i class="fa-solid fa-chevron-down tl-exercise-chevron"></i>
                    </summary>
                    <div class="tl-exercise-body">
                        <div class="tl-exercise-targets">
                            <div><span>Section</span><strong>{{ $exercise['section'] ?? 'Main' }}</strong></div>
                            <div><span>Sets</span><strong>{{ $exercise['sets'] ?? '-' }}</strong></div>
                            <div><span>Reps / time</span><strong>{{ $exercise['reps'] ?? '-' }}</strong></div>
                            <div><span>Load</span><strong>{{ $exercise['load'] ?? '-' }}</strong></div>
                        </div>
                        @if($exercise['note'] ?? null)<div class="tl-coach-cue"><i class="fa-solid fa-comment-dots"></i><span>{{ $exercise['note'] }}</span></div>@endif
                        <x-tl.training-media :media="$media" :title="($exercise['name'] ?? 'Exercise').' demonstration'" :compact="true" />
                    </div>
                </details>
            @empty<div class="tl-empty-state compact"><span>No exercises have been prescribed.</span></div>@endforelse
        </div>
    </x-tl.section-card>

    <x-tl.section-card title="Execution log" subtitle="Complete every prescribed set before marking the workout complete. Partial saves are always allowed.">
        @if($errors->any())
            <div class="alert alert-danger tl-alert"><i class="fa-solid fa-triangle-exclamation"></i><div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>
        @endif
        <div class="d-flex justify-content-end mb-3"><span class="tl-badge {{ $workout->status === 'completed' ? 'green' : 'gray' }}">{{ $workout->status }}</span></div>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><label class="form-label">Duration minutes</label><input class="form-control" type="number" min="1" max="600" wire:model="durationMinutes"></div>
            <div class="col-md-3"><label class="form-label">Session RPE</label><input class="form-control" type="number" min="1" max="10" wire:model="rpe"></div>
            <div class="col-md-6"><label class="form-label">Journal note</label><textarea class="form-control" rows="2" wire:model="notes" placeholder="How did the workout feel?"></textarea></div>
        </div>

        <div class="d-md-none vstack gap-2 mb-4">
            @foreach($setLogs as $index => $row)
                <div class="tl-mobile-record" wire:key="set-mobile-{{ $index }}">
                    <div class="d-flex justify-content-between gap-2 align-items-start">
                        <div><strong>{{ $row['exercise'] }}</strong><div class="tl-muted small">Set {{ $row['set'] }} · {{ $row['target_reps'] ?: '-' }} reps · {{ $row['target_load'] ?: 'Bodyweight' }} · {{ $row['target_rest_seconds'] ?: '0' }} sec rest</div></div>
                        <input class="form-check-input mt-1" type="checkbox" wire:model="setLogs.{{ $index }}.completed" aria-label="Complete {{ $row['exercise'] }} set {{ $row['set'] }}">
                    </div>
                    <div class="tl-set-input-grid mt-3">
                        <label><span>Reps</span><input class="form-control" type="number" min="0" step="0.01" wire:model="setLogs.{{ $index }}.actual_reps"></label>
                        <label><span>Load</span><input class="form-control" type="number" min="0" step="0.01" wire:model="setLogs.{{ $index }}.actual_load"></label>
                        <label><span>RPE</span><input class="form-control" type="number" min="1" max="10" wire:model="setLogs.{{ $index }}.rpe"></label>
                    </div>
                    <label class="mt-3"><span class="form-label small">Set note</span><input class="form-control" wire:model="setLogs.{{ $index }}.notes"></label>
                </div>
            @endforeach
        </div>

        <div class="tl-table-wrap d-none d-md-block mb-4"><table class="table tl-table align-middle"><thead><tr><th>Done</th><th>Exercise</th><th>Set</th><th>Target reps</th><th>Target load</th><th>Rest</th><th>Actual reps</th><th>Actual load</th><th>RPE</th><th>Note</th></tr></thead><tbody>
            @foreach($setLogs as $index => $row)<tr wire:key="set-table-{{ $index }}"><td><input class="form-check-input" type="checkbox" wire:model="setLogs.{{ $index }}.completed"></td><td><strong>{{ $row['exercise'] }}</strong></td><td>{{ $row['set'] }}</td><td>{{ $row['target_reps'] ?: '-' }}</td><td>{{ $row['target_load'] ?: '-' }}</td><td>{{ $row['target_rest_seconds'] ?: '0' }} sec</td><td><input class="form-control form-control-sm" type="number" min="0" step="0.01" wire:model="setLogs.{{ $index }}.actual_reps"></td><td><input class="form-control form-control-sm" type="number" min="0" step="0.01" wire:model="setLogs.{{ $index }}.actual_load"></td><td><input class="form-control form-control-sm" type="number" min="1" max="10" wire:model="setLogs.{{ $index }}.rpe"></td><td><input class="form-control form-control-sm" wire:model="setLogs.{{ $index }}.notes"></td></tr>@endforeach
        </tbody></table></div>

        <div class="tl-workout-actions">
            <button class="btn btn-outline-tl" type="button" wire:click="mark('skipped')" wire:loading.attr="disabled">Skip</button>
            <button class="btn btn-outline-danger" type="button" wire:click="mark('missed')" wire:loading.attr="disabled">Mark missed</button>
            <button class="btn btn-outline-tl" type="button" wire:click="mark('partial')" wire:loading.attr="disabled">Save partial</button>
            <button class="btn btn-tl" type="button" wire:click="mark('completed')" wire:confirm="Confirm that every prescribed set is complete and finish today’s training?" wire:loading.attr="disabled"><span wire:loading.remove wire:target="mark">Confirm workout complete</span><span wire:loading wire:target="mark">Saving…</span></button>
        </div>
    </x-tl.section-card>
</div>
