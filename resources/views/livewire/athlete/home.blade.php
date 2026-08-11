<div>
    @php
        $primarySession = $todaySessions->first();
        $primaryLog = $primarySession?->logs->firstWhere('athlete_id', auth()->id());
        $todayTotal = $todaySessions->count();
        $todayCompleted = $todaySessions->filter(fn ($session) => $session->logs->firstWhere('athlete_id', auth()->id())?->status === 'completed')->count();
        $todayPercent = $todayTotal > 0 ? (int) round(($todayCompleted / $todayTotal) * 100) : 0;
        $coachNames = $programs->pluck('coach.name')->filter()->unique()->join(', ');
    @endphp

    <section class="tl-app-hero-card mb-3">
        <div>
            <div class="tl-eyebrow">Athlete app</div>
            <h2>Your training cockpit</h2>
            <p>{{ $coachNames ? 'Coach: '.$coachNames : 'No coach assigned yet.' }} · {{ $programs->count() }} active program(s)</p>
            <div class="d-flex gap-2 flex-wrap mt-3">
                <a class="btn btn-tl" href="#schedule">Today schedule</a>
                <a class="btn btn-outline-tl" href="{{ route('app.progress') }}">Log progress</a>
            </div>
        </div>
        <div class="tl-app-ring" style="--value: {{ $todayPercent }};">
            <span>{{ $todayPercent }}%</span>
            <small>Today complete</small>
        </div>
    </section>

    <div class="row g-3 mb-3">
        <div class="col-xl-5">
            <section class="tl-app-today-card h-100" id="today">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="tl-eyebrow">Today</div>
                        <h3>{{ $primarySession ? $primarySession->title : 'No workout today' }}</h3>
                    </div>
                    <span class="tl-badge {{ $primaryLog?->status === 'completed' ? 'green' : 'gold' }}">{{ $primaryLog?->status ?? ($primarySession ? 'open' : 'rest') }}</span>
                </div>

                @if($primarySession)
                    <p class="tl-muted mb-3">{{ $primarySession->program->title }} · {{ $primarySession->program->coach->name }}</p>
                    <div class="tl-session-focus mb-3">
                        <span><i class="fa-solid fa-bullseye"></i> {{ $primarySession->focus ?: 'Training session' }}</span>
                        <span><i class="fa-solid fa-list-check"></i> {{ $primarySession->exerciseSummary() }}</span>
                        <span><i class="fa-solid {{ $primarySession->hasMedia() ? 'fa-circle-play' : 'fa-image' }}"></i> {{ $primarySession->hasMedia() ? $primarySession->mediaCount().' demo item(s)' : 'No media attached' }}</span>
                    </div>
                    <a class="btn btn-tl w-100" href="{{ route('app.workouts.show', $primarySession) }}">Open workout</a>
                @else
                    <p class="tl-muted mb-3">Nothing is scheduled for today. Pick another day below or wait for your coach to assign a session.</p>
                    <a class="btn btn-outline-tl w-100" href="#schedule">Open calendar</a>
                @endif
            </section>
        </div>
        <div class="col-xl-7">
            <section class="tl-app-progress-card h-100">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="tl-eyebrow">Latest check-in</div>
                        <h3>{{ $latestProgress ? $latestProgress->logged_on->format('M j') : 'Not logged yet' }}</h3>
                    </div>
                    <a class="btn btn-outline-tl btn-sm" href="{{ route('app.progress') }}">Update</a>
                </div>

                @if($latestProgress)
                    <div class="tl-health-grid">
                        <div><span>Weight</span><strong>{{ $latestProgress->weight ? $latestProgress->weight.' kg' : '-' }}</strong></div>
                        <div><span>Calories</span><strong>{{ $latestProgress->calories ?: '-' }}</strong></div>
                        <div><span>Protein</span><strong>{{ $latestProgress->protein ? $latestProgress->protein.' g' : '-' }}</strong></div>
                        <div><span>Hydration</span><strong>{{ $latestProgress->hydration ? $latestProgress->hydration.' ml' : '-' }}</strong></div>
                        <div><span>Sleep</span><strong>{{ $latestProgress->sleep_quality ? $latestProgress->sleep_quality.'/10' : '-' }}</strong></div>
                        <div><span>Energy</span><strong>{{ $latestProgress->energy ? $latestProgress->energy.'/10' : '-' }}</strong></div>
                    </div>
                @else
                    <p class="tl-muted mb-0">Log weight, food, hydration, sleep quality, soreness, and energy so the coach has something real to work with.</p>
                @endif
            </section>
        </div>
    </div>

    <x-tl.section-card id="programs" title="Active programs" subtitle="Completion, next session, and media readiness for each assigned program.">
        <div class="row g-3">
            @forelse($programSummaries as $summary)
                @php($program = $summary['program'])
                <div class="col-lg-4">
                    <a class="tl-program-card h-100" href="{{ route('app.programs.show', $program) }}">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="tl-muted small">{{ $program->coach->name }}</div>
                                <strong>{{ $program->title }}</strong>
                            </div>
                            <span class="tl-badge green">{{ $summary['progress'] }}%</span>
                        </div>
                        <div class="tl-program-meter mt-3"><span style="width: {{ $summary['progress'] }}%"></span></div>
                        <div class="tl-mobile-record-grid mt-3">
                            <div><span class="tl-muted small d-block">Done</span><span>{{ $summary['completed'] }}/{{ $summary['total'] }}</span></div>
                            <div><span class="tl-muted small d-block">Media</span><span>{{ $summary['media'] }} demo item(s)</span></div>
                        </div>
                        <div class="tl-muted small mt-3">
                            Next: {{ $summary['nextSession'] ? $summary['nextSession']->scheduled_on->format('M j').' · '.$summary['nextSession']->title : 'No upcoming session' }}
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12"><p class="tl-muted mb-0">No programs assigned yet.</p></div>
            @endforelse
        </div>
    </x-tl.section-card>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <section class="tl-section-card h-100" id="calendar">
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
                        <span class="{{ $session->hasMedia() ? 'tl-badge green' : 'tl-muted small' }}">{{ $session->hasMedia() ? $session->mediaCount().' demo item(s)' : 'No media' }}</span>
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
                    <td>@if($session->hasMedia())<span class="tl-badge green">{{ $session->mediaCount() }} demo item(s)</span>@else<span class="tl-muted">None</span>@endif</td>
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
