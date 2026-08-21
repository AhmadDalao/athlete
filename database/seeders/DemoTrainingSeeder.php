<?php

namespace Database\Seeders;

use App\Models\AthleteProfile;
use App\Models\CoachAthleteAssignment;
use App\Models\CoachProfile;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\ProgramAssignment;
use App\Models\ProgressEntry;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\ProgramScheduleService;
use App\Services\WorkoutExecutionService;
use App\Support\OrganizationContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoTrainingSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Three-month demo data must never be seeded in production.');
        }

        $organization = Organization::query()->where('slug', 'throughline')->firstOrFail();
        app(OrganizationContext::class)->set($organization);

        try {
            $coach = User::query()->where('email', 'coach@throughline.test')->firstOrFail();
            $athlete = User::query()->where('email', 'athlete@throughline.test')->firstOrFail();
            $performanceCoach = $this->demoUser(
                'Noura Kareem',
                'noura.coach@throughline.test',
                'coach',
                'Build speed without sacrificing resilience.',
            );
            $sprinter = $this->demoUser(
                'Maya Hassan',
                'maya.athlete@throughline.test',
                'athlete',
                'Improve 30 m acceleration and stay healthy.',
            );
            $runner = $this->demoUser(
                'Omar Nasser',
                'omar.athlete@throughline.test',
                'athlete',
                'Complete a strong, pain-free 10 km.',
            );

            $this->join($organization, $performanceCoach, 'coach');
            $this->join($organization, $sprinter, 'athlete');
            $this->join($organization, $runner, 'athlete');

            CoachProfile::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'user_id' => $coach->id],
                [
                    'title' => 'Head Strength Coach',
                    'specialties' => ['strength', 'general preparation', 'return to performance'],
                    'certifications' => 'CSCS',
                    'years_experience' => 9,
                ],
            );
            CoachProfile::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'user_id' => $performanceCoach->id],
                [
                    'title' => 'Speed & Conditioning Coach',
                    'specialties' => ['sprint mechanics', 'conditioning', 'mobility'],
                    'certifications' => 'UKA Athletics Coach',
                    'years_experience' => 7,
                ],
            );

            $this->athleteProfile($organization, $athlete, 'General performance', 'Hybrid athlete', 182);
            $this->athleteProfile($organization, $sprinter, 'Track and field', '100 m', 171);
            $this->athleteProfile($organization, $runner, 'Road running', '10 km', 178);

            $this->assignCoach($organization, $coach, $athlete);
            $this->assignCoach($organization, $coach, $runner);
            $this->assignCoach($organization, $performanceCoach, $sprinter);
            $this->assignCoach($organization, $coach, $sprinter);

            $start = Carbon::now($organization->timezone)->startOfWeek()->startOfDay();
            $schedule = app(ProgramScheduleService::class);
            $demoAssignments = collect($this->demoAthletePlans())->map(
                fn (array $plan): ProgramAssignment => $this->createProgram(
                    $organization,
                    $coach,
                    $athlete,
                    $start,
                    $plan,
                    $schedule,
                ),
            );
            $this->completeFirstDueWorkout($demoAssignments->first());

            $this->createProgram(
                $organization,
                $performanceCoach,
                $sprinter,
                $start,
                $this->speedPlan(),
                $schedule,
            );
            $this->createProgram(
                $organization,
                $coach,
                $runner,
                $start,
                $this->runningPlan(),
                $schedule,
            );

            $this->seedProgress($organization, $athlete, 82.4, 45);
            $this->seedProgress($organization, $sprinter, 61.8, 24);
            $this->seedProgress($organization, $runner, 76.2, 30);
        } finally {
            app(OrganizationContext::class)->clear();
        }
    }

    private function demoUser(string $name, string $email, string $role, string $goal): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => $role,
                'status' => 'active',
                'email_verified_at' => now(),
                'primary_goal' => $goal,
            ],
        );
        $user->syncDefaultPermissions();

        return $user;
    }

    private function join(Organization $organization, User $user, string $role): void
    {
        OrganizationMembership::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'user_id' => $user->id],
            ['role' => $role, 'status' => 'active', 'joined_at' => now()],
        );
        $user->forceFill(['current_organization_id' => $organization->id])->saveQuietly();
    }

    private function athleteProfile(
        Organization $organization,
        User $athlete,
        string $sport,
        string $position,
        int $heightCm,
    ): void {
        AthleteProfile::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'user_id' => $athlete->id],
            [
                'sport' => $sport,
                'position' => $position,
                'height_cm' => $heightCm,
                'timezone' => $organization->timezone,
            ],
        );
    }

    private function assignCoach(Organization $organization, User $coach, User $athlete): void
    {
        CoachAthleteAssignment::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'coach_id' => $coach->id,
                'athlete_id' => $athlete->id,
            ],
            ['status' => 'active', 'started_at' => today()->toDateString()],
        );
    }

    /**
     * @param  array<string, mixed>  $blueprint
     */
    private function createProgram(
        Organization $organization,
        User $coach,
        User $athlete,
        Carbon $start,
        array $blueprint,
        ProgramScheduleService $schedule,
    ): ProgramAssignment {
        $weeks = (int) $blueprint['weeks'];
        $program = TrainingProgram::query()->create([
            'organization_id' => $organization->id,
            'coach_id' => $coach->id,
            'athlete_id' => null,
            'title' => $blueprint['title'],
            'goal' => $blueprint['goal'],
            'status' => 'active',
            'starts_on' => $start->toDateString(),
            'ends_on' => $start->copy()->addWeeks($weeks)->subDay()->toDateString(),
            'notes' => $blueprint['notes'],
            'is_template' => true,
            'visibility' => 'private',
            'estimated_weeks' => $weeks,
        ]);

        $phaseForWeek = [];
        foreach ($blueprint['phases'] as $phaseIndex => $phaseData) {
            $phase = $program->phases()->create([
                'organization_id' => $organization->id,
                'title' => $phaseData['title'],
                'description' => $phaseData['description'],
                'sort_order' => $phaseIndex + 1,
                'duration_weeks' => $phaseData['weeks'],
            ]);
            for ($week = 0; $week < (int) $phaseData['weeks']; $week++) {
                $phaseForWeek[] = $phase;
            }
        }

        $sortOrder = 1;
        for ($week = 0; $week < $weeks; $week++) {
            foreach ($blueprint['sessions'] as $template) {
                $dayOffset = ($week * 7) + (int) $template['day'];
                $exercises = collect($template['exercises'])->map(fn (array $exercise): array => [
                    'name' => $exercise['name'],
                    'sets' => $exercise['sets'],
                    'reps' => $exercise['reps'],
                    'rest' => $exercise['rest_seconds'].' sec',
                    'rest_seconds' => $exercise['rest_seconds'],
                    'load' => $exercise['load'] ?? '',
                    'unit' => $exercise['unit'] ?? '',
                    'note' => $exercise['notes'] ?? '',
                    'section' => $exercise['section'] ?? 'Main work',
                    'movement_type' => $exercise['movement_type'] ?? '',
                ])->all();
                $session = $program->sessions()->create([
                    'organization_id' => $organization->id,
                    'program_phase_id' => $phaseForWeek[$week]->id,
                    'title' => 'Week '.($week + 1).' · '.$template['title'],
                    'focus' => $template['focus'],
                    'scheduled_on' => $start->copy()->addDays($dayOffset)->toDateString(),
                    'status' => 'scheduled',
                    'exercises' => $exercises,
                    'coach_notes' => $template['coach_notes'],
                    'day_offset' => $dayOffset,
                    'sort_order' => $sortOrder++,
                    'estimated_minutes' => $template['minutes'],
                ]);

                foreach ($template['exercises'] as $exerciseIndex => $exercise) {
                    $session->prescribedExercises()->create([
                        'organization_id' => $organization->id,
                        'sort_order' => $exerciseIndex + 1,
                        'section' => $exercise['section'] ?? 'Main work',
                        'name' => $exercise['name'],
                        'target_sets' => $exercise['sets'],
                        'target_reps' => $exercise['reps'],
                        'target_load' => $exercise['load'] ?? null,
                        'unit' => $exercise['unit'] ?? null,
                        'rest_seconds' => $exercise['rest_seconds'],
                        'notes' => $exercise['notes'] ?? null,
                        'movement_type' => $exercise['movement_type'] ?? null,
                    ]);
                }
            }
        }

        return $schedule->assign(
            $program,
            $athlete,
            $coach,
            $start->toDateString(),
            'Three-month demo plan generated with realistic weekly progression.',
        );
    }

    private function completeFirstDueWorkout(ProgramAssignment $assignment): void
    {
        $workout = $assignment->scheduledWorkouts()
            ->where('scheduled_for', '<=', now())
            ->first();
        if (! $workout) {
            return;
        }

        $workout->load('session.prescribedExercises');
        $sets = $workout->session->prescribedExercises->values()->flatMap(
            fn ($exercise, int $exerciseIndex) => collect(range(1, max(1, (int) $exercise->target_sets)))
                ->map(fn (int $set): array => [
                    'exercise_id' => $exercise->id,
                    'exercise_index' => $exerciseIndex,
                    'exercise' => $exercise->name,
                    'set' => $set,
                    'target_reps' => $exercise->target_reps,
                    'target_load' => $exercise->target_load,
                    'target_rest_seconds' => $exercise->rest_seconds,
                    'actual_reps' => (float) ((int) $exercise->target_reps ?: 1),
                    'actual_load' => is_numeric($exercise->target_load)
                        ? (float) $exercise->target_load
                        : ($exercise->unit === 'kg' ? 60.0 : null),
                    'rpe' => 7,
                    'notes' => null,
                    'completed' => true,
                ])
        )->values()->all();

        app(WorkoutExecutionService::class)->save($workout, $assignment->athlete, [
            'confirmedComplete' => true,
            'durationMinutes' => 58,
            'rpe' => 7,
            'notes' => 'Seeded completed workout for progress and completion previews.',
            'setLogs' => $sets,
        ], 'completed');
    }

    private function seedProgress(Organization $organization, User $athlete, float $currentWeight, int $days): void
    {
        foreach (range(0, $days) as $index) {
            if ($index % 3 !== 0) {
                continue;
            }
            ProgressEntry::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'athlete_id' => $athlete->id,
                    'logged_on' => today()->subDays($index)->toDateString(),
                ],
                [
                    'weight' => $currentWeight + ($index * 0.025),
                    'calories' => 2380 + (($index % 4) * 45),
                    'protein' => 155 + ($index % 12),
                    'hydration' => 2600 + (($index % 5) * 150),
                    'sleep_quality' => 6 + ($index % 3),
                    'soreness' => 2 + ($index % 4),
                    'energy' => 7 + ($index % 2),
                    'notes' => $index === 0
                        ? 'Current demo check-in: ready for the next training block.'
                        : 'Historical demo check-in for trend testing.',
                ],
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function demoAthletePlans(): array
    {
        return [
            [
                'title' => '12-Week Strength Architecture',
                'goal' => 'Increase total-body strength while preserving movement quality.',
                'weeks' => 12,
                'notes' => 'Primary plan: foundation, build, and performance phases.',
                'phases' => [
                    ['title' => 'Foundation', 'description' => 'Own the positions and accumulate clean volume.', 'weeks' => 4],
                    ['title' => 'Build', 'description' => 'Increase load while keeping one to two reps in reserve.', 'weeks' => 4],
                    ['title' => 'Performance', 'description' => 'Lower volume, move heavier loads, and test repeatable strength.', 'weeks' => 4],
                ],
                'sessions' => [
                    $this->session(0, 'Lower Strength', 'Squat and hinge strength', 60, [
                        $this->exercise('Trap Bar Deadlift', 4, '5', 120, 'RPE 7', 'kg', 'Hinge'),
                        $this->exercise('Front-Foot Elevated Split Squat', 3, '8/side', 90, 'Moderate', 'kg', 'Squat'),
                        $this->exercise('Suitcase Carry', 3, '30 m/side', 60, 'Steady', 'kg', 'Carry'),
                    ]),
                    $this->session(2, 'Upper Strength', 'Press, pull, and trunk control', 55, [
                        $this->exercise('Dumbbell Bench Press', 4, '6', 90, 'RPE 7', 'kg', 'Push'),
                        $this->exercise('Chest-Supported Row', 4, '8', 90, 'Moderate', 'kg', 'Pull'),
                        $this->exercise('Half-Kneeling Pallof Press', 3, '10/side', 45, 'Light', 'kg', 'Core'),
                    ]),
                    $this->session(4, 'Total Body Power', 'Fast intent with controlled landings', 50, [
                        $this->exercise('Box Jump', 4, '3', 90, 'Bodyweight', null, 'Power'),
                        $this->exercise('Kettlebell Swing', 4, '10', 75, 'Moderate', 'kg', 'Hinge'),
                        $this->exercise('Sled Push', 5, '20 m', 75, 'Heavy', 'kg', 'Locomotion'),
                    ]),
                ],
            ],
            [
                'title' => 'Mobility & Recovery Track',
                'goal' => 'Maintain hip, ankle, and thoracic mobility across the full block.',
                'weeks' => 12,
                'notes' => 'Low-intensity support work. These sessions should improve readiness, not create fatigue.',
                'phases' => [
                    ['title' => 'Restore', 'description' => 'Build a repeatable recovery routine.', 'weeks' => 4],
                    ['title' => 'Expand', 'description' => 'Increase usable range under light control.', 'weeks' => 4],
                    ['title' => 'Maintain', 'description' => 'Keep the minimum effective recovery dose.', 'weeks' => 4],
                ],
                'sessions' => [
                    $this->session(1, 'Mobility Reset', 'Hips, ankles, and thoracic rotation', 25, [
                        $this->exercise('90/90 Hip Switch', 2, '8/side', 30, 'Bodyweight', null, 'Mobility'),
                        $this->exercise('Knee-to-Wall Ankle Rock', 2, '10/side', 30, 'Bodyweight', null, 'Mobility'),
                        $this->exercise('Open Book Rotation', 2, '8/side', 30, 'Bodyweight', null, 'Mobility'),
                    ]),
                    $this->session(6, 'Recovery Flow', 'Breathing and low-intensity movement', 30, [
                        $this->exercise('Zone 1 Walk', 1, '20 min', 0, 'Easy', null, 'Recovery'),
                        $this->exercise('Crocodile Breathing', 3, '5 breaths', 30, 'Bodyweight', null, 'Recovery'),
                        $this->exercise('Supported Deep Squat', 3, '30 sec', 30, 'Bodyweight', null, 'Mobility'),
                    ]),
                ],
            ],
            [
                'title' => 'Aerobic Engine Builder',
                'goal' => 'Build repeatable aerobic capacity without compromising strength days.',
                'weeks' => 12,
                'notes' => 'One focused conditioning day each week with conservative progression.',
                'phases' => [
                    ['title' => 'Base', 'description' => 'Accumulate easy conversational work.', 'weeks' => 4],
                    ['title' => 'Threshold', 'description' => 'Introduce controlled tempo intervals.', 'weeks' => 4],
                    ['title' => 'Consolidate', 'description' => 'Blend longer easy work with short threshold exposure.', 'weeks' => 4],
                ],
                'sessions' => [
                    $this->session(3, 'Engine Session', 'Aerobic base and controlled tempo', 42, [
                        $this->exercise('Bike or Run Warm-Up', 1, '10 min', 0, 'Easy', null, 'Conditioning'),
                        $this->exercise('Tempo Intervals', 4, '5 min', 90, 'RPE 6', null, 'Conditioning'),
                        $this->exercise('Easy Cool-Down', 1, '8 min', 0, 'Easy', null, 'Conditioning'),
                    ]),
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function speedPlan(): array
    {
        return [
            'title' => '8-Week Acceleration Lab',
            'goal' => 'Improve first-step power and 30 m acceleration.',
            'weeks' => 8,
            'notes' => 'High-quality sprint work with complete recovery.',
            'phases' => [
                ['title' => 'Positions', 'description' => 'Own projection and first-step mechanics.', 'weeks' => 4],
                ['title' => 'Expression', 'description' => 'Apply force faster over 10 to 30 metres.', 'weeks' => 4],
            ],
            'sessions' => [
                $this->session(0, 'Acceleration', 'Starts and projection', 50, [
                    $this->exercise('Wall Drill', 3, '5/side', 45, 'Bodyweight', null, 'Sprint drill'),
                    $this->exercise('10 m Sprint', 6, '1 rep', 150, 'Fast', null, 'Sprint'),
                ]),
                $this->session(2, 'Sprint Strength', 'Unilateral force production', 55, [
                    $this->exercise('Rear-Foot Elevated Split Squat', 4, '6/side', 120, 'RPE 8', 'kg', 'Squat'),
                    $this->exercise('Nordic Hamstring Curl', 3, '5', 120, 'Bodyweight', null, 'Hinge'),
                ]),
                $this->session(5, 'Maximum Velocity', 'Upright mechanics and rhythm', 45, [
                    $this->exercise('Wicket Run', 6, '20 m', 150, 'Fast', null, 'Sprint'),
                    $this->exercise('Flying 20 m', 4, '1 rep', 240, 'Fast', null, 'Sprint'),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function runningPlan(): array
    {
        return [
            'title' => '10-Week Durable 10K',
            'goal' => 'Build to a comfortable, pain-free 10 km effort.',
            'weeks' => 10,
            'notes' => 'Three runs and one strength support session each week.',
            'phases' => [
                ['title' => 'Consistency', 'description' => 'Establish repeatable weekly volume.', 'weeks' => 4],
                ['title' => 'Capacity', 'description' => 'Extend the long run and add tempo work.', 'weeks' => 3],
                ['title' => 'Specificity', 'description' => 'Practice sustainable 10 km rhythm.', 'weeks' => 3],
            ],
            'sessions' => [
                $this->session(0, 'Easy Run', 'Conversational aerobic volume', 40, [
                    $this->exercise('Easy Run', 1, '35 min', 0, 'RPE 4', null, 'Running'),
                ]),
                $this->session(2, 'Runner Strength', 'Calf, hip, and trunk capacity', 45, [
                    $this->exercise('Step-Up', 3, '8/side', 75, 'Moderate', 'kg', 'Squat'),
                    $this->exercise('Single-Leg Calf Raise', 3, '12/side', 60, 'Bodyweight', null, 'Calf'),
                ]),
                $this->session(4, 'Tempo Run', 'Controlled sustainable pace', 45, [
                    $this->exercise('Tempo Intervals', 3, '8 min', 120, 'RPE 6', null, 'Running'),
                ]),
                $this->session(6, 'Long Run', 'Durable easy volume', 65, [
                    $this->exercise('Long Easy Run', 1, '55 min', 0, 'RPE 4', null, 'Running'),
                ]),
            ],
        ];
    }

    /** @param array<int, array<string, mixed>> $exercises */
    private function session(int $day, string $title, string $focus, int $minutes, array $exercises): array
    {
        return [
            'day' => $day,
            'title' => $title,
            'focus' => $focus,
            'minutes' => $minutes,
            'coach_notes' => 'Finish with clean technique. Record RPE and any pain immediately.',
            'exercises' => $exercises,
        ];
    }

    private function exercise(
        string $name,
        int $sets,
        string $reps,
        int $restSeconds,
        string $load,
        ?string $unit,
        string $movementType,
    ): array {
        return [
            'name' => $name,
            'sets' => $sets,
            'reps' => $reps,
            'rest_seconds' => $restSeconds,
            'load' => $load,
            'unit' => $unit,
            'movement_type' => $movementType,
            'notes' => 'Stop the set if position or speed degrades.',
        ];
    }
}
