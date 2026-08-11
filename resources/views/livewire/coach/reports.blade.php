<div>
    <x-tl.page-hero eyebrow="Analytics" title="Coaching reports" subtitle="Adherence is calculated from scheduled workouts and athlete execution logs. No vanity numbers, no guessed compliance.">
        <x-slot:actions><a class="btn btn-outline-tl" href="{{ route('coach.reports.export', ['from' => $from, 'to' => $to, 'search' => $search]) }}"><i class="fa-solid fa-download"></i> Export CSV</a></x-slot:actions>
    </x-tl.page-hero>

    <section class="tl-summary-grid mb-4">
        <x-tl.metric-card label="Scheduled" :value="$summary['scheduled']" icon="fa-solid fa-calendar-check" />
        <x-tl.metric-card label="Completed" :value="$summary['completed']" icon="fa-solid fa-circle-check" tone="green" />
        <x-tl.metric-card label="Completion rate" :value="$summary['completion_rate'].'%'" icon="fa-solid fa-chart-line" tone="cyan" />
        <x-tl.metric-card label="Overdue" :value="$summary['overdue']" icon="fa-solid fa-triangle-exclamation" tone="gold" />
    </section>

    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search athlete or email'])

    <x-tl.table-card title="Athlete adherence" subtitle="Completed means a completed execution log. Partial and missed sessions remain visible instead of being counted as success." :count="$athletes->total()" icon="fa-solid fa-chart-column">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-6 col-md-3"><label class="form-label">From</label><input class="form-control" type="date" wire:model.live="from"></div>
            <div class="col-6 col-md-3"><label class="form-label">To</label><input class="form-control" type="date" wire:model.live="to"></div>
            <div class="col-md-auto ms-md-auto d-flex gap-3 tl-muted small"><span>Partial: {{ $summary['partial'] }}</span><span>Missed: {{ $summary['missed'] }}</span><span>Avg RPE: {{ $summary['average_rpe'] ?: '—' }}</span><span>Duration: {{ $summary['duration_minutes'] }} min</span></div>
        </div>

        <div class="tl-table-wrap"><table class="table tl-table align-middle"><thead><tr><th>Athlete</th><th>Scheduled</th><th>Completed</th><th>Partial</th><th>Missed</th><th>Completion</th><th>Avg RPE</th><th>Duration</th><th>Action</th></tr></thead><tbody>
            @forelse($athletes as $athlete)
                @php($rate = $athlete->scheduled_count > 0 ? round(($athlete->completed_count / $athlete->scheduled_count) * 100, 1) : 0)
                <tr wire:key="report-athlete-{{ $athlete->id }}"><td><a class="tl-table-primary-link" href="{{ route('coach.athletes.show', $athlete) }}">{{ $athlete->name }}</a><span class="d-block tl-muted">{{ $athlete->email }}</span></td><td>{{ $athlete->scheduled_count }}</td><td>{{ $athlete->completed_count }}</td><td>{{ $athlete->partial_count }}</td><td>{{ $athlete->missed_count }}</td><td><strong>{{ $rate }}%</strong><div class="progress tl-progress mt-1" role="progressbar" aria-label="Completion rate" aria-valuenow="{{ $rate }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ min($rate, 100) }}%"></div></div></td><td>{{ $athlete->average_rpe ? number_format($athlete->average_rpe, 1) : '—' }}</td><td>{{ (int) $athlete->duration_minutes }} min</td><td><a class="btn btn-outline-tl btn-sm" href="{{ route('coach.athletes.show', ['athlete' => $athlete, 'tab' => 'workouts']) }}">Review</a></td></tr>
            @empty
                <tr><td colspan="9" class="tl-muted">No assigned athletes match this report.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="mt-3">{{ $athletes->links() }}</div>
    </x-tl.table-card>
</div>
