<div>
    <div class="tl-hero">
        <div class="tl-eyebrow">User profile</div>
        <div class="d-flex justify-content-between gap-3 flex-wrap align-items-start">
            <div>
                <h2 class="h1 fw-bold">{{ $user->name }}</h2>
                <p class="tl-muted mb-0">{{ $user->email }} · {{ ucfirst($user->role) }} · {{ ucfirst($user->status) }}</p>
            </div>
            <a class="btn btn-outline-tl" href="{{ route('admin.users') }}"><i class="fa-solid fa-arrow-left"></i> Back to users</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="tl-stat"><span class="tl-muted">Permissions</span><strong>{{ $user->permissions_count }}</strong></div></div>
        <div class="col-md-3"><div class="tl-stat"><span class="tl-muted">Coach athletes</span><strong>{{ $user->coach_assignments_count }}</strong></div></div>
        <div class="col-md-3"><div class="tl-stat"><span class="tl-muted">Programs</span><strong>{{ $user->coach_programs_count }}</strong></div></div>
        <div class="col-md-3"><div class="tl-stat"><span class="tl-muted">Workout logs</span><strong>{{ $user->workout_logs_count }}</strong></div></div>
    </div>

    <div class="tl-panel">
        <div class="tl-eyebrow">Account control</div>
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
    </div>

    <div class="tl-panel">
        <h3 class="h5">Coach assignments</h3>
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
    </div>

    <div class="tl-panel">
        <h3 class="h5">Programs</h3>
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
    </div>

    <div class="tl-panel">
        <h3 class="h5">Workout logs</h3>
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
    </div>

    <div class="tl-panel">
        <h3 class="h5">Progress entries</h3>
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
    </div>

    <div class="tl-panel">
        <h3 class="h5">Audit trail</h3>
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
    </div>
</div>
