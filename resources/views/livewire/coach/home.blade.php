<div>
    <div class="tl-hero">
        <div class="tl-eyebrow">Coach workspace</div>
        <h2 class="h1 fw-bold">Coach home</h2>
        <p class="tl-muted mb-0">Your athletes, programs, schedule, and invite queue in one direct view.</p>
    </div>

    <div class="row g-3 mb-3">
        @foreach($stats as $label => $value)
            <div class="col-6 col-lg-3"><div class="tl-stat"><span class="tl-muted">{{ str($label)->headline() }}</span><strong>{{ $value }}</strong></div></div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="tl-panel">
                <div class="d-flex justify-content-between align-items-center mb-3"><h3 class="h5 mb-0">Assigned athletes</h3><a class="btn btn-outline-tl btn-sm" href="{{ route('coach.athletes') }}">Open table</a></div>
                @forelse($athletes as $assignment)
                    <div class="tl-stat mb-2"><strong class="fs-5">{{ $assignment->athlete->name }}</strong><span class="tl-muted d-block">{{ $assignment->athlete->email }}</span></div>
                @empty
                    <p class="tl-muted mb-0">No athletes assigned yet.</p>
                @endforelse
            </div>
        </div>
        <div class="col-lg-7">
            <div class="tl-panel">
                <div class="d-flex justify-content-between align-items-center mb-3"><h3 class="h5 mb-0">Upcoming sessions</h3><a class="btn btn-outline-tl btn-sm" href="{{ route('coach.programs') }}">Programs</a></div>
                <div class="tl-table-wrap"><table class="table tl-table align-middle">
                    <thead><tr><th>Date</th><th>Session</th><th>Athlete</th><th>Preview</th></tr></thead>
                    <tbody>
                    @forelse($sessions as $session)
                        <tr><td>{{ $session->scheduled_on->format('M j') }}</td><td><strong>{{ $session->title }}</strong><br><span class="tl-muted">{{ $session->focus }}</span></td><td>{{ $session->program->athlete->name }}</td><td>{{ $session->exerciseSummary() }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="tl-muted">No upcoming sessions.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</div>
