<div>
    <x-tl.page-hero
        eyebrow="User profile"
        :title="$user->name"
        :subtitle="$user->email.' · '.ucfirst($user->role).' · '.ucfirst($user->status)"
    >
        <x-slot:actions>
            <a class="btn btn-outline-tl" href="{{ route('admin.users') }}"><i class="fa-solid fa-arrow-left"></i> Back to users</a>
        </x-slot:actions>
    </x-tl.page-hero>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-key" label="Permissions" :value="$user->permissions_count" tone="lime" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-users-line" label="Coach athletes" :value="$user->coach_assignments_count" tone="emerald" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-dumbbell" label="Programs" :value="$user->coach_programs_count" tone="gold" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-clipboard-check" label="Workout logs" :value="$user->workout_logs_count" tone="blue" /></div>
    </div>

    <x-tl.section-card eyebrow="Account control" title="Edit profile and access" subtitle="Owner accounts stay protected; normal accounts can be updated here.">
        <form class="row g-3 align-items-end" wire:submit.prevent="updateUser">
            <div class="col-md-3"><label class="form-label">Name</label><input class="form-control" wire:model="name"></div>
            <div class="col-md-3"><label class="form-label">Email</label><input class="form-control" type="email" wire:model="email"></div>
            <div class="col-md-2"><label class="form-label">Phone</label><input class="form-control" wire:model="phone"></div>
            <div class="col-md-2">
                <label class="form-label">Role</label>
                <select class="form-select" wire:model="role" @disabled($user->isOwner())>
                    <option value="owner">Owner</option>
                    <option value="admin">Admin</option>
                    <option value="coach">Coach</option>
                    <option value="athlete">Athlete</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" wire:model="status" @disabled($user->isOwner())>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">Primary goal</label><input class="form-control" wire:model="primaryGoal"></div>
            <div class="col-md-3"><label class="form-label">New password</label><input class="form-control" type="password" wire:model="password" placeholder="Leave blank"></div>
            <div class="col-md-3"><button class="btn btn-tl w-100" type="submit">Save user</button></div>
            <div class="col-12"><label class="form-label">Bio / notes</label><textarea class="form-control" rows="3" wire:model="bio"></textarea></div>
        </form>
        @if($errors->any())<div class="text-danger small mt-3">{{ $errors->first() }}</div>@endif
    </x-tl.section-card>

    <x-tl.table-card title="Coach assignments" subtitle="Roster links for this user, whether they are coach or athlete." :count="$athleteAssignments->count() + $coachAssignments->count()" icon="fa-solid fa-user-group">
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Coach</th><th>Athlete</th><th>Status</th><th>Started</th><th>Ended</th></tr></thead>
            <tbody>
            @forelse($athleteAssignments as $assignment)
                <tr><td>{{ $assignment->coach->name }}</td><td>{{ $user->name }}</td><td><span class="tl-badge green">{{ $assignment->status }}</span></td><td>{{ $assignment->started_at?->format('Y-m-d') ?: '-' }}</td><td>{{ $assignment->ended_at?->format('Y-m-d') ?: '-' }}</td></tr>
            @empty
                <tr><td colspan="5" class="tl-muted">No athlete-side coach assignments.</td></tr>
            @endforelse
            @forelse($coachAssignments as $assignment)
                <tr><td>{{ $user->name }}</td><td>{{ $assignment->athlete->name }}</td><td><span class="tl-badge green">{{ $assignment->status }}</span></td><td>{{ $assignment->started_at?->format('Y-m-d') ?: '-' }}</td><td>{{ $assignment->ended_at?->format('Y-m-d') ?: '-' }}</td></tr>
            @empty
            @endforelse
            </tbody>
        </table></div>
    </x-tl.table-card>

    <x-tl.table-card title="Programs" subtitle="Programs this user owns as coach or receives as athlete." :count="$programsAsCoach->count() + $programsAsAthlete->count()" icon="fa-solid fa-dumbbell">
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Program</th><th>Coach</th><th>Athlete</th><th>Status</th><th>Dates</th></tr></thead>
            <tbody>
            @forelse($programsAsCoach as $program)
                <tr><td>{{ $program->title }}</td><td>{{ $user->name }}</td><td>{{ $program->athlete->name }}</td><td><span class="tl-badge green">{{ $program->status }}</span></td><td>{{ $program->starts_on?->format('Y-m-d') ?: '-' }} to {{ $program->ends_on?->format('Y-m-d') ?: '-' }}</td></tr>
            @empty
            @endforelse
            @forelse($programsAsAthlete as $program)
                <tr><td>{{ $program->title }}</td><td>{{ $program->coach->name }}</td><td>{{ $user->name }}</td><td><span class="tl-badge green">{{ $program->status }}</span></td><td>{{ $program->starts_on?->format('Y-m-d') ?: '-' }} to {{ $program->ends_on?->format('Y-m-d') ?: '-' }}</td></tr>
            @empty
            @endforelse
            @if($programsAsCoach->isEmpty() && $programsAsAthlete->isEmpty())
                <tr><td colspan="5" class="tl-muted">No programs tied to this user.</td></tr>
            @endif
            </tbody>
        </table></div>
    </x-tl.table-card>

    <x-tl.table-card title="Workout logs" subtitle="Completed, partial, and missed session records tied to this user." :count="$workoutLogs->count()" icon="fa-solid fa-clipboard-check">
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Date</th><th>Session</th><th>Coach</th><th>Status</th><th>RPE</th><th>Duration</th><th>Notes</th></tr></thead>
            <tbody>
            @forelse($workoutLogs as $log)
                <tr><td>{{ $log->created_at->format('Y-m-d') }}</td><td>{{ $log->session->title }}</td><td>{{ $log->session->program->coach->name }}</td><td><span class="tl-badge {{ $log->status === 'completed' ? 'green' : 'gray' }}">{{ $log->status }}</span></td><td>{{ $log->rpe ?: '-' }}</td><td>{{ $log->duration_minutes ? $log->duration_minutes.' min' : '-' }}</td><td>{{ $log->notes ?: '-' }}</td></tr>
            @empty
                <tr><td colspan="7" class="tl-muted">No workout logs yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-tl.table-card>

    <x-tl.table-card title="Progress entries" subtitle="Body, food, hydration, sleep quality, energy, and notes." :count="$progressEntries->count()" icon="fa-solid fa-chart-line">
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Date</th><th>Weight</th><th>Calories</th><th>Protein</th><th>Hydration</th><th>Sleep</th><th>Energy</th><th>Notes</th></tr></thead>
            <tbody>
            @forelse($progressEntries as $entry)
                <tr><td>{{ $entry->logged_on->format('Y-m-d') }}</td><td>{{ $entry->weight ?: '-' }}</td><td>{{ $entry->calories ?: '-' }}</td><td>{{ $entry->protein ?: '-' }}</td><td>{{ $entry->hydration ?: '-' }}</td><td>{{ $entry->sleep_quality ?: '-' }}/10</td><td>{{ $entry->energy ?: '-' }}/10</td><td>{{ $entry->notes ?: '-' }}</td></tr>
            @empty
                <tr><td colspan="8" class="tl-muted">No progress entries yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-tl.table-card>

    <x-tl.table-card title="Audit trail" subtitle="Recent recorded actions for this user." :count="$auditLogs->count()" icon="fa-solid fa-clipboard-list">
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>When</th><th>Action</th><th>Summary</th><th>IP</th></tr></thead>
            <tbody>
            @forelse($auditLogs as $log)
                <tr><td>{{ $log->created_at->format('Y-m-d H:i') }}</td><td>{{ $log->action }}</td><td>{{ $log->summary }}</td><td>{{ $log->ip_address ?: '-' }}</td></tr>
            @empty
                <tr><td colspan="4" class="tl-muted">No user audit records yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-tl.table-card>
</div>
