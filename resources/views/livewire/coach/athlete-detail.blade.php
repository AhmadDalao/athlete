<div>
    <div class="tl-hero">
        <div class="tl-eyebrow">Athlete profile</div>
        <div class="d-flex justify-content-between gap-3 flex-wrap align-items-start">
            <div>
                <h2 class="h1 fw-bold">{{ $athlete->name }}</h2>
                <p class="tl-muted mb-0">{{ $athlete->email }} · {{ $athlete->primary_goal ?: 'No goal set' }}</p>
            </div>
            <a class="btn btn-outline-tl" href="{{ route('coach.athletes') }}"><i class="fa-solid fa-arrow-left"></i> Back to roster</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="tl-stat"><span class="tl-muted">Programs</span><strong>{{ $programs->count() }}</strong></div></div>
        <div class="col-md-3"><div class="tl-stat"><span class="tl-muted">Sessions</span><strong>{{ $sessions->count() }}</strong></div></div>
        <div class="col-md-3"><div class="tl-stat"><span class="tl-muted">Workout logs</span><strong>{{ $workoutLogs->count() }}</strong></div></div>
        <div class="col-md-3"><div class="tl-stat"><span class="tl-muted">Progress logs</span><strong>{{ $progressEntries->count() }}</strong></div></div>
    </div>

    <div class="tl-panel">
        <h3 class="h5">Assigned programs</h3>
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Program</th><th>Goal</th><th>Status</th><th>Dates</th><th>Sessions</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($programs as $program)
                <tr>
                    <td><strong>{{ $program->title }}</strong></td>
                    <td>{{ $program->goal ?: '-' }}</td>
                    <td><span class="tl-badge {{ $program->status === 'active' ? 'green' : 'gray' }}">{{ $program->status }}</span></td>
                    <td>{{ $program->starts_on?->format('Y-m-d') ?: '-' }} to {{ $program->ends_on?->format('Y-m-d') ?: '-' }}</td>
                    <td>{{ $program->sessions->count() }}</td>
                    <td><a class="btn btn-outline-tl btn-sm" href="{{ route('coach.programs.show', $program) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="tl-muted">No programs assigned by you yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="tl-panel">
        <h3 class="h5">Schedule</h3>
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Date</th><th>Session</th><th>Program</th><th>Focus</th><th>Exercises</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($sessions as $session)
                @php($log = $session->logs->firstWhere('athlete_id', $athlete->id))
                <tr>
                    <td>{{ $session->scheduled_on->format('Y-m-d') }}</td>
                    <td><strong>{{ $session->title }}</strong></td>
                    <td>{{ $session->program->title }}</td>
                    <td>{{ $session->focus ?: '-' }}</td>
                    <td>{{ $session->exerciseSummary() }}</td>
                    <td><span class="tl-badge {{ $log?->status === 'completed' ? 'green' : 'gray' }}">{{ $log?->status ?? 'not started' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="tl-muted">No scheduled sessions.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="tl-panel">
        <h3 class="h5">Workout logs</h3>
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Logged</th><th>Session</th><th>Status</th><th>RPE</th><th>Duration</th><th>Notes</th></tr></thead>
            <tbody>
            @forelse($workoutLogs as $log)
                <tr>
                    <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->session->title }}</td>
                    <td><span class="tl-badge {{ $log->status === 'completed' ? 'green' : 'gray' }}">{{ $log->status }}</span></td>
                    <td>{{ $log->rpe ?: '-' }}</td>
                    <td>{{ $log->duration_minutes ? $log->duration_minutes.' min' : '-' }}</td>
                    <td>{{ $log->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="tl-muted">No workout logs yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="tl-panel">
        <h3 class="h5">Progress logs</h3>
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Date</th><th>Weight</th><th>Calories</th><th>Protein</th><th>Hydration</th><th>Sleep</th><th>Soreness</th><th>Energy</th><th>Notes</th></tr></thead>
            <tbody>
            @forelse($progressEntries as $entry)
                <tr>
                    <td>{{ $entry->logged_on->format('Y-m-d') }}</td>
                    <td>{{ $entry->weight ?: '-' }}</td>
                    <td>{{ $entry->calories ?: '-' }}</td>
                    <td>{{ $entry->protein ?: '-' }}</td>
                    <td>{{ $entry->hydration ?: '-' }}</td>
                    <td>{{ $entry->sleep_quality ?: '-' }}/10</td>
                    <td>{{ $entry->soreness ?: '-' }}/10</td>
                    <td>{{ $entry->energy ?: '-' }}/10</td>
                    <td>{{ $entry->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="tl-muted">No progress logs yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>
