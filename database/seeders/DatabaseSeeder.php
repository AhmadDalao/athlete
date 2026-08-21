<?php

namespace Database\Seeders;

use App\Models\AthleteProfile;
use App\Models\CoachAthleteAssignment;
use App\Models\CoachProfile;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo accounts must never be seeded in production.');
        }

        collect([
            'app_name' => 'Throughline',
            'tagline' => 'Coach performance OS',
            'support_email' => 'admin@throughline.test',
            'invite_expiry_days' => '7',
            'homepage_headline' => 'Training, coaching, and progress tracking without the mess.',
            'homepage_subheadline' => 'A direct platform for coaches to manage athletes, assign programs, and track real execution.',
            'contact_headline' => 'Tell us what you need.',
            'contact_subheadline' => 'Questions, coaching setup, or platform access. This saves directly into the admin database.',
            'contact_button_label' => 'Send message',
            'pricing_headline' => 'Simple plans for real coaching.',
            'pricing_subheadline' => 'Start with the workflow you need now. Payments and automation can come after the coaching system is stable.',
            'plan_one_name' => 'Athlete',
            'plan_one_price' => 'Contact for pricing',
            'plan_one_description' => 'For one athlete working directly with a coach.',
            'plan_one_features' => "Assigned coach\nWorkout calendar\nProgress check-ins\nSession completion logs",
            'plan_two_name' => 'Coach',
            'plan_two_price' => 'Contact for pricing',
            'plan_two_description' => 'For coaches managing their own roster.',
            'plan_two_features' => "Athlete invitations\nPrograms and sessions\nRoster tracking\nCoach-scoped athlete profiles",
            'plan_three_name' => 'Team',
            'plan_three_price' => 'Custom',
            'plan_three_description' => 'For businesses that need admin control and multiple coaches.',
            'plan_three_features' => "Admin dashboard\nPermissions and settings\nAudit and email logs\nCSV exports",
            'invite_email_subject' => 'Your Throughline athlete invitation',
            'invite_email_body' => "Coach {coach_name} invited you to {app_name}.\n\nAccept here: {invite_link}\n\nThis invite expires on {expires_at}.",
        ])->each(function (string $value, string $key): void {
            $group = match (true) {
                str_starts_with($key, 'invite_') => 'invitations',
                str_starts_with($key, 'contact_') => 'contact',
                str_starts_with($key, 'pricing_'), str_starts_with($key, 'plan_') => 'pricing',
                default => 'site',
            };

            PlatformSetting::put($key, $value, $group);
        });

        $owner = User::query()->create([
            'name' => 'Ahmad Dalao',
            'email' => 'owner@throughline.test',
            'phone' => '+15550000001',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'status' => 'active',
            'email_verified_at' => now(),
            'primary_goal' => 'Run the Throughline business.',
        ]);
        $owner->syncDefaultPermissions();

        $admin = User::query()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@throughline.test',
            'phone' => '+15550000002',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
            'primary_goal' => 'Manage operations.',
        ]);
        $admin->syncDefaultPermissions();

        $coach = User::query()->create([
            'name' => 'Demo Coach',
            'email' => 'coach@throughline.test',
            'phone' => '+15550000003',
            'password' => Hash::make('password'),
            'role' => 'coach',
            'status' => 'active',
            'email_verified_at' => now(),
            'primary_goal' => 'Build better athlete plans.',
        ]);
        $coach->syncDefaultPermissions();

        $athlete = User::query()->create([
            'name' => 'Demo Athlete',
            'email' => 'athlete@throughline.test',
            'phone' => '+15550000004',
            'password' => Hash::make('password'),
            'role' => 'athlete',
            'status' => 'active',
            'email_verified_at' => now(),
            'primary_goal' => 'Get stronger and more consistent.',
        ]);
        $athlete->syncDefaultPermissions();

        $organization = Organization::query()->firstOrCreate(
            ['slug' => 'throughline'],
            [
                'name' => 'Throughline',
                'status' => 'active',
                'timezone' => 'Asia/Riyadh',
                'default_theme' => 'system',
                'plan_key' => 'team',
            ]
        );
        $organization->forceFill(['owner_id' => $owner->id])->save();

        collect([
            [$owner, 'organization_owner'],
            [$admin, 'organization_admin'],
            [$coach, 'coach'],
            [$athlete, 'athlete'],
        ])->each(function (array $member) use ($organization): void {
            [$user, $role] = $member;

            OrganizationMembership::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'user_id' => $user->id],
                ['role' => $role, 'status' => 'active', 'joined_at' => now()]
            );
            $user->forceFill(['current_organization_id' => $organization->id])->save();
        });

        CoachProfile::query()->firstOrCreate([
            'organization_id' => $organization->id,
            'user_id' => $coach->id,
        ]);
        AthleteProfile::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'user_id' => $athlete->id],
            ['timezone' => 'Asia/Riyadh']
        );

        CoachAthleteAssignment::query()->create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $this->call(DemoTrainingSeeder::class);
    }
}
