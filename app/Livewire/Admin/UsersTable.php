<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Livewire\Concerns\WithTableControls;
use App\Models\AthleteProfile;
use App\Models\AuditLog;
use App\Models\CoachProfile;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Queries\Admin\ManagedUserQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UsersTable extends Component
{
    use AuthorizesComponentAccess;
    use WithPagination;
    use WithTableControls;

    public string $role = 'all';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $newRole = 'athlete';

    public string $password = '';

    public bool $roleLocked = false;

    public function mount(): void
    {
        if ($this->roleLocked) {
            $this->newRole = $this->role;
        }
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function createUser(): void
    {
        $actor = Auth::user();
        $roleOptions = $this->creationRoleOptions($actor);
        $fixedRole = $this->constrainedRole();

        if ($fixedRole) {
            $this->newRole = $fixedRole;
        }

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'newRole' => ['required', Rule::in(array_keys($roleOptions))],
            'password' => ['required', 'string', 'min:8'],
        ]);

        ManagedUserQuery::authorizeRole($actor, $data['newRole']);

        DB::transaction(function () use ($actor, $data): void {
            $organizationRole = $actor->isPlatformAdmin() ? null : $data['newRole'];
            $globalRole = $organizationRole === 'organization_admin' ? 'athlete' : $data['newRole'];
            $organizationId = $actor->isPlatformAdmin() ? null : ManagedUserQuery::organizationId($actor);

            $user = User::create([
                'current_organization_id' => $organizationId,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?: null,
                'role' => $globalRole,
                'status' => 'active',
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
            ]);
            $user->syncDefaultPermissions();

            if ($organizationId && $organizationRole) {
                OrganizationMembership::create([
                    'organization_id' => $organizationId,
                    'user_id' => $user->id,
                    'role' => $organizationRole,
                    'status' => 'active',
                    'joined_at' => now(),
                ]);
                $this->ensureRoleProfile($organizationId, $user, $organizationRole);
            }

            AuditLog::create([
                'organization_id' => $organizationId,
                'user_id' => $actor->id,
                'action' => 'user.created',
                'entity' => 'user',
                'entity_id' => $user->id,
                'summary' => 'Created '.($organizationRole ?: $globalRole)." {$user->email}.",
                'ip_address' => request()->ip(),
            ]);
        });

        $this->reset(['name', 'email', 'phone', 'password']);
        session()->flash('status', 'User created.');
    }

    public function toggleStatus(int $userId): void
    {
        $actor = Auth::user();
        $user = ManagedUserQuery::findVisibleOrFail($actor, $userId);
        $displayRole = ManagedUserQuery::displayRole($actor, $user);
        ManagedUserQuery::authorizeRole($actor, $displayRole);

        if ($actor->isPlatformAdmin() && ($user->isOwner() || $user->organizationMemberships()
            ->where('role', 'organization_owner')->where('status', 'active')->exists())) {
            session()->flash('status', 'Owner accounts cannot be disabled.');

            return;
        }

        if ($actor->isPlatformAdmin()) {
            $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
            $status = $user->status;
            $organizationId = null;
        } else {
            $membership = ManagedUserQuery::membership($actor, $user);

            if ($membership->isOwner()) {
                session()->flash('status', 'Organization owners cannot be disabled.');

                return;
            }

            $status = $membership->status === 'active' ? 'inactive' : 'active';
            $membership->update(['status' => $status]);
            $organizationId = ManagedUserQuery::organizationId($actor);

            if ($status === 'inactive' && $user->current_organization_id === $organizationId) {
                $nextOrganizationId = $user->organizationMemberships()
                    ->where('status', 'active')
                    ->where('organization_id', '!=', $organizationId)
                    ->value('organization_id');
                $user->forceFill(['current_organization_id' => $nextOrganizationId])->saveQuietly();
            }
        }

        AuditLog::create([
            'organization_id' => $organizationId,
            'user_id' => $actor->id,
            'action' => 'user.status',
            'entity' => 'user',
            'entity_id' => $user->id,
            'summary' => "Changed {$user->email} status to {$status}.",
            'ip_address' => request()->ip(),
        ]);
    }

    public function render()
    {
        $actor = Auth::user();
        $effectiveRole = $this->constrainedRole() ?: $this->role;
        $query = ManagedUserQuery::visibleTo($actor, $effectiveRole)
            ->when($this->search, function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('primary_goal', 'like', "%{$this->search}%");
                });
            })
            ->latest();

        return view('livewire.admin.users-table', [
            'users' => $this->paginateQuery($query),
            'platformMode' => $actor->isPlatformAdmin(),
            'creationRoleOptions' => $this->creationRoleOptions($actor),
            'effectiveRole' => $effectiveRole,
        ])->layout('layouts.app', ['title' => 'Users']);
    }

    private function creationRoleOptions(User $actor): array
    {
        if ($role = $this->constrainedRole()) {
            return [$role => ucfirst($role)];
        }

        return $actor->isPlatformAdmin()
            ? ['admin' => 'Platform admin', 'coach' => 'Coach', 'athlete' => 'Athlete']
            : ['organization_admin' => 'Organization admin', 'coach' => 'Coach', 'athlete' => 'Athlete'];
    }

    protected function constrainedRole(): ?string
    {
        return null;
    }

    protected function componentPermissions(): array
    {
        $role = $this->constrainedRole();

        return $role
            ? ['users.manage', ManagedUserQuery::permissionForRole($role)]
            : ['users.manage'];
    }

    private function ensureRoleProfile(int $organizationId, User $user, string $role): void
    {
        if ($role === 'coach') {
            CoachProfile::firstOrCreate(['organization_id' => $organizationId, 'user_id' => $user->id]);
        }

        if ($role === 'athlete') {
            AthleteProfile::firstOrCreate(['organization_id' => $organizationId, 'user_id' => $user->id]);
        }
    }
}
