<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Models\SystemNotification;
use App\Models\User;
use App\Support\TablePageSize;
use Inertia\Inertia;
use Inertia\Response;

class NotificationIndexController extends Controller
{
    public function __invoke(): Response
    {
        /** @var User $user */
        $user = request()->user()->loadMissing('roles');

        $query = SystemNotification::query()
            ->visibleTo($user)
            ->with(['creator', 'reads' => fn ($query) => $query->where('user_id', $user->id)]);

        $notifications = $query
            ->latest()
            ->paginate(TablePageSize::resolve(request(), $query))
            ->withQueryString()
            ->through(fn (SystemNotification $notification): array => [
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'actionLabel' => $notification->action_label,
                'actionUrl' => $notification->action_url,
                'targetType' => $notification->target_type,
                'targetRole' => $notification->target_role,
                'startsAt' => $notification->starts_at?->toDateTimeString(),
                'expiresAt' => $notification->expires_at?->toDateTimeString(),
                'createdAt' => $notification->created_at?->toDateTimeString(),
                'creatorName' => $notification->creator?->name,
                'readAt' => $notification->reads->first()?->read_at?->toDateTimeString(),
            ]);

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
            'canCreateNotifications' => $user->hasPermission('notifications.manage'),
            'roleOptions' => collect(RoleName::cases())
                ->map(fn (RoleName $role): array => ['value' => $role->value, 'label' => $role->label()])
                ->values()
                ->all(),
            'userOptions' => User::query()
                ->orderBy('name')
                ->take(50)
                ->get(['id', 'name', 'email'])
                ->map(fn (User $target): array => [
                    'id' => $target->id,
                    'name' => $target->name,
                    'email' => $target->email,
                ])
                ->values()
                ->all(),
        ]);
    }
}
