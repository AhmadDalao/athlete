<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\AuthorizesComponentAccess;
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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class OrganizationsTable extends Component
{
    use AuthorizesComponentAccess;
    use WithPagination;
    use WithTableControls;

    public string $status = 'all';

    public string $theme = 'all';

    public string $name = '';

    public string $slug = '';

    public string $ownerEmail = '';

    public string $timezone = 'Asia/Riyadh';

    public string $defaultTheme = 'system';

    public string $planKey = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedTheme(): void
    {
        $this->resetPage();
    }

    public function updatedName(string $value): void
    {
        if ($this->slug === '' || $this->slug === Str::slug($this->name)) {
            $this->slug = Str::slug($value);
        }
    }

    public function createOrganization(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('organizations', 'slug')],
            'ownerEmail' => ['nullable', 'email', Rule::exists('users', 'email')->where('status', 'active')],
            'timezone' => ['required', 'timezone'],
            'defaultTheme' => ['required', Rule::in(['system', 'dark', 'light'])],
            'planKey' => ['nullable', 'string', 'max:80'],
        ]);

        $organization = DB::transaction(function () use ($data): Organization {
            $owner = filled($data['ownerEmail'])
                ? User::query()->where('email', $data['ownerEmail'])->firstOrFail()
                : null;

            $organization = Organization::create([
                'owner_id' => $owner?->id,
                'name' => $data['name'],
                'slug' => Str::lower($data['slug']),
                'status' => 'active',
                'timezone' => $data['timezone'],
                'default_theme' => $data['defaultTheme'],
                'plan_key' => $data['planKey'] ?: null,
            ]);

            if ($owner) {
                OrganizationMembership::create([
                    'organization_id' => $organization->id,
                    'user_id' => $owner->id,
                    'role' => 'organization_owner',
                    'status' => 'active',
                    'joined_at' => now(),
                ]);
                $owner->forceFill(['current_organization_id' => $owner->current_organization_id ?: $organization->id])->saveQuietly();
                $this->ensureRoleProfile($organization, $owner, 'organization_owner');
            }

            AuditLog::create([
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'action' => 'organization.created',
                'entity' => 'organization',
                'entity_id' => $organization->id,
                'summary' => "Created organization {$organization->name}.",
                'ip_address' => request()->ip(),
            ]);

            return $organization;
        });

        $this->reset(['name', 'slug', 'ownerEmail', 'planKey']);
        $this->timezone = 'Asia/Riyadh';
        $this->defaultTheme = 'system';

        $this->redirectRoute('admin.organizations.show', $organization, navigate: true);
    }

    public function toggleStatus(int $organizationId): void
    {
        $organization = Organization::query()->findOrFail($organizationId);
        $nextStatus = $organization->status === 'active' ? 'inactive' : 'active';

        $organization->update(['status' => $nextStatus]);

        AuditLog::create([
            'organization_id' => $organization->id,
            'user_id' => Auth::id(),
            'action' => 'organization.status',
            'entity' => 'organization',
            'entity_id' => $organization->id,
            'summary' => "Changed {$organization->name} status to {$nextStatus}.",
            'ip_address' => request()->ip(),
        ]);

        session()->flash('status', "{$organization->name} is now {$nextStatus}.");
    }

    protected function allowedSortFields(): array
    {
        return ['name', 'status', 'plan_key', 'created_at'];
    }

    protected function componentPermissions(): array
    {
        return ['organizations.manage'];
    }

    private function ensureRoleProfile(Organization $organization, User $user, string $role): void
    {
        if ($role === 'coach') {
            CoachProfile::firstOrCreate(['organization_id' => $organization->id, 'user_id' => $user->id]);
        }

        if ($role === 'athlete') {
            AthleteProfile::firstOrCreate(['organization_id' => $organization->id, 'user_id' => $user->id]);
        }
    }

    public function render()
    {
        $query = Organization::query()
            ->with('owner')
            ->withCount([
                'memberships as active_members_count' => fn (Builder $query) => $query->where('status', 'active'),
                'memberships as coaches_count' => fn (Builder $query) => $query->where('status', 'active')->where('role', 'coach'),
                'memberships as athletes_count' => fn (Builder $query) => $query->where('status', 'active')->where('role', 'athlete'),
            ])
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->theme !== 'all', fn (Builder $query) => $query->where('default_theme', $this->theme))
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('slug', 'like', "%{$this->search}%")
                        ->orWhere('plan_key', 'like', "%{$this->search}%")
                        ->orWhereHas('owner', fn (Builder $query) => $query->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%"));
                });
            });

        return view('livewire.admin.organizations-table', [
            'organizations' => $this->paginateQuery($this->applySorting($query, 'name')),
        ])->layout('layouts.app', ['title' => 'Organizations']);
    }
}
