<?php

namespace App\Livewire\Admin;

use App\Models\AthleteProfile;
use App\Models\AuditLog;
use App\Models\CoachProfile;
use App\Models\User;
use App\Queries\Admin\ManagedUserQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UserDetail extends Component
{
    public User $user;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = '';

    public string $status = '';

    public string $primaryGoal = '';

    public string $bio = '';

    public string $password = '';

    public bool $platformMode = false;

    public int $organizationId = 0;

    public ?int $membershipId = null;

    public function mount(User $user): void
    {
        $actor = Auth::user();
        $this->user = ManagedUserQuery::findVisibleOrFail($actor, $user->id);
        $this->platformMode = $actor->isPlatformAdmin();
        $this->organizationId = (int) $actor->current_organization_id;
        $this->membershipId = ManagedUserQuery::membership($actor, $this->user)?->id;
        ManagedUserQuery::authorizeRole($actor, ManagedUserQuery::displayRole($actor, $this->user));
        $this->fillFromUser();
    }

    public function updateUser(): void
    {
        $actor = Auth::user();
        $this->user = ManagedUserQuery::findVisibleOrFail($actor, $this->user->id);
        ManagedUserQuery::authorizeRole($actor, ManagedUserQuery::displayRole($actor, $this->user));

        if (! $actor->isPlatformAdmin()) {
            $this->updateMembership($actor);

            return;
        }

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($this->user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::in(['owner', 'admin', 'coach', 'athlete'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'primaryGoal' => ['nullable', 'string', 'max:180'],
            'bio' => ['nullable', 'string', 'max:1200'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($this->user->isOwner()) {
            $data['role'] = 'owner';
            $data['status'] = 'active';
        }

        if (! $actor->isPlatformOwner() && $data['role'] === 'owner') {
            $data['role'] = $this->user->role;
        }

        if ($this->user->organizationMemberships()->where('role', 'organization_owner')->where('status', 'active')->exists()) {
            $data['status'] = 'active';
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'role' => $data['role'],
            'status' => $data['status'],
            'primary_goal' => $data['primaryGoal'] ?: null,
            'bio' => $data['bio'] ?: null,
        ];

        if (filled($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $this->user->update($payload);
        $this->user->permissions()->delete();
        $this->user->syncDefaultPermissions();
        $this->user->refresh();
        $this->password = '';

        AuditLog::create([
            'organization_id' => null,
            'user_id' => Auth::id(),
            'action' => 'user.updated',
            'entity' => 'user',
            'entity_id' => $this->user->id,
            'summary' => "Updated {$this->user->email}.",
            'ip_address' => request()->ip(),
        ]);

        session()->flash('status', 'User updated.');
    }

    private function fillFromUser(): void
    {
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = (string) $this->user->phone;
        $actor = Auth::user();
        $this->role = ManagedUserQuery::displayRole($actor, $this->user);
        $this->status = ManagedUserQuery::displayStatus($actor, $this->user);
        $this->primaryGoal = (string) $this->user->primary_goal;
        $this->bio = (string) $this->user->bio;
    }

    public function render()
    {
        $actor = Auth::user();
        $this->user = ManagedUserQuery::findVisibleOrFail($actor, $this->user->id);
        $this->platformMode = $actor->isPlatformAdmin();
        $this->organizationId = $this->platformMode ? 0 : ManagedUserQuery::organizationId($actor);
        $organizationId = $this->platformMode ? null : $this->organizationId;
        $scope = fn ($query) => $query->when($organizationId, fn ($query) => $query->where('organization_id', $organizationId));

        $this->user->loadCount([
            'permissions',
            'coachAssignments' => $scope,
            'athleteAssignments' => $scope,
            'coachPrograms' => $scope,
            'progressEntries' => $scope,
            'workoutLogs' => $scope,
        ]);

        return view('livewire.admin.user-detail', [
            'coachAssignments' => $scope($this->user->coachAssignments())->with('athlete')->latest()->limit(20)->get(),
            'athleteAssignments' => $scope($this->user->athleteAssignments())->with('coach')->latest()->limit(20)->get(),
            'programsAsCoach' => $scope($this->user->coachPrograms())->with('athlete')->latest()->limit(20)->get(),
            'programsAsAthlete' => $scope($this->user->athletePrograms())->with('coach')->latest()->limit(20)->get(),
            'progressEntries' => $scope($this->user->progressEntries())->latest('logged_on')->limit(20)->get(),
            'workoutLogs' => $scope($this->user->workoutLogs())->with('session.program.coach')->latest()->limit(20)->get(),
            'auditLogs' => $actor->hasPermission('admin.audit')
                ? AuditLog::query()
                    ->when(! $actor->isPlatformAdmin(), fn ($query) => $query->where('organization_id', $this->organizationId))
                    ->where('entity', 'user')->where('entity_id', $this->user->id)->latest()->limit(20)->get()
                : collect(),
            'canViewAudit' => $actor->hasPermission('admin.audit'),
        ])->layout('layouts.app', ['title' => $this->user->name]);
    }

    private function updateMembership(User $actor): void
    {
        $organizationId = ManagedUserQuery::organizationId($actor);
        $membership = $this->user->organizationMemberships()
            ->where('organization_id', $organizationId)
            ->firstOrFail();

        if ($membership->isOwner()) {
            session()->flash('status', 'Organization owners are protected. Assign a new owner from organization control.');

            return;
        }

        $data = validator([
            'role' => $this->role,
            'status' => $this->status,
        ], [
            'role' => ['required', Rule::in(['organization_admin', 'coach', 'athlete'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ])->validate();
        ManagedUserQuery::authorizeRole($actor, $data['role']);

        DB::transaction(function () use ($actor, $membership, $data, $organizationId): void {
            $membership->update($data);

            if ($data['role'] === 'coach') {
                CoachProfile::firstOrCreate(['organization_id' => $organizationId, 'user_id' => $this->user->id]);
            }

            if ($data['role'] === 'athlete') {
                AthleteProfile::firstOrCreate(['organization_id' => $organizationId, 'user_id' => $this->user->id]);
            }

            if ($data['status'] === 'inactive' && $this->user->current_organization_id === $organizationId) {
                $nextOrganizationId = $this->user->organizationMemberships()
                    ->where('status', 'active')
                    ->where('organization_id', '!=', $organizationId)
                    ->value('organization_id');
                $this->user->forceFill(['current_organization_id' => $nextOrganizationId])->saveQuietly();
            }

            AuditLog::create([
                'organization_id' => $organizationId,
                'user_id' => $actor->id,
                'action' => 'organization.member_updated',
                'entity' => 'user',
                'entity_id' => $this->user->id,
                'summary' => "Changed {$this->user->email} membership to {$data['role']} / {$data['status']}.",
                'ip_address' => request()->ip(),
            ]);
        });

        $this->fillFromUser();
        session()->flash('status', 'Organization membership updated.');
    }
}
