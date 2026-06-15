<?php

namespace Database\Seeders;

use App\Enums\BillingInterval;
use App\Enums\CoachAthleteStatus;
use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\MembershipStatus;
use App\Enums\PaymentEventStatus;
use App\Enums\PaymentEventType;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Enums\WorkoutCompletionStatus;
use App\Models\AthleteCheckIn;
use App\Models\CoachAthleteAssignment;
use App\Models\DeviceConnection;
use App\Models\DeviceMetricIngest;
use App\Models\Membership;
use App\Models\MetricSnapshot;
use App\Models\PaymentEvent;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        collect(RoleName::cases())->each(fn (RoleName $role) => Role::query()->updateOrCreate(
            ['name' => $role->value],
            ['label' => $role->label()],
        ));

        $coachPlan = SubscriptionPlan::query()->updateOrCreate(
            ['slug' => 'coach-pro-monthly'],
            [
                'name' => 'Coach Pro Monthly',
                'description' => 'Monthly coach workspace subscription.',
                'billing_interval' => BillingInterval::Monthly,
                'duration_days' => 30,
                'price' => 79,
                'currency' => 'USD',
                'is_active' => true,
            ],
        );

        $athletePlan = SubscriptionPlan::query()->updateOrCreate(
            ['slug' => 'athlete-performance-monthly'],
            [
                'name' => 'Athlete Performance Monthly',
                'description' => 'Monthly athlete coaching membership.',
                'billing_interval' => BillingInterval::Monthly,
                'duration_days' => 30,
                'price' => 129,
                'currency' => 'USD',
                'is_active' => true,
            ],
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@throughline.test'],
            [
                'name' => 'Platform Admin',
                'phone' => '+15550000001',
                'password' => Hash::make('password'),
                'primary_goal' => 'Keep platform operations clean',
                'preferred_contact_method' => 'email',
                'registration_channel' => 'email',
            ],
        );
        $coachOne = User::query()->updateOrCreate(
            ['email' => 'coach@throughline.test'],
            [
                'name' => 'Maya Carter',
                'phone' => '+15550000011',
                'password' => Hash::make('password'),
                'primary_goal' => 'Scale a high-touch roster without losing quality',
                'preferred_contact_method' => 'email',
                'registration_channel' => 'email',
            ],
        );
        $coachTwo = User::query()->updateOrCreate(
            ['email' => 'coach2@throughline.test'],
            [
                'name' => 'Rami Sayegh',
                'phone' => '+15550000012',
                'password' => Hash::make('password'),
                'primary_goal' => 'Run clear return-to-performance programs',
                'preferred_contact_method' => 'phone',
                'registration_channel' => 'email',
            ],
        );
        $athleteOne = User::query()->updateOrCreate(
            ['email' => 'athlete1@throughline.test'],
            [
                'name' => 'Lina Brooks',
                'phone' => '+15550000021',
                'password' => Hash::make('password'),
                'primary_goal' => 'Improve HYROX race durability',
                'preferred_contact_method' => 'phone',
                'registration_channel' => 'email',
            ],
        );
        $athleteTwo = User::query()->updateOrCreate(
            ['email' => 'athlete2@throughline.test'],
            [
                'name' => 'Noah Patel',
                'phone' => '+15550000022',
                'password' => Hash::make('password'),
                'primary_goal' => 'Peak strength without trashing recovery',
                'preferred_contact_method' => 'email',
                'registration_channel' => 'email',
            ],
        );
        $athleteThree = User::query()->updateOrCreate(
            ['email' => 'athlete3@throughline.test'],
            [
                'name' => 'Zayd Hassan',
                'phone' => '+15550000023',
                'password' => Hash::make('password'),
                'primary_goal' => 'Rebuild tolerance and consistency',
                'preferred_contact_method' => 'phone',
                'registration_channel' => 'email',
            ],
        );

        $admin->assignRole(RoleName::Admin);
        $coachOne->assignRole(RoleName::Coach);
        $coachTwo->assignRole(RoleName::Coach);
        $athleteOne->assignRole(RoleName::Athlete);
        $athleteTwo->assignRole(RoleName::Athlete);
        $athleteThree->assignRole(RoleName::Athlete);

        Membership::query()->updateOrCreate(
            ['user_id' => $coachOne->id, 'subscription_plan_id' => $coachPlan->id],
            [
                'status' => MembershipStatus::Active,
                'starts_at' => now()->subDays(10)->toDateString(),
                'renews_at' => now()->addDays(20)->toDateString(),
                'ends_at' => now()->addDays(20)->toDateString(),
                'grace_ends_at' => null,
                'cancelled_at' => null,
                'auto_renew' => true,
                'price' => $coachPlan->price,
                'currency' => $coachPlan->currency,
                'notes' => 'Primary coach subscription',
            ],
        );

        Membership::query()->updateOrCreate(
            ['user_id' => $athleteOne->id, 'subscription_plan_id' => $athletePlan->id],
            [
                'status' => MembershipStatus::Active,
                'starts_at' => now()->subDays(18)->toDateString(),
                'renews_at' => now()->addDays(12)->toDateString(),
                'ends_at' => now()->addDays(12)->toDateString(),
                'grace_ends_at' => null,
                'cancelled_at' => null,
                'auto_renew' => true,
                'price' => $athletePlan->price,
                'currency' => $athletePlan->currency,
                'notes' => 'Healthy active client',
            ],
        );

        Membership::query()->updateOrCreate(
            ['user_id' => $athleteTwo->id, 'subscription_plan_id' => $athletePlan->id],
            [
                'status' => MembershipStatus::Grace,
                'starts_at' => now()->subDays(27)->toDateString(),
                'renews_at' => now()->subDay()->toDateString(),
                'ends_at' => now()->subDay()->toDateString(),
                'grace_ends_at' => now()->addDays(4)->toDateString(),
                'cancelled_at' => null,
                'auto_renew' => false,
                'price' => $athletePlan->price,
                'currency' => $athletePlan->currency,
                'notes' => 'Needs renewal attention',
            ],
        );

        Membership::query()->updateOrCreate(
            ['user_id' => $athleteThree->id, 'subscription_plan_id' => $athletePlan->id],
            [
                'status' => MembershipStatus::PastDue,
                'starts_at' => now()->subDays(31)->toDateString(),
                'renews_at' => now()->subDays(2)->toDateString(),
                'ends_at' => now()->addDays(2)->toDateString(),
                'grace_ends_at' => now()->addDays(5)->toDateString(),
                'cancelled_at' => null,
                'auto_renew' => false,
                'price' => $athletePlan->price,
                'currency' => $athletePlan->currency,
                'notes' => 'Billing retry in progress',
            ],
        );

        foreach (Membership::query()->with(['user', 'plan'])->get() as $membership) {
            PaymentEvent::query()->updateOrCreate(
                ['membership_id' => $membership->id, 'reference' => sprintf('seed-charge-%s', $membership->id)],
                [
                    'user_id' => $membership->user_id,
                    'created_by_user_id' => $admin->id,
                    'event_type' => PaymentEventType::Charge,
                    'status' => match ($membership->status) {
                        MembershipStatus::PastDue => PaymentEventStatus::Failed,
                        MembershipStatus::Grace => PaymentEventStatus::Pending,
                        default => PaymentEventStatus::Succeeded,
                    },
                    'provider' => 'manual',
                    'amount' => $membership->price,
                    'currency' => $membership->currency,
                    'event_at' => now()->subDays(match ($membership->status) {
                        MembershipStatus::PastDue => 2,
                        MembershipStatus::Grace => 1,
                        default => 12,
                    }),
                    'notes' => match ($membership->status) {
                        MembershipStatus::PastDue => 'Card retry failed and needs manual follow-up.',
                        MembershipStatus::Grace => 'Renewal invoice sent and waiting on recovery.',
                        default => 'Successful recurring charge recorded for seeded demo data.',
                    },
                    'metadata' => [
                        'plan' => $membership->plan?->name,
                        'seeded' => true,
                    ],
                ],
            );
        }

        CoachAthleteAssignment::query()->updateOrCreate(
            ['coach_id' => $coachOne->id, 'athlete_id' => $athleteOne->id],
            [
                'status' => CoachAthleteStatus::Active,
                'goal' => 'HYROX prep',
                'notes' => 'Monitor workload response across the next training block.',
                'started_at' => now()->subDays(60)->toDateString(),
                'ended_at' => null,
            ],
        );

        CoachAthleteAssignment::query()->updateOrCreate(
            ['coach_id' => $coachOne->id, 'athlete_id' => $athleteTwo->id],
            [
                'status' => CoachAthleteStatus::Active,
                'goal' => 'Powerlifting peak',
                'notes' => 'Billing risk but strong compliance.',
                'started_at' => now()->subDays(40)->toDateString(),
                'ended_at' => null,
            ],
        );

        CoachAthleteAssignment::query()->updateOrCreate(
            ['coach_id' => $coachTwo->id, 'athlete_id' => $athleteThree->id],
            [
                'status' => CoachAthleteStatus::Active,
                'goal' => 'Return-to-strength block',
                'notes' => 'Back on-ramp after time away from training.',
                'started_at' => now()->subDays(22)->toDateString(),
                'ended_at' => null,
            ],
        );

        $hyroxProgram = TrainingProgram::query()->updateOrCreate(
            ['coach_id' => $coachOne->id, 'athlete_id' => $athleteOne->id, 'title' => 'HYROX Build Block'],
            [
                'goal' => 'Build race pace repeatability',
                'status' => TrainingProgramStatus::Active,
                'start_date' => now()->subDays(7)->toDateString(),
                'end_date' => now()->addDays(21)->toDateString(),
                'notes' => 'Push threshold work only if readiness stays above 75.',
            ],
        );

        $hyroxTempoSession = TrainingSession::query()->updateOrCreate(
            ['training_program_id' => $hyroxProgram->id, 'title' => 'Tempo engine'],
            [
                'scheduled_date' => now()->subDay()->toDateString(),
                'focus' => 'Threshold pacing',
                'instructions' => 'Start controlled. If the final rep falls apart, the pace was stupid.',
                'exercises' => [
                    ['name' => 'Run intervals', 'prescription' => '5 x 1 km @ threshold', 'note' => 'Walk 90s between reps'],
                    ['name' => 'SkiErg flush', 'prescription' => '10 min easy', 'note' => 'Keep breathing calm'],
                ],
                'sort_order' => 1,
            ],
        );

        TrainingSession::query()->updateOrCreate(
            ['training_program_id' => $hyroxProgram->id, 'title' => 'Sled threshold circuit'],
            [
                'scheduled_date' => now()->addDays(2)->toDateString(),
                'focus' => 'Work capacity',
                'instructions' => 'Stay smooth through transitions. No redlining before round three.',
                'exercises' => [
                    ['name' => 'Sled push', 'prescription' => '6 x 20 m', 'note' => 'Moderate-heavy load'],
                    ['name' => 'Burpee broad jumps', 'prescription' => '4 x 12', 'note' => 'Consistent rhythm'],
                ],
                'sort_order' => 2,
            ],
        );

        WorkoutLog::query()->updateOrCreate(
            ['training_session_id' => $hyroxTempoSession->id, 'athlete_id' => $athleteOne->id],
            [
                'completion_status' => WorkoutCompletionStatus::Completed,
                'performed_at' => now()->subDay()->setTime(18, 15),
                'duration_minutes' => 64,
                'exertion_rating' => 7,
                'notes' => 'Held pace cleanly. Last rep was hard but not messy.',
            ],
        );

        $powerProgram = TrainingProgram::query()->updateOrCreate(
            ['coach_id' => $coachOne->id, 'athlete_id' => $athleteTwo->id, 'title' => 'Power Peak Bridge'],
            [
                'goal' => 'Carry strength without frying recovery',
                'status' => TrainingProgramStatus::Active,
                'start_date' => now()->subDays(5)->toDateString(),
                'end_date' => now()->addDays(18)->toDateString(),
                'notes' => 'Athlete is a billing risk, not a compliance risk. Keep the work sharp and short.',
            ],
        );

        $deadliftSession = TrainingSession::query()->updateOrCreate(
            ['training_program_id' => $powerProgram->id, 'title' => 'Heavy pull day'],
            [
                'scheduled_date' => now()->subDays(2)->toDateString(),
                'focus' => 'Top-end strength',
                'instructions' => 'No grinders. Stop the set when bar speed lies to you.',
                'exercises' => [
                    ['name' => 'Deadlift', 'prescription' => '5 x 3 @ 85%', 'note' => 'Rest 3 min'],
                    ['name' => 'Paused bench', 'prescription' => '4 x 4 @ 75%', 'note' => '1-second pause'],
                ],
                'sort_order' => 1,
            ],
        );

        TrainingSession::query()->updateOrCreate(
            ['training_program_id' => $powerProgram->id, 'title' => 'Dynamic lower'],
            [
                'scheduled_date' => now()->addDays(1)->toDateString(),
                'focus' => 'Speed-strength',
                'instructions' => 'Treat every rep like it owes you speed.',
                'exercises' => [
                    ['name' => 'Box squat', 'prescription' => '8 x 2 @ 60%', 'note' => 'Explode up'],
                    ['name' => 'Reverse lunge', 'prescription' => '3 x 8 / leg', 'note' => 'Controlled eccentric'],
                ],
                'sort_order' => 2,
            ],
        );

        WorkoutLog::query()->updateOrCreate(
            ['training_session_id' => $deadliftSession->id, 'athlete_id' => $athleteTwo->id],
            [
                'completion_status' => WorkoutCompletionStatus::Partial,
                'performed_at' => now()->subDays(2)->setTime(17, 30),
                'duration_minutes' => 52,
                'exertion_rating' => 8,
                'notes' => 'Cut accessory volume after low back tightened up.',
            ],
        );

        $returnProgram = TrainingProgram::query()->updateOrCreate(
            ['coach_id' => $coachTwo->id, 'athlete_id' => $athleteThree->id, 'title' => 'Return To Strength Ramp'],
            [
                'goal' => 'Rebuild tolerance and consistency',
                'status' => TrainingProgramStatus::Draft,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(28)->toDateString(),
                'notes' => 'Keep load conservative until wearable recovery stabilizes.',
            ],
        );

        TrainingSession::query()->updateOrCreate(
            ['training_program_id' => $returnProgram->id, 'title' => 'Base re-entry session'],
            [
                'scheduled_date' => now()->addDays(3)->toDateString(),
                'focus' => 'Movement quality',
                'instructions' => 'Nothing maximal. This block is about rebuilding rhythm, not pretending time away never happened.',
                'exercises' => [
                    ['name' => 'Goblet squat', 'prescription' => '4 x 8 · load 24 kg · rest 90s · RPE 6', 'sets' => 4, 'reps' => '8', 'load' => '24 kg', 'rest_seconds' => 90, 'rest_label' => '90s', 'target' => 'RPE 6', 'note' => 'Leave 3 reps in reserve'],
                    ['name' => 'Bike', 'prescription' => '1 x 25 min zone 2 · rest 0s · nasal breathing', 'sets' => 1, 'reps' => '25 min zone 2', 'load' => 'Easy spin', 'rest_seconds' => 0, 'rest_label' => '0s', 'target' => 'Nasal breathing', 'note' => 'Steady nasal breathing'],
                ],
                'sort_order' => 1,
            ],
        );

        $garminConnection = DeviceConnection::query()->updateOrCreate(
            ['user_id' => $athleteOne->id, 'provider' => DeviceProvider::Garmin],
            [
                'status' => DeviceConnectionStatus::Connected,
                'external_user_id' => 'garmin-athlete-001',
                'granted_scopes' => ['sleep', 'hrv', 'activity'],
                'last_synced_at' => now()->subHours(2),
            ],
        );

        $whoopConnection = DeviceConnection::query()->updateOrCreate(
            ['user_id' => $athleteTwo->id, 'provider' => DeviceProvider::Whoop],
            [
                'status' => DeviceConnectionStatus::Attention,
                'auth_type' => 'oauth',
                'external_user_id' => 'whoop-athlete-002',
                'granted_scopes' => ['offline', 'read:profile', 'read:recovery', 'read:sleep', 'read:cycles', 'read:workout'],
                'provider_account_payload' => [
                    'profile' => [
                        'user_id' => 'whoop-athlete-002',
                        'first_name' => 'Noah',
                        'last_name' => 'Patel',
                    ],
                ],
                'last_synced_at' => now()->subDays(2),
            ],
        );

        $stravaConnection = DeviceConnection::query()->updateOrCreate(
            ['user_id' => $athleteThree->id, 'provider' => DeviceProvider::Strava],
            [
                'status' => DeviceConnectionStatus::Connected,
                'external_user_id' => 'strava-athlete-003',
                'granted_scopes' => ['activity'],
                'last_synced_at' => now()->subHours(8),
            ],
        );

        DeviceMetricIngest::query()->updateOrCreate(
            [
                'device_connection_id' => $garminConnection->id,
                'external_event_id' => 'garmin-ingest-2026-06-09',
            ],
            [
                'metric_date' => now()->subDay()->toDateString(),
                'payload' => [
                    'metric_date' => now()->subDay()->toDateString(),
                    'metrics' => [
                        'readiness_score' => 84,
                        'sleep_minutes' => 452,
                        'strain_score' => 11.8,
                        'resting_heart_rate' => 48,
                        'heart_rate_variability' => 72.5,
                        'steps' => 12840,
                        'training_load' => 438.2,
                    ],
                ],
                'processing_status' => 'processed',
                'received_at' => now()->subHours(2),
                'processed_at' => now()->subHours(2),
            ],
        );

        $this->upsertMetricSnapshot($garminConnection->id, now()->subDay()->toDateString(), [
            'user_id' => $athleteOne->id,
            'provider' => DeviceProvider::Garmin,
            'readiness_score' => 84,
            'strain_score' => 11.8,
            'sleep_minutes' => 452,
            'steps' => 12840,
            'distance_meters' => 9200,
            'calories_burned' => 2180,
            'active_minutes' => 76,
            'resting_heart_rate' => 48,
            'heart_rate_variability' => 72.5,
            'training_load' => 438.2,
        ]);

        DeviceMetricIngest::query()->updateOrCreate(
            [
                'device_connection_id' => $whoopConnection->id,
                'external_event_id' => 'whoop-ingest-2026-06-07',
            ],
            [
                'metric_date' => now()->subDays(3)->toDateString(),
                'payload' => [
                    'metric_date' => now()->subDays(3)->toDateString(),
                    'metrics' => [
                        'readiness_score' => 61,
                        'sleep_minutes' => 361,
                        'sleep_need_minutes' => 470,
                        'sleep_performance_percentage' => 77,
                        'sleep_consistency_percentage' => 71,
                        'sleep_efficiency_percentage' => 88,
                        'rem_sleep_minutes' => 62,
                        'slow_wave_sleep_minutes' => 54,
                        'strain_score' => 16.4,
                        'resting_heart_rate' => 56,
                        'heart_rate_variability' => 44.9,
                        'respiratory_rate' => 16.8,
                        'blood_oxygen_percent' => 95.4,
                        'skin_temperature_celsius' => 33.9,
                        'training_load' => 519.5,
                    ],
                ],
                'processing_status' => 'processed',
                'received_at' => now()->subDays(2),
                'processed_at' => now()->subDays(2),
            ],
        );

        $this->upsertMetricSnapshot($whoopConnection->id, now()->subDays(3)->toDateString(), [
            'user_id' => $athleteTwo->id,
            'provider' => DeviceProvider::Whoop,
            'readiness_score' => 61,
            'strain_score' => 16.4,
            'sleep_minutes' => 361,
            'sleep_need_minutes' => 470,
            'sleep_performance_percentage' => 77,
            'sleep_consistency_percentage' => 71,
            'sleep_efficiency_percentage' => 88,
            'rem_sleep_minutes' => 62,
            'slow_wave_sleep_minutes' => 54,
            'steps' => 7340,
            'distance_meters' => 5200,
            'calories_burned' => 1840,
            'active_minutes' => 49,
            'resting_heart_rate' => 56,
            'heart_rate_variability' => 44.9,
            'respiratory_rate' => 16.8,
            'blood_oxygen_percent' => 95.4,
            'skin_temperature_celsius' => 33.9,
            'training_load' => 519.5,
        ]);

        DeviceMetricIngest::query()->updateOrCreate(
            [
                'device_connection_id' => $stravaConnection->id,
                'external_event_id' => 'strava-ingest-2026-06-09',
            ],
            [
                'metric_date' => now()->subDay()->toDateString(),
                'payload' => [
                    'metric_date' => now()->subDay()->toDateString(),
                    'metrics' => [
                        'readiness_score' => 76,
                        'sleep_minutes' => 415,
                        'strain_score' => 13.1,
                        'steps' => 16420,
                        'distance_meters' => 12480,
                        'training_load' => 472.7,
                    ],
                ],
                'processing_status' => 'processed',
                'received_at' => now()->subHours(8),
                'processed_at' => now()->subHours(8),
            ],
        );

        $this->upsertMetricSnapshot($stravaConnection->id, now()->subDay()->toDateString(), [
            'user_id' => $athleteThree->id,
            'provider' => DeviceProvider::Strava,
            'readiness_score' => 76,
            'strain_score' => 13.1,
            'sleep_minutes' => 415,
            'steps' => 16420,
            'distance_meters' => 12480,
            'calories_burned' => 2415,
            'active_minutes' => 91,
            'resting_heart_rate' => 51,
            'heart_rate_variability' => 58.3,
            'training_load' => 472.7,
        ]);

        foreach (range(0, 6) as $offset) {
            $date = now()->subDays(6 - $offset)->toDateString();

            $this->upsertAthleteCheckIn($athleteOne->id, $date, [
                'weight_kg' => 68.4 - ($offset * 0.1),
                'body_fat_percentage' => 17.8 - ($offset * 0.05),
                'waist_cm' => 74.0 - ($offset * 0.08),
                'calories_consumed' => 2480 + ($offset % 3) * 90,
                'protein_grams' => 164 + ($offset % 4) * 4,
                'carbs_grams' => 286 + ($offset * 6),
                'fat_grams' => 72 + ($offset % 2) * 4,
                'water_liters' => 3.0 + (($offset % 3) * 0.2),
                'meals_logged_count' => 4,
                'energy_score' => 7 + ($offset % 2),
                'soreness_score' => 4 + ($offset % 3),
                'stress_score' => 3 + ($offset % 2),
                'sleep_quality_score' => 7 + ($offset % 2),
                'notes' => match ($offset) {
                    2 => 'Legs were heavy but food stayed on target.',
                    5 => 'Race-pace work felt cleaner once hydration was sorted.',
                    default => 'Solid training support day with no major issues.',
                },
            ]);

            $this->upsertAthleteCheckIn($athleteTwo->id, $date, [
                'weight_kg' => 92.6 - ($offset * 0.12),
                'body_fat_percentage' => 20.2 - ($offset * 0.04),
                'waist_cm' => 86.0 - ($offset * 0.05),
                'calories_consumed' => 3190 + (($offset + 1) % 3) * 110,
                'protein_grams' => 196 + ($offset % 3) * 6,
                'carbs_grams' => 312 + ($offset * 8),
                'fat_grams' => 92 + ($offset % 2) * 6,
                'water_liters' => 2.4 + (($offset % 3) * 0.2),
                'meals_logged_count' => 4,
                'energy_score' => $offset >= 4 ? 4 : 5,
                'soreness_score' => 6 + ($offset % 3),
                'stress_score' => 5 + ($offset % 2),
                'sleep_quality_score' => 4 + ($offset % 3),
                'notes' => match ($offset) {
                    1 => 'Lower back tightness showed up after the heavy pull session.',
                    4 => 'Calories were fine, but energy still felt flat after short sleep.',
                    default => 'Holding output, but recovery support is not exactly elegant.',
                },
            ]);

            $this->upsertAthleteCheckIn($athleteThree->id, $date, [
                'weight_kg' => 79.3 + ($offset * 0.14),
                'body_fat_percentage' => 18.4 + ($offset * 0.02),
                'waist_cm' => 81.2 + ($offset * 0.03),
                'calories_consumed' => 2590 + (($offset + 2) % 3) * 120,
                'protein_grams' => 158 + ($offset % 4) * 5,
                'carbs_grams' => 274 + ($offset * 7),
                'fat_grams' => 78 + ($offset % 2) * 5,
                'water_liters' => 2.8 + (($offset % 2) * 0.3),
                'meals_logged_count' => 4,
                'energy_score' => 5 + ($offset % 3),
                'soreness_score' => 3 + ($offset % 2),
                'stress_score' => 4 + ($offset % 2),
                'sleep_quality_score' => 6 + ($offset % 2),
                'notes' => match ($offset) {
                    0 => 'Appetite came back. Good sign after the slow restart.',
                    6 => 'Bodyweight is climbing back the right way without feeling sloppy.',
                    default => 'Rebuild week. Nothing heroic, just consistent.',
                },
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertMetricSnapshot(int $deviceConnectionId, string $metricDate, array $attributes): void
    {
        $snapshot = MetricSnapshot::query()
            ->where('device_connection_id', $deviceConnectionId)
            ->whereDate('metric_date', $metricDate)
            ->first();

        if (! $snapshot) {
            MetricSnapshot::query()->create(array_merge($attributes, [
                'device_connection_id' => $deviceConnectionId,
                'metric_date' => $metricDate,
            ]));

            return;
        }

        $snapshot->fill($attributes);
        $snapshot->metric_date = $metricDate;
        $snapshot->save();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertAthleteCheckIn(int $userId, string $loggedDate, array $attributes): void
    {
        AthleteCheckIn::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'logged_date' => $loggedDate,
            ],
            $attributes,
        );
    }
}
