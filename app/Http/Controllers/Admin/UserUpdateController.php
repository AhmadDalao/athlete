<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\RedirectResponse;

class UserUpdateController extends Controller
{
    public function __invoke(UserUpdateRequest $request, User $user, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless($viewer->hasPermission('admin.users.manage'), 403);
        abort_if($user->hasRole(RoleName::Owner) && ! $viewer->hasRole(RoleName::Owner), 403);

        $validated = $request->validated();

        abort_if(in_array(RoleName::Owner->value, $validated['roles'], true) && ! $viewer->hasRole(RoleName::Owner), 403);
        abort_if(
            $user->hasRole(RoleName::Owner)
            && ! in_array(RoleName::Owner->value, $validated['roles'], true)
            && ! User::query()->role(RoleName::Owner)->whereKeyNot($user->id)->exists(),
            422,
            'At least one owner account must remain.',
        );

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'primary_goal' => $user->primary_goal,
            'position' => $user->position,
            'preferred_contact_method' => $user->preferred_contact_method,
            'registration_channel' => $user->registration_channel,
            'roles' => $user->roleNames(),
            'permissions' => $user->permissionKeys(),
        ];

        $user->fill($request->safe()->except(['roles', 'permissions']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($user->isDirty('phone')) {
            $user->phone_verified_at = null;
        }

        $user->save();
        $user->syncRoles($validated['roles']);
        $user->syncPermissions($validated['permissions'] ?? [], $viewer);

        $auditLogger->record(
            $request,
            'user.updated',
            $user,
            "Updated {$user->name}'s profile, onboarding fields, or roles.",
            [
                'before' => $before,
                'after' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'primary_goal' => $user->primary_goal,
                    'position' => $user->position,
                    'preferred_contact_method' => $user->preferred_contact_method,
                    'registration_channel' => $user->registration_channel,
                    'roles' => $validated['roles'],
                    'permissions' => $user->permissionKeys(),
                ],
            ],
        );

        return back();
    }
}
