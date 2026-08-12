<div>
    <x-tl.page-hero
        eyebrow="People control"
        :title="$effectiveRole === 'all' ? 'Users' : ucfirst($effectiveRole).'s'"
        :subtitle="$platformMode ? 'Create users, control roles, and keep access direct.' : 'Manage only members of the active organization.'"
    >
        <x-slot:actions>
            <a class="btn btn-outline-tl" href="{{ route('admin.users.export', ['role' => $effectiveRole, 'search' => $search]) }}">
                <i class="fa-solid fa-download"></i> Export CSV
            </a>
        </x-slot:actions>
        <x-slot:visual>
            <div class="row g-3">
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-users" label="Visible rows" :value="$users->total()" tone="blue" /></div>
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-filter" label="Filter" :value="str($effectiveRole)->headline()" tone="lime" /></div>
            </div>
        </x-slot:visual>
    </x-tl.page-hero>

    <x-tl.section-card eyebrow="Create account" title="Add user" :subtitle="$platformMode ? 'Create platform accounts without leaving the control table.' : 'New accounts are attached only to the active organization.'">
        <form class="row g-3 align-items-end" wire:submit.prevent="createUser">
            <div class="col-md-3"><label class="form-label">Name</label><input class="form-control" wire:model="name"></div>
            <div class="col-md-3"><label class="form-label">Email</label><input class="form-control" type="email" wire:model="email"></div>
            <div class="col-md-2"><label class="form-label">Phone</label><input class="form-control" wire:model="phone"></div>
            <div class="col-md-2"><label class="form-label">Role</label><select class="form-select" wire:model="newRole" @disabled($roleLocked)>@foreach($creationRoleOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Password</label><input class="form-control" type="password" wire:model="password"></div>
            <div class="col-12"><button class="btn btn-tl" type="submit">Create user</button></div>
        </form>
        @if($errors->any())<div class="text-danger small mt-3">{{ $errors->first() }}</div>@endif
    </x-tl.section-card>

    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search name, email, phone, or goal'])

    <x-tl.table-card
        title="User table"
        subtitle="Search the current result, filter by role, export what is visible, and open full profiles."
        :count="$users->total()"
        icon="fa-solid fa-users"
    >
        @unless($roleLocked)<div class="row g-3 align-items-end mb-3">
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
                <a class="btn btn-outline-tl w-100" href="{{ route('admin.users.export', ['role' => $effectiveRole, 'search' => $search]) }}">
                    <i class="fa-solid fa-download"></i> Export CSV
                </a>
            </div>
        </div>@endunless

        <div class="tl-table-wrap">
            <table class="table tl-table align-middle">
                <thead><tr><th>User</th><th>Role</th><th>Contact</th><th>Status</th><th>Tracking</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($users as $user)
                    @php
                        $membership = $platformMode ? null : $user->organizationMemberships->first();
                        $displayRole = $membership?->role ?? $user->role;
                        $displayStatus = $membership?->status ?? $user->status;
                        $protectedOwner = $displayRole === 'organization_owner' || ($platformMode && $user->isOwner());
                    @endphp
                    <tr>
                        <td><strong>{{ $user->name }}</strong><br><span class="tl-muted">{{ $user->email }}</span></td>
                        <td><span class="tl-badge {{ str_contains($displayRole, 'owner') ? 'gold' : 'gray' }}">{{ str($displayRole)->headline() }}</span></td>
                        <td>{{ $user->phone ?: 'No phone' }}</td>
                        <td><span class="tl-badge {{ $displayStatus === 'active' ? 'green' : 'gray' }}">{{ $displayStatus }}</span></td>
                        <td>
                            <span class="d-block">Programs: {{ $user->coach_programs_count }}</span>
                            <span class="d-block">Athletes: {{ $user->coach_assignments_count }}</span>
                            <span class="d-block">Check-ins: {{ $user->progress_entries_count }}</span>
                        </td>
                        <td>{{ $user->created_at->format('Y-m-d') }}</td>
                        <td>
                            <a class="btn btn-outline-tl btn-sm" href="{{ route('admin.users.show', $user) }}">Open</a>
                            @if($protectedOwner)
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
    </x-tl.table-card>
</div>
