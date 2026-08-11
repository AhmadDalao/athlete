<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableControls;
use App\Models\AthleteProfile;
use App\Models\AuditLog;
use App\Models\CoachProfile;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class OrganizationDetail extends Component
{
    use WithPagination;
    use WithTableControls;

    public Organization $organization;

    public string $name = '';

    public string $slug = '';

    public string $status = '';

    public string $timezone = '';

    public string $defaultTheme = '';

    public string $planKey = '';

    public string $ownerEmail = '';

    public string $memberEmail = '';

    public string $memberRole = 'athlete';

    public string $memberStatus = 'all';

    public string $memberRoleFilter = 'all';

    public array $membershipRoles = [];

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
        $this->fillFromOrganization();
    }

    public function updatedSearch(): void
    {
        $this->resetPage('membersPage');
    }

    public function updatedPerPage(): void
    {
        $this->resetPage('membersPage');
    }

    public function updatedMemberStatus(): void
    {
        $this->resetPage('membersPage');
    }

    public function updatedMemberRoleFilter(): void
    {
        $this->resetPage('membersPage');
    }

    public function saveOrganization(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('organizations', 'slug')->ignore($this->organization->id)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'timezone' => ['required', 'timezone'],
            'defaultTheme' => ['required', Rule::in(['system', 'dark', 'light'])],
            'planKey' => ['nullable', 'string', 'max:80'],
            'ownerEmail' => ['nullable', 'email', Rule::exists('users', 'email')->where('status', 'active')],
        ]);

        DB::transaction(function () use ($data): void {
            $previousOwnerId = $this->organization->owner_id;
            $owner = filled($data['ownerEmail'])
                ? User::query()->where('email', $data['ownerEmail'])->firstOrFail()
                : null;

            $this->organization->update([
                'owner_id' => $owner?->id,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'status' => $data['status'],
                'timezone' => $data['timezone'],
                'default_theme' => $data['defaultTheme'],
                'plan_key' => $data['planKey'] ?: null,
            ]);

            if ($owner) {
                OrganizationMembership::updateOrCreate(
                    ['organization_id' => $this->organization->id, 'user_id' => $owner->id],
                    ['role' => 'organization_owner', 'status' => 'active', 'joined_at' => now()]
                );
                $owner->forceFill(['current_organization_id' => $owner->current_organization_id ?: $this->organization->id])->saveQuietly();
            }

            if ($previousOwnerId && $previousOwnerId !== $owner?->id) {
                OrganizationMembership::query()
                    ->where('organization_id', $this->organization->id)
                    ->where('user_id', $previousOwnerId)
                    ->where('role', 'organization_owner')
                    ->update(['role' => 'organization_admin', 'status' => 'active']);
            }

            $this->audit('organization.updated', "Updated {$this->organization->name} settings.");
        });

        $this->organization->refresh();
        $this->fillFromOrganization();
        session()->flash('status', 'Organization updated.');
    }

    public function addMember(): void
    {
        $data = $this->validate([
            'memberEmail' => ['required', 'email', Rule::exists('users', 'email')->where('status', 'active')],
            'memberRole' => ['required', Rule::in(['organization_admin', 'coach', 'athlete'])],
        ]);
        $user = User::query()->where('email', $data['memberEmail'])->firstOrFail();

        $membership = OrganizationMembership::updateOrCreate(
            ['organization_id' => $this->organization->id, 'user_id' => $user->id],
            ['role' => $data['memberRole'], 'status' => 'active', 'joined_at' => now()]
        );
        $this->ensureRoleProfile($user, $membership->role);
        $user->forceFill(['current_organization_id' => $user->current_organization_id ?: $this->organization->id])->saveQuietly();
        $this->membershipRoles[$membership->id] = $membership->role;
        $this->audit('organization.member_added', "Added {$user->email} as {$membership->role}.");

        $this->reset('memberEmail');
        $this->memberRole = 'athlete';
        session()->flash('status', 'Organization member saved.');
    }

    public function saveMemberRole(int $membershipId): void
    {
        $membership = $this->membership($membershipId);

        if ($membership->isOwner()) {
            session()->flash('status', 'The organization owner role is protected. Assign a new owner first.');

            return;
        }

        $role = $this->membershipRoles[$membershipId] ?? $membership->role;
        validator(['role' => $role], ['role' => ['required', Rule::in(['organization_admin', 'coach', 'athlete'])]])->validate();

        $membership->update(['role' => $role]);
        $this->ensureRoleProfile($membership->user, $role);
        $this->audit('organization.member_role', "Changed {$membership->user->email} role to {$role}.");
        session()->flash('status', 'Member role updated.');
    }

    public function toggleMemberStatus(int $membershipId): void
    {
        $membership = $this->membership($membershipId);

        if ($membership->isOwner() || $membership->user_id === $this->organization->owner_id) {
            session()->flash('status', 'The organization owner cannot be disabled. Assign a new owner first.');

            return;
        }

        $status = $membership->status === 'active' ? 'inactive' : 'active';
        $membership->update(['status' => $status]);

        if ($status === 'inactive' && $membership->user->current_organization_id === $this->organization->id) {
            $nextOrganizationId = $membership->user->organizationMemberships()
                ->where('status', 'active')
                ->where('organization_id', '!=', $this->organization->id)
                ->value('organization_id');
            $membership->user->forceFill(['current_organization_id' => $nextOrganizationId])->saveQuietly();
        }

        $this->audit('organization.member_status', "Changed {$membership->user->email} membership to {$status}.");
        session()->flash('status', 'Member status updated.');
    }

    private function membership(int $membershipId): OrganizationMembership
    {
        return $this->organization->memberships()->with('user')->findOrFail($membershipId);
    }

    private function ensureRoleProfile(User $user, string $role): void
    {
        if ($role === 'coach') {
            CoachProfile::firstOrCreate(['organization_id' => $this->organization->id, 'user_id' => $user->id]);
        }

        if ($role === 'athlete') {
            AthleteProfile::firstOrCreate(['organization_id' => $this->organization->id, 'user_id' => $user->id]);
        }
    }

    private function audit(string $action, string $summary): void
    {
        AuditLog::create([
            'organization_id' => $this->organization->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'entity' => 'organization',
            'entity_id' => $this->organization->id,
            'summary' => $summary,
            'ip_address' => request()->ip(),
        ]);
    }

    private function fillFromOrganization(): void
    {
        $this->organization->loadMissing('owner');
        $this->name = $this->organization->name;
        $this->slug = $this->organization->slug;
        $this->status = $this->organization->status;
        $this->timezone = $this->organization->timezone;
        $this->defaultTheme = $this->organization->default_theme;
        $this->planKey = (string) $this->organization->plan_key;
        $this->ownerEmail = (string) $this->organization->owner?->email;
    }

    public function render()
    {
        $query = $this->organization->memberships()
            ->with('user')
            ->when($this->memberStatus !== 'all', fn (Builder $query) => $query->where('status', $this->memberStatus))
            ->when($this->memberRoleFilter !== 'all', fn (Builder $query) => $query->where('role', $this->memberRoleFilter))
            ->when($this->search !== '', function (Builder $query): void {
                $query->whereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%"));
            })
            ->orderByRaw("case when role = 'organization_owner' then 0 when role = 'organization_admin' then 1 when role = 'coach' then 2 else 3 end")
            ->latest('joined_at');

        $memberships = $this->paginateQuery($query, 'membersPage');
        foreach ($memberships as $membership) {
            $this->membershipRoles[$membership->id] ??= $membership->role;
        }

        $counts = $this->organization->memberships()
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when status = 'active' then 1 else 0 end) as active")
            ->selectRaw("sum(case when status = 'active' and role = 'coach' then 1 else 0 end) as coaches")
            ->selectRaw("sum(case when status = 'active' and role = 'athlete' then 1 else 0 end) as athletes")
            ->first();

        return view('livewire.admin.organization-detail', [
            'memberships' => $memberships,
            'counts' => $counts,
            'recentAudit' => AuditLog::query()
                ->withoutGlobalScope('organization')
                ->where('entity', 'organization')
                ->where('entity_id', $this->organization->id)
                ->latest()
                ->limit(15)
                ->get(),
        ])->layout('layouts.app', ['title' => $this->organization->name]);
    }
}
