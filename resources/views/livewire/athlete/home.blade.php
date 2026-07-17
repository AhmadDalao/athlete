<div>
    <x-tl.page-hero
        eyebrow="Athlete app"
        title="Your training"
        subtitle="Assigned programs, calendar, and today’s workouts. No admin dashboard noise."
    >
        <x-slot:actions>
            <a class="btn btn-tl" href="#schedule">Open schedule</a>
            <a class="btn btn-outline-tl" href="{{ route('app.progress') }}">Log progress</a>
        </x-slot:actions>
        <x-slot:visual>
            <div class="row g-3">
                <div class="col-6">
                    <x-tl.metric-card icon="fa-solid fa-dumbbell" label="Programs" :value="$programs->count()" tone="lime" />
                </div>
                <div class="col-6">
                    <x-tl.metric-card icon="fa-solid fa-calendar-check" label="Selected day" :value="$selectedSessions->count()" detail="Workout(s)" tone="emerald" />
                </div>
                <div class="col-12">
                    <x-tl.metric-card icon="fa-solid fa-video" label="Media ready" :value="$selectedSessions->filter(fn ($session) => filled($session->media_url))->count()" detail="Session(s) include video or image links." tone="blue" />
                </div>
            </div>
        </x-slot:visual>
    </x-tl.page-hero>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <x-tl.section-card title="Active programs" subtitle="Open the assigned plan and review sessions.">
                @forelse($programs as $program)
                    <a class="tl-mobile-record mb-2" href="{{ route('app.programs.show', $program) }}">
                        <strong>{{ $program->title }}</strong>
                        <span class="tl-muted d-block">{{ $program->coach->name }} · {{ $program->sessions->count() }} sessions</span>
                    </a>
                @empty
                    <p class="tl-muted mb-0">No programs assigned yet.</p>
                @endforelse
            </x-tl.section-card>
        </div>
        <div class="col-lg-8">
            <section class="tl-section-card h-100">
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
            </section>
        </div>
    </div>

    <x-tl.section-card
        id="schedule"
        eyebrow="Selected day"
        :title="\Illuminate\Support\Carbon::parse($selectedDate)->format('M j, Y')"
        subtitle="Click a workout to open sets, reps, rest, media, and completion logging."
    >
        <div class="d-md-none vstack gap-2">
            @forelse($selectedSessions as $session)
                @php($log = $session->logs->firstWhere('athlete_id', auth()->id()))
                <a class="tl-mobile-record" href="{{ route('app.workouts.show', $session) }}">
                    <div class="d-flex justify-content-between gap-2 align-items-start">
                        <div>
                            <div class="fw-bold">{{ $session->title }}</div>
                            <div class="tl-muted small">{{ $session->program->title }} · {{ $session->program->coach->name }}</div>
                        </div>
                        <span class="tl-badge {{ $log?->status === 'completed' ? 'green' : 'gray' }}">{{ $log?->status ?? 'open' }}</span>
                    </div>
                    <div class="tl-mobile-record-grid mt-3">
                        <div><span class="tl-muted small d-block">Focus</span><span>{{ $session->focus ?: '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Exercises</span><span>{{ $session->exerciseSummary() }}</span></div>
                    </div>
                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <span class="{{ $session->media_url ? 'tl-badge green' : 'tl-muted small' }}">{{ $session->media_url ? 'Media attached' : 'No media' }}</span>
                        <span class="btn btn-tl btn-sm">Open</span>
                    </div>
                </a>
            @empty
                <div class="tl-mobile-record tl-muted">No workouts scheduled for this day.</div>
            @endforelse
        </div>

        <div class="tl-table-wrap d-none d-md-block"><table class="table tl-table align-middle">
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
    </x-tl.section-card>
</div>
