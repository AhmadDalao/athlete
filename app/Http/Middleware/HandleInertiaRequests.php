<?php

namespace App\Http\Middleware;

use App\Models\SystemNotification;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Throwable;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'quote' => ['message' => 'Coach the whole athlete.', 'author' => 'Throughline'],
            'flash' => [
                'success' => $request->session()->get('success'),
                'status' => $request->session()->get('status'),
            ],
            'auth' => [
                'user' => $request->user()
                    ? $this->transformUser($request->user()->loadMissing('roles', 'permissions', 'socialAccounts'))
                    : null,
            ],
            'notifications' => [
                'unreadCount' => $request->user() ? $this->unreadNotificationCount($request) : 0,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatarUrl(),
            'email_verified_at' => $user->email_verified_at,
            'phone_verified_at' => $user->phone_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'role_names' => $user->roleNames(),
            'primary_role' => $user->primaryRoleName(),
            'position' => $user->position,
            'permissions' => $user->permissionKeys(),
            'primary_goal' => $user->primary_goal,
            'preferred_contact_method' => $user->preferred_contact_method,
            'registration_channel' => $user->registration_channel,
            'landing_path' => $user->landingPath(),
        ];
    }

    private function unreadNotificationCount(Request $request): int
    {
        $user = $request->user()->loadMissing('roles');

        try {
            return SystemNotification::query()
                ->visibleTo($user)
                ->whereDoesntHave('reads', fn ($query) => $query->where('user_id', $user->id))
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }
}
