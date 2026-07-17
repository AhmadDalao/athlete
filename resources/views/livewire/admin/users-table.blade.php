<div>
    <x-tl.page-hero
        eyebrow="People control"
        :title="$role === 'all' ? 'Users' : ucfirst($role).'s'"
        subtitle="Create users, control roles, and keep access direct."
    >
        <x-slot:actions>
            <a class="btn btn-outline-tl" href="{{ route('admin.users.export', ['role' => $role, 'search' => $search]) }}">
                <i class="fa-solid fa-download"></i> Export CSV
            </a>
        </x-slot:actions>
        <x-slot:visual>
            <div class="row g-3">
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-users" label="Visible rows" :value="$users->total()" tone="blue" /></div>
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-filter" label="Filter" :value="str($role)->headline()" tone="lime" /></div>
            </div>
        </x-slot:visual>
    </x-tl.page-hero>

    <div class="tl-panel">
        <div class="tl-eyebrow">Create account</div>
        <form class="row g-3 align-items-end" wire:submit.prevent="createUser">
            <div class="col-md-3"><label class="form-label">Name</label><input class="form-control" wire:model="name"></div>
            <div class="col-md-3"><label class="form-label">Email</label><input class="form-control" type="email" wire:model="email"></div>
            <div class="col-md-2"><label class="form-label">Phone</label><input class="form-control" wire:model="phone"></div>
            <div class="col-md-2"><label class="form-label">Role</label><select class="form-select" wire:model="newRole"><option value="admin">Admin</option><option value="coach">Coach</option><option value="athlete">Athlete</option></select></div>
            <div class="col-md-2"><label class="form-label">Password</label><input class="form-control" type="password" wire:model="password"></div>
            <div class="col-12"><button class="btn btn-tl" type="submit">Create user</button></div>
        </form>
        @if($errors->any())<div class="text-danger small mt-3">{{ $errors->first() }}</div>@endif
    </div>

    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search name, email, phone, or goal'])

    <div class="tl-panel">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label">Role</label>
                <select class="form-select" wire:model.live="role">
                    <option value="all">All roles</option>
                    <option value="owner">Owner</option>
                    <option value="admin">Admin</option>
                    <option value="coach">Coach</option>
                    <option value="athlete">Athlete</option>
                </select>
            </div>
            <div class="col-md-3">
                <a class="btn btn-outline-tl w-100" href="{{ route('admin.users.export', ['role' => $role, 'search' => $search]) }}">
                    <i class="fa-solid fa-download"></i> Export CSV
                </a>
            </div>
        </div>

        <div class="tl-table-wrap">
            <table class="table tl-table align-middle">
                <thead><tr><th>User</th><th>Role</th><th>Contact</th><th>Status</th><th>Tracking</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong><br><span class="tl-muted">{{ $user->email }}</span></td>
                        <td><span class="tl-badge {{ $user->role === 'owner' ? 'gold' : 'gray' }}">{{ $user->role }}</span></td>
                        <td>{{ $user->phone ?: 'No phone' }}</td>
                        <td><span class="tl-badge {{ $user->status === 'active' ? 'green' : 'gray' }}">{{ $user->status }}</span></td>
                        <td>
                            <span class="d-block">Programs: {{ $user->coach_programs_count }}</span>
                            <span class="d-block">Athletes: {{ $user->coach_assignments_count }}</span>
                            <span class="d-block">Check-ins: {{ $user->progress_entries_count }}</span>
                        </td>
                        <td>{{ $user->created_at->format('Y-m-d') }}</td>
                        <td>
                            <a class="btn btn-outline-tl btn-sm" href="{{ route('admin.users.show', $user) }}">Open</a>
                            @if($user->role === 'owner')
                                <span class="tl-badge gold">Owner locked</span>
                            @else
                                <button class="btn btn-outline-tl btn-sm" wire:click="toggleStatus({{ $user->id }})">Toggle status</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="tl-muted">No users match the filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $users->links() }}</div>
    </div>
</div>
