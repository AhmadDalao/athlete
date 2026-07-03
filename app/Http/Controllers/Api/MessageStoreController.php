<?php

namespace App\Http\Controllers\Api;

use App\Enums\CoachAthleteStatus;
use App\Http\Controllers\Api\Concerns\BuildsMobileAppPayloads;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\CoachAthleteAssignment;
use App\Models\CoachAthleteMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageStoreController extends Controller
{
    use BuildsMobileAppPayloads;
    use FormatsApiPayloads;

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('roles');
        $this->abortUnlessMobileUser($user);

        $validated = $request->validate([
            'assignment_id' => ['required', 'integer', 'exists:coach_athlete_assignments,id'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $assignment = CoachAthleteAssignment::query()
            ->with(['coach', 'athlete'])
            ->findOrFail($validated['assignment_id']);

        $isCoach = $assignment->coach_id === $user->id;
        $isAthlete = $assignment->athlete_id === $user->id;

        abort_unless($assignment->status === CoachAthleteStatus::Active && ($isCoach || $isAthlete), 403);

        $message = CoachAthleteMessage::query()->create([
            'coach_athlete_assignment_id' => $assignment->id,
            'sender_id' => $user->id,
            'recipient_id' => $isCoach ? $assignment->athlete_id : $assignment->coach_id,
            'body' => $validated['body'],
        ]);

        $assignment->load(['coach', 'athlete', 'messages.sender', 'messages.recipient']);

        return response()->json([
            'data' => [
                'message' => [
                    'id' => $message->id,
                    'senderId' => $message->sender_id,
                    'recipientId' => $message->recipient_id,
                    'body' => $message->body,
                    'sentAt' => $message->created_at?->toIso8601String(),
                    'readAt' => $message->read_at?->toIso8601String(),
                    'isMine' => true,
                ],
                'thread' => $this->mobileThreadPayload($user, $assignment),
            ],
            'meta' => $this->metaPayload(),
        ], 201);
    }
}
