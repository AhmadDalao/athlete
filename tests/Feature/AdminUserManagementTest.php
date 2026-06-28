<?php

namespace Tests\Feature;

use App\Enums\BillingInterval;
use App\Enums\MembershipStatus;
use App\Enums\RoleName;
use App\Enums\SignupMethod;
use App\Models\Membership;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_cannot_open_the_admin_user_index(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole(RoleName::Coach);

        $this->actingAs($coach)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_view_the_admin_user_index(): void
    {
        $admin = User::factory()->create();
        $coach = User::factory()->create(['registration_channel' => SignupMethod::Google->value]);
        $athlete = User::factory()->create();

        $admin->assignRole(RoleName::Admin);
        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/index')
                ->where('summary.totalUsers', 3)
                ->where('summary.owners', 0)
                ->where('summary.coaches', 1)
                ->where('summary.athletes', 1)
                ->where('users.total', 3)
                ->has('permissionGroups')
                ->where('channelOptions.1.value', SignupMethod::Google->value)
            );
    }

    public function test_admin_user_index_supports_table_page_size_options(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        User::factory()
            ->count(14)
            ->create()
            ->each(fn (User $user) => $user->assignRole(RoleName::Athlete));

        $this->actingAs($admin)
            ->get('/admin/users?per_page=10')
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/index')
                ->where('filters.per_page', '10')
                ->where('users.total', 15)
                ->has('users.data', 10)
            );

        $this->actingAs($admin)
            ->get('/admin/users?per_page=25')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.per_page', '25')
                ->where('users.total', 15)
                ->has('users.data', 15)
            );

        $this->actingAs($admin)
            ->get('/admin/users?per_page=all')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.per_page', 'all')
                ->where('users.total', 15)
                ->has('users.data', 15)
            );
    }

    public function test_owner_can_see_owner_role_in_the_admin_user_index(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Owner);
        $owner->assignRole(RoleName::Admin);

        $this->actingAs($owner)
            ->get('/admin/users')
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/index')
                ->where('summary.owners', 1)
                ->where('roleOptions.0.value', RoleName::Owner->value)
            );
    }

    public function test_admin_user_index_includes_subscription_dates_for_tracking_tables(): void
    {
        $admin = User::factory()->create();
        $athlete = User::factory()->create(['email' => 'tracking-athlete@example.com']);
        $plan = SubscriptionPlan::query()->create([
            'slug' => 'tracking-monthly',
            'name' => 'Tracking Monthly',
            'description' => null,
            'billing_interval' => BillingInterval::Monthly,
            'duration_days' => 30,
            'price' => 129,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $admin->assignRole(RoleName::Admin);
        $athlete->assignRole(RoleName::Athlete);

        Membership::query()->create([
            'user_id' => $athlete->id,
            'subscription_plan_id' => $plan->id,
            'status' => MembershipStatus::Active,
            'starts_at' => now()->subDays(4)->toDateString(),
            'renews_at' => now()->addDays(26)->toDateString(),
            'ends_at' => now()->addDays(26)->toDateString(),
            'grace_ends_at' => null,
            'cancelled_at' => null,
            'auto_renew' => true,
            'price' => 129,
            'currency' => 'USD',
            'notes' => null,
        ]);

        $this->actingAs($admin)
            ->get('/admin/users?role=athlete')
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/index')
                ->where('users.data.0.email', 'tracking-athlete@example.com')
                ->where('users.data.0.currentMembership.planName', 'Tracking Monthly')
                ->where('users.data.0.currentMembership.startsAt', now()->subDays(4)->toDateString())
                ->where('users.data.0.currentMembership.renewsAt', now()->addDays(26)->toDateString())
                ->where('users.data.0.currentMembership.endsAt', now()->addDays(26)->toDateString())
            );
    }

    public function test_admin_can_create_users_and_admins_from_the_ui(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Operator',
                'email' => 'operator@example.com',
                'phone' => '+15551234567',
                'password' => 'temporary-password',
                'primary_goal' => 'Run support and account cleanup',
                'position' => 'Support Admin',
                'preferred_contact_method' => 'email',
                'registration_channel' => SignupMethod::Email->value,
                'roles' => [RoleName::Admin->value, RoleName::Coach->value],
                'permissions' => ['dashboard.view', 'admin.users.view', 'memberships.view'],
                'email_verified' => true,
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'operator@example.com')->firstOrFail();

        $this->assertSame('New Operator', $created->name);
        $this->assertSame('+15551234567', $created->phone);
        $this->assertSame('Run support and account cleanup', $created->primary_goal);
        $this->assertSame('Support Admin', $created->position);
        $this->assertNotNull($created->email_verified_at);
        $this->assertTrue($created->hasRole(RoleName::Admin));
        $this->assertTrue($created->hasRole(RoleName::Coach));
        $this->assertSame(['dashboard.view', 'admin.users.view', 'memberships.view'], $created->permissionKeys());
    }

    public function test_admin_cannot_create_owner_accounts(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Fake Owner',
                'email' => 'fake-owner@example.com',
                'phone' => '+15551234567',
                'password' => 'temporary-password',
                'primary_goal' => 'Take over',
                'position' => 'Owner',
                'preferred_contact_method' => 'email',
                'registration_channel' => SignupMethod::Email->value,
                'roles' => [RoleName::Owner->value, RoleName::Admin->value],
                'permissions' => PermissionCatalog::keys(),
                'email_verified' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'fake-owner@example.com']);
    }

    public function test_owner_can_create_owner_admin_accounts(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Owner);
        $owner->assignRole(RoleName::Admin);

        $this->actingAs($owner)
            ->post(route('admin.users.store'), [
                'name' => 'Second Owner',
                'email' => 'second-owner@example.com',
                'phone' => '+15551234568',
                'password' => 'temporary-password',
                'primary_goal' => 'Run operations',
                'position' => 'Owner / General Manager',
                'preferred_contact_method' => 'email',
                'registration_channel' => SignupMethod::Email->value,
                'roles' => [RoleName::Owner->value, RoleName::Admin->value],
                'permissions' => PermissionCatalog::keys(),
                'email_verified' => true,
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'second-owner@example.com')->firstOrFail();

        $this->assertSame('Owner / General Manager', $created->position);
        $this->assertTrue($created->hasRole(RoleName::Owner));
        $this->assertTrue($created->hasRole(RoleName::Admin));
        $this->assertSame(PermissionCatalog::keys(), $created->permissionKeys());
    }

    public function test_non_admin_users_cannot_open_admin_user_profiles(): void
    {
        $coach = User::factory()->create();
        $athlete = User::factory()->create();

        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        $this->actingAs($coach)
            ->get(route('admin.users.show', $athlete, absolute: false))
            ->assertForbidden();
    }

    public function test_admin_can_view_a_user_profile_with_subscription_context(): void
    {
        $admin = User::factory()->create();
        $athlete = User::factory()->create(['email' => 'athlete@example.com']);
        $plan = SubscriptionPlan::query()->create([
            'slug' => 'profile-monthly',
            'name' => 'Profile Monthly',
            'description' => null,
            'billing_interval' => BillingInterval::Monthly,
            'duration_days' => 30,
            'price' => 129,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $admin->assignRole(RoleName::Admin);
        $athlete->assignRole(RoleName::Athlete);

        Membership::query()->create([
            'user_id' => $athlete->id,
            'subscription_plan_id' => $plan->id,
            'status' => MembershipStatus::Active,
            'starts_at' => now()->subDays(9)->toDateString(),
            'renews_at' => now()->addDays(21)->toDateString(),
            'ends_at' => now()->addDays(21)->toDateString(),
            'grace_ends_at' => null,
            'cancelled_at' => null,
            'auto_renew' => true,
            'price' => 129,
            'currency' => 'USD',
            'notes' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $athlete, absolute: false))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/show')
                ->where('profile.id', $athlete->id)
                ->where('profile.email', 'athlete@example.com')
                ->where('profile.permissionCount', count(PermissionCatalog::defaultsForRole(RoleName::Athlete)))
                ->where('summary.currentPlan', 'Profile Monthly')
                ->where('summary.subscribedAt', now()->subDays(9)->toDateString())
                ->where('memberships.0.planName', 'Profile Monthly')
                ->where('memberships.0.startsAt', now()->subDays(9)->toDateString())
            );
    }

    public function test_admin_can_update_roles_and_onboarding_fields_from_the_ui(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'phone' => '+15550000111',
            'primary_goal' => 'Old goal',
            'position' => 'Athlete',
            'preferred_contact_method' => 'email',
            'registration_channel' => SignupMethod::Email->value,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $admin->assignRole(RoleName::Admin);
        $user->assignRole(RoleName::Athlete);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => 'Updated User',
                'email' => 'new@example.com',
                'phone' => '+15550000999',
                'primary_goal' => 'New goal',
                'position' => 'Assistant Coach',
                'preferred_contact_method' => 'phone',
                'registration_channel' => SignupMethod::Google->value,
                'roles' => [RoleName::Coach->value, RoleName::Athlete->value],
                'permissions' => ['dashboard.view', 'training.view', 'training.manage'],
            ])
            ->assertRedirect();

        $user->refresh()->load(['roles', 'permissions']);

        $this->assertSame('Updated User', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('+15550000999', $user->phone);
        $this->assertSame('New goal', $user->primary_goal);
        $this->assertSame('Assistant Coach', $user->position);
        $this->assertSame('phone', $user->preferred_contact_method);
        $this->assertSame(SignupMethod::Google->value, $user->registration_channel);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->phone_verified_at);
        $this->assertTrue($user->hasRole(RoleName::Coach));
        $this->assertTrue($user->hasRole(RoleName::Athlete));
        $this->assertSame(['dashboard.view', 'training.view', 'training.manage'], $user->permissionKeys());
    }
}
