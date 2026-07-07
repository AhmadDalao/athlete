<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Support\PermissionCatalog;
use Livewire\Component;

class PermissionsPanel extends Component
{
    public ?int $selectedUserId = null;

    public array $selectedPermissions = [];

    public function mount(): void
    {
        $this->selectedUserId = User::where('role', 'admin')->where('status', 'active')->value('id');
        $this->loadPermissions();
    }

    public function updatedSelectedUserId(): void
    {
        $this->loadPermissions();
    }

    public function loadPermissions(): void
    {
        $user = $this->selectedUserId ? User::with('permissions')->find($this->selectedUserId) : null;
        $this->selectedPermissions = $user?->permissions->pluck('permission')->values()->all() ?? [];
    }

    public function save(): void
    {
        $user = User::findOrFail($this->selectedUserId);

        if ($user->isOwner()) {
            session()->flash('status', 'Owner permissions are locked to full access.');

            return;
        }

        $valid = PermissionCatalog::all();
        $permissions = collect($this->selectedPermissions)->intersect($valid)->values();

        $user->permissions()->delete();
        $permissions->each(fn (string $permission) => $user->permissions()->create(['permission' => $permission]));

        session()->flash('status', 'Permissions updated.');
    }

    public function render()
    {
        return view('livewire.admin.permissions-panel', [
            'users' => User::whereIn('role', ['owner', 'admin', 'coach'])->orderBy('role')->orderBy('name')->get(),
            'groups' => PermissionCatalog::groups(),
        ])->layout('layouts.app', ['title' => 'Permissions']);
    }
}
