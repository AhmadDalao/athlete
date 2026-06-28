<?php

namespace Tests\Feature;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Models\CoachAthleteAssignment;
use App\Models\CoachAthleteMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CoachAthleteMessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_athlete_and_coach_can_message_each_other_through_active_assignment(): void
    {
        [$coach, $athlete, $assignment] = $this->assignmentFixture();

        $this->actingAs($athlete)
            ->get('/messages')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('messages/index')
                ->where('viewerRole', RoleName::Athlete->value)
                ->where('threads.0.assignmentId', $assignment->id)
                ->where('threads.0.participant.name', $coach->name)
            );

        $this->actingAs($athlete)
            ->from('/messages')
            ->post('/messages', [
                'assignment_id' => $assignment->id,
                'body' => 'Can we adjust tomorrow based on low sleep?',
            ])
            ->assertRedirect('/messages');

        $this->assertDatabaseHas('coach_athlete_messages', [
            'coach_athlete_assignment_id' => $assignment->id,
            'sender_id' => $athlete->id,
            'recipient_id' => $coach->id,
            'body' => 'Can we adjust tomorrow based on low sleep?',
        ]);

        $this->actingAs($coach)
            ->from('/messages')
            ->post('/messages', [
                'assignment_id' => $assignment->id,
                'body' => 'Yes. Cap it at RPE 6 and log notes.',
            ])
            ->assertRedirect('/messages');

        $this->assertSame(2, CoachAthleteMessage::query()->where('coach_athlete_assignment_id', $assignment->id)->count());
    }

    public function test_unassigned_athlete_cannot_message_random_coach(): void
    {
        [$coach, , $assignment] = $this->assignmentFixture();
        $randomAthlete = User::factory()->create();
        $randomAthlete->assignRole(RoleName::Athlete);

        $this->actingAs($randomAthlete)
            ->post('/messages', [
                'assignment_id' => $assignment->id,
                'body' => 'This should not send.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('coach_athlete_messages', [
            'sender_id' => $randomAthlete->id,
            'recipient_id' => $coach->id,
        ]);
    }

    /**
     * @return array{User, User, CoachAthleteAssignment}
     */
    private function assignmentFixture(): array
    {
        $coach = User::factory()->create(['name' => 'Coach Maya']);
        $athlete = User::factory()->create(['name' => 'Athlete Noor']);

        $coach->assignRole(RoleName::Coach);
        $athlete->assignRole(RoleName::Athlete);

        $assignment = CoachAthleteAssignment::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => CoachAthleteStatus::Active,
            'goal' => 'Return to performance',
            'notes' => null,
            'started_at' => now()->subDays(8)->toDateString(),
        ]);

        return [$coach, $athlete, $assignment];
    }
}
