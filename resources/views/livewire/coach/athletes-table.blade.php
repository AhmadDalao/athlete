<div>
    <div class="tl-hero"><div class="tl-eyebrow">Roster</div><h2 class="h1 fw-bold">My athletes</h2><p class="tl-muted mb-0">Coach-scoped table. You only see athletes assigned to you.</p></div>
    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search athlete, email, goal'])
    <div class="tl-panel">
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Athlete</th><th>Goal</th><th>Progress logs</th><th>Workout logs</th><th>Joined</th></tr></thead>
            <tbody>
            @forelse($athletes as $athlete)
                <tr><td><strong>{{ $athlete->name }}</strong><br><span class="tl-muted">{{ $athlete->email }}</span></td><td>{{ $athlete->primary_goal ?: 'Not set' }}</td><td>{{ $athlete->progress_entries_count }}</td><td>{{ $athlete->workout_logs_count }}</td><td>{{ $athlete->created_at->format('Y-m-d') }}</td></tr>
            @empty
                <tr><td colspan="5" class="tl-muted">No assigned athletes.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="mt-3">{{ $athletes->links() }}</div>
    </div>
</div>
