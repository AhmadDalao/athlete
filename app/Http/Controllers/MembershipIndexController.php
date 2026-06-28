<?php

namespace App\Http\Controllers;

use App\Enums\BillingInterval;
use App\Enums\MembershipStatus;
use App\Enums\PaymentEventStatus;
use App\Enums\PaymentEventType;
use App\Enums\RoleName;
use App\Models\Membership;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Billing\StripeBillingService;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class MembershipIndexController extends Controller
{
    public function __invoke(StripeBillingService $billing): Response
    {
        /** @var User $user */
        $user = request()->user()->loadMissing('roles', 'memberships.plan');
        $statusFilter = request()->string('status')->value();
        $allowedStatuses = collect(MembershipStatus::cases())->map->value->all();
        $normalizedStatusFilter = in_array($statusFilter, $allowedStatuses, true) ? $statusFilter : null;
        $currentMembership = $user->currentMembership();

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
                'userName' => $membership->user->name,
                'userEmail' => $membership->user->email,
                'userPhone' => $membership->user->phone,
                'userRole' => $membership->user->primaryRoleName(),
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
                'billingProvider' => $membership->billing_provider,
                'providerSubscriptionId' => $membership->provider_subscription_id,
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

        return Inertia::render('memberships/index', [
            'viewerRole' => $user->primaryRoleName(),
            'canManageMemberships' => $user->hasRole(RoleName::Admin) || $user->hasRole(RoleName::Coach),
            'scopeLabel' => $this->scopeLabel($user),
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
            'billing' => [
                'provider' => (string) config('throughline.billing.provider'),
                'checkoutEnabled' => $billing->checkoutEnabled(),
                'portalEnabled' => $billing->portalEnabled(),
                'webhookEnabled' => $billing->webhookEnabled(),
                'hasCustomerProfile' => filled($user->stripe_customer_id),
                'currentPlanId' => $currentMembership?->subscription_plan_id,
                'activePlans' => SubscriptionPlan::query()
                    ->where('is_active', true)
                    ->orderBy('price')
                    ->get()
                    ->map(fn (SubscriptionPlan $plan): array => [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'description' => $plan->description,
                        'price' => (float) $plan->price,
                        'currency' => $plan->currency,
                        'durationDays' => $plan->duration_days,
                        'billingInterval' => $plan->billing_interval->value,
                        'checkoutReady' => filled($plan->stripe_price_id),
                    ])
                    ->values()
                    ->all(),
            ],
            'memberships' => $memberships,
            'auditCommand' => 'php artisan throughline:memberships:audit',
            'paymentEventTypes' => collect(PaymentEventType::cases())
                ->map(fn (PaymentEventType $type) => ['value' => $type->value, 'label' => $type->label()])
                ->values()
                ->all(),
            'paymentEventStatuses' => collect(PaymentEventStatus::cases())
                ->map(fn (PaymentEventStatus $status) => ['value' => $status->value, 'label' => $status->label()])
                ->values()
                ->all(),
        ]);
    }

    private function scopeLabel(User $user): string
    {
        if ($user->hasRole(RoleName::Admin)) {
            return 'All platform memberships';
        }

        if ($user->hasRole(RoleName::Coach)) {
            return 'Your roster and coach memberships';
        }

        return 'Your membership timeline';
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
