<?php

namespace App\Services;

use App\Models\Message;
use App\Models\ProgramAssignment;
use App\Models\ScheduledWorkout;
use App\Notifications\ThroughlineNotification;

class UserNotificationService
{
    public function programAssigned(ProgramAssignment $assignment): void
    {
        $assignment->loadMissing(['athlete', 'program']);
        $assignment->athlete->notify(new ThroughlineNotification(
            $assignment->organization_id,
            'program',
            'New program assigned',
            "{$assignment->program->title} starts on {$assignment->starts_on->format('M j')}.",
            'program_assignment',
            $assignment->id,
            ['program_title' => $assignment->program->title],
        ));
    }

    public function workoutRescheduled(ScheduledWorkout $workout): void
    {
        $workout->loadMissing(['athlete', 'session']);
        $workout->athlete->notify(new ThroughlineNotification(
            $workout->organization_id,
            'schedule',
            'Workout rescheduled',
            "{$workout->session->title} is now scheduled for {$workout->scheduled_for->format('M j')}.",
            'scheduled_workout',
            $workout->id,
        ));
    }

    public function assignmentStatusChanged(ProgramAssignment $assignment): void
    {
        $assignment->loadMissing(['athlete', 'program']);
        $assignment->athlete->notify(new ThroughlineNotification(
            $assignment->organization_id,
            'program',
            'Program status updated',
            "{$assignment->program->title} is now {$assignment->status}.",
            'program_assignment',
            $assignment->id,
            ['status' => $assignment->status],
        ));
    }

    public function workoutSaved(ScheduledWorkout $workout, string $status): void
    {
        if ($status === 'partial') {
            return;
        }

        $workout->loadMissing(['coach', 'athlete', 'session']);
        $workout->coach?->notify(new ThroughlineNotification(
            $workout->organization_id,
            'workout',
            'Workout '.$status,
            "{$workout->athlete->name} marked {$workout->session->title} as {$status}.",
            'athlete_workout',
            $workout->id,
            ['athlete_id' => $workout->athlete_id, 'status' => $status],
        ));
    }

    public function messageSent(Message $message): void
    {
        $message->loadMissing(['sender', 'conversation.participants']);
        foreach ($message->conversation->participants->where('id', '!=', $message->sender_id) as $recipient) {
            $recipient->notify(new ThroughlineNotification(
                $message->conversation->organization_id,
                'message',
                'New message from '.$message->sender->name,
                str($message->body)->limit(110)->toString(),
                'conversation',
                $message->conversation_id,
                ['sender_id' => $message->sender_id],
            ));
        }
    }
}
