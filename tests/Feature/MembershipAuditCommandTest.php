<?php

namespace Tests\Feature;

use App\Enums\BillingInterval;
use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_audit_updates_lifecycle_statuses(): void
    {
        $plan = SubscriptionPlan::query()->create([
            'slug' => 'audit-monthly',
            'name' => 'Audit Monthly',
            'description' => null,
            'billing_interval' => BillingInterval::Monthly,
            'duration_days' => 30,
            'price' => 129,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $graceMembership = $this->membership($plan->id, MembershipStatus::Active, now()->subDay()->toDateString(), now()->addDays(3)->toDateString());
        $expiredMembership = $this->membership($plan->id, MembershipStatus::Grace, now()->subDays(4)->toDateString(), now()->subDay()->toDateString());
        $cancelledMembership = $this->membership($plan->id, MembershipStatus::Active, now()->addDays(20)->toDateString(), null, now()->subDay()->toDateString());
        $pastDueMembership = $this->membership($plan->id, MembershipStatus::PastDue, now()->addDays(2)->toDateString(), now()->addDays(5)->toDateString());
        $trialMembership = $this->membership($plan->id, MembershipStatus::Trialing, now()->addDays(7)->toDateString(), null, null, now()->addDays(2)->toDateString());

        $this->artisan('throughline:memberships:audit')
            ->expectsOutput('Processed 5 memberships.')
            ->expectsOutput('Updated 3 membership statuses.')
            ->assertExitCode(0);

        $graceMembership->refresh();
        $expiredMembership->refresh();
        $cancelledMembership->refresh();
        $pastDueMembership->refresh();
        $trialMembership->refresh();

        $this->assertSame(MembershipStatus::Grace, $graceMembership->status);
        $this->assertSame(MembershipStatus::Expired, $expiredMembership->status);
        $this->assertSame(MembershipStatus::Cancelled, $cancelledMembership->status);
        $this->assertSame(MembershipStatus::PastDue, $pastDueMembership->status);
        $this->assertSame(MembershipStatus::Trialing, $trialMembership->status);
    }

    private function membership(
        int $planId,
        MembershipStatus $status,
        string $endsAt,
        ?string $graceEndsAt = null,
        ?string $cancelledAt = null,
        ?string $startsAt = null,
    ): Membership {
        return Membership::query()->create([
            'user_id' => User::factory()->create()->id,
            'subscription_plan_id' => $planId,
            'status' => $status,
            'starts_at' => $startsAt ?? now()->subDays(10)->toDateString(),
            'renews_at' => $endsAt,
            'ends_at' => $endsAt,
            'grace_ends_at' => $graceEndsAt,
            'cancelled_at' => $cancelledAt,
            'auto_renew' => true,
            'price' => 129,
            'currency' => 'USD',
            'notes' => null,
        ]);
    }
}
