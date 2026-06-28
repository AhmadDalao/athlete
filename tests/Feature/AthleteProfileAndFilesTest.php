<?php

namespace Tests\Feature;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Enums\WorkoutCompletionStatus;
use App\Models\AthleteCheckIn;
use App\Models\AthleteFile;
use App\Models\CoachAthleteAssignment;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Models\WorkoutSetLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AthleteProfileAndFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_coach_can_view_athlete_profile_tables(): void
    {
        [$coach, $athlete] = $this->assignedCoachAthlete();

        AthleteCheckIn::query()->create([
            'user_id' => $athlete->id,
            'logged_date' => now()->toDateString(),
            'weight_kg' => 82.4,
            'calories_consumed' => 2400,
            'protein_grams' => 170,
            'water_liters' => 3.1,
            'energy_score' => 8,
            'soreness_score' => 3,
        ]);

        $program = TrainingProgram::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'title' => 'Strength Block',
            'goal' => 'Build base',
            'status' => TrainingProgramStatus::Active,
            'start_date' => now()->subDays(2)->toDateString(),
        ]);
        $session = TrainingSession::query()->create([
            'training_program_id' => $program->id,
            'title' => 'Lower Body',
            'scheduled_date' => now()->toDateString(),
            'focus' => 'Strength',
            'sort_order' => 1,
        ]);
        $workoutLog = WorkoutLog::query()->create([
            'training_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'completion_status' => WorkoutCompletionStatus::Completed,
            'performed_at' => now(),
            'duration_minutes' => 55,
            'exertion_rating' => 7,
        ]);
        WorkoutSetLog::query()->create([
            'workout_log_id' => $workoutLog->id,
            'training_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'exercise_index' => 0,
            'exercise_name' => 'Back squat',
            'set_number' => 1,
            'target_reps' => '5',
            'target_load' => '100kg',
            'target_rest_seconds' => 120,
            'actual_reps' => '5',
            'actual_load' => '102.5kg',
            'actual_rpe' => 8,
            'completed_at' => now(),
            'notes' => 'Moved well.',
        ]);

        $this->actingAs($coach)
            ->get(route('athletes.show', $athlete, false))
            ->assertInertia(fn (Assert $page) => $page
                ->component('athletes/show')
                ->where('profile.email', $athlete->email)
                ->where('summary.sessions', 1)
                ->where('summary.completedSessions', 1)
                ->where('progress.0.weightKg', 82.4)
                ->where('sessions.0.title', 'Lower Body')
                ->where('sessions.0.completedSets', 1)
                ->where('setLogs.0.exerciseName', 'Back squat')
                ->where('setLogs.0.actualLoad', '102.5kg')
                ->where('setLogs.0.actualRpe', 8)
            );
    }

    public function test_unassigned_coach_cannot_view_another_coachs_athlete_profile(): void
    {
        [, $athlete] = $this->assignedCoachAthlete();
        $otherCoach = User::factory()->create();
        $otherCoach->assignRole(RoleName::Coach);

        $this->actingAs($otherCoach)
            ->get(route('athletes.show', $athlete, false))
            ->assertNotFound();
    }

    public function test_assigned_coach_can_upload_and_download_athlete_files(): void
    {
        Storage::fake();
        [$coach, $athlete] = $this->assignedCoachAthlete();

        $this->actingAs($coach)
            ->post(route('athletes.files.store', $athlete, false), [
                'file' => UploadedFile::fake()->create('training-plan.pdf', 20, 'application/pdf'),
                'display_name' => 'Training plan PDF',
                'category' => 'training',
                'visibility' => AthleteFile::VISIBILITY_COACH_ADMIN,
                'notes' => 'Initial upload',
            ])
            ->assertRedirect();

        $file = AthleteFile::query()->firstOrFail();

        Storage::assertExists($file->stored_path);
        $this->assertDatabaseHas('athlete_files', [
            'athlete_id' => $athlete->id,
            'uploaded_by_user_id' => $coach->id,
            'display_name' => 'Training plan PDF',
            'category' => 'training',
        ]);

        $this->actingAs($coach)
            ->get(route('athlete-files.download', $file, false))
            ->assertOk();
    }

    public function test_unassigned_coach_cannot_download_private_athlete_file(): void
    {
        Storage::fake();
        [$coach, $athlete] = $this->assignedCoachAthlete();
        $otherCoach = User::factory()->create();
        $otherCoach->assignRole(RoleName::Coach);

        $path = UploadedFile::fake()->create('private.pdf')->store('athlete-files/'.$athlete->id);
        $file = AthleteFile::query()->create([
            'athlete_id' => $athlete->id,
            'uploaded_by_user_id' => $coach->id,
            'category' => 'admin',
            'visibility' => AthleteFile::VISIBILITY_COACH_ADMIN,
            'status' => AthleteFile::STATUS_ACTIVE,
            'display_name' => 'Private file',
            'original_filename' => 'private.pdf',
            'stored_path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
        ]);

        $this->actingAs($otherCoach)
            ->get(route('athlete-files.download', $file, false))
            ->assertForbidden();
    }

    public function test_admin_can_move_and_archive_athlete_file(): void
    {
        Storage::fake();
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);
        $athleteOne = User::factory()->create();
        $athleteTwo = User::factory()->create();
        $athleteOne->assignRole(RoleName::Athlete);
        $athleteTwo->assignRole(RoleName::Athlete);

        $path = UploadedFile::fake()->create('move.pdf')->store('athlete-files/'.$athleteOne->id);
        $file = AthleteFile::query()->create([
            'athlete_id' => $athleteOne->id,
            'uploaded_by_user_id' => $admin->id,
            'category' => 'admin',
            'visibility' => AthleteFile::VISIBILITY_COACH_ADMIN,
            'status' => AthleteFile::STATUS_ACTIVE,
            'display_name' => 'Move me',
            'original_filename' => 'move.pdf',
            'stored_path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
        ]);

        $this->actingAs($admin)
            ->patch(route('athlete-files.update', $file, false), [
                'athlete_id' => $athleteTwo->id,
                'display_name' => 'Moved file',
                'category' => 'medical',
                'visibility' => AthleteFile::VISIBILITY_ADMIN,
                'status' => AthleteFile::STATUS_ARCHIVED,
                'notes' => 'Moved to the right athlete.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('athlete_files', [
            'id' => $file->id,
            'athlete_id' => $athleteTwo->id,
            'display_name' => 'Moved file',
            'category' => 'medical',
            'visibility' => AthleteFile::VISIBILITY_ADMIN,
            'status' => AthleteFile::STATUS_ARCHIVED,
        ]);
    }

    /**
     * @return array{User, User}
     */
    private function assignedCoachAthlete(): array
    {
        $coach = User::factory()->create();
        $athlete = User::factory()->create();
        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Main block',
            'started_at' => now()->subDays(7)->toDateString(),
        ]);

        return [$coach, $athlete];
    }
}
