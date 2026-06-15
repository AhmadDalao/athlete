<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\PaymentEventStoreRequest;
use App\Models\Membership;
use App\Models\PaymentEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class PaymentEventStoreController extends Controller
{
    public function __invoke(PaymentEventStoreRequest $request, Membership $membership): RedirectResponse
    {
        $viewer = $this->manageableMembership($request->user(), $membership);

        $membership->loadMissing('user');

        PaymentEvent::query()->create([
            'membership_id' => $membership->id,
            'user_id' => $membership->user_id,
            'created_by_user_id' => $viewer->id,
            ...$request->validated(),
        ]);

        return back();
    }

    private function manageableMembership(User $viewer, Membership $membership): User
    {
        $viewer->loadMissing('roles');

        abort_unless($viewer->hasRole(RoleName::Admin) || $viewer->hasRole(RoleName::Coach), 403);
        abort_unless(
            Membership::query()->visibleTo($viewer)->whereKey($membership->id)->exists(),
            404,
        );

        return $viewer;
    }
}
