<?php

namespace Tests\Feature;

use App\Enums\BillingInterval;
use App\Enums\MembershipStatus;
use App\Enums\RoleName;
use App\Models\Membership;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_redirects_by_role(): void
    {
        $admin = User::factory()->create();
        $coach = User::factory()->create();
        $athlete = User::factory()->create();
        $unscoped = User::factory()->create();

        $admin->assignRole(RoleName::Admin);
        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin/dashboard');
        $this->actingAs($coach)->get('/dashboard')->assertRedirect('/coach');
        $this->actingAs($athlete)->get('/dashboard')->assertRedirect('/app');
        $this->actingAs($unscoped)->get('/dashboard')->assertRedirect('/settings/profile');
    }

    public function test_admin_dashboard_is_admin_only(): void
    {
        $admin = User::factory()->create();
        $coach = User::factory()->create();
        $athlete = User::factory()->create();

        $admin->assignRole(RoleName::Admin);
        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($coach)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($athlete)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_users_receive_platform_metrics(): void
    {
        $admin = User::factory()->create();
        $coach = User::factory()->create();
        $athlete = User::factory()->create();
        $plan = SubscriptionPlan::query()->create([
            'slug' => 'athlete-monthly',
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

        Membership::query()->create([
            'user_id' => $athlete->id,
            'subscription_plan_id' => $plan->id,
            'status' => MembershipStatus::Active,
            'starts_at' => now()->subDays(5)->toDateString(),
            'renews_at' => now()->addDays(25)->toDateString(),
            'ends_at' => now()->addDays(25)->toDateString(),
            'grace_ends_at' => null,
            'cancelled_at' => null,
            'auto_renew' => true,
            'price' => 129,
            'currency' => 'USD',
            'notes' => null,
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('viewer.primaryRole', RoleName::Admin->value)
                ->where('admin.metrics.totalUsers', 3)
                ->where('admin.metrics.totalCoaches', 1)
                ->where('admin.metrics.totalAthletes', 1)
                ->where('admin.metrics.activeMemberships', 1)
                ->where('admin.signupMix.0.method', 'email')
                ->where('admin.signupMix.0.count', 3)
                ->where('admin.recentUsers.0.id', fn (int $id) => $id > 0)
                ->where('admin.athleteTable.0.name', $athlete->name)
                ->where('admin.athleteTable.0.currentMembership.subscribedAt', now()->subDays(5)->toDateString())
                ->where('admin.subscriptionTable.0.userId', $athlete->id)
                ->where('admin.subscriptionTable.0.startsAt', now()->subDays(5)->toDateString())
            );
    }
}
