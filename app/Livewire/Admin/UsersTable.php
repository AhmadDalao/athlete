<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableControls;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UsersTable extends Component
{
    use WithPagination;
    use WithTableControls;

    public string $role = 'all';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $newRole = 'athlete';

    public string $password = '';

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function createUser(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'newRole' => ['required', Rule::in(['admin', 'coach', 'athlete'])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'role' => $data['newRole'],
            'status' => 'active',
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);
        $user->syncDefaultPermissions();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'user.created',
            'entity' => 'user',
            'entity_id' => $user->id,
            'summary' => "Created {$user->role} {$user->email}.",
            'ip_address' => request()->ip(),
        ]);

        $this->reset(['name', 'email', 'phone', 'password']);
        session()->flash('status', 'User created.');
    }

    public function toggleStatus(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->isOwner()) {
            session()->flash('status', 'Owner accounts cannot be disabled.');

            return;
        }

        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'user.status',
            'entity' => 'user',
            'entity_id' => $user->id,
            'summary' => "Changed {$user->email} status to {$user->status}.",
            'ip_address' => request()->ip(),
        ]);
    }

    public function render()
    {
        $query = User::query()
            ->withCount(['coachAssignments', 'athleteAssignments', 'coachPrograms', 'progressEntries'])
            ->when($this->role !== 'all', fn ($query) => $query->where('role', $this->role))
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
        ])->layout('layouts.app', ['title' => 'Users']);
    }
}
