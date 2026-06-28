<?php

namespace App\Http\Controllers;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Models\CoachAthleteAssignment;
use App\Models\CoachAthleteMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless($viewer->hasRole(RoleName::Athlete) || $viewer->hasRole(RoleName::Coach), 403);

        $assignments = $this->visibleAssignments($viewer);
        $assignmentIds = $assignments->pluck('id');

        CoachAthleteMessage::query()
            ->whereIn('coach_athlete_assignment_id', $assignmentIds)
            ->where('recipient_id', $viewer->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $assignments->load(['coach', 'athlete', 'messages.sender', 'messages.recipient']);

        return Inertia::render('messages/index', [
            'viewerRole' => $viewer->primaryRoleName(),
            'threads' => $assignments
                ->map(fn (CoachAthleteAssignment $assignment): array => $this->threadPayload($viewer, $assignment))
                ->values()
                ->all(),
        ]);
    }

    private function visibleAssignments(User $viewer)
    {
        $query = CoachAthleteAssignment::query()
            ->where('status', CoachAthleteStatus::Active->value);

        if ($viewer->hasRole(RoleName::Coach)) {
            return $query->where('coach_id', $viewer->id)->latest('started_at')->get();
        }

        return $query->where('athlete_id', $viewer->id)->latest('started_at')->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function threadPayload(User $viewer, CoachAthleteAssignment $assignment): array
    {
        $participant = $viewer->id === $assignment->coach_id ? $assignment->athlete : $assignment->coach;

        return [
            'assignmentId' => $assignment->id,
            'participant' => [
                'id' => $participant->id,
                'name' => $participant->name,
                'email' => $participant->email,
            ],
            'goal' => $assignment->goal,
            'status' => $assignment->status->value,
            'messages' => $assignment->messages
                ->sortBy('created_at')
                ->map(fn (CoachAthleteMessage $message): array => [
                    'id' => $message->id,
                    'senderId' => $message->sender_id,
                    'recipientId' => $message->recipient_id,
                    'senderName' => $message->sender->name,
                    'body' => $message->body,
                    'sentAt' => $message->created_at?->toIso8601String(),
                    'readAt' => $message->read_at?->toIso8601String(),
                    'isMine' => $message->sender_id === $viewer->id,
                ])
                ->values()
                ->all(),
        ];
    }
}
