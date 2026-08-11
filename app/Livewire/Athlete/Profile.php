<?php

namespace App\Livewire\Athlete;

use App\Models\AthleteProfile;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';

    public string $phone = '';

    public string $primaryGoal = '';

    public string $bio = '';

    public string $sport = '';

    public string $position = '';

    public string $heightCm = '';

    public string $timezone = 'Asia/Riyadh';

    public string $emergencyContactName = '';

    public string $emergencyContactPhone = '';

    public function mount(): void
    {
        $user = Auth::user();
        $profile = $user->athleteProfiles()->first();
        $this->fill([
            'name' => $user->name,
            'phone' => $user->phone ?? '',
            'primaryGoal' => $user->primary_goal ?? '',
            'bio' => $user->bio ?? '',
            'sport' => $profile?->sport ?? '',
            'position' => $profile?->position ?? '',
            'heightCm' => (string) ($profile?->height_cm ?? ''),
            'timezone' => $profile?->timezone ?? 'Asia/Riyadh',
            'emergencyContactName' => $profile?->emergency_contact_name ?? '',
            'emergencyContactPhone' => $profile?->emergency_contact_phone ?? '',
        ]);
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'primaryGoal' => ['nullable', 'string', 'max:240'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'sport' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'heightCm' => ['nullable', 'numeric', 'min:80', 'max:260'],
            'timezone' => ['required', 'timezone'],
            'emergencyContactName' => ['nullable', 'string', 'max:120'],
            'emergencyContactPhone' => ['nullable', 'string', 'max:30'],
        ]);
        $user = Auth::user();
        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?: null,
            'primary_goal' => $data['primaryGoal'] ?: null,
            'bio' => $data['bio'] ?: null,
        ]);
        AthleteProfile::updateOrCreate([
            'organization_id' => $user->current_organization_id,
            'user_id' => $user->id,
        ], [
            'sport' => $data['sport'] ?: null,
            'position' => $data['position'] ?: null,
            'height_cm' => $data['heightCm'] ?: null,
            'timezone' => $data['timezone'],
            'emergency_contact_name' => $data['emergencyContactName'] ?: null,
            'emergency_contact_phone' => $data['emergencyContactPhone'] ?: null,
        ]);
        session()->flash('status', 'Profile updated.');
    }

    public function render()
    {
        return view('livewire.athlete.profile', [
            'organizations' => Auth::user()->organizations()->wherePivot('status', 'active')->get(),
        ])->layout('layouts.app', ['title' => 'Profile']);
    }
}
