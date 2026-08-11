<div>
    @php
        $primaryWorkout = $todayWorkouts->first();
        $todayCompleted = $todayWorkouts->where('status', 'completed')->count();
        $todayPercent = $todayWorkouts->count() ? (int) round(($todayCompleted / $todayWorkouts->count()) * 100) : 0;
        $coachNames = $assignments->pluck('program.coach.name')->filter()->unique()->join(', ');
    @endphp

    <section class="tl-app-hero-card mb-3">
        <div>
            <div class="tl-eyebrow">Athlete home</div>
            <h2>Train with intent.</h2>
            <p>{{ $coachNames ? 'Coached by '.$coachNames : 'No coach assigned yet.' }} · {{ $assignments->count() }} active program(s)</p>
            <div class="d-flex gap-2 flex-wrap mt-3">
                <a class="btn btn-tl" href="#today">Today’s workout</a>
                <a class="btn btn-outline-tl" href="{{ route('app.progress') }}">Log progress</a>
            </div>
        </div>
        <div class="tl-app-ring" style="--value: {{ $todayPercent }}">
            <span>{{ $todayPercent }}%</span>
            <small>Today complete</small>
        </div>
    </section>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <section class="tl-app-today-card h-100" id="today">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="tl-eyebrow">Today</div>
                        <h3>{{ $primaryWorkout?->session?->title ?? 'Recovery day' }}</h3>
                    </div>
                    <span class="tl-badge {{ $primaryWorkout?->status === 'completed' ? 'green' : 'gold' }}">{{ $primaryWorkout?->status ?? 'rest' }}</span>
                </div>

                @if($primaryWorkout)
                    <p class="tl-muted mb-3">{{ $primaryWorkout->assignment->program->title }} · {{ $primaryWorkout->assignment->program->coach->name }}</p>
                    <div class="tl-session-focus mb-3">
                        <span><i class="fa-solid fa-bullseye"></i> {{ $primaryWorkout->session->focus ?: 'Training session' }}</span>
                        <span><i class="fa-solid fa-list-check"></i> {{ $primaryWorkout->session->exerciseSummary() }}</span>
                        <span><i class="fa-solid fa-clock"></i> {{ $primaryWorkout->session->estimated_minutes ?: '-' }} minutes</span>
                    </div>
                    <a class="btn btn-tl w-100" href="{{ route('app.workouts.show', $primaryWorkout) }}">{{ $primaryWorkout->executionLog ? 'Continue workout' : 'Start workout' }}</a>
                @else
                    <p class="tl-muted mb-3">Nothing is scheduled today. Use the calendar to inspect another day or recover properly.</p>
                    <a class="btn btn-outline-tl w-100" href="#calendar">Open calendar</a>
                @endif
            </section>
        </div>
        <div class="col-xl-5">
            <section class="tl-app-progress-card h-100">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div><div class="tl-eyebrow">Latest check-in</div><h3>{{ $latestProgress?->logged_on?->format('M j') ?? 'Not logged yet' }}</h3></div>
                    <a class="btn btn-outline-tl btn-sm" href="{{ route('app.progress') }}">Update</a>
                </div>
                @if($latestProgress)
                    <div class="tl-health-grid">
                        <div><span>Weight</span><strong>{{ $latestProgress->weight ? $latestProgress->weight.' kg' : '-' }}</strong></div>
                        <div><span>Protein</span><strong>{{ $latestProgress->protein ? $latestProgress->protein.' g' : '-' }}</strong></div>
                        <div><span>Water</span><strong>{{ $latestProgress->hydration ? $latestProgress->hydration.' ml' : '-' }}</strong></div>
                        <div><span>Sleep</span><strong>{{ $latestProgress->sleep_quality ? $latestProgress->sleep_quality.'/10' : '-' }}</strong></div>
                        <div><span>Soreness</span><strong>{{ $latestProgress->soreness ? $latestProgress->soreness.'/10' : '-' }}</strong></div>
                        <div><span>Energy</span><strong>{{ $latestProgress->energy ? $latestProgress->energy.'/10' : '-' }}</strong></div>
                    </div>
                @else
                    <p class="tl-muted mb-0">Add your first check-in so your coach can review recovery, nutrition, and readiness.</p>
                @endif
            </section>
        </div>
    </div>

    <x-tl.section-card id="programs" title="Assigned programs" subtitle="Every active assignment, its coach, completion, and next workout.">
        <div class="row g-3">
            @forelse($programSummaries as $summary)
                @php($assignment = $summary['assignment'])
                <div class="col-lg-4">
                    <a class="tl-program-card h-100" href="{{ route('app.programs.show', $assignment) }}">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div><div class="tl-muted small">{{ $assignment->program->coach->name }}</div><strong>{{ $assignment->program->title }}</strong></div>
                            <span class="tl-badge green">{{ $summary['percent'] }}%</span>
                        </div>
                        <div class="tl-program-meter mt-3"><span style="width: {{ $summary['percent'] }}%"></span></div>
                        <div class="tl-mobile-record-grid mt-3">
                            <div><span class="tl-muted small d-block">Done</span><span>{{ $summary['completed'] }}/{{ $summary['total'] }}</span></div>
                            <div><span class="tl-muted small d-block">Media</span><span>{{ $summary['media'] }} item(s)</span></div>
                        </div>
                        <div class="tl-muted small mt-3">Next: {{ $summary['nextWorkout'] ? $summary['nextWorkout']->scheduled_for->timezone($assignment->timezone)->format('M j').' · '.$summary['nextWorkout']->session->title : 'No upcoming workout' }}</div>
                    </a>
                </div>
            @empty
                <div class="col-12"><div class="tl-empty-state"><i class="fa-solid fa-dumbbell"></i><strong>No program assigned</strong><span>Your coach’s assignments will appear here.</span></div></div>
            @endforelse
        </div>
    </x-tl.section-card>

    <section class="tl-section-card mb-3" id="calendar">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div><div class="tl-eyebrow">Schedule</div><h3 class="h5 mb-0">{{ \Illuminate\Support\Carbon::parse($month.'-01')->format('F Y') }}</h3></div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-tl btn-sm" type="button" wire:click="previousMonth" wire:loading.attr="disabled"><i class="fa-solid fa-chevron-left"></i></button>
                <button class="btn btn-outline-tl btn-sm" type="button" wire:click="goToday">Today</button>
                <button class="btn btn-outline-tl btn-sm" type="button" wire:click="nextMonth" wire:loading.attr="disabled"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>
        <div class="tl-calendar-weekdays" aria-hidden="true">@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $weekday)<span>{{ $weekday }}</span>@endforeach</div>
        <div class="tl-calendar" wire:loading.class="opacity-50">
            @foreach($days as $day)
                @if($day['date'])
                    <button class="tl-day {{ $selectedDate === $day['date'] ? 'active' : '' }}" type="button" wire:click="selectDate('{{ $day['date'] }}')">
                        <span class="d-block small text-uppercase d-md-none">{{ $day['label'] }}</span>
                        <strong>{{ $day['day'] }}</strong>
                        @if($day['workouts'])<span class="tl-day-dot"></span><small>{{ $day['workouts'] }}</small>@endif
                    </button>
                @else
                    <span class="tl-day is-empty" aria-hidden="true"></span>
                @endif
            @endforeach
        </div>
    </section>

    <x-tl.table-card :title="\Illuminate\Support\Carbon::parse($selectedDate)->format('M j, Y').' schedule'" subtitle="Open a workout to view the prescription, media, and execution log." :count="$selectedWorkouts->count()" icon="fa-solid fa-calendar-check">
        <div class="d-md-none vstack gap-2">
            @forelse($selectedWorkouts as $workout)
                <a class="tl-mobile-record" href="{{ route('app.workouts.show', $workout) }}">
                    <div class="d-flex justify-content-between gap-2"><div><strong>{{ $workout->session->title }}</strong><div class="tl-muted small">{{ $workout->assignment->program->title }} · {{ $workout->assignment->program->coach->name }}</div></div><span class="tl-badge {{ $workout->status === 'completed' ? 'green' : 'gray' }}">{{ $workout->status }}</span></div>
                    <div class="tl-muted small mt-3">{{ $workout->session->exerciseSummary() }}</div>
                </a>
            @empty
                <div class="tl-empty-state compact"><span>No workout scheduled for this date.</span></div>
            @endforelse
        </div>
        <div class="tl-table-wrap d-none d-md-block"><table class="table tl-table align-middle"><thead><tr><th>Workout</th><th>Coach</th><th>Program</th><th>Prescription</th><th>Media</th><th>Status</th><th></th></tr></thead><tbody>
            @forelse($selectedWorkouts as $workout)
                <tr><td><strong>{{ $workout->session->title }}</strong><br><span class="tl-muted">{{ $workout->session->focus ?: '-' }}</span></td><td>{{ $workout->assignment->program->coach->name }}</td><td>{{ $workout->assignment->program->title }}</td><td>{{ $workout->session->exerciseSummary() }}</td><td>{{ $workout->session->mediaCount() ?: '-' }}</td><td><span class="tl-badge {{ $workout->status === 'completed' ? 'green' : 'gray' }}">{{ $workout->status }}</span></td><td><a class="btn btn-outline-tl btn-sm" href="{{ route('app.workouts.show', $workout) }}">Open</a></td></tr>
            @empty<tr><td colspan="7" class="tl-muted">No workout scheduled for this date.</td></tr>@endforelse
        </tbody></table></div>
    </x-tl.table-card>
</div>
