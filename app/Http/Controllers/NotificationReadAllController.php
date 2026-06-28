<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class NotificationReadAllController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        /** @var User $user */
        $user = request()->user()->loadMissing('roles');

        SystemNotification::query()
            ->visibleTo($user)
            ->whereDoesntHave('reads', fn ($query) => $query->where('user_id', $user->id))
            ->get()
            ->each(fn (SystemNotification $notification) => $notification->reads()->create([
                'user_id' => $user->id,
                'read_at' => now(),
            ]));

        return back();
    }
}
