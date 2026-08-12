<div>
    <x-tl.page-hero eyebrow="Business intelligence" title="Operations reports" subtitle="{{ $organization->name }} coaching delivery, adherence, and workload from real schedules and execution logs.">
        <x-slot:actions><a class="btn btn-outline-tl" href="{{ route('admin.reports.export', ['from' => $from, 'to' => $to, 'search' => $search]) }}"><i class="fa-solid fa-download me-2"></i>Export CSV</a></x-slot:actions>
    </x-tl.page-hero>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-tl.metric-card label="Athletes" :value="$summary['athletes']" icon="fa-solid fa-person-running" tone="emerald" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card label="Coaches" :value="$summary['coaches']" icon="fa-solid fa-user-tie" tone="gold" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card label="Scheduled" :value="$summary['scheduled']" icon="fa-solid fa-calendar-check" tone="blue" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card label="Completion" :value="$summary['completion_rate'].'%'" icon="fa-solid fa-chart-line" tone="lime" /></div>
    </div>

    <x-tl.section-card title="Report range" subtitle="Date and search filters refresh without a full page reload.">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4"><label class="form-label">Search coach</label><input class="form-control" type="search" placeholder="Coach name or email" wire:model.live.debounce.300ms="search"></div>
            <div class="col-6 col-md-2"><label class="form-label">From</label><input class="form-control" type="date" wire:model.live="from"></div>
            <div class="col-6 col-md-2"><label class="form-label">To</label><input class="form-control" type="date" wire:model.live="to"></div>
            <div class="col-md-2"><label class="form-label">Show</label><select class="form-select" wire:model.live="perPage">@foreach($pageSizeOptions as $option)<option value="{{ $option }}">{{ $option === 'all' ? 'All' : $option }}</option>@endforeach</select></div>
            <div class="col-md-auto ms-md-auto d-flex gap-3 flex-wrap tl-muted small"><span>Programs: {{ $summary['programs'] }}</span><span>Open invites: {{ $summary['open_invites'] }}</span><span>Check-ins: {{ $summary['check_ins'] }}</span><span>Overdue: {{ $summary['overdue'] }}</span></div>
        </div>
    </x-tl.section-card>

    <x-tl.table-card title="Coach delivery" subtitle="Each row connects roster size and active programs to actual scheduled and completed work." :count="$coaches->total()" icon="fa-solid fa-chart-column">
        <div class="tl-table-wrap"><table class="table tl-table align-middle"><thead><tr><th>Coach</th><th>Athletes</th><th>Programs</th><th>Scheduled</th><th>Completed</th><th>Partial</th><th>Missed</th><th>Overdue</th><th>Completion</th></tr></thead><tbody>
            @forelse($coaches as $coach)
                @php($rate = $coach->scheduled_count > 0 ? round(($coach->completed_count / $coach->scheduled_count) * 100, 1) : 0)
                <tr wire:key="operations-coach-{{ $coach->id }}"><td><strong>{{ $coach->name }}</strong><span class="d-block tl-muted">{{ $coach->email }}</span></td><td>{{ $coach->athlete_count }}</td><td>{{ $coach->active_program_count }}</td><td>{{ $coach->scheduled_count }}</td><td>{{ $coach->completed_count }}</td><td>{{ $coach->partial_count }}</td><td>{{ $coach->missed_count }}</td><td>{{ $coach->overdue_count }}</td><td><strong>{{ $rate }}%</strong><div class="progress tl-progress mt-1" role="progressbar" aria-label="Completion rate" aria-valuenow="{{ $rate }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ min($rate, 100) }}%"></div></div></td></tr>
            @empty
                <tr><td colspan="9" class="tl-muted">No coaches match this report.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="mt-3">{{ $coaches->links() }}</div>
    </x-tl.table-card>
</div>
