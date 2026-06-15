<?php

namespace App\Http\Controllers;

use App\Enums\MembershipStatus;
use App\Enums\PaymentEventStatus;
use App\Enums\PaymentEventType;
use App\Enums\RoleName;
use App\Http\Requests\MembershipUpdateRequest;
use App\Models\Membership;
use App\Models\PaymentEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class MembershipUpdateController extends Controller
{
    public function __invoke(MembershipUpdateRequest $request, Membership $membership): RedirectResponse
    {
        $viewer = $this->manageableMembership($request->user(), $membership);

        $membership->loadMissing(['user', 'plan']);

        $membership->fill($request->safe()->except('extension_days'));
        $changeSummary = [];
        $extensionDays = (int) ($request->validated('extension_days') ?? 0);

        if ($extensionDays > 0) {
            $this->extendAccess($membership, $extensionDays);
            $changeSummary[] = "extended access by {$extensionDays} day(s)";
        }

        if ($membership->status === MembershipStatus::Cancelled) {
            $membership->cancelled_at = $membership->cancelled_at ?? now()->toDateString();
            $membership->auto_renew = false;
        } elseif ($membership->cancelled_at) {
            $membership->cancelled_at = null;
        }

        if ($membership->isDirty('status')) {
            $changeSummary[] = sprintf('status %s -> %s', $membership->getRawOriginal('status'), $membership->status->value);
        }

        if ($membership->isDirty('auto_renew')) {
            $changeSummary[] = sprintf('auto renew %s', $membership->auto_renew ? 'enabled' : 'disabled');
        }

        foreach (['renews_at' => 'renewal date', 'ends_at' => 'end date', 'grace_ends_at' => 'grace end'] as $field => $label) {
            if ($membership->isDirty($field)) {
                $changeSummary[] = sprintf(
                    '%s %s -> %s',
                    $label,
                    $membership->getRawOriginal($field) ?: 'none',
                    $membership->{$field}?->toDateString() ?? 'none',
                );
            }
        }

        if ($membership->isDirty('notes')) {
            $changeSummary[] = 'operator notes updated';
        }

        $membership->save();

        if ($changeSummary !== []) {
            PaymentEvent::query()->create([
                'membership_id' => $membership->id,
                'user_id' => $membership->user_id,
                'created_by_user_id' => $viewer->id,
                'event_type' => PaymentEventType::MembershipChange,
                'status' => PaymentEventStatus::Info,
                'provider' => 'manual',
                'reference' => null,
                'amount' => null,
                'currency' => $membership->currency,
                'event_at' => now(),
                'notes' => implode('; ', $changeSummary),
                'metadata' => [
                    'membership_status' => $membership->status->value,
                    'auto_renew' => $membership->auto_renew,
                ],
            ]);
        }

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

    private function extendAccess(Membership $membership, int $extensionDays): void
    {
        $extended = false;

        foreach (['renews_at', 'ends_at', 'grace_ends_at'] as $field) {
            if ($membership->{$field}) {
                $membership->{$field} = $membership->{$field}->copy()->addDays($extensionDays);
                $extended = true;
            }
        }

        if (! $extended) {
            $membership->ends_at = now()->addDays($extensionDays)->toDateString();
        }
    }
}
