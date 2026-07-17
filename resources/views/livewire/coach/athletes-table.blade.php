<div>
    <x-tl.page-hero
        eyebrow="Roster"
        title="My athletes"
        subtitle="Coach-scoped roster. Open an athlete to review programs, sessions, workout logs, and progress in one place."
    />

    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search athlete, email, goal'])

    <x-tl.table-card
        title="Athlete table"
        subtitle="Click Open to manage this athlete's coaching record."
        :count="$athletes->total()"
        icon="fa-solid fa-users-line"
    >
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Athlete</th><th>Goal</th><th>Progress logs</th><th>Workout logs</th><th>Joined</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($athletes as $athlete)
                <tr><td><strong>{{ $athlete->name }}</strong><br><span class="tl-muted">{{ $athlete->email }}</span></td><td>{{ $athlete->primary_goal ?: 'Not set' }}</td><td>{{ $athlete->progress_entries_count }}</td><td>{{ $athlete->workout_logs_count }}</td><td>{{ $athlete->created_at->format('Y-m-d') }}</td><td><a class="btn btn-outline-tl btn-sm" href="{{ route('coach.athletes.show', $athlete) }}">Open</a></td></tr>
            @empty
                <tr><td colspan="6" class="tl-muted">No assigned athletes.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="mt-3">{{ $athletes->links() }}</div>
    </x-tl.table-card>
</div>
