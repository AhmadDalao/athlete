<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationStoreController extends Controller
{
    public function __invoke(Request $request, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless($viewer->hasPermission('notifications.manage'), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:3000'],
            'target_type' => ['required', 'string', Rule::in(['all', 'role', 'user'])],
            'target_role' => ['nullable', 'required_if:target_type,role', 'string', Rule::in(collect(RoleName::cases())->map->value->all())],
            'target_user_id' => ['nullable', 'required_if:target_type,user', 'integer', 'exists:users,id'],
            'action_label' => ['nullable', 'string', 'max:80'],
            'action_url' => ['nullable', 'string', 'max:2048'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        $notification = SystemNotification::query()->create([
            'created_by_user_id' => $viewer->id,
            'target_type' => $validated['target_type'],
            'target_role' => $validated['target_type'] === 'role' ? $validated['target_role'] : null,
            'target_user_id' => $validated['target_type'] === 'user' ? $validated['target_user_id'] : null,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'action_label' => $validated['action_label'] ?? null,
            'action_url' => $validated['action_url'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        $auditLogger->record(
            $request,
            'notification.published',
            $notification,
            "Published notification: {$notification->title}.",
            [
                'target_type' => $notification->target_type,
                'target_role' => $notification->target_role,
                'target_user_id' => $notification->target_user_id,
            ],
        );

        return back()->with('success', 'Notification published.');
    }
}
