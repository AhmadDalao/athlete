<?php

namespace App\Http\Controllers\Api;

use App\Enums\BillingInterval;
use App\Enums\MembershipStatus;
use App\Enums\PaymentEventStatus;
use App\Enums\PaymentEventType;
use App\Enums\RoleName;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipIndexController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('roles');
        $statusFilter = $request->string('status')->value();
        $allowedStatuses = collect(MembershipStatus::cases())->map->value->all();
        $normalizedStatusFilter = in_array($statusFilter, $allowedStatuses, true) ? $statusFilter : null;

        $baseQuery = Membership::query()->visibleTo($user);
        $memberships = (clone $baseQuery)
            ->with(['user.roles', 'plan', 'paymentEvents.createdBy'])
            ->when($normalizedStatusFilter, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByRaw(
                "case status
                    when 'past_due' then 0
                    when 'grace' then 1
                    when 'cancelled' then 2
                    when 'trialing' then 3
                    when 'active' then 4
                    when 'expired' then 5
                    else 6
                end"
            )
            ->orderBy('renews_at')
            ->orderBy('ends_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Membership $membership): array => [
                'id' => $membership->id,
                'user' => [
                    'id' => $membership->user->id,
                    'name' => $membership->user->name,
                    'email' => $membership->user->email,
                    'phone' => $membership->user->phone,
                    'role' => $membership->user->primaryRoleName(),
                ],
                'planName' => $membership->plan?->name ?? 'Custom plan',
                'status' => $membership->status->value,
                'daysRemaining' => $membership->daysRemaining(),
                'renewsAt' => $membership->renews_at?->toDateString(),
                'endsAt' => $membership->ends_at?->toDateString(),
                'effectiveEndsAt' => $membership->effectiveEndDate()?->toDateString(),
                'graceEndsAt' => $membership->grace_ends_at?->toDateString(),
                'cancelledAt' => $membership->cancelled_at?->toDateString(),
                'autoRenew' => $membership->auto_renew,
                'price' => (float) $membership->price,
                'currency' => $membership->currency,
                'notes' => $membership->notes,
                'paymentEvents' => $membership->paymentEvents
                    ->sortByDesc(fn ($event) => $event->event_at?->timestamp ?? $event->created_at?->timestamp ?? 0)
                    ->take(4)
                    ->map(fn ($event): array => [
                        'id' => $event->id,
                        'eventType' => $event->event_type->value,
                        'status' => $event->status->value,
                        'provider' => $event->provider,
                        'reference' => $event->reference,
                        'amount' => $event->amount !== null ? (float) $event->amount : null,
                        'currency' => $event->currency,
                        'eventAt' => $event->event_at?->toDateTimeString(),
                        'notes' => $event->notes,
                        'createdBy' => $event->createdBy?->name,
                    ])
                    ->values()
                    ->all(),
            ]);

        return response()->json([
            'data' => [
                'viewerRole' => $user->primaryRoleName(),
                'canManageMemberships' => $user->hasRole(RoleName::Admin) || $user->hasRole(RoleName::Coach),
                'filters' => [
                    'status' => $normalizedStatusFilter,
                ],
                'summary' => [
                    'totalMemberships' => (clone $baseQuery)->count(),
                    'currentAccess' => (clone $baseQuery)
                        ->whereIn('status', [
                            MembershipStatus::Trialing->value,
                            MembershipStatus::Active->value,
                            MembershipStatus::Grace->value,
                            MembershipStatus::PastDue->value,
                            MembershipStatus::Cancelled->value,
                        ])
                        ->count(),
                    'attentionRequired' => (clone $baseQuery)
                        ->whereIn('status', [
                            MembershipStatus::Grace->value,
                            MembershipStatus::PastDue->value,
                            MembershipStatus::Cancelled->value,
                        ])
                        ->count(),
                    'renewingThisWeek' => (clone $baseQuery)
                        ->where(function (Builder $query): void {
                            $query
                                ->whereBetween('grace_ends_at', [now()->toDateString(), now()->addDays(7)->toDateString()])
                                ->orWhereBetween('ends_at', [now()->toDateString(), now()->addDays(7)->toDateString()])
                                ->orWhereBetween('renews_at', [now()->toDateString(), now()->addDays(7)->toDateString()]);
                        })
                        ->count(),
                    'autoRenewEnabled' => (clone $baseQuery)
                        ->where('auto_renew', true)
                        ->count(),
                    'projectedMonthlyRevenue' => round($this->projectedMonthlyRevenue($baseQuery), 2),
                    'paymentVolumeThisMonth' => round($this->paymentVolumeThisMonth($baseQuery), 2),
                    'failedPaymentsThisMonth' => $this->failedPaymentsThisMonth($baseQuery),
                ],
                'memberships' => $this->paginationPayload($memberships),
                'paymentEventTypes' => collect(PaymentEventType::cases())
                    ->map(fn (PaymentEventType $type) => ['value' => $type->value, 'label' => $type->label()])
                    ->values()
                    ->all(),
                'paymentEventStatuses' => collect(PaymentEventStatus::cases())
                    ->map(fn (PaymentEventStatus $status) => ['value' => $status->value, 'label' => $status->label()])
                    ->values()
                    ->all(),
            ],
            'meta' => $this->metaPayload(),
        ]);
    }

    private function projectedMonthlyRevenue(Builder $baseQuery): float
    {
        return (clone $baseQuery)
            ->whereIn('status', [
                MembershipStatus::Trialing->value,
                MembershipStatus::Active->value,
                MembershipStatus::Grace->value,
                MembershipStatus::PastDue->value,
                MembershipStatus::Cancelled->value,
            ])
            ->leftJoin('subscription_plans', 'subscription_plans.id', '=', 'memberships.subscription_plan_id')
            ->selectRaw(
                'coalesce(sum(case subscription_plans.billing_interval when ? then memberships.price / 3 when ? then memberships.price / 12 else memberships.price end), 0) as monthly_revenue',
                [BillingInterval::Quarterly->value, BillingInterval::Yearly->value],
            )
            ->value('monthly_revenue') ?? 0.0;
    }

    private function paymentVolumeThisMonth(Builder $baseQuery): float
    {
        return (float) Membership::query()
            ->fromSub($baseQuery->select('memberships.id'), 'visible_memberships')
            ->join('payment_events', 'payment_events.membership_id', '=', 'visible_memberships.id')
            ->where('payment_events.status', PaymentEventStatus::Succeeded->value)
            ->where('payment_events.event_at', '>=', now()->startOfMonth())
            ->sum('payment_events.amount');
    }

    private function failedPaymentsThisMonth(Builder $baseQuery): int
    {
        return Membership::query()
            ->fromSub($baseQuery->select('memberships.id'), 'visible_memberships')
            ->join('payment_events', 'payment_events.membership_id', '=', 'visible_memberships.id')
            ->where('payment_events.status', PaymentEventStatus::Failed->value)
            ->where('payment_events.event_at', '>=', now()->startOfMonth())
            ->count();
    }
}
