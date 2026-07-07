<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->fillFromUser();
    }

    public function updateUser(): void
    {
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
        $this->role = $this->user->role;
        $this->status = $this->user->status;
        $this->primaryGoal = (string) $this->user->primary_goal;
        $this->bio = (string) $this->user->bio;
    }

    public function render()
    {
        $this->user->loadCount(['permissions', 'coachAssignments', 'athleteAssignments', 'coachPrograms', 'progressEntries', 'workoutLogs']);

        return view('livewire.admin.user-detail', [
            'coachAssignments' => $this->user->coachAssignments()->with('athlete')->latest()->limit(20)->get(),
            'athleteAssignments' => $this->user->athleteAssignments()->with('coach')->latest()->limit(20)->get(),
            'programsAsCoach' => $this->user->coachPrograms()->with('athlete')->latest()->limit(20)->get(),
            'programsAsAthlete' => $this->user->athletePrograms()->with('coach')->latest()->limit(20)->get(),
            'progressEntries' => $this->user->progressEntries()->latest('logged_on')->limit(20)->get(),
            'workoutLogs' => $this->user->workoutLogs()->with('session.program.coach')->latest()->limit(20)->get(),
            'auditLogs' => AuditLog::query()->where('entity', 'user')->where('entity_id', $this->user->id)->latest()->limit(20)->get(),
        ])->layout('layouts.app', ['title' => $this->user->name]);
    }
}
