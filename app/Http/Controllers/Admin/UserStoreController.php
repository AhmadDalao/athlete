<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\RedirectResponse;

class UserStoreController extends Controller
{
    public function __invoke(UserStoreRequest $request, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless($viewer->hasPermission('admin.users.manage'), 403);

        $validated = $request->validated();

        abort_if(in_array(RoleName::Owner->value, $validated['roles'], true) && ! $viewer->hasRole(RoleName::Owner), 403);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'primary_goal' => $validated['primary_goal'] ?? null,
            'position' => $validated['position'] ?? null,
            'preferred_contact_method' => $validated['preferred_contact_method'],
            'registration_channel' => $validated['registration_channel'],
            'email_verified_at' => $validated['email_verified'] ? now() : null,
        ]);

        $user->syncRoles($validated['roles']);
        $user->syncPermissions($validated['permissions'] ?? [], $viewer);

        $auditLogger->record(
            $request,
            'user.created',
            $user,
            "Created {$user->name} with roles ".implode(', ', $validated['roles']).'.',
            [
                'email' => $user->email,
                'roles' => $validated['roles'],
                'position' => $user->position,
                'permissions' => $user->permissionKeys(),
                'registration_channel' => $user->registration_channel,
            ],
        );

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User created.');
    }
}
