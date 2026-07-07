<div>
    <div class="tl-hero"><div class="tl-eyebrow">Training</div><h2 class="h1 fw-bold">Programs</h2><p class="tl-muted mb-0">Create athlete programs and open them to build sessions.</p></div>
    <div class="tl-panel">
        <form class="row g-3 align-items-end" wire:submit.prevent="createProgram">
            <div class="col-md-3"><label class="form-label">Athlete</label><select class="form-select" wire:model="athleteId"><option value="">Select athlete</option>@foreach($athletes as $athlete)<option value="{{ $athlete->id }}">{{ $athlete->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Title</label><input class="form-control" wire:model="title"></div>
            <div class="col-md-2"><label class="form-label">Goal</label><input class="form-control" wire:model="goal"></div>
            <div class="col-md-2"><label class="form-label">Start</label><input class="form-control" type="date" wire:model="startsOn"></div>
            <div class="col-md-2"><label class="form-label">End</label><input class="form-control" type="date" wire:model="endsOn"></div>
            <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" rows="2" wire:model="notes"></textarea></div>
            <div class="col-12"><button class="btn btn-tl" type="submit">Create program</button></div>
        </form>
    </div>
    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search program or athlete'])
    <div class="tl-panel">
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Program</th><th>Athlete</th><th>Status</th><th>Dates</th><th>Sessions</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($programs as $program)
                <tr><td><strong>{{ $program->title }}</strong><br><span class="tl-muted">{{ $program->goal }}</span></td><td>{{ $program->athlete->name }}</td><td><span class="tl-badge green">{{ $program->status }}</span></td><td>{{ $program->starts_on?->format('Y-m-d') ?: '-' }} → {{ $program->ends_on?->format('Y-m-d') ?: '-' }}</td><td>{{ $program->sessions->count() }}</td><td><a class="btn btn-outline-tl btn-sm" href="{{ route('coach.programs.show', $program) }}">Open</a></td></tr>
            @empty
                <tr><td colspan="6" class="tl-muted">No programs yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="mt-3">{{ $programs->links() }}</div>
    </div>
</div>
