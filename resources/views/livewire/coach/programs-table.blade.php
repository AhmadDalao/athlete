<div>
    <x-tl.page-hero eyebrow="Program library" title="Reusable programs" subtitle="Build a template once, assign it to multiple athletes, and keep each athlete's schedule independent.">
        <x-slot:actions>
            <a class="btn btn-outline-tl" href="{{ route('coach.exercises') }}"><i class="fa-solid fa-list-check"></i> Exercise library</a>
            <a class="btn btn-outline-tl" href="{{ route('coach.schedule') }}"><i class="fa-solid fa-calendar-days"></i> Schedule</a>
        </x-slot:actions>
    </x-tl.page-hero>

    <x-tl.section-card eyebrow="New template" title="Create reusable program" subtitle="Athlete assignment is optional. Build the template first, or assign it immediately to someone on your roster.">
        <form class="row g-3 align-items-end" wire:submit.prevent="createProgram">
            <div class="col-md-4"><label class="form-label">Title</label><input class="form-control" wire:model="title" placeholder="12-week strength foundation"></div>
            <div class="col-md-3"><label class="form-label">Goal</label><input class="form-control" wire:model="goal" placeholder="Build maximal strength"></div>
            <div class="col-6 col-md-2"><label class="form-label">Weeks</label><input class="form-control" type="number" min="1" wire:model="estimatedWeeks"></div>
            <div class="col-6 col-md-3"><label class="form-label">Visibility</label><select class="form-select" wire:model="visibility"><option value="private">Only me</option><option value="organization">Organization coaches</option></select></div>
            <div class="col-md-4"><label class="form-label">Assign now (optional)</label><select class="form-select" wire:model="athleteId"><option value="">Build template only</option>@foreach($athletes as $athlete)<option value="{{ $athlete->id }}">{{ $athlete->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Notes</label><input class="form-control" wire:model="notes" placeholder="Internal template notes"></div>
            <div class="col-md-2"><button class="btn btn-tl w-100" type="submit"><i class="fa-solid fa-plus"></i> Create</button></div>
        </form>
        @if($errors->any())<div class="text-danger small mt-3">{{ $errors->first() }}</div>@endif
    </x-tl.section-card>

    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search title or goal'])

    <x-tl.table-card title="Program library" subtitle="Templates stay reusable. Assignments and athlete schedule dates are tracked separately." :count="$programs->total()" icon="fa-solid fa-dumbbell">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" wire:model.live="listStatus"><option value="all">All statuses</option><option value="draft">Draft</option><option value="active">Active</option><option value="archived">Archived</option></select></div>
            <div class="col-md-auto ms-md-auto"><a class="btn btn-outline-tl" href="{{ route('coach.programs.export', ['search' => $search, 'status' => $listStatus]) }}"><i class="fa-solid fa-download"></i> Export CSV</a></div>
        </div>
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th><button class="tl-sort-button" wire:click="sortBy('title')">Program <i class="fa-solid fa-sort"></i></button></th><th>Status</th><th>Visibility</th><th>Weeks</th><th>Sessions</th><th>Assignments</th><th>Execution</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($programs as $program)
                @php($completion = $program->completionStats())
                <tr wire:key="program-{{ $program->id }}">
                    <td><a class="tl-table-primary-link" href="{{ route('coach.programs.show', $program) }}">{{ $program->title }}</a><span class="d-block tl-muted">{{ $program->goal ?: 'No goal set' }}</span></td>
                    <td><span class="tl-badge {{ $program->status === 'active' ? 'green' : ($program->status === 'draft' ? 'gold' : 'gray') }}">{{ $program->status }}</span></td>
                    <td>{{ str($program->visibility)->headline() }}</td>
                    <td>{{ $program->estimated_weeks ?: '-' }}</td>
                    <td>{{ $program->sessions_count }}</td>
                    <td>{{ $program->assignments_count }}</td>
                    <td><strong>{{ $completion['percent'] }}%</strong><span class="d-block tl-muted">{{ $completion['completed'] }}/{{ $completion['total'] }} workouts</span></td>
                    <td><a class="btn btn-outline-tl btn-sm" href="{{ route('coach.programs.show', $program) }}">Open builder</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="tl-muted">No programs match the current filters.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="mt-3">{{ $programs->links() }}</div>
    </x-tl.table-card>
</div>
