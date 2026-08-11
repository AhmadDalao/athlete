<div>
    <x-tl.page-hero
        eyebrow="Coach workspace"
        title="Coach home"
        subtitle="Your athletes, programs, schedule, and invite queue in one direct view."
    >
        <x-slot:actions>
            <a class="btn btn-tl" href="{{ route('coach.programs') }}">Build program</a>
            <a class="btn btn-outline-tl" href="{{ route('coach.invitations') }}">Invite athlete</a>
        </x-slot:actions>
        <x-slot:visual>
            <div class="row g-3">
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-users-line" label="Athletes" :value="$stats['athletes']" tone="emerald" /></div>
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-dumbbell" label="Programs" :value="$stats['programs']" tone="lime" /></div>
                <div class="col-12"><x-tl.metric-card icon="fa-solid fa-calendar-day" label="Today" :value="$stats['sessionsToday']" detail="Sessions on the schedule." tone="blue" /></div>
            </div>
        </x-slot:visual>
    </x-tl.page-hero>

    @php
        $metricMeta = [
            'athletes' => ['icon' => 'fa-solid fa-users-line', 'tone' => 'emerald'],
            'programs' => ['icon' => 'fa-solid fa-dumbbell', 'tone' => 'lime'],
            'sessionsToday' => ['icon' => 'fa-solid fa-calendar-day', 'tone' => 'blue'],
            'pendingInvites' => ['icon' => 'fa-solid fa-paper-plane', 'tone' => 'gold'],
            'completedLogs' => ['icon' => 'fa-solid fa-circle-check', 'tone' => 'emerald'],
        ];
    @endphp
    <div class="row g-3 mb-3">
        @foreach($stats as $label => $value)
            <div class="col-6 col-lg-3">
                <x-tl.metric-card
                    :icon="$metricMeta[$label]['icon'] ?? 'fa-solid fa-chart-simple'"
                    :label="str($label)->headline()"
                    :value="$value"
                    :tone="$metricMeta[$label]['tone'] ?? 'lime'"
                />
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <x-tl.section-card
                title="Assigned athletes"
                subtitle="Open a profile when someone needs attention."
                :action-href="route('coach.athletes')"
                action-label="Open table"
                icon="fa-solid fa-users-line"
            >
                @forelse($athletes as $assignment)
                    <a class="tl-mobile-record mb-2" href="{{ route('coach.athletes.show', $assignment->athlete) }}">
                        <strong>{{ $assignment->athlete->name }}</strong>
                        <span class="tl-muted d-block">{{ $assignment->athlete->email }}</span>
                    </a>
                @empty
                    <p class="tl-muted mb-0">No athletes assigned yet.</p>
                @endforelse
            </x-tl.section-card>
        </div>
        <div class="col-lg-7">
            <x-tl.section-card
                title="Upcoming sessions"
                subtitle="The next scheduled work for your roster."
                :action-href="route('coach.schedule')"
                action-label="Full schedule"
                icon="fa-solid fa-dumbbell"
            >
                <div class="tl-table-wrap"><table class="table tl-table align-middle">
                    <thead><tr><th>Date</th><th>Session</th><th>Athlete</th><th>Preview</th></tr></thead>
                    <tbody>
                    @forelse($sessions as $session)
                        <tr><td>{{ $session->scheduled_for->timezone($session->assignment->timezone)->format('M j') }}</td><td><strong>{{ $session->session->title }}</strong><br><span class="tl-muted">{{ $session->session->focus }}</span></td><td>{{ $session->athlete->name }}</td><td>{{ $session->session->exerciseSummary() }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="tl-muted">No upcoming sessions.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </x-tl.section-card>
        </div>
    </div>
</div>
