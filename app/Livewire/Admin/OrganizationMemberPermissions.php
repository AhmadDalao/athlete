<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\MembershipPermissionOverride;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Queries\Admin\ManagedUserQuery;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class OrganizationMemberPermissions extends Component
{
    public Organization $organization;

    public OrganizationMembership $membership;

    /** @var array<string, string> */
    public array $access = [];

    public function boot(): void
    {
        abort_unless(Auth::user()?->can('users.manage'), 403);
    }

    public function mount(Organization $organization, OrganizationMembership $membership): void
    {
        abort_unless($membership->organization_id === $organization->id, 404);

        $this->organization = $organization;
        $this->membership = $membership->load('user', 'permissionOverrides');
        $this->authorizeAccess();
        $this->fillAccess();
    }

    public function save(): void
    {
        $this->authorizeAccess();

        if ($this->membership->isOwner()) {
            session()->flash('status', 'Organization owner access is protected and always includes every organization permission.');

            return;
        }

        $organizationPermissions = $this->organizationPermissions();
        $rules = collect($organizationPermissions)
            ->mapWithKeys(fn (string $permission): array => ['access.'.$this->accessKey($permission) => ['required', Rule::in(['default', 'allow', 'deny'])]])
            ->all();
        $this->validate($rules);

        DB::transaction(function () use ($organizationPermissions): void {
            $this->membership->permissionOverrides()->delete();

            foreach ($organizationPermissions as $permission) {
                $value = $this->access[$this->accessKey($permission)] ?? 'default';

                if ($value === 'default') {
                    continue;
                }

                MembershipPermissionOverride::create([
                    'organization_membership_id' => $this->membership->id,
                    'permission' => $permission,
                    'allowed' => $value === 'allow',
                ]);
            }

            AuditLog::create([
                'organization_id' => $this->organization->id,
                'user_id' => Auth::id(),
                'action' => 'organization.member_permissions',
                'entity' => 'organization_membership',
                'entity_id' => $this->membership->id,
                'summary' => "Updated organization permissions for {$this->membership->user->email}.",
                'ip_address' => request()->ip(),
            ]);
        });

        $this->membership->load('permissionOverrides');
        $this->fillAccess();
        session()->flash('status', 'Member permissions updated.');
    }

    public function useRoleDefaults(): void
    {
        $this->authorizeAccess();

        if (! $this->membership->isOwner()) {
            $this->access = collect($this->organizationPermissions())
                ->mapWithKeys(fn (string $permission): array => [$this->accessKey($permission) => 'default'])
                ->all();
        }
    }

    public function render()
    {
        $this->authorizeAccess();

        return view('livewire.admin.organization-member-permissions', [
            'groups' => collect(PermissionCatalog::groups())
                ->map(fn (array $permissions): array => collect($permissions)
                    ->reject(fn (string $description, string $permission): bool => PermissionCatalog::isPlatformOnly($permission))
                    ->all())
                ->filter()
                ->all(),
            'defaults' => PermissionCatalog::defaultsForOrganizationRole($this->membership->role),
            'overrideCount' => $this->membership->permissionOverrides->count(),
        ])->layout('layouts.app', ['title' => 'Member permissions']);
    }

    private function authorizeAccess(): void
    {
        $actor = Auth::user();
        abort_unless($actor?->can('users.manage'), 403);

        if (! $actor->isPlatformAdmin()) {
            abort_unless(ManagedUserQuery::organizationId($actor) === $this->organization->id, 403);
        }

        abort_unless($this->membership->organization_id === $this->organization->id, 404);
    }

    private function fillAccess(): void
    {
        $overrides = $this->membership->permissionOverrides->keyBy('permission');

        $this->access = collect($this->organizationPermissions())
            ->mapWithKeys(function (string $permission) use ($overrides): array {
                $override = $overrides->get($permission);

                return [$this->accessKey($permission) => $override ? ($override->allowed ? 'allow' : 'deny') : 'default'];
            })
            ->all();
    }

    /** @return array<int, string> */
    private function organizationPermissions(): array
    {
        return collect(PermissionCatalog::all())
            ->reject(fn (string $permission): bool => PermissionCatalog::isPlatformOnly($permission))
            ->values()
            ->all();
    }

    private function accessKey(string $permission): string
    {
        return str_replace('.', '__', $permission);
    }
}
