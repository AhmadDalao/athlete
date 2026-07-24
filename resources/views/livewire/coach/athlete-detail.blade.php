<div>
    <x-tl.page-hero
        eyebrow="Athlete profile"
        :title="$athlete->name"
        :subtitle="$athlete->email.' · '.($athlete->primary_goal ?: 'No goal set')"
    >
        <x-slot:actions>
            <a class="btn btn-tl" href="{{ route('coach.programs', ['athlete' => $athlete->id]) }}"><i class="fa-solid fa-plus"></i> Create program</a>
            <a class="btn btn-outline-tl" href="{{ route('coach.athletes') }}"><i class="fa-solid fa-arrow-left"></i> Back to roster</a>
        </x-slot:actions>
    </x-tl.page-hero>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-dumbbell" label="Programs" :value="$programs->count()" tone="lime" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-calendar-check" label="Sessions" :value="$sessions->count()" tone="emerald" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-clipboard-check" label="Workout logs" :value="$workoutLogs->count()" tone="gold" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-chart-line" label="Progress logs" :value="$progressEntries->count()" tone="blue" /></div>
    </div>

    <x-tl.table-card title="Assigned programs" subtitle="Programs this coach assigned to the athlete." :count="$programs->count()" icon="fa-solid fa-dumbbell">
        <div class="d-md-none vstack gap-2">
            @forelse($programs as $program)
                <a class="tl-mobile-record" href="{{ route('coach.programs.show', $program) }}">
                    <div class="d-flex justify-content-between gap-2 align-items-start">
                        <div>
                            <div class="fw-bold">{{ $program->title }}</div>
                            <div class="tl-muted small">{{ $program->goal ?: 'No goal set' }}</div>
                        </div>
                        <span class="tl-badge {{ $program->status === 'active' ? 'green' : 'gray' }}">{{ $program->status }}</span>
                    </div>
                    <div class="tl-mobile-record-grid mt-3">
                        @php($completion = $program->completionStats())
                        <div><span class="tl-muted small d-block">Dates</span><span>{{ $program->starts_on?->format('Y-m-d') ?: '-' }} to {{ $program->ends_on?->format('Y-m-d') ?: '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Sessions</span><span>{{ $program->sessions->count() }}</span></div>
                        <div><span class="tl-muted small d-block">Progress</span><span>{{ $completion['completed'] }}/{{ $completion['total'] }} · {{ $completion['percent'] }}%</span></div>
                    </div>
                </a>
            @empty
                <div class="tl-mobile-record tl-muted">No programs assigned by you yet.</div>
            @endforelse
        </div>
        <div class="tl-table-wrap d-none d-md-block"><table class="table tl-table align-middle">
            <thead><tr><th>Program</th><th>Goal</th><th>Status</th><th>Dates</th><th>Sessions</th><th>Progress</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($programs as $program)
                <tr>
                    @php($completion = $program->completionStats())
                    <td><strong>{{ $program->title }}</strong></td>
                    <td>{{ $program->goal ?: '-' }}</td>
                    <td><span class="tl-badge {{ $program->status === 'active' ? 'green' : 'gray' }}">{{ $program->status }}</span></td>
                    <td>{{ $program->starts_on?->format('Y-m-d') ?: '-' }} to {{ $program->ends_on?->format('Y-m-d') ?: '-' }}</td>
                    <td>{{ $program->sessions->count() }}</td>
                    <td>{{ $completion['completed'] }}/{{ $completion['total'] }} · {{ $completion['percent'] }}%</td>
                    <td><a class="btn btn-outline-tl btn-sm" href="{{ route('coach.programs.show', $program) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="tl-muted">No programs assigned by you yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-tl.table-card>

    <x-tl.table-card title="Schedule" subtitle="Upcoming and historical sessions for this athlete." :count="$sessions->count()" icon="fa-solid fa-calendar-days">
        <div class="d-md-none vstack gap-2">
            @forelse($sessions as $session)
                @php($log = $session->logs->firstWhere('athlete_id', $athlete->id))
                <div class="tl-mobile-record">
                    <div class="d-flex justify-content-between gap-2 align-items-start">
                        <div>
                            <div class="tl-muted small">{{ $session->scheduled_on->format('M j, Y') }}</div>
                            <div class="fw-bold">{{ $session->title }}</div>
                        </div>
                        <span class="tl-badge {{ $log?->status === 'completed' ? 'green' : 'gray' }}">{{ $log?->status ?? 'open' }}</span>
                    </div>
                    <div class="tl-mobile-record-grid mt-3">
                        <div><span class="tl-muted small d-block">Program</span><span>{{ $session->program->title }}</span></div>
                        <div><span class="tl-muted small d-block">Focus</span><span>{{ $session->focus ?: '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Exercises</span><span>{{ $session->exerciseSummary() }}</span></div>
                    </div>
                </div>
            @empty
                <div class="tl-mobile-record tl-muted">No scheduled sessions.</div>
            @endforelse
        </div>
        <div class="tl-table-wrap d-none d-md-block"><table class="table tl-table align-middle">
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
    </x-tl.table-card>

    <x-tl.table-card title="Workout logs" subtitle="Execution history: completed, partial, missed, notes, RPE, and duration." :count="$workoutLogs->count()" icon="fa-solid fa-clipboard-check">
        <div class="d-md-none vstack gap-2">
            @forelse($workoutLogs as $log)
                <div class="tl-mobile-record">
                    <div class="d-flex justify-content-between gap-2 align-items-start">
                        <div>
                            <div class="tl-muted small">{{ $log->created_at->format('Y-m-d H:i') }}</div>
                            <div class="fw-bold">{{ $log->session->title }}</div>
                        </div>
                        <span class="tl-badge {{ $log->status === 'completed' ? 'green' : 'gray' }}">{{ $log->status }}</span>
                    </div>
                    <div class="tl-mobile-record-grid mt-3">
                        <div><span class="tl-muted small d-block">RPE</span><span>{{ $log->rpe ?: '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Duration</span><span>{{ $log->duration_minutes ? $log->duration_minutes.' min' : '-' }}</span></div>
                    </div>
                    @if($log->notes)
                        <div class="tl-muted small mt-3">{{ $log->notes }}</div>
                    @endif
                </div>
            @empty
                <div class="tl-mobile-record tl-muted">No workout logs yet.</div>
            @endforelse
        </div>
        <div class="tl-table-wrap d-none d-md-block"><table class="table tl-table align-middle">
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
    </x-tl.table-card>

    <x-tl.table-card title="Progress logs" subtitle="Athlete check-ins for food, body, hydration, sleep quality, soreness, and energy." :count="$progressEntries->count()" icon="fa-solid fa-chart-line">
        <div class="d-md-none vstack gap-2">
            @forelse($progressEntries as $entry)
                <div class="tl-mobile-record">
                    <div class="d-flex justify-content-between gap-2 align-items-start">
                        <div>
                            <div class="tl-muted small">Progress check-in</div>
                            <div class="fw-bold">{{ $entry->logged_on->format('M j, Y') }}</div>
                        </div>
                        <span class="tl-badge gray">Energy {{ $entry->energy ?: '-' }}/10</span>
                    </div>
                    <div class="tl-mobile-record-grid mt-3">
                        <div><span class="tl-muted small d-block">Weight</span><span>{{ $entry->weight ?: '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Calories</span><span>{{ $entry->calories ?: '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Protein</span><span>{{ $entry->protein ?: '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Hydration</span><span>{{ $entry->hydration ?: '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Sleep</span><span>{{ $entry->sleep_quality ?: '-' }}/10</span></div>
                        <div><span class="tl-muted small d-block">Soreness</span><span>{{ $entry->soreness ?: '-' }}/10</span></div>
                    </div>
                    @if($entry->notes)
                        <div class="tl-muted small mt-3">{{ $entry->notes }}</div>
                    @endif
                </div>
            @empty
                <div class="tl-mobile-record tl-muted">No progress logs yet.</div>
            @endforelse
        </div>
        <div class="tl-table-wrap d-none d-md-block"><table class="table tl-table align-middle">
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
    </x-tl.table-card>
</div>
