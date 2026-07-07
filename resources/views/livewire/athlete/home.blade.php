<div>
    <div class="tl-hero">
        <div class="tl-eyebrow">Athlete app</div>
        <h2 class="h1 fw-bold">Your training</h2>
        <p class="tl-muted mb-0">Assigned programs, calendar, and today’s workouts. No admin dashboard noise.</p>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="tl-panel h-100">
                <h3 class="h5">Active programs</h3>
                @forelse($programs as $program)
                    <a class="tl-stat d-block mb-2" href="{{ route('app.programs.show', $program) }}">
                        <strong class="fs-5">{{ $program->title }}</strong>
                        <span class="tl-muted d-block">{{ $program->coach->name }} · {{ $program->sessions->count() }} sessions</span>
                    </a>
                @empty
                    <p class="tl-muted mb-0">No programs assigned yet.</p>
                @endforelse
            </div>
        </div>
        <div class="col-lg-8">
            <div class="tl-panel h-100">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <div class="tl-eyebrow">Calendar</div>
                        <h3 class="h5 mb-0">{{ \Illuminate\Support\Carbon::parse($month.'-01')->format('F Y') }}</h3>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-tl btn-sm" wire:click="previousMonth"><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="btn btn-outline-tl btn-sm" wire:click="nextMonth"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="tl-calendar">
                    @foreach($days as $day)
                        <button class="tl-day {{ $selectedDate === $day['date'] ? 'active' : '' }}" wire:click="selectDate('{{ $day['date'] }}')">
                            <span class="d-block small text-uppercase">{{ $day['label'] }}</span>
                            <strong class="fs-4">{{ $day['day'] }}</strong>
                            <span class="d-block small">{{ $day['sessions'] ? $day['sessions'].' workout' : 'Rest' }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="tl-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div><div class="tl-eyebrow">Selected day</div><h3 class="h5 mb-0">{{ \Illuminate\Support\Carbon::parse($selectedDate)->format('M j, Y') }}</h3></div>
        </div>
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Workout</th><th>Coach</th><th>Program</th><th>Preview</th><th>Media</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($selectedSessions as $session)
                @php($log = $session->logs->firstWhere('athlete_id', auth()->id()))
                <tr>
                    <td><strong>{{ $session->title }}</strong><br><span class="tl-muted">{{ $session->focus }}</span></td>
                    <td>{{ $session->program->coach->name }}</td>
                    <td>{{ $session->program->title }}</td>
                    <td>{{ $session->exerciseSummary() }}</td>
                    <td>{!! $session->media_url ? '<span class="tl-badge green">Media</span>' : '<span class="tl-muted">None</span>' !!}</td>
                    <td><span class="tl-badge {{ $log?->status === 'completed' ? 'green' : 'gray' }}">{{ $log?->status ?? 'not started' }}</span></td>
                    <td><a class="btn btn-tl btn-sm" href="{{ route('app.workouts.show', $session) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="tl-muted">No workouts scheduled for this day.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>
