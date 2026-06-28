<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class NotificationReadController extends Controller
{
    public function __invoke(SystemNotification $notification): RedirectResponse
    {
        /** @var User $user */
        $user = request()->user()->loadMissing('roles');

        abort_unless(
            SystemNotification::query()
                ->whereKey($notification->id)
                ->visibleTo($user)
                ->exists(),
            404,
        );

        $notification->reads()->updateOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()],
        );

        return back();
    }
}
