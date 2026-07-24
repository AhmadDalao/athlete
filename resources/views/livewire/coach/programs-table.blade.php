<div>
    <x-tl.page-hero
        eyebrow="Training"
        title="Programs"
        subtitle="Create athlete programs, then open each one to build the exact sessions, exercises, media, and schedule."
    />

    <x-tl.section-card title="Create program" subtitle="Start with the athlete, goal, and date range. Sessions are built after the program exists.">
        <form class="row g-3 align-items-end" wire:submit.prevent="createProgram">
            <div class="col-md-3"><label class="form-label">Athlete</label><select class="form-select" wire:model="athleteId"><option value="">Select athlete</option>@foreach($athletes as $athlete)<option value="{{ $athlete->id }}">{{ $athlete->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Title</label><input class="form-control" wire:model="title"></div>
            <div class="col-md-2"><label class="form-label">Goal</label><input class="form-control" wire:model="goal"></div>
            <div class="col-md-2"><label class="form-label">Start</label><input class="form-control" type="date" wire:model="startsOn"></div>
            <div class="col-md-2"><label class="form-label">End</label><input class="form-control" type="date" wire:model="endsOn"></div>
            <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" rows="2" wire:model="notes"></textarea></div>
            <div class="col-12"><button class="btn btn-tl" type="submit">Create program</button></div>
        </form>
    </x-tl.section-card>

    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search program or athlete'])

    <x-tl.table-card
        title="Program table"
        subtitle="Open a program to add sessions, edit exercises, or archive completed work."
        :count="$programs->total()"
        icon="fa-solid fa-dumbbell"
    >
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Program</th><th>Athlete</th><th>Status</th><th>Dates</th><th>Sessions</th><th>Progress</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($programs as $program)
                @php($completion = $program->completionStats())
                <tr>
                    <td><strong>{{ $program->title }}</strong><br><span class="tl-muted">{{ $program->goal }}</span></td>
                    <td><a href="{{ route('coach.athletes.show', $program->athlete) }}">{{ $program->athlete->name }}</a></td>
                    <td><span class="tl-badge green">{{ $program->status }}</span></td>
                    <td>{{ $program->starts_on?->format('Y-m-d') ?: '-' }} → {{ $program->ends_on?->format('Y-m-d') ?: '-' }}</td>
                    <td>{{ $program->sessions->count() }}</td>
                    <td>
                        <div class="tl-progress-cell">
                            <strong>{{ $completion['percent'] }}%</strong>
                            <span>{{ $completion['completed'] }}/{{ $completion['total'] }} complete</span>
                        </div>
                    </td>
                    <td><a class="btn btn-outline-tl btn-sm" href="{{ route('coach.programs.show', $program) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="tl-muted">No programs yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="mt-3">{{ $programs->links() }}</div>
    </x-tl.table-card>
</div>
