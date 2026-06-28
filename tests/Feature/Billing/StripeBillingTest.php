<?php

namespace Tests\Feature\Billing;

use App\Enums\BillingInterval;
use App\Enums\MembershipStatus;
use App\Enums\PaymentEventStatus;
use App\Enums\RoleName;
use App\Models\Membership;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Billing\StripeBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class StripeBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_launch_checkout_for_a_stripe_ready_plan(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Athlete);

        $plan = SubscriptionPlan::query()->create([
            'slug' => 'athlete-checkout-test',
            'name' => 'Athlete Checkout Test',
            'description' => 'Test plan',
            'billing_interval' => BillingInterval::Monthly,
            'duration_days' => 30,
            'price' => 129,
            'currency' => 'USD',
            'stripe_price_id' => 'price_test_athlete_checkout',
            'is_active' => true,
        ]);

        $this->mock(StripeBillingService::class, function (MockInterface $mock) use ($user, $plan): void {
            $mock->shouldReceive('createCheckoutUrl')
                ->once()
                ->withArgs(fn (User $checkoutUser, SubscriptionPlan $checkoutPlan) => $checkoutUser->is($user) && $checkoutPlan->is($plan))
                ->andReturn('https://checkout.stripe.com/c/pay/test-session');
        });

        $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->post(route('billing.checkout.store', absolute: false), [
                'plan_id' => $plan->id,
            ])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/c/pay/test-session');
    }

    public function test_subscription_webhook_syncs_membership_state(): void
    {
        config()->set('throughline.billing.stripe.secret_key', 'sk_test_123');
        config()->set('throughline.billing.stripe.webhook_secret', 'whsec_test_123');

        $user = User::factory()->create([
            'stripe_customer_id' => 'cus_test_123',
        ]);
        $user->assignRole(RoleName::Athlete);

        $plan = SubscriptionPlan::query()->create([
            'slug' => 'athlete-webhook-test',
            'name' => 'Athlete Webhook Test',
            'description' => 'Webhook synced plan',
            'billing_interval' => BillingInterval::Monthly,
            'duration_days' => 30,
            'price' => 129,
            'currency' => 'USD',
            'stripe_price_id' => 'price_test_webhook',
            'is_active' => true,
        ]);

        $membership = Membership::query()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => MembershipStatus::Grace,
            'starts_at' => now()->subDays(10)->toDateString(),
            'renews_at' => now()->addDays(2)->toDateString(),
            'ends_at' => now()->addDays(2)->toDateString(),
            'grace_ends_at' => now()->addDays(5)->toDateString(),
            'cancelled_at' => null,
            'auto_renew' => false,
            'price' => 129,
            'currency' => 'USD',
            'notes' => 'Pre-stripe membership',
        ]);

        $payload = json_encode([
            'id' => 'evt_subscription_updated_1',
            'object' => 'event',
            'type' => 'customer.subscription.updated',
            'livemode' => false,
            'data' => [
                'object' => [
                    'id' => 'sub_test_123',
                    'object' => 'subscription',
                    'customer' => 'cus_test_123',
                    'status' => 'active',
                    'cancel_at_period_end' => false,
                    'start_date' => now()->subDays(1)->timestamp,
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => now()->addDays(30)->timestamp,
                    'currency' => 'usd',
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'subscription_plan_id' => (string) $plan->id,
                    ],
                    'items' => [
                        'data' => [[
                            'price' => [
                                'id' => 'price_test_webhook',
                                'unit_amount' => 12900,
                                'currency' => 'usd',
                            ],
                        ]],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->stripeSignature($payload, 'whsec_test_123');

        $this->call(
            'POST',
            route('billing.webhooks.stripe', absolute: false),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        )
            ->assertOk()
            ->assertJson([
                'received' => true,
                'processed' => true,
            ]);

        $membership->refresh();

        $this->assertSame(MembershipStatus::Active, $membership->status);
        $this->assertSame('stripe', $membership->billing_provider);
        $this->assertSame('cus_test_123', $membership->provider_customer_id);
        $this->assertSame('sub_test_123', $membership->provider_subscription_id);
        $this->assertSame('price_test_webhook', $membership->provider_price_id);
        $this->assertTrue($membership->auto_renew);
        $this->assertDatabaseHas('billing_webhook_events', [
            'provider' => 'stripe',
            'provider_event_id' => 'evt_subscription_updated_1',
            'event_type' => 'customer.subscription.updated',
        ]);
    }

    public function test_failed_invoice_webhook_records_one_payment_event_even_when_replayed(): void
    {
        config()->set('throughline.billing.stripe.secret_key', 'sk_test_123');
        config()->set('throughline.billing.stripe.webhook_secret', 'whsec_test_123');

        $user = User::factory()->create([
            'stripe_customer_id' => 'cus_invoice_test_123',
        ]);
        $user->assignRole(RoleName::Athlete);

        $plan = SubscriptionPlan::query()->create([
            'slug' => 'athlete-invoice-test',
            'name' => 'Athlete Invoice Test',
            'description' => 'Invoice synced plan',
            'billing_interval' => BillingInterval::Monthly,
            'duration_days' => 30,
            'price' => 129,
            'currency' => 'USD',
            'stripe_price_id' => 'price_test_invoice',
            'is_active' => true,
        ]);

        Membership::query()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => MembershipStatus::PastDue,
            'starts_at' => now()->subDays(12)->toDateString(),
            'renews_at' => now()->addDays(18)->toDateString(),
            'ends_at' => now()->addDays(18)->toDateString(),
            'grace_ends_at' => null,
            'cancelled_at' => null,
            'auto_renew' => true,
            'price' => 129,
            'currency' => 'USD',
            'billing_provider' => 'stripe',
            'provider_customer_id' => 'cus_invoice_test_123',
            'provider_subscription_id' => 'sub_invoice_test_123',
            'provider_price_id' => 'price_test_invoice',
            'notes' => 'Stripe controlled membership',
        ]);

        $payload = json_encode([
            'id' => 'evt_invoice_failed_1',
            'object' => 'event',
            'type' => 'invoice.payment_failed',
            'livemode' => false,
            'data' => [
                'object' => [
                    'id' => 'in_test_failed_123',
                    'object' => 'invoice',
                    'customer' => 'cus_invoice_test_123',
                    'subscription' => 'sub_invoice_test_123',
                    'status' => 'open',
                    'amount_due' => 12900,
                    'currency' => 'usd',
                    'created' => now()->timestamp,
                    'hosted_invoice_url' => 'https://stripe.test/invoices/in_test_failed_123',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->stripeSignature($payload, 'whsec_test_123');

        $request = fn () => $this->call(
            'POST',
            route('billing.webhooks.stripe', absolute: false),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        );

        $request()->assertOk()->assertJson(['processed' => true]);
        $request()->assertOk()->assertJson(['processed' => false]);

        $this->assertDatabaseHas('payment_events', [
            'provider' => 'stripe',
            'reference' => 'in_test_failed_123',
            'status' => PaymentEventStatus::Failed->value,
        ]);
        $this->assertDatabaseCount('payment_events', 1);
        $this->assertDatabaseCount('billing_webhook_events', 1);
    }

    private function stripeSignature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', sprintf('%s.%s', $timestamp, $payload), $secret);

        return sprintf('t=%s,v1=%s', $timestamp, $signature);
    }
}
