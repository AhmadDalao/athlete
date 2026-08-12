<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Support\PlatformSettingCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsPanel extends Component
{
    use WithFileUploads;

    public array $settings = [];

    public $logo = null;

    public function boot(): void
    {
        $this->authorizeSettings();
    }

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('admin.settings'), 403);

        $defaults = PlatformSettingCatalog::defaults();
        $stored = collect($defaults)->mapWithKeys(fn (mixed $fallback, string $key): array => [
            $key => PlatformSetting::get($key, (string) $fallback),
        ])->all();

        $this->settings = PlatformSettingCatalog::normalizeForForm($stored);
    }

    public function save(): void
    {
        $this->authorizeSettings();

        $rules = PlatformSettingCatalog::rules();
        $rules['logo'] = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'];
        $this->validate($rules);

        if ($this->logo) {
            $oldLogo = $this->settings['logo_path'] ?? null;
            $this->settings['logo_path'] = $this->logo->storePublicly('branding', 'public');

            if ($oldLogo && $oldLogo !== $this->settings['logo_path']) {
                Storage::disk('public')->delete($oldLogo);
            }

            $this->reset('logo');
        }

        foreach ($this->settings as $key => $value) {
            PlatformSetting::put(
                $key,
                PlatformSettingCatalog::serialize($key, $value),
                PlatformSettingCatalog::groupFor($key),
            );
        }

        $this->audit('settings.updated', 'Updated website, communication, theme, and feature settings.');
        session()->flash('status', 'Website and system settings saved.');
    }

    public function removeLogo(): void
    {
        $this->authorizeSettings();

        $logoPath = $this->settings['logo_path'] ?? null;
        if ($logoPath) {
            Storage::disk('public')->delete($logoPath);
        }

        $this->settings['logo_path'] = '';
        PlatformSetting::put('logo_path', '', 'branding');
        $this->audit('settings.logo_removed', 'Removed the public website logo.');
        session()->flash('status', 'Logo removed. The Throughline mark is active again.');
    }

    private function authorizeSettings(): void
    {
        abort_unless(Auth::user()?->can('admin.settings'), 403);
    }

    private function audit(string $action, string $summary): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity' => 'platform_settings',
            'summary' => $summary,
            'ip_address' => request()->ip(),
        ]);
    }

    public function render()
    {
        return view('livewire.admin.settings-panel')->layout('layouts.app', ['title' => 'Website control']);
    }
}
