<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless($viewer->hasPermission('admin.system_settings.manage'), 403);

        $definitions = PlatformSetting::definitions();
        $values = PlatformSetting::values();

        return Inertia::render('admin/system-settings', [
            'groups' => collect($definitions)
                ->groupBy(fn (array $definition) => $definition['group'])
                ->map(fn ($settings, string $group): array => [
                    'name' => $group,
                    'settings' => $settings
                        ->map(fn (array $definition, string $key): array => [
                            'key' => $key,
                            'label' => $definition['label'],
                            'help' => $definition['help'],
                            'value' => $values[$key] ?? $definition['default'],
                            'default' => $definition['default'],
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'mailRuntime' => [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'fromAddress' => config('mail.from.address'),
                'fromName' => config('mail.from.name'),
            ],
        ]);
    }

    public function update(Request $request, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless($viewer->hasPermission('admin.system_settings.manage'), 403);

        $definitions = PlatformSetting::definitions();
        $keys = array_keys($definitions);
        $payload = $request->input('settings', []);

        if (! is_array($payload)) {
            throw ValidationException::withMessages(['settings' => 'Settings payload is invalid.']);
        }

        $validator = Validator::make(
            ['settings' => $payload],
            [
                'settings' => ['required', 'array'],
                'settings.*' => ['nullable', 'string', 'max:3000'],
            ],
        );

        $validator->after(function ($validator) use ($payload, $keys): void {
            $unknown = array_diff(array_keys($payload), $keys);

            if ($unknown !== []) {
                $validator->errors()->add('settings', 'Unknown setting key: '.implode(', ', $unknown));
            }
        });

        $validator->validate();

        foreach ($keys as $key) {
            PlatformSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => trim((string) ($payload[$key] ?? '')),
                    'group' => $definitions[$key]['group'],
                    'label' => $definitions[$key]['label'],
                    'help' => $definitions[$key]['help'],
                    'updated_by_user_id' => $viewer->id,
                ],
            );
        }

        $auditLogger->record(
            $request,
            'system_settings.updated',
            null,
            'Updated website control and system settings.',
            [
                'keys' => $keys,
            ],
        );

        return back()->with('success', 'System settings updated.');
    }
}
