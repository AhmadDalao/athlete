<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\PaymentEventStoreRequest;
use App\Models\Membership;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\RedirectResponse;

class PaymentEventStoreController extends Controller
{
    public function __invoke(PaymentEventStoreRequest $request, Membership $membership, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        $viewer = $this->manageableMembership($request->user(), $membership);

        $membership->loadMissing('user');

        $event = PaymentEvent::query()->create([
            'membership_id' => $membership->id,
            'user_id' => $membership->user_id,
            'created_by_user_id' => $viewer->id,
            ...$request->validated(),
        ]);

        $auditLogger->record(
            $request,
            'payment_event.created',
            $event,
            "Recorded {$event->event_type->value} payment event for {$membership->user->name}.",
            [
                'membership_id' => $membership->id,
                'user_id' => $membership->user_id,
                'status' => $event->status->value,
                'amount' => $event->amount,
                'currency' => $event->currency,
            ],
        );

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
