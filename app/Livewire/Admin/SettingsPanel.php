<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Auth;
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
        'pricing_headline' => '',
        'pricing_subheadline' => '',
        'plan_one_name' => '',
        'plan_one_price' => '',
        'plan_one_description' => '',
        'plan_one_features' => '',
        'plan_two_name' => '',
        'plan_two_price' => '',
        'plan_two_description' => '',
        'plan_two_features' => '',
        'plan_three_name' => '',
        'plan_three_price' => '',
        'plan_three_description' => '',
        'plan_three_features' => '',
        'invite_email_subject' => '',
        'invite_email_body' => '',
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
            'settings.pricing_headline' => ['required', 'string', 'max:160'],
            'settings.pricing_subheadline' => ['nullable', 'string', 'max:260'],
            'settings.plan_one_name' => ['required', 'string', 'max:80'],
            'settings.plan_one_price' => ['required', 'string', 'max:80'],
            'settings.plan_one_description' => ['nullable', 'string', 'max:180'],
            'settings.plan_one_features' => ['nullable', 'string', 'max:600'],
            'settings.plan_two_name' => ['required', 'string', 'max:80'],
            'settings.plan_two_price' => ['required', 'string', 'max:80'],
            'settings.plan_two_description' => ['nullable', 'string', 'max:180'],
            'settings.plan_two_features' => ['nullable', 'string', 'max:600'],
            'settings.plan_three_name' => ['required', 'string', 'max:80'],
            'settings.plan_three_price' => ['required', 'string', 'max:80'],
            'settings.plan_three_description' => ['nullable', 'string', 'max:180'],
            'settings.plan_three_features' => ['nullable', 'string', 'max:600'],
            'settings.invite_email_subject' => ['required', 'string', 'max:180'],
            'settings.invite_email_body' => ['required', 'string', 'max:2000'],
            'settings.mail_from_name' => ['nullable', 'string', 'max:120'],
            'settings.mail_from_address' => ['nullable', 'email'],
        ]);

        foreach ($this->settings as $key => $value) {
            $group = match (true) {
                str_starts_with($key, 'mail_') => 'mail',
                str_starts_with($key, 'invite_') => 'invitations',
                str_starts_with($key, 'pricing_'), str_starts_with($key, 'plan_') => 'pricing',
                default => 'site',
            };

            PlatformSetting::put($key, (string) $value, $group);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'settings.updated',
            'entity' => 'platform_settings',
            'summary' => 'Updated platform settings.',
            'ip_address' => request()->ip(),
        ]);

        session()->flash('status', 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings-panel')->layout('layouts.app', ['title' => 'Settings']);
    }
}
