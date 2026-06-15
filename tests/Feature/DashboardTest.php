<?php

namespace Tests\Feature;

use App\Enums\BillingInterval;
use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\MembershipStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Enums\WorkoutCompletionStatus;
use App\Models\AthleteCheckIn;
use App\Models\CoachAthleteAssignment;
use App\Models\DeviceConnection;
use App\Models\Membership;
use App\Models\MetricSnapshot;
use App\Models\SubscriptionPlan;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAs($user = User::factory()->create());
        $user->assignRole(RoleName::Athlete);

        $this->get('/dashboard')->assertOk();
    }

    public function test_admin_users_receive_platform_metrics()
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
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('viewer.primaryRole', RoleName::Admin->value)
                ->where('admin.metrics.totalUsers', 3)
                ->where('admin.metrics.totalCoaches', 1)
                ->where('admin.metrics.totalAthletes', 1)
                ->where('admin.metrics.activeMemberships', 1)
                ->where('admin.signupMix.0.method', 'email')
                ->where('admin.signupMix.0.count', 3)
            );
    }

    public function test_coach_users_receive_roster_data()
    {
        $coach = User::factory()->create();
        $athlete = User::factory()->create();
        $plan = SubscriptionPlan::query()->create([
            'slug' => 'coach-athlete-monthly',
            'name' => 'Athlete Monthly',
            'description' => null,
            'billing_interval' => BillingInterval::Monthly,
            'duration_days' => 30,
            'price' => 129,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        Membership::query()->create([
            'user_id' => $athlete->id,
            'subscription_plan_id' => $plan->id,
            'status' => MembershipStatus::Grace,
            'starts_at' => now()->subDays(28)->toDateString(),
            'renews_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->subDay()->toDateString(),
            'grace_ends_at' => now()->addDays(3)->toDateString(),
            'cancelled_at' => null,
            'auto_renew' => false,
            'price' => 129,
            'currency' => 'USD',
            'notes' => null,
        ]);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Powerlifting block',
            'notes' => null,
            'started_at' => now()->subDays(10)->toDateString(),
            'ended_at' => null,
        ]);

        $program = TrainingProgram::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Power Build',
            'goal' => 'Peak cleanly',
            'status' => TrainingProgramStatus::Active,
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'notes' => null,
        ]);

        TrainingSession::query()->create([
            'training_program_id' => $program->id,
            'title' => 'Heavy lower',
            'scheduled_date' => now()->subDay()->toDateString(),
            'focus' => 'Strength',
            'instructions' => 'No missed reps.',
            'exercises' => [],
            'sort_order' => 1,
        ]);

        AthleteCheckIn::query()->create([
            'user_id' => $athlete->id,
            'logged_date' => now()->subDay()->toDateString(),
            'weight_kg' => 92.4,
            'calories_consumed' => 3180,
            'protein_grams' => 205,
            'water_liters' => 2.8,
            'energy_score' => 4,
            'soreness_score' => 7,
            'stress_score' => 5,
            'sleep_quality_score' => 5,
            'notes' => 'Pulled back slightly after a rough recovery day.',
        ]);

        $this->actingAs($coach)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('viewer.primaryRole', RoleName::Coach->value)
                ->where('coach.metrics.rosterCount', 1)
                ->where('coach.metrics.athletesNeedingAttention', 1)
                ->where('coach.metrics.activePrograms', 1)
                ->where('coach.metrics.pendingWorkoutLogs', 1)
                ->where('coach.roster.0.name', $athlete->name)
                ->where('coach.roster.0.latestCheckIn.weightKg', 92.4)
                ->where('coach.roster.0.currentProgram.title', 'Power Build')
            );
    }

    public function test_athlete_users_receive_training_and_recovery_details()
    {
        $coach = User::factory()->create();
        $athlete = User::factory()->create([
            'primary_goal' => 'Race build',
        ]);
        $plan = SubscriptionPlan::query()->create([
            'slug' => 'athlete-dash-monthly',
            'name' => 'Athlete Monthly',
            'description' => null,
            'billing_interval' => BillingInterval::Monthly,
            'duration_days' => 30,
            'price' => 129,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        Membership::query()->create([
            'user_id' => $athlete->id,
            'subscription_plan_id' => $plan->id,
            'status' => MembershipStatus::Active,
            'starts_at' => now()->subDays(6)->toDateString(),
            'renews_at' => now()->addDays(24)->toDateString(),
            'ends_at' => now()->addDays(24)->toDateString(),
            'grace_ends_at' => null,
            'cancelled_at' => null,
            'auto_renew' => true,
            'price' => 129,
            'currency' => 'USD',
            'notes' => null,
        ]);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Race build',
            'notes' => null,
            'started_at' => now()->subDays(14)->toDateString(),
            'ended_at' => null,
        ]);

        $program = TrainingProgram::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Race Preparation',
            'goal' => 'Hold pace under fatigue',
            'status' => TrainingProgramStatus::Active,
            'start_date' => now()->subDays(4)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'notes' => null,
        ]);

        $session = TrainingSession::query()->create([
            'training_program_id' => $program->id,
            'title' => 'Tempo intervals',
            'scheduled_date' => now()->addDay()->toDateString(),
            'focus' => 'Threshold',
            'instructions' => 'Stay controlled.',
            'exercises' => [],
            'sort_order' => 1,
        ]);

        WorkoutLog::query()->create([
            'training_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'completion_status' => WorkoutCompletionStatus::Completed,
            'performed_at' => now()->subDay(),
            'duration_minutes' => 48,
            'exertion_rating' => 7,
            'notes' => null,
        ]);

        $connection = DeviceConnection::query()->create([
            'user_id' => $athlete->id,
            'provider' => DeviceProvider::Garmin,
            'status' => DeviceConnectionStatus::Connected,
            'external_user_id' => 'garmin-athlete-dash',
            'granted_scopes' => ['sleep', 'activity'],
            'last_synced_at' => now()->subHour(),
        ]);

        MetricSnapshot::query()->create([
            'user_id' => $athlete->id,
            'device_connection_id' => $connection->id,
            'provider' => DeviceProvider::Garmin,
            'metric_date' => now()->toDateString(),
            'readiness_score' => 82,
            'strain_score' => 12.4,
            'sleep_minutes' => 438,
            'steps' => 11200,
            'distance_meters' => 8400,
            'calories_burned' => 2100,
            'active_minutes' => 74,
            'resting_heart_rate' => 49,
            'heart_rate_variability' => 68.1,
            'training_load' => 421.8,
        ]);

        AthleteCheckIn::query()->create([
            'user_id' => $athlete->id,
            'logged_date' => now()->toDateString(),
            'weight_kg' => 67.8,
            'body_fat_percentage' => 17.2,
            'waist_cm' => 73.4,
            'calories_consumed' => 2560,
            'protein_grams' => 168,
            'carbs_grams' => 302,
            'fat_grams' => 74,
            'water_liters' => 3.2,
            'meals_logged_count' => 4,
            'energy_score' => 8,
            'soreness_score' => 5,
            'stress_score' => 3,
            'sleep_quality_score' => 8,
            'notes' => 'Bodyweight stable and the threshold session felt controlled.',
        ]);

        $this->actingAs($athlete)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('viewer.primaryRole', RoleName::Athlete->value)
                ->where('athlete.metrics.coachCount', 1)
                ->where('athlete.metrics.connectedDevices', 1)
                ->where('athlete.metrics.latestWeightKg', 67.8)
                ->where('athlete.metrics.averageProteinGrams', 168)
                ->where('athlete.training.title', 'Race Preparation')
                ->where('athlete.training.coachName', $coach->name)
                ->where('athlete.latestSnapshot.readinessScore', 82)
                ->where('athlete.latestCheckIn.weightKg', 67.8)
            );
    }
}
