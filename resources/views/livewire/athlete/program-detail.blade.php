<div>
    <x-tl.page-hero eyebrow="Assigned program" :title="$assignment->program->title" :subtitle="'Coach '.$assignment->program->coach->name.' · '.($assignment->program->goal ?: 'Structured training plan')">
        <x-slot:actions><a class="btn btn-outline-tl" href="{{ route('app.home') }}#programs"><i class="fa-solid fa-arrow-left"></i> Programs</a></x-slot:actions>
    </x-tl.page-hero>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-chart-pie" label="Completion" :value="$stats['percent'].'%'" :detail="$stats['completed'].' of '.$stats['total'].' workouts'" tone="lime" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-calendar-day" label="Starts" :value="$assignment->starts_on->format('M j')" :detail="$assignment->timezone" tone="emerald" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-flag-checkered" label="Ends" :value="$assignment->ends_on?->format('M j') ?? '-'" detail="Planned finish" tone="gold" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-signal" label="Status" :value="str($assignment->status)->headline()" detail="Assignment state" tone="blue" /></div>
    </div>

    @if($assignment->notes)
        <x-tl.section-card title="Coach note" subtitle="Context attached to this assignment."><p class="mb-0">{{ $assignment->notes }}</p></x-tl.section-card>
    @endif

    @if($assignment->program->phases->isNotEmpty())
        <x-tl.section-card title="Program phases" subtitle="The progression your coach planned across this assignment.">
            <div class="row g-3">
                @foreach($assignment->program->phases as $phase)
                    <div class="col-md-6 col-xl-4">
                        <div class="tl-program-card h-100">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="tl-eyebrow">Phase {{ $loop->iteration }}</div>
                                    <strong>{{ $phase->title }}</strong>
                                </div>
                                <span class="tl-badge green">{{ $phase->duration_weeks ?: '-' }} weeks</span>
                            </div>
                            <p class="tl-muted small mb-0 mt-3">{{ $phase->description ?: 'No phase notes.' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-tl.section-card>
    @endif

    <x-tl.table-card title="Workout schedule" subtitle="Every dated workout generated from this program assignment." :count="$workouts->total()" icon="fa-solid fa-calendar-check">
        <div class="d-md-none vstack gap-2">
            @forelse($workouts as $workout)
                <a class="tl-mobile-record" href="{{ route('app.workouts.show', $workout) }}">
                    <div class="d-flex justify-content-between gap-2"><div><div class="tl-muted small">{{ $workout->scheduled_for->timezone($assignment->timezone)->format('M j, Y') }}</div><strong>{{ $workout->session->title }}</strong></div><span class="tl-badge {{ $workout->status === 'completed' ? 'green' : 'gray' }}">{{ $workout->status }}</span></div>
                    <div class="tl-mobile-record-grid mt-3"><div><span class="tl-muted small d-block">Focus</span>{{ $workout->session->focus ?: '-' }}</div><div><span class="tl-muted small d-block">Prescription</span>{{ $workout->session->exerciseSummary() }}</div><div><span class="tl-muted small d-block">Media</span>{{ $workout->session->mediaCount() ?: 'None' }}</div></div>
                </a>
            @empty<div class="tl-empty-state compact"><span>No workouts have been scheduled.</span></div>@endforelse
        </div>
        <div class="tl-table-wrap d-none d-md-block"><table class="table tl-table align-middle"><thead><tr><th>Date</th><th>Workout</th><th>Focus</th><th>Prescription</th><th>Media</th><th>Status</th><th></th></tr></thead><tbody>
            @forelse($workouts as $workout)<tr><td>{{ $workout->scheduled_for->timezone($assignment->timezone)->format('Y-m-d') }}</td><td><strong>{{ $workout->session->title }}</strong></td><td>{{ $workout->session->focus ?: '-' }}</td><td>{{ $workout->session->exerciseSummary() }}</td><td>{{ $workout->session->mediaCount() ?: '-' }}</td><td><span class="tl-badge {{ $workout->status === 'completed' ? 'green' : 'gray' }}">{{ $workout->status }}</span></td><td><a class="btn btn-outline-tl btn-sm" href="{{ route('app.workouts.show', $workout) }}">Open</a></td></tr>@empty<tr><td colspan="7" class="tl-muted">No workouts have been scheduled.</td></tr>@endforelse
        </tbody></table></div>
        <div class="mt-3">{{ $workouts->links() }}</div>
    </x-tl.table-card>
</div>
