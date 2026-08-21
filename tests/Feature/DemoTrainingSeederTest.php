<?php

namespace Tests\Feature;

use App\Models\ProgramAssignment;
use App\Models\ScheduledWorkout;
use App\Models\User;
use App\Models\WorkoutSetLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoTrainingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_builds_a_multi_program_three_month_athlete_calendar(): void
    {
        $this->seed();

        $athlete = User::query()->where('email', 'athlete@throughline.test')->firstOrFail();
        $organizationId = $athlete->current_organization_id;
        $assignments = ProgramAssignment::query()
            ->where('athlete_id', $athlete->id)
            ->with('program.phases')
            ->get();
        $workouts = ScheduledWorkout::query()
            ->where('athlete_id', $athlete->id)
            ->orderBy('scheduled_for')
            ->get();

        $this->assertCount(3, $assignments);
        $this->assertGreaterThanOrEqual(72, $workouts->count());
        $this->assertGreaterThanOrEqual(3, $workouts->pluck('scheduled_for')->map->format('Y-m')->unique()->count());
        $strengthAssignment = $assignments->first(
            fn (ProgramAssignment $assignment): bool => $assignment->program->title === '12-Week Strength Architecture'
        );
        $this->assertNotNull($strengthAssignment);
        $this->assertSame(3, $strengthAssignment->program->phases->count());
        $this->assertGreaterThanOrEqual(15, $athlete->progressEntries()->count());
        $this->assertGreaterThan(0, WorkoutSetLog::query()->where('athlete_id', $athlete->id)->whereNotNull('completed_at')->count());

        $this->actingAs($athlete)
            ->get(route('app.home'))
            ->assertOk()
            ->assertSee('12-Week Strength Architecture')
            ->assertSee('Mobility &amp; Recovery Track', false)
            ->assertSee('Aerobic Engine Builder');

        foreach ($assignments as $assignment) {
            $this->actingAs($athlete)
                ->get(route('app.programs.show', $assignment))
                ->assertOk()
                ->assertSee($assignment->program->title)
                ->assertSee($assignment->program->phases->first()->title);
        }

        $this->actingAs($athlete, 'sanctum')
            ->withHeader('X-Organization-ID', (string) $organizationId)
            ->getJson('/api/v1/app/programs?per_page=100')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        foreach ($workouts->pluck('scheduled_for')->map->format('Y-m')->unique()->take(3) as $month) {
            $response = $this->actingAs($athlete, 'sanctum')
                ->withHeader('X-Organization-ID', (string) $organizationId)
                ->getJson('/api/v1/app/calendar?month='.$month.'&date='.$month.'-01')
                ->assertOk()
                ->assertJsonPath('data.month', $month)
                ->assertJsonMissingPath('data.workouts.0.athlete.password');
            $this->assertNotEmpty($response->json('data.workouts'));
        }
    }
}
