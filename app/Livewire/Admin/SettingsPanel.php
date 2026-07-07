<?php

namespace App\Livewire\Admin;

use App\Models\PlatformSetting;
use Livewire\Component;

class SettingsPanel extends Component
{
    public array $settings = [
        'app_name' => '',
        'tagline' => '',
        'support_email' => '',
        'invite_expiry_days' => '7',
        'homepage_headline' => '',
        'homepage_subheadline' => '',
        'mail_from_name' => '',
        'mail_from_address' => '',
    ];

    public function mount(): void
    {
        foreach (array_keys($this->settings) as $key) {
            $this->settings[$key] = PlatformSetting::get($key, $this->settings[$key]);
        }
    }

    public function save(): void
    {
        $this->validate([
            'settings.app_name' => ['required', 'string', 'max:80'],
            'settings.tagline' => ['nullable', 'string', 'max:120'],
            'settings.support_email' => ['required', 'email'],
            'settings.invite_expiry_days' => ['required', 'integer', 'min:1', 'max:60'],
            'settings.homepage_headline' => ['required', 'string', 'max:180'],
            'settings.homepage_subheadline' => ['nullable', 'string', 'max:260'],
            'settings.mail_from_name' => ['nullable', 'string', 'max:120'],
            'settings.mail_from_address' => ['nullable', 'email'],
        ]);

        foreach ($this->settings as $key => $value) {
            PlatformSetting::put($key, (string) $value, str_starts_with($key, 'mail_') ? 'mail' : 'site');
        }

        session()->flash('status', 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings-panel')->layout('layouts.app', ['title' => 'Settings']);
    }
}
