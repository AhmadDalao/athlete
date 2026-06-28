<?php

namespace Tests\Feature;

use App\Enums\BillingInterval;
use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\MembershipStatus;
use App\Enums\PaymentEventStatus;
use App\Enums\PaymentEventType;
use App\Enums\RoleName;
use App\Enums\SignupMethod;
use App\Models\DeviceConnection;
use App\Models\Membership;
use App\Models\PaymentEvent;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminControlCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_cannot_open_the_control_center(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole(RoleName::Coach);

        $this->actingAs($coach)
            ->get('/admin/control-center')
            ->assertForbidden();
    }

    public function test_admin_can_view_the_control_center(): void
    {
        $admin = User::factory()->create();
        $coach = User::factory()->create();
        $athlete = User::factory()->create([
            'registration_channel' => SignupMethod::Google->value,
        ]);

        $plan = SubscriptionPlan::query()->create([
            'slug' => 'control-center-athlete-monthly',
            'name' => 'Athlete Monthly',
            'description' => null,
            'billing_interval' => BillingInterval::Monthly,
            'duration_days' => 30,
            'price' => 129,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $admin->assignRole(RoleName::Admin);
        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        $membership = Membership::query()->create([
            'user_id' => $athlete->id,
            'subscription_plan_id' => $plan->id,
            'status' => MembershipStatus::Active,
            'starts_at' => now()->subDays(20)->toDateString(),
            'renews_at' => now()->addDays(5)->toDateString(),
            'ends_at' => now()->addDays(5)->toDateString(),
            'grace_ends_at' => null,
            'cancelled_at' => null,
            'auto_renew' => true,
            'price' => 129,
            'currency' => 'USD',
            'notes' => null,
        ]);

        PaymentEvent::query()->create([
            'membership_id' => $membership->id,
            'user_id' => $athlete->id,
            'created_by_user_id' => $admin->id,
            'event_type' => PaymentEventType::Charge,
            'status' => PaymentEventStatus::Failed,
            'provider' => 'manual',
            'reference' => 'cc_fail_001',
            'amount' => 129,
            'currency' => 'USD',
            'event_at' => now()->toDateTimeString(),
            'notes' => null,
            'metadata' => [],
        ]);

        DeviceConnection::query()->create([
            'user_id' => $athlete->id,
            'provider' => DeviceProvider::Whoop,
            'status' => DeviceConnectionStatus::Attention,
            'auth_type' => 'oauth',
            'external_user_id' => 'whoop-athlete-1',
            'access_token' => 'test-token',
            'refresh_token' => 'refresh-token',
            'token_expires_at' => now()->addHour(),
            'granted_scopes' => ['read:recovery'],
            'provider_account_payload' => ['email' => $athlete->email],
            'last_synced_at' => now()->subHours(6),
        ]);

        $this->actingAs($admin)
            ->get('/admin/control-center')
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/control-center')
                ->where('summary.totalUsers', 3)
                ->where('summary.coaches', 1)
                ->where('summary.athletes', 1)
                ->where('summary.activeMemberships', 1)
                ->where('summary.failedPaymentsThisMonth', 1)
                ->where('summary.attentionConnections', 1)
                ->where('summary.athletesWithCoverageGaps', 1)
                ->where('queues.membershipQueue.0.userName', $athlete->name)
                ->where('queues.paymentQueue.0.reference', 'cc_fail_001')
                ->where('queues.deviceQueue.0.provider', DeviceProvider::Whoop->label())
                ->where('queues.deviceQueue.0.issue', 'No normalized snapshot exists yet.')
                ->where('signupMix.1.method', SignupMethod::Google->value)
                ->where('signupMix.1.count', 1)
            );
    }
}
