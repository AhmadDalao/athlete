<?php

namespace Tests\Feature;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Models\AthleteCheckIn;
use App\Models\CoachAthleteAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProgressIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_athlete_can_view_progress_page_and_manage_their_own_check_ins(): void
    {
        $athlete = User::factory()->create();
        $athlete->assignRole(RoleName::Athlete);

        $this->actingAs($athlete)
            ->get('/progress')
            ->assertInertia(fn (Assert $page) => $page
                ->component('progress/index')
                ->where('viewerRole', RoleName::Athlete->value)
                ->where('canManageOwnCheckIns', true)
            );

        $response = $this->actingAs($athlete)->post(route('progress.check-ins.store', absolute: false), [
            'logged_date' => now()->toDateString(),
            'weight_kg' => 78.4,
            'body_fat_percentage' => 16.9,
            'waist_cm' => 80.2,
            'calories_consumed' => 2840,
            'protein_grams' => 182,
            'carbs_grams' => 318,
            'fat_grams' => 78,
            'water_liters' => 3.1,
            'meals_logged_count' => 4,
            'energy_score' => 7,
            'soreness_score' => 5,
            'stress_score' => 4,
            'sleep_quality_score' => 7,
            'notes' => 'Solid day and food stayed clean.',
        ]);

        $response->assertRedirect('/progress');

        $checkIn = AthleteCheckIn::query()->firstOrFail();

        $this->assertDatabaseHas('athlete_check_ins', [
            'user_id' => $athlete->id,
            'calories_consumed' => 2840,
            'protein_grams' => 182,
        ]);

        $updateResponse = $this->actingAs($athlete)->patch(route('progress.check-ins.update', $checkIn, absolute: false), [
            'logged_date' => now()->toDateString(),
            'weight_kg' => 78.0,
            'body_fat_percentage' => 16.8,
            'waist_cm' => 80.0,
            'calories_consumed' => 2760,
            'protein_grams' => 188,
            'carbs_grams' => 300,
            'fat_grams' => 76,
            'water_liters' => 3.3,
            'meals_logged_count' => 4,
            'energy_score' => 8,
            'soreness_score' => 4,
            'stress_score' => 3,
            'sleep_quality_score' => 8,
            'notes' => 'Recovered better than expected.',
        ]);

        $updateResponse->assertRedirect('/progress');

        $this->assertDatabaseHas('athlete_check_ins', [
            'id' => $checkIn->id,
            'weight_kg' => 78.0,
            'protein_grams' => 188,
        ]);
    }

    public function test_coach_sees_only_assigned_athlete_progress(): void
    {
        $coach = User::factory()->create();
        $athleteOne = User::factory()->create();
        $athleteTwo = User::factory()->create();

        $coach->assignRole(RoleName::Coach);
        $athleteOne->assignRole(RoleName::Athlete);
        $athleteTwo->assignRole(RoleName::Athlete);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athleteOne->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Track cut phase',
            'started_at' => now()->subDays(7)->toDateString(),
        ]);

        AthleteCheckIn::query()->create([
            'user_id' => $athleteOne->id,
            'logged_date' => now()->toDateString(),
            'weight_kg' => 70.4,
            'calories_consumed' => 2410,
            'protein_grams' => 172,
            'water_liters' => 2.9,
            'energy_score' => 7,
            'soreness_score' => 4,
            'stress_score' => 3,
            'sleep_quality_score' => 7,
        ]);

        AthleteCheckIn::query()->create([
            'user_id' => $athleteTwo->id,
            'logged_date' => now()->toDateString(),
            'weight_kg' => 88.2,
            'calories_consumed' => 3300,
            'protein_grams' => 210,
            'water_liters' => 2.4,
            'energy_score' => 5,
            'soreness_score' => 6,
            'stress_score' => 5,
            'sleep_quality_score' => 5,
        ]);

        $this->actingAs($coach)
            ->get('/progress')
            ->assertInertia(fn (Assert $page) => $page
                ->component('progress/index')
                ->where('viewerRole', RoleName::Coach->value)
                ->where('summary.visibleAthletes', 1)
                ->where('athletes.data.0.name', $athleteOne->name)
                ->where('athletes.data.0.latestCheckIn.weightKg', 70.4)
            );
    }

    public function test_athlete_cannot_update_someone_elses_check_in(): void
    {
        $athleteOne = User::factory()->create();
        $athleteTwo = User::factory()->create();

        $athleteOne->assignRole(RoleName::Athlete);
        $athleteTwo->assignRole(RoleName::Athlete);

        $checkIn = AthleteCheckIn::query()->create([
            'user_id' => $athleteTwo->id,
            'logged_date' => now()->toDateString(),
            'weight_kg' => 82.1,
            'calories_consumed' => 2710,
            'protein_grams' => 176,
            'water_liters' => 2.7,
            'energy_score' => 6,
            'soreness_score' => 5,
            'stress_score' => 4,
            'sleep_quality_score' => 6,
        ]);

        $response = $this->actingAs($athleteOne)->patch(route('progress.check-ins.update', $checkIn, absolute: false), [
            'logged_date' => now()->toDateString(),
            'weight_kg' => 79.0,
            'calories_consumed' => 2300,
            'protein_grams' => 150,
            'water_liters' => 3.1,
            'energy_score' => 8,
            'soreness_score' => 3,
            'stress_score' => 2,
            'sleep_quality_score' => 8,
        ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('athlete_check_ins', [
            'id' => $checkIn->id,
            'weight_kg' => 82.1,
            'protein_grams' => 176,
        ]);
    }
}
