<?php

namespace App\Services\Billing;

use App\Enums\MembershipStatus;
use App\Enums\PaymentEventStatus;
use App\Enums\PaymentEventType;
use App\Models\BillingWebhookEvent;
use App\Models\Membership;
use App\Models\PaymentEvent;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use RuntimeException;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeBillingService
{
    private ?StripeClient $client = null;

    public function checkoutEnabled(): bool
    {
        return filled(config('throughline.billing.stripe.secret_key'));
    }

    public function portalEnabled(): bool
    {
        return $this->checkoutEnabled();
    }

    public function webhookEnabled(): bool
    {
        return $this->checkoutEnabled() && filled(config('throughline.billing.stripe.webhook_secret'));
    }

    public function createCheckoutUrl(User $user, SubscriptionPlan $plan): string
    {
        if (! $this->checkoutEnabled()) {
            throw new RuntimeException('Stripe checkout is not configured yet.');
        }

        if (! $plan->stripe_price_id) {
            throw new RuntimeException('That plan is not linked to a Stripe price yet.');
        }

        $customerId = $this->ensureCustomer($user);

        $session = $this->client()->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'line_items' => [[
                'price' => $plan->stripe_price_id,
                'quantity' => 1,
            ]],
            'client_reference_id' => (string) $user->id,
            'success_url' => $this->membershipsUrl(['billing' => 'success']),
            'cancel_url' => $this->membershipsUrl(['billing' => 'cancelled']),
            'metadata' => [
                'user_id' => (string) $user->id,
                'subscription_plan_id' => (string) $plan->id,
            ],
            'subscription_data' => [
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'subscription_plan_id' => (string) $plan->id,
                ],
            ],
        ]);

        return (string) $session->url;
    }

    public function createPortalUrl(User $user): string
    {
        if (! $this->portalEnabled()) {
            throw new RuntimeException('Stripe billing portal is not configured yet.');
        }

        if (! $user->stripe_customer_id) {
            throw new RuntimeException('This account does not have a Stripe billing profile yet.');
        }

        $session = $this->client()->billingPortal->sessions->create([
            'customer' => $user->stripe_customer_id,
            'return_url' => $this->membershipsUrl(),
        ]);

        return (string) $session->url;
    }

    /**
     * @return array{event: Event, webhookEvent: BillingWebhookEvent, processed: bool}
     */
    public function handleWebhook(string $payload, ?string $signature): array
    {
        if (! $this->webhookEnabled()) {
            throw new RuntimeException('Stripe webhook signing is not configured yet.');
        }

        $event = Webhook::constructEvent(
            $payload,
            $signature ?? '',
            (string) config('throughline.billing.stripe.webhook_secret'),
        );

        $eventPayload = json_decode($event->toJSON(), true, 512, JSON_THROW_ON_ERROR);

        $webhookEvent = BillingWebhookEvent::query()->firstOrCreate(
            [
                'provider' => 'stripe',
                'provider_event_id' => $event->id,
            ],
            [
                'event_type' => $event->type,
                'livemode' => (bool) $event->livemode,
                'payload' => $eventPayload,
            ],
        );

        if ($webhookEvent->processed_at) {
            return [
                'event' => $event,
                'webhookEvent' => $webhookEvent,
                'processed' => false,
            ];
        }

        $this->dispatchWebhook($eventPayload);

        $webhookEvent->forceFill([
            'event_type' => $event->type,
            'livemode' => (bool) $event->livemode,
            'payload' => $eventPayload,
            'processed_at' => now(),
        ])->save();

        return [
            'event' => $event,
            'webhookEvent' => $webhookEvent,
            'processed' => true,
        ];
    }

    public function syncMembershipFromSubscriptionPayload(array $subscription): ?Membership
    {
        $subscriptionId = Arr::get($subscription, 'id');
        $customerId = Arr::get($subscription, 'customer');
        $priceId = Arr::get($subscription, 'items.data.0.price.id');
        $metadata = Arr::get($subscription, 'metadata', []);

        if (! is_string($subscriptionId) || $subscriptionId === '') {
            return null;
        }

        $user = $this->resolveUserFromProviderMetadata($metadata, $customerId);

        if (! $user) {
            return null;
        }

        if ($customerId && $user->stripe_customer_id !== $customerId) {
            $user->forceFill(['stripe_customer_id' => $customerId])->save();
        }

        $plan = $this->resolvePlanFromProviderMetadata($metadata, $priceId);
        $membership = Membership::query()
            ->where('provider_subscription_id', $subscriptionId)
            ->first();

        if (! $membership) {
            $membership = Membership::query()
                ->where('user_id', $user->id)
                ->when(
                    $plan?->id,
                    fn ($query) => $query->where('subscription_plan_id', $plan->id),
                )
                ->orderByDesc('id')
                ->first();
        }

        $membership ??= new Membership([
            'user_id' => $user->id,
        ]);

        $membership->fill([
            'subscription_plan_id' => $plan?->id ?? $membership->subscription_plan_id,
            'status' => $this->mapMembershipStatus(
                Arr::get($subscription, 'status'),
                (bool) Arr::get($subscription, 'cancel_at_period_end', false),
                Arr::get($subscription, 'canceled_at'),
            ),
            'starts_at' => $this->dateFromUnix(
                Arr::get($subscription, 'start_date') ?: Arr::get($subscription, 'current_period_start')
            ) ?? $membership->starts_at ?? now()->toDateString(),
            'renews_at' => $this->dateFromUnix(
                Arr::get($subscription, 'current_period_end') ?: Arr::get($subscription, 'trial_end')
            ),
            'ends_at' => $this->dateFromUnix(
                Arr::get($subscription, 'current_period_end') ?: Arr::get($subscription, 'trial_end')
            ),
            'grace_ends_at' => null,
            'cancelled_at' => $this->dateFromUnix(
                Arr::get($subscription, 'cancel_at') ?: Arr::get($subscription, 'canceled_at')
            ),
            'auto_renew' => ! (bool) Arr::get($subscription, 'cancel_at_period_end', false)
                && Arr::get($subscription, 'status') !== 'canceled',
            'price' => $this->normalizeAmount(Arr::get($subscription, 'items.data.0.price.unit_amount'))
                ?? $plan?->price
                ?? $membership->price
                ?? 0,
            'currency' => strtoupper((string) (Arr::get($subscription, 'currency') ?: Arr::get($subscription, 'items.data.0.price.currency') ?: $plan?->currency ?: 'USD')),
            'billing_provider' => 'stripe',
            'provider_customer_id' => $customerId,
            'provider_subscription_id' => $subscriptionId,
            'provider_price_id' => $priceId,
            'provider_payload' => [
                'status' => Arr::get($subscription, 'status'),
                'cancel_at_period_end' => (bool) Arr::get($subscription, 'cancel_at_period_end', false),
                'current_period_start' => Arr::get($subscription, 'current_period_start'),
                'current_period_end' => Arr::get($subscription, 'current_period_end'),
                'trial_start' => Arr::get($subscription, 'trial_start'),
                'trial_end' => Arr::get($subscription, 'trial_end'),
                'metadata' => $metadata,
            ],
            'notes' => $membership->notes,
        ]);

        $membership->save();

        return $membership->fresh(['user', 'plan']);
    }

    public function recordCheckoutCompletion(array $checkoutSession): ?Membership
    {
        $metadata = Arr::get($checkoutSession, 'metadata', []);
        $customerId = Arr::get($checkoutSession, 'customer');
        $subscriptionId = Arr::get($checkoutSession, 'subscription');
        $user = $this->resolveUserFromProviderMetadata($metadata, $customerId);

        if (! $user) {
            return null;
        }

        if ($customerId && $user->stripe_customer_id !== $customerId) {
            $user->forceFill(['stripe_customer_id' => $customerId])->save();
        }

        if (! $subscriptionId) {
            return null;
        }

        $membership = Membership::query()
            ->where('provider_subscription_id', $subscriptionId)
            ->first();

        if (! $membership) {
            $plan = $this->resolvePlanFromProviderMetadata($metadata, null);

            $membership = Membership::query()
                ->where('user_id', $user->id)
                ->when($plan?->id, fn ($query) => $query->where('subscription_plan_id', $plan->id))
                ->orderByDesc('id')
                ->first();
        }

        if (! $membership) {
            return null;
        }

        $membership->forceFill([
            'billing_provider' => 'stripe',
            'provider_customer_id' => $customerId,
            'provider_subscription_id' => $subscriptionId,
        ])->save();

        return $membership->fresh(['user', 'plan']);
    }

    public function recordInvoiceEvent(array $invoice, PaymentEventStatus $status): ?PaymentEvent
    {
        $membership = $this->resolveMembershipForInvoice($invoice);

        if (! $membership) {
            return null;
        }

        return PaymentEvent::query()->updateOrCreate(
            [
                'membership_id' => $membership->id,
                'reference' => (string) Arr::get($invoice, 'id'),
            ],
            [
                'user_id' => $membership->user_id,
                'created_by_user_id' => null,
                'event_type' => PaymentEventType::Invoice,
                'status' => $status,
                'provider' => 'stripe',
                'amount' => $this->normalizeAmount(
                    $status === PaymentEventStatus::Succeeded
                        ? Arr::get($invoice, 'amount_paid')
                        : Arr::get($invoice, 'amount_due')
                ),
                'currency' => strtoupper((string) (Arr::get($invoice, 'currency') ?: $membership->currency)),
                'event_at' => $this->timestampFromUnix(
                    Arr::get($invoice, 'status_transitions.paid_at')
                    ?: Arr::get($invoice, 'created')
                ),
                'notes' => $status === PaymentEventStatus::Succeeded
                    ? 'Stripe invoice payment succeeded.'
                    : 'Stripe invoice payment failed and needs follow-up.',
                'metadata' => [
                    'subscription_id' => Arr::get($invoice, 'subscription'),
                    'customer_id' => Arr::get($invoice, 'customer'),
                    'invoice_status' => Arr::get($invoice, 'status'),
                    'hosted_invoice_url' => Arr::get($invoice, 'hosted_invoice_url'),
                ],
            ],
        );
    }

    private function dispatchWebhook(array $eventPayload): void
    {
        $type = Arr::get($eventPayload, 'type');
        $object = Arr::get($eventPayload, 'data.object', []);

        match ($type) {
            'checkout.session.completed' => $this->recordCheckoutCompletion($object),
            'customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted' => $this->syncMembershipFromSubscriptionPayload($object),
            'invoice.payment_succeeded' => $this->recordInvoiceEvent($object, PaymentEventStatus::Succeeded),
            'invoice.payment_failed' => $this->recordInvoiceEvent($object, PaymentEventStatus::Failed),
            default => null,
        };
    }

    private function ensureCustomer(User $user): string
    {
        if ($user->stripe_customer_id) {
            return $user->stripe_customer_id;
        }

        $customer = $this->client()->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'phone' => $user->phone,
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
        ]);

        $user->forceFill([
            'stripe_customer_id' => (string) $customer->id,
        ])->save();

        return $user->stripe_customer_id;
    }

    private function resolveMembershipForInvoice(array $invoice): ?Membership
    {
        $subscriptionId = Arr::get($invoice, 'subscription');

        if (is_string($subscriptionId) && $subscriptionId !== '') {
            $membership = Membership::query()
                ->where('provider_subscription_id', $subscriptionId)
                ->first();

            if ($membership) {
                return $membership;
            }
        }

        $customerId = Arr::get($invoice, 'customer');

        if (! is_string($customerId) || $customerId === '') {
            return null;
        }

        return Membership::query()
            ->where('provider_customer_id', $customerId)
            ->orderByDesc('id')
            ->first();
    }

    private function resolveUserFromProviderMetadata(array $metadata, mixed $customerId): ?User
    {
        $userId = Arr::get($metadata, 'user_id');

        if ($userId) {
            $user = User::query()->find((int) $userId);

            if ($user) {
                return $user;
            }
        }

        if (! is_string($customerId) || $customerId === '') {
            return null;
        }

        return User::query()->where('stripe_customer_id', $customerId)->first();
    }

    private function resolvePlanFromProviderMetadata(array $metadata, mixed $priceId): ?SubscriptionPlan
    {
        $planId = Arr::get($metadata, 'subscription_plan_id');

        if ($planId) {
            $plan = SubscriptionPlan::query()->find((int) $planId);

            if ($plan) {
                return $plan;
            }
        }

        if (! is_string($priceId) || $priceId === '') {
            return null;
        }

        return SubscriptionPlan::query()->where('stripe_price_id', $priceId)->first();
    }

    private function mapMembershipStatus(mixed $status, bool $cancelAtPeriodEnd, mixed $canceledAt): MembershipStatus
    {
        return match ($status) {
            'trialing' => MembershipStatus::Trialing,
            'active' => MembershipStatus::Active,
            'past_due', 'unpaid', 'incomplete', 'incomplete_expired' => MembershipStatus::PastDue,
            'canceled' => $canceledAt ? MembershipStatus::Expired : MembershipStatus::Cancelled,
            default => $cancelAtPeriodEnd ? MembershipStatus::Cancelled : MembershipStatus::Active,
        };
    }

    private function membershipsUrl(array $query = []): string
    {
        $base = route('memberships.index');

        if ($query === []) {
            return $base;
        }

        return sprintf('%s?%s', $base, http_build_query($query));
    }

    private function normalizeAmount(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return round(((float) $value) / 100, 2);
    }

    private function dateFromUnix(mixed $value): ?string
    {
        $timestamp = $this->timestampFromUnix($value);

        return $timestamp?->toDateString();
    }

    private function timestampFromUnix(mixed $value): ?Carbon
    {
        if (! is_numeric($value)) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $value);
    }

    private function client(): StripeClient
    {
        if ($this->client instanceof StripeClient) {
            return $this->client;
        }

        $secretKey = config('throughline.billing.stripe.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            throw new RuntimeException('Stripe secret key is missing.');
        }

        return $this->client = new StripeClient($secretKey);
    }
}
