<div>
    <div class="tl-hero"><div class="tl-eyebrow">Program</div><h2 class="h1 fw-bold">{{ $program->title }}</h2><p class="tl-muted mb-0">{{ $program->coach->name }} · {{ $program->goal }}</p></div>
    <div class="tl-panel">
        <div class="d-md-none vstack gap-2">
            @forelse($sessions as $session)
                @php($log = $session->logs->firstWhere('athlete_id', auth()->id()))
                <a class="tl-mobile-record" href="{{ route('app.workouts.show', $session) }}">
                    <div class="d-flex justify-content-between gap-2 align-items-start">
                        <div>
                            <div class="tl-muted small">{{ $session->scheduled_on->format('M j, Y') }}</div>
                            <div class="fw-bold">{{ $session->title }}</div>
                        </div>
                        <span class="tl-badge {{ $log?->status === 'completed' ? 'green' : 'gray' }}">{{ $log?->status ?? 'open' }}</span>
                    </div>
                    <div class="tl-mobile-record-grid mt-3">
                        <div><span class="tl-muted small d-block">Focus</span><span>{{ $session->focus ?: '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Exercises</span><span>{{ $session->exerciseSummary() }}</span></div>
                    </div>
                </a>
            @empty
                <div class="tl-mobile-record tl-muted">No sessions yet.</div>
            @endforelse
        </div>

        <div class="tl-table-wrap d-none d-md-block"><table class="table tl-table align-middle">
            <thead><tr><th>Date</th><th>Session</th><th>Focus</th><th>Exercises</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($sessions as $session)
                @php($log = $session->logs->firstWhere('athlete_id', auth()->id()))
                <tr><td>{{ $session->scheduled_on->format('Y-m-d') }}</td><td>{{ $session->title }}</td><td>{{ $session->focus }}</td><td>{{ $session->exerciseSummary() }}</td><td><span class="tl-badge {{ $log?->status === 'completed' ? 'green' : 'gray' }}">{{ $log?->status ?? 'not started' }}</span></td><td><a class="btn btn-outline-tl btn-sm" href="{{ route('app.workouts.show', $session) }}">Open</a></td></tr>
            @empty
                <tr><td colspan="6" class="tl-muted">No sessions yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="mt-3">{{ $sessions->links() }}</div>
    </div>
</div>
