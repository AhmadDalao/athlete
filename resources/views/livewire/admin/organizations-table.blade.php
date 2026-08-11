<div>
    <x-tl.page-hero
        eyebrow="Platform control"
        title="Organizations"
        subtitle="Create coaching businesses, assign their owners, and inspect member access from one operational directory."
    >
        <x-slot:actions>
            <a class="btn btn-outline-tl" href="{{ route('admin.organizations.export', ['status' => $status, 'theme' => $theme, 'search' => $search]) }}">
                <i class="fa-solid fa-download"></i> Export CSV
            </a>
        </x-slot:actions>
        <x-slot:visual>
            <div class="row g-3">
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-building" label="Filtered" :value="$organizations->total()" tone="lime" /></div>
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-users" label="Visible members" :value="$organizations->sum('active_members_count')" tone="blue" /></div>
            </div>
        </x-slot:visual>
    </x-tl.page-hero>

    <x-tl.section-card eyebrow="New organization" title="Create workspace" subtitle="The owner must already have an active Throughline account. You can add other members from the organization detail page.">
        <form class="row g-3 align-items-end" wire:submit.prevent="createOrganization">
            <div class="col-md-4">
                <label class="form-label" for="organization-name">Name</label>
                <input id="organization-name" class="form-control" wire:model.live.debounce.350ms="name" placeholder="North Performance Lab">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="organization-slug">Slug</label>
                <input id="organization-slug" class="form-control" wire:model="slug" placeholder="north-performance-lab">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="organization-owner">Owner email</label>
                <input id="organization-owner" class="form-control" type="email" wire:model="ownerEmail" placeholder="owner@example.com">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="organization-timezone">Timezone</label>
                <input id="organization-timezone" class="form-control" wire:model="timezone">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="organization-theme">Default theme</label>
                <select id="organization-theme" class="form-select" wire:model="defaultTheme">
                    <option value="system">System</option>
                    <option value="dark">Dark</option>
                    <option value="light">Light</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="organization-plan">Plan key</label>
                <input id="organization-plan" class="form-control" wire:model="planKey" placeholder="coach-plus">
            </div>
            <div class="col-md-3"><button class="btn btn-tl w-100" type="submit"><i class="fa-solid fa-plus"></i> Create organization</button></div>
        </form>
        @if($errors->any())<div class="text-danger small mt-3">{{ $errors->first() }}</div>@endif
    </x-tl.section-card>

    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search organization, slug, plan, or owner'])

    <x-tl.table-card
        title="Organization directory"
        subtitle="Filter, sort, export, and open a workspace to control its owner and members."
        :count="$organizations->total()"
        icon="fa-solid fa-building-shield"
    >
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="organization-status-filter">Status</label>
                <select id="organization-status-filter" class="form-select" wire:model.live="status">
                    <option value="all">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="organization-theme-filter">Theme</label>
                <select id="organization-theme-filter" class="form-select" wire:model.live="theme">
                    <option value="all">All themes</option>
                    <option value="system">System</option>
                    <option value="dark">Dark</option>
                    <option value="light">Light</option>
                </select>
            </div>
            <div class="col-md-3 ms-md-auto">
                <a class="btn btn-outline-tl w-100" href="{{ route('admin.organizations.export', ['status' => $status, 'theme' => $theme, 'search' => $search]) }}">
                    <i class="fa-solid fa-download"></i> Export filtered CSV
                </a>
            </div>
        </div>

        <div class="tl-table-wrap">
            <table class="table tl-table align-middle">
                <thead>
                    <tr>
                        <th><button class="tl-sort-button" type="button" wire:click="sortBy('name')">Organization <i class="fa-solid fa-sort"></i></button></th>
                        <th>Owner</th>
                        <th><button class="tl-sort-button" type="button" wire:click="sortBy('plan_key')">Plan <i class="fa-solid fa-sort"></i></button></th>
                        <th>Members</th>
                        <th>Theme / timezone</th>
                        <th><button class="tl-sort-button" type="button" wire:click="sortBy('status')">Status <i class="fa-solid fa-sort"></i></button></th>
                        <th><button class="tl-sort-button" type="button" wire:click="sortBy('created_at')">Created <i class="fa-solid fa-sort"></i></button></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($organizations as $organization)
                    <tr wire:key="organization-{{ $organization->id }}">
                        <td>
                            <a class="tl-table-primary-link" href="{{ route('admin.organizations.show', $organization) }}">{{ $organization->name }}</a>
                            <span class="d-block tl-muted">{{ $organization->slug }}</span>
                        </td>
                        <td>
                            <strong>{{ $organization->owner?->name ?: 'Not assigned' }}</strong>
                            <span class="d-block tl-muted">{{ $organization->owner?->email ?: 'Assign an owner' }}</span>
                        </td>
                        <td>{{ $organization->plan_key ?: 'Unassigned' }}</td>
                        <td>
                            <strong>{{ $organization->active_members_count }} active</strong>
                            <span class="d-block tl-muted">{{ $organization->coaches_count }} coaches · {{ $organization->athletes_count }} athletes</span>
                        </td>
                        <td>{{ str($organization->default_theme)->headline() }}<span class="d-block tl-muted">{{ $organization->timezone }}</span></td>
                        <td><span class="tl-badge {{ $organization->status === 'active' ? 'green' : 'gray' }}">{{ $organization->status }}</span></td>
                        <td>{{ $organization->created_at->format('Y-m-d') }}</td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap">
                                <a class="btn btn-outline-tl btn-sm" href="{{ route('admin.organizations.show', $organization) }}">Open</a>
                                <button class="btn btn-outline-tl btn-sm" type="button" wire:click="toggleStatus({{ $organization->id }})" wire:confirm="Change this organization's status?">
                                    {{ $organization->status === 'active' ? 'Pause' : 'Activate' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="tl-muted">No organizations match the current filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $organizations->links() }}</div>
    </x-tl.table-card>
</div>
