<div>
    <x-tl.page-hero
        eyebrow="Organization control"
        :title="$organization->name"
        :subtitle="$organization->slug.' · '.ucfirst($organization->status).' · '.($organization->plan_key ?: 'No plan assigned')"
    >
        <x-slot:actions>
            <a class="btn btn-outline-tl" href="{{ route('admin.organizations') }}"><i class="fa-solid fa-arrow-left"></i> Organizations</a>
        </x-slot:actions>
        <x-slot:visual>
            <div class="row g-3">
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-users" label="Active members" :value="(int) ($counts->active ?? 0)" tone="lime" /></div>
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-person-running" label="Athletes" :value="(int) ($counts->athletes ?? 0)" tone="emerald" /></div>
            </div>
        </x-slot:visual>
    </x-tl.page-hero>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-users" label="Total members" :value="(int) ($counts->total ?? 0)" tone="blue" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-user-tie" label="Coaches" :value="(int) ($counts->coaches ?? 0)" tone="gold" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-palette" label="Default theme" :value="str($organization->default_theme)->headline()" tone="lime" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-earth-asia" label="Timezone" :value="$organization->timezone" tone="emerald" /></div>
    </div>

    <x-tl.section-card eyebrow="Workspace identity" title="Organization settings" subtitle="Changing the owner safely promotes the new owner and keeps the previous owner active as an organization admin.">
        <form class="row g-3 align-items-end" wire:submit.prevent="saveOrganization">
            <div class="col-md-4"><label class="form-label" for="detail-name">Name</label><input id="detail-name" class="form-control" wire:model="name"></div>
            <div class="col-md-4"><label class="form-label" for="detail-slug">Slug</label><input id="detail-slug" class="form-control" wire:model="slug"></div>
            <div class="col-md-4"><label class="form-label" for="detail-owner">Owner email</label><input id="detail-owner" class="form-control" type="email" wire:model="ownerEmail" placeholder="owner@example.com"></div>
            <div class="col-md-3">
                <label class="form-label" for="detail-status">Status</label>
                <select id="detail-status" class="form-select" wire:model="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
            </div>
            <div class="col-md-3"><label class="form-label" for="detail-timezone">Timezone</label><input id="detail-timezone" class="form-control" wire:model="timezone"></div>
            <div class="col-md-3">
                <label class="form-label" for="detail-theme">Default theme</label>
                <select id="detail-theme" class="form-select" wire:model="defaultTheme"><option value="system">System</option><option value="dark">Dark</option><option value="light">Light</option></select>
            </div>
            <div class="col-md-3"><label class="form-label" for="detail-plan">Plan key</label><input id="detail-plan" class="form-control" wire:model="planKey"></div>
            <div class="col-12"><button class="btn btn-tl" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save organization</button></div>
        </form>
        @if($errors->any())<div class="text-danger small mt-3">{{ $errors->first() }}</div>@endif
    </x-tl.section-card>

    <x-tl.section-card eyebrow="Membership" title="Add or restore member" subtitle="Use an existing active account. A matching inactive membership is restored instead of duplicated.">
        <form class="row g-3 align-items-end" wire:submit.prevent="addMember">
            <div class="col-md-6"><label class="form-label" for="member-email">User email</label><input id="member-email" class="form-control" type="email" wire:model="memberEmail" placeholder="member@example.com"></div>
            <div class="col-md-3">
                <label class="form-label" for="member-role">Organization role</label>
                <select id="member-role" class="form-select" wire:model="memberRole"><option value="organization_admin">Organization admin</option><option value="coach">Coach</option><option value="athlete">Athlete</option></select>
            </div>
            <div class="col-md-3"><button class="btn btn-tl w-100" type="submit"><i class="fa-solid fa-user-plus"></i> Save member</button></div>
        </form>
    </x-tl.section-card>

    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search member name, email, or phone'])

    <x-tl.table-card title="Organization members" subtitle="Roles and access status are organization-specific. The owner row is locked until another owner is assigned above." :count="$memberships->total()" icon="fa-solid fa-people-group">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="member-role-filter">Role</label>
                <select id="member-role-filter" class="form-select" wire:model.live="memberRoleFilter">
                    <option value="all">All roles</option><option value="organization_owner">Owner</option><option value="organization_admin">Admin</option><option value="coach">Coach</option><option value="athlete">Athlete</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="member-status-filter">Status</label>
                <select id="member-status-filter" class="form-select" wire:model.live="memberStatus"><option value="all">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
            </div>
            <div class="col-md-3 ms-md-auto">
                <a class="btn btn-outline-tl w-100" href="{{ route('admin.organizations.members.export', ['organization' => $organization, 'status' => $memberStatus, 'role' => $memberRoleFilter, 'search' => $search]) }}">
                    <i class="fa-solid fa-download"></i> Export members
                </a>
            </div>
        </div>

        <div class="tl-table-wrap">
            <table class="table tl-table align-middle">
                <thead><tr><th>Member</th><th>Platform role</th><th>Organization role</th><th>Status</th><th>Joined</th><th>Last active</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($memberships as $membership)
                    <tr wire:key="membership-{{ $membership->id }}">
                        <td><a class="tl-table-primary-link" href="{{ route('admin.users.show', $membership->user) }}">{{ $membership->user->name }}</a><span class="d-block tl-muted">{{ $membership->user->email }}</span></td>
                        <td><span class="tl-badge gray">{{ $membership->user->role }}</span></td>
                        <td>
                            @if($membership->isOwner())
                                <span class="tl-badge gold">Organization owner</span>
                            @else
                                <select class="form-select form-select-sm" wire:model="membershipRoles.{{ $membership->id }}" aria-label="Role for {{ $membership->user->name }}">
                                    <option value="organization_admin">Organization admin</option><option value="coach">Coach</option><option value="athlete">Athlete</option>
                                </select>
                            @endif
                        </td>
                        <td><span class="tl-badge {{ $membership->status === 'active' ? 'green' : 'gray' }}">{{ $membership->status }}</span></td>
                        <td>{{ $membership->joined_at?->format('Y-m-d') ?: '-' }}</td>
                        <td>{{ $membership->last_active_at?->diffForHumans() ?: 'Never' }}</td>
                        <td>
                            @if($membership->isOwner())
                                <span class="tl-badge gold">Owner protected</span>
                            @else
                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="btn btn-outline-tl btn-sm" type="button" wire:click="saveMemberRole({{ $membership->id }})">Save role</button>
                                    <a class="btn btn-outline-tl btn-sm" href="{{ route('admin.organizations.members.permissions', [$organization, $membership]) }}"><i class="fa-solid fa-key"></i> Permissions</a>
                                    <button class="btn btn-outline-tl btn-sm" type="button" wire:click="toggleMemberStatus({{ $membership->id }})" wire:confirm="Change this member's organization access?">{{ $membership->status === 'active' ? 'Disable' : 'Activate' }}</button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="tl-muted">No members match the current filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $memberships->links() }}</div>
    </x-tl.table-card>

    <x-tl.table-card title="Organization audit trail" subtitle="Recent owner, member, status, and settings actions." :count="$recentAudit->count()" icon="fa-solid fa-clock-rotate-left">
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>When</th><th>Action</th><th>Summary</th><th>IP</th></tr></thead>
            <tbody>
            @forelse($recentAudit as $log)
                <tr><td>{{ $log->created_at->format('Y-m-d H:i') }}</td><td>{{ $log->action }}</td><td>{{ $log->summary }}</td><td>{{ $log->ip_address ?: '-' }}</td></tr>
            @empty
                <tr><td colspan="4" class="tl-muted">No organization activity has been recorded.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-tl.table-card>
</div>
