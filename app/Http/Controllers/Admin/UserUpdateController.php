<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserUpdateController extends Controller
{
    public function __invoke(UserUpdateRequest $request, User $user): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless($viewer->hasRole(RoleName::Admin), 403);

        $user->fill($request->safe()->except('roles'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($user->isDirty('phone')) {
            $user->phone_verified_at = null;
        }

        $user->save();
        $user->syncRoles($request->validated('roles'));

        return back();
    }
}
