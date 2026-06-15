<?php

namespace Tests\Feature;

use App\Enums\BillingInterval;
use App\Enums\CoachAthleteStatus;
use App\Enums\MembershipStatus;
use App\Enums\PaymentEventStatus;
use App\Enums\PaymentEventType;
use App\Enums\RoleName;
use App\Models\CoachAthleteAssignment;
use App\Models\Membership;
use App\Models\PaymentEvent;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_update_an_assigned_athlete_membership(): void
    {
        [$coach, $athlete, $membership] = $this->membershipFixture();

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Return to form',
            'notes' => null,
            'started_at' => now()->subDays(30)->toDateString(),
            'ended_at' => null,
        ]);

        $this->actingAs($coach)
            ->patch(route('memberships.update', $membership), [
                'status' => MembershipStatus::Active->value,
                'auto_renew' => true,
                'renews_at' => now()->addDays(10)->toDateString(),
                'ends_at' => now()->addDays(10)->toDateString(),
                'grace_ends_at' => '',
                'extension_days' => 5,
                'notes' => 'Recovered and back on track.',
            ])
            ->assertRedirect();

        $membership->refresh();

        $this->assertSame(MembershipStatus::Active, $membership->status);
        $this->assertTrue($membership->auto_renew);
        $this->assertSame('Recovered and back on track.', $membership->notes);
        $this->assertNotNull(
            PaymentEvent::query()
                ->where('membership_id', $membership->id)
                ->where('event_type', PaymentEventType::MembershipChange)
                ->first(),
        );
    }

    public function test_admin_can_record_a_manual_payment_event(): void
    {
        [$coach, $athlete, $membership] = $this->membershipFixture();

        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        $this->actingAs($admin)
            ->post(route('memberships.events.store', $membership), [
                'event_type' => PaymentEventType::Charge->value,
                'status' => PaymentEventStatus::Succeeded->value,
                'provider' => 'manual',
                'reference' => 'INV-9000',
                'amount' => 129,
                'currency' => 'USD',
                'event_at' => now()->toDateTimeString(),
                'notes' => 'Manual recovery payment recorded.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payment_events', [
            'membership_id' => $membership->id,
            'user_id' => $athlete->id,
            'created_by_user_id' => $admin->id,
            'reference' => 'INV-9000',
            'status' => PaymentEventStatus::Succeeded->value,
        ]);
    }

    public function test_athletes_cannot_manage_memberships(): void
    {
        [, $athlete, $membership] = $this->membershipFixture();

        $this->actingAs($athlete)
            ->patch(route('memberships.update', $membership), [
                'status' => MembershipStatus::Cancelled->value,
                'auto_renew' => false,
                'renews_at' => now()->addDays(5)->toDateString(),
                'ends_at' => now()->addDays(5)->toDateString(),
                'grace_ends_at' => '',
                'extension_days' => 0,
                'notes' => 'Should not work.',
            ])
            ->assertForbidden();
    }

    /**
     * @return array{User, User, Membership}
     */
    private function membershipFixture(): array
    {
        $plan = SubscriptionPlan::query()->create([
            'slug' => 'membership-ops-monthly',
            'name' => 'Membership Ops Monthly',
            'description' => null,
            'billing_interval' => BillingInterval::Monthly,
            'duration_days' => 30,
            'price' => 129,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $coach = User::factory()->create();
        $athlete = User::factory()->create();

        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        $membership = Membership::query()->create([
            'user_id' => $athlete->id,
            'subscription_plan_id' => $plan->id,
            'status' => MembershipStatus::Grace,
            'starts_at' => now()->subDays(25)->toDateString(),
            'renews_at' => now()->addDays(2)->toDateString(),
            'ends_at' => now()->addDays(2)->toDateString(),
            'grace_ends_at' => now()->addDays(5)->toDateString(),
            'cancelled_at' => null,
            'auto_renew' => false,
            'price' => 129,
            'currency' => 'USD',
            'notes' => 'Needs cleanup',
        ]);

        return [$coach, $athlete, $membership];
    }
}
