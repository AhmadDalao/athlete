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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MembershipIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_all_memberships(): void
    {
        [$plan, $admin, $coach, $athleteOne, $athleteTwo] = $this->membershipFixtures();

        Membership::query()->create($this->membershipData($coach->id, $plan->id, MembershipStatus::Active, 79, 12));
        $graceMembership = Membership::query()->create($this->membershipData($athleteOne->id, $plan->id, MembershipStatus::Grace, 129, 4, -1));
        Membership::query()->create($this->membershipData($athleteTwo->id, $plan->id, MembershipStatus::Active, 129, 18));
        PaymentEvent::query()->create([
            'membership_id' => $graceMembership->id,
            'user_id' => $athleteOne->id,
            'created_by_user_id' => $admin->id,
            'event_type' => PaymentEventType::Charge,
            'status' => PaymentEventStatus::Failed,
            'provider' => 'manual',
            'reference' => 'FAIL-100',
            'amount' => 129,
            'currency' => 'USD',
            'event_at' => now()->toDateTimeString(),
            'notes' => 'Card failed.',
        ]);

        $this->actingAs($admin)
            ->get('/memberships')
            ->assertInertia(fn (Assert $page) => $page
                ->component('memberships/index')
                ->where('viewerRole', RoleName::Admin->value)
                ->where('canManageMemberships', true)
                ->where('scopeLabel', 'All platform memberships')
                ->where('summary.totalMemberships', 3)
                ->where('summary.failedPaymentsThisMonth', 1)
                ->where('memberships.total', 3)
            );
    }

    public function test_coach_sees_their_own_membership_and_assigned_athletes_only(): void
    {
        [$plan, $admin, $coach, $athleteOne, $athleteTwo] = $this->membershipFixtures();

        Membership::query()->create($this->membershipData($coach->id, $plan->id, MembershipStatus::Active, 79, 20));
        Membership::query()->create($this->membershipData($athleteOne->id, $plan->id, MembershipStatus::Grace, 129, 3, -1));
        Membership::query()->create($this->membershipData($athleteTwo->id, $plan->id, MembershipStatus::Active, 129, 21));

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athleteOne->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Strength cycle',
            'notes' => null,
            'started_at' => now()->subDays(30)->toDateString(),
            'ended_at' => null,
        ]);

        $this->actingAs($coach)
            ->get('/memberships')
            ->assertInertia(fn (Assert $page) => $page
                ->component('memberships/index')
                ->where('viewerRole', RoleName::Coach->value)
                ->where('canManageMemberships', true)
                ->where('scopeLabel', 'Your roster and coach memberships')
                ->where('summary.totalMemberships', 2)
                ->where('summary.attentionRequired', 1)
                ->where('memberships.total', 2)
                ->where('memberships.data.0.userName', $athleteOne->name)
                ->where('memberships.data.1.userName', $coach->name)
            );
    }

    public function test_athlete_only_sees_their_own_membership(): void
    {
        [$plan, , $coach, $athleteOne, $athleteTwo] = $this->membershipFixtures();

        Membership::query()->create($this->membershipData($coach->id, $plan->id, MembershipStatus::Active, 79, 20));
        Membership::query()->create($this->membershipData($athleteOne->id, $plan->id, MembershipStatus::Active, 129, 15));
        Membership::query()->create($this->membershipData($athleteTwo->id, $plan->id, MembershipStatus::Grace, 129, 3, -1));

        $this->actingAs($athleteOne)
            ->get('/memberships')
            ->assertInertia(fn (Assert $page) => $page
                ->component('memberships/index')
                ->where('viewerRole', RoleName::Athlete->value)
                ->where('canManageMemberships', false)
                ->where('scopeLabel', 'Your membership timeline')
                ->where('summary.totalMemberships', 1)
                ->where('memberships.total', 1)
                ->where('memberships.data.0.userName', $athleteOne->name)
            );
    }

    /**
     * @return array{SubscriptionPlan, User, User, User, User}
     */
    private function membershipFixtures(): array
    {
        $plan = SubscriptionPlan::query()->create([
            'slug' => 'membership-index-monthly',
            'name' => 'Membership Index Monthly',
            'description' => null,
            'billing_interval' => BillingInterval::Monthly,
            'duration_days' => 30,
            'price' => 129,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $admin = User::factory()->create();
        $coach = User::factory()->create();
        $athleteOne = User::factory()->create();
        $athleteTwo = User::factory()->create();

        $admin->assignRole(RoleName::Admin);
        $coach->assignRole(RoleName::Coach);
        $athleteOne->assignRole(RoleName::Athlete);
        $athleteTwo->assignRole(RoleName::Athlete);

        return [$plan, $admin, $coach, $athleteOne, $athleteTwo];
    }

    /**
     * @return array<string, mixed>
     */
    private function membershipData(
        int $userId,
        int $planId,
        MembershipStatus $status,
        float $price,
        int $renewsInDays,
        int $endsInDays = 0,
    ): array {
        return [
            'user_id' => $userId,
            'subscription_plan_id' => $planId,
            'status' => $status,
            'starts_at' => now()->subDays(10)->toDateString(),
            'renews_at' => now()->addDays($renewsInDays)->toDateString(),
            'ends_at' => now()->addDays($endsInDays === 0 ? $renewsInDays : $endsInDays)->toDateString(),
            'grace_ends_at' => $status === MembershipStatus::Grace ? now()->addDays(3)->toDateString() : null,
            'cancelled_at' => null,
            'auto_renew' => $status !== MembershipStatus::Grace,
            'price' => $price,
            'currency' => 'USD',
            'notes' => null,
        ];
    }
}
