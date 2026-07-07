<?php

namespace Database\Seeders;

use App\Models\CoachAthleteAssignment;
use App\Models\PlatformSetting;
use App\Models\ProgressEntry;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'app_name' => 'Throughline',
            'tagline' => 'Coach performance OS',
            'support_email' => 'admin@throughline.test',
            'invite_expiry_days' => '7',
            'homepage_headline' => 'Training, coaching, and progress tracking without the mess.',
            'homepage_subheadline' => 'A direct platform for coaches to manage athletes, assign programs, and track real execution.',
            'invite_email_subject' => 'Your Throughline athlete invitation',
            'invite_email_body' => "Coach {coach_name} invited you to {app_name}.\n\nAccept here: {invite_link}\n\nThis invite expires on {expires_at}.",
        ])->each(function (string $value, string $key): void {
            PlatformSetting::put($key, $value, str_starts_with($key, 'invite_') ? 'invitations' : 'site');
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

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $program = TrainingProgram::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Strength Foundation',
            'goal' => 'Build repeatable strength habits',
            'status' => 'active',
            'starts_on' => now()->startOfWeek()->toDateString(),
            'ends_on' => now()->addWeeks(4)->toDateString(),
            'notes' => 'Simple base phase with clear sets and logging.',
        ]);

        foreach (range(0, 9) as $index) {
            TrainingSession::query()->create([
                'training_program_id' => $program->id,
                'title' => $index % 2 === 0 ? 'Lower Strength' : 'Upper Strength',
                'focus' => $index % 2 === 0 ? 'Squat and hinge' : 'Press and pull',
                'scheduled_on' => now()->startOfWeek()->addDays($index)->toDateString(),
                'status' => 'scheduled',
                'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'coach_notes' => 'Log honest reps and stop two reps before form breaks.',
                'exercises' => [
                    ['name' => 'Trap Bar Deadlift', 'sets' => 4, 'reps' => '5', 'rest' => '90 sec', 'load' => 'moderate', 'note' => 'Smooth reps'],
                    ['name' => 'Goblet Squat', 'sets' => 3, 'reps' => '8', 'rest' => '75 sec', 'load' => 'light', 'note' => 'Control depth'],
                    ['name' => 'Core Carry', 'sets' => 3, 'reps' => '30m', 'rest' => '60 sec', 'load' => 'steady', 'note' => 'Tall posture'],
                ],
            ]);
        }

        foreach (range(0, 14) as $index) {
            ProgressEntry::query()->updateOrCreate(
                ['athlete_id' => $athlete->id, 'logged_on' => now()->subDays($index)->toDateString()],
                [
                    'weight' => 82.5 - ($index * 0.05),
                    'calories' => 2400 + ($index * 20),
                    'protein' => 150 + ($index % 5),
                    'hydration' => 2800 + ($index * 30),
                    'sleep_quality' => 7,
                    'soreness' => 3,
                    'energy' => 8,
                    'notes' => 'Seeded MVP progress entry.',
                ]
            );
        }
    }
}
