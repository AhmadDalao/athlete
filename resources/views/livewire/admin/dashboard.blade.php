<div>
    <x-tl.page-hero
        eyebrow="Admin command board"
        title="Operations comes first."
        subtitle="Users, coaches, athletes, programs, invitations, and progress are visible without hunting."
    >
        <x-slot:actions>
            <a class="btn btn-tl" href="{{ route('admin.users') }}">Open users</a>
            <a class="btn btn-outline-tl" href="{{ route('admin.settings') }}">System settings</a>
        </x-slot:actions>
        <x-slot:visual>
            <x-tl.product-preview />
        </x-slot:visual>
    </x-tl.page-hero>

    @php
        $metricMeta = [
            'users' => ['icon' => 'fa-solid fa-users', 'tone' => 'blue'],
            'coaches' => ['icon' => 'fa-solid fa-user-tie', 'tone' => 'gold'],
            'athletes' => ['icon' => 'fa-solid fa-person-running', 'tone' => 'emerald'],
            'activePrograms' => ['icon' => 'fa-solid fa-dumbbell', 'tone' => 'lime'],
            'todaySessions' => ['icon' => 'fa-solid fa-calendar-day', 'tone' => 'blue'],
            'openInvites' => ['icon' => 'fa-solid fa-envelope-open-text', 'tone' => 'gold'],
            'assignments' => ['icon' => 'fa-solid fa-link', 'tone' => 'emerald'],
            'checkIns' => ['icon' => 'fa-solid fa-clipboard-check', 'tone' => 'lime'],
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

    <x-tl.section-card
        eyebrow="Audit"
        title="Recent activity"
        subtitle="Latest system changes, invites, account actions, and settings updates."
        :action-href="route('admin.audit')"
        action-label="Open logs"
        icon="fa-solid fa-clipboard-list"
    >
        <div class="tl-table-wrap">
            <table class="table tl-table align-middle">
                <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Summary</th></tr></thead>
                <tbody>
                @forelse($recentAudits as $log)
                    <tr>
                        <td>{{ $log->created_at->format('M j, H:i') }}</td>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                        <td><span class="tl-badge gray">{{ $log->action }}</span></td>
                        <td>{{ $log->summary }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="tl-muted">No audit activity yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-tl.section-card>
</div>
