<div>
    <x-tl.page-hero eyebrow="Program delivery" title="Presets and athlete plans" subtitle="Build reusable prescriptions, then personalize and publish an isolated plan for each athlete.">
        <x-slot:actions>
            <a class="btn btn-outline-tl" href="{{ route('coach.exercises') }}"><i class="fa-solid fa-list-check"></i> Exercise library</a>
            <a class="btn btn-outline-tl" href="{{ route('coach.schedule') }}"><i class="fa-solid fa-calendar-days"></i> Schedule</a>
        </x-slot:actions>
    </x-tl.page-hero>

    <x-tl.section-card eyebrow="New preset" title="Create reusable program" subtitle="Optionally create an unpublished athlete copy immediately. You can edit it before the athlete sees anything.">
        <form class="row g-3 align-items-end" wire:submit.prevent="createProgram">
            <div class="col-md-4"><label class="form-label">Title</label><input class="form-control" wire:model="title" placeholder="12-week strength foundation"></div>
            <div class="col-md-3"><label class="form-label">Goal</label><input class="form-control" wire:model="goal" placeholder="Build maximal strength"></div>
            <div class="col-6 col-md-2"><label class="form-label">Weeks</label><input class="form-control" type="number" min="1" wire:model="estimatedWeeks"></div>
            <div class="col-6 col-md-3"><label class="form-label">Visibility</label><select class="form-select" wire:model="visibility"><option value="private">Only me</option><option value="organization">Organization coaches</option></select></div>
            <div class="col-md-4"><label class="form-label">Personalize now (optional)</label><select class="form-select" wire:model="athleteId"><option value="">Create preset only</option>@foreach($athletes as $athlete)<option value="{{ $athlete->id }}">{{ $athlete->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Notes</label><input class="form-control" wire:model="notes" placeholder="Internal template notes"></div>
            <div class="col-md-2"><button class="btn btn-tl w-100" type="submit"><i class="fa-solid fa-plus"></i> Create</button></div>
        </form>
        @if($errors->any())<div class="text-danger small mt-3">{{ $errors->first() }}</div>@endif
    </x-tl.section-card>

    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search title or goal'])

    <x-tl.table-card :title="$listKind === 'preset' ? 'Presets' : 'Athlete plans'" :subtitle="$listKind === 'preset' ? 'Source presets never overwrite an existing athlete plan.' : 'Each plan belongs to one athlete and can be edited without changing its preset.'" :count="$programs->total()" icon="fa-solid fa-dumbbell">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-4"><label class="form-label">Program type</label><select class="form-select" wire:model.live="listKind"><option value="preset">Presets</option><option value="athlete_plan">Athlete plans</option></select></div>
            <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" wire:model.live="listStatus"><option value="all">All statuses</option><option value="draft">Draft</option><option value="active">Active</option><option value="archived">Archived</option></select></div>
            <div class="col-md-auto ms-md-auto"><a class="btn btn-outline-tl" href="{{ route('coach.programs.export', ['search' => $search, 'status' => $listStatus, 'kind' => $listKind]) }}"><i class="fa-solid fa-download"></i> Export CSV</a></div>
        </div>
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th><button class="tl-sort-button" wire:click="sortBy('title')">Program <i class="fa-solid fa-sort"></i></button></th><th>{{ $listKind === 'preset' ? 'Visibility' : 'Athlete' }}</th><th>Status</th><th>Weeks</th><th>Sessions</th><th>{{ $listKind === 'preset' ? 'Athlete plans' : 'Assignments' }}</th><th>Execution</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($programs as $program)
                @php($completion = $program->completionStats())
                @php($delivery = $program->is_template ? null : $program->assignments->first())
                <tr wire:key="program-{{ $program->id }}">
                    <td><a class="tl-table-primary-link" href="{{ route('coach.programs.show', $program) }}">{{ $program->title }}</a><span class="d-block tl-muted">{{ $program->goal ?: 'No goal set' }}</span></td>
                    <td>@if($program->is_template){{ str($program->visibility)->headline() }}@else<a href="{{ route('coach.athletes.show', $program->athlete) }}">{{ $program->athlete?->name ?: 'Unassigned' }}</a><span class="d-block tl-muted">From {{ $program->source?->title ?: 'legacy program' }}</span>@endif</td>
                    <td><span class="tl-badge {{ ($delivery?->status ?? $program->status) === 'active' ? 'green' : (in_array(($delivery?->status ?? $program->status), ['draft','paused']) ? 'gold' : 'gray') }}">{{ $delivery?->status ?? $program->status }}</span>@if($delivery)<span class="d-block tl-muted">{{ $delivery->published_at ? 'Published' : 'Not published' }}</span>@endif</td>
                    <td>{{ $program->estimated_weeks ?: '-' }}</td>
                    <td>{{ $program->sessions_count }}</td>
                    <td>{{ $program->is_template ? $program->athlete_plans_count : $program->assignments_count }}</td>
                    <td><strong>{{ $completion['percent'] }}%</strong><span class="d-block tl-muted">{{ $completion['completed'] }}/{{ $completion['total'] }} workouts</span></td>
                    <td><a class="btn btn-outline-tl btn-sm" href="{{ route('coach.programs.show', $program) }}">{{ $program->is_template ? 'Open preset' : 'Personalize' }}</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="tl-muted">No programs match the current filters.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="mt-3">{{ $programs->links() }}</div>
    </x-tl.table-card>
</div>
