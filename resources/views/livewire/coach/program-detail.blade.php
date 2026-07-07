<div>
    <div class="tl-hero"><div class="tl-eyebrow">Program builder</div><h2 class="h1 fw-bold">{{ $program->title }}</h2><p class="tl-muted mb-0">{{ $program->athlete->name }} · {{ $program->goal }}</p></div>
    <div class="tl-panel">
        <h3 class="h5">Add session</h3>
        <form wire:submit.prevent="createSession" class="vstack gap-3">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Session title</label><input class="form-control" wire:model="title"></div>
                <div class="col-md-3"><label class="form-label">Focus</label><input class="form-control" wire:model="focus"></div>
                <div class="col-md-2"><label class="form-label">Date</label><input class="form-control" type="date" wire:model="scheduledOn"></div>
                <div class="col-md-3"><label class="form-label">Video/image URL</label><input class="form-control" wire:model="mediaUrl"></div>
            </div>
            <textarea class="form-control" rows="2" placeholder="Coach notes" wire:model="coachNotes"></textarea>
            <div class="tl-table-wrap"><table class="table tl-table align-middle">
                <thead><tr><th>Exercise</th><th>Sets</th><th>Reps/time</th><th>Rest</th><th>Load</th><th>Note</th><th></th></tr></thead>
                <tbody>
                @foreach($exercises as $index => $exercise)
                    <tr>
                        <td><input class="form-control" wire:model="exercises.{{ $index }}.name"></td>
                        <td><input class="form-control" wire:model="exercises.{{ $index }}.sets"></td>
                        <td><input class="form-control" wire:model="exercises.{{ $index }}.reps"></td>
                        <td><input class="form-control" wire:model="exercises.{{ $index }}.rest"></td>
                        <td><input class="form-control" wire:model="exercises.{{ $index }}.load"></td>
                        <td><input class="form-control" wire:model="exercises.{{ $index }}.note"></td>
                        <td><button class="btn btn-outline-danger btn-sm" type="button" wire:click="removeExercise({{ $index }})">Remove</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="d-flex gap-2"><button class="btn btn-outline-tl" type="button" wire:click="addExercise">Add exercise</button><button class="btn btn-tl" type="submit">Save session</button></div>
        </form>
    </div>
    <div class="tl-panel">
        <h3 class="h5">Sessions</h3>
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Date</th><th>Session</th><th>Focus</th><th>Exercises</th><th>Media</th><th>Logs</th></tr></thead>
            <tbody>@forelse($sessions as $session)<tr><td>{{ $session->scheduled_on->format('Y-m-d') }}</td><td>{{ $session->title }}</td><td>{{ $session->focus }}</td><td>{{ $session->exerciseSummary() }}</td><td>@if($session->media_url)<a href="{{ $session->media_url }}" target="_blank">Open</a>@else - @endif</td><td>{{ $session->logs->count() }}</td></tr>@empty<tr><td colspan="6" class="tl-muted">No sessions yet.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="mt-3">{{ $sessions->links() }}</div>
    </div>
</div>
