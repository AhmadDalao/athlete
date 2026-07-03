<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Models\CoachAthleteAssignment;
use App\Models\CoachAthleteMessage;
use App\Models\Membership;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\TrainingAppPresenter;
use Illuminate\Database\Eloquent\Builder;

trait BuildsMobileAppPayloads
{
    protected function abortUnlessMobileUser(User $user): void
    {
        abort_unless($user->hasRole(RoleName::Athlete) || $user->hasRole(RoleName::Coach), 403);
    }

    protected function mobileProgramsQuery(User $user): Builder
    {
        $query = TrainingProgram::query()
            ->whereIn('status', [
                TrainingProgramStatus::Active->value,
                TrainingProgramStatus::Draft->value,
            ]);

        if ($user->hasRole(RoleName::Coach)) {
            return $query->where('coach_id', $user->id);
        }

        return $query->where('athlete_id', $user->id);
    }

    protected function canOpenMobileProgram(User $user, TrainingProgram $program): bool
    {
        if ($user->hasRole(RoleName::Coach)) {
            return $program->coach_id === $user->id;
        }

        return $program->athlete_id === $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    protected function mobileSessionPayload(TrainingSession $session, TrainingAppPresenter $presenter): array
    {
        $sessionPayload = $presenter->session($session);

        return array_merge($sessionPayload, [
            'program' => [
                'id' => $session->program->id,
                'title' => $session->program->title,
                'goal' => $session->program->goal,
                'status' => $session->program->status->value,
            ],
            'coach' => [
                'id' => $session->program->coach->id,
                'name' => $session->program->coach->name,
                'email' => $session->program->coach->email,
            ],
            'athlete' => [
                'id' => $session->program->athlete->id,
                'name' => $session->program->athlete->name,
                'email' => $session->program->athlete->email,
            ],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function visibleMessageThreads(User $viewer, bool $markRead = false): array
    {
        $assignments = CoachAthleteAssignment::query()
            ->where('status', CoachAthleteStatus::Active->value)
            ->when(
                $viewer->hasRole(RoleName::Coach),
                fn (Builder $query): Builder => $query->where('coach_id', $viewer->id),
                fn (Builder $query): Builder => $query->where('athlete_id', $viewer->id),
            )
            ->latest('started_at')
            ->get();

        if ($markRead && $assignments->isNotEmpty()) {
            CoachAthleteMessage::query()
                ->whereIn('coach_athlete_assignment_id', $assignments->pluck('id'))
                ->where('recipient_id', $viewer->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $assignments->load(['coach', 'athlete', 'messages.sender', 'messages.recipient']);

        return $assignments
            ->map(fn (CoachAthleteAssignment $assignment): array => $this->mobileThreadPayload($viewer, $assignment))
            ->values()
            ->all();
    }

    protected function unreadMessageCount(User $viewer): int
    {
        $assignmentQuery = CoachAthleteAssignment::query()
            ->where('status', CoachAthleteStatus::Active->value)
            ->when(
                $viewer->hasRole(RoleName::Coach),
                fn (Builder $query): Builder => $query->where('coach_id', $viewer->id),
                fn (Builder $query): Builder => $query->where('athlete_id', $viewer->id),
            );

        return CoachAthleteMessage::query()
            ->whereIn('coach_athlete_assignment_id', $assignmentQuery->select('id'))
            ->where('recipient_id', $viewer->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    protected function mobileThreadPayload(User $viewer, CoachAthleteAssignment $assignment): array
    {
        $participant = $viewer->id === $assignment->coach_id ? $assignment->athlete : $assignment->coach;

        return [
            'assignmentId' => $assignment->id,
            'participant' => [
                'id' => $participant->id,
                'name' => $participant->name,
                'email' => $participant->email,
            ],
            'coach' => [
                'id' => $assignment->coach->id,
                'name' => $assignment->coach->name,
                'email' => $assignment->coach->email,
            ],
            'athlete' => [
                'id' => $assignment->athlete->id,
                'name' => $assignment->athlete->name,
                'email' => $assignment->athlete->email,
            ],
            'goal' => $assignment->goal,
            'status' => $assignment->status->value,
            'unreadCount' => $assignment->messages
                ->where('recipient_id', $viewer->id)
                ->whereNull('read_at')
                ->count(),
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

    /**
     * @return array<string, mixed>|null
     */
    protected function mobileMembershipPayload(User $user): ?array
    {
        $membership = $user->currentMembership();

        if (! $membership) {
            return null;
        }

        $membership->loadMissing('plan');

        return [
            'id' => $membership->id,
            'planName' => $membership->plan?->name,
            'status' => $membership->status->value,
            'statusLabel' => $membership->status->label(),
            'startsAt' => $membership->starts_at?->toDateString(),
            'renewsAt' => $membership->renews_at?->toDateString(),
            'endsAt' => $membership->effectiveEndDate()?->toDateString(),
            'daysRemaining' => $membership->daysRemaining(),
            'autoRenew' => $membership->auto_renew,
            'price' => (string) $membership->price,
            'currency' => $membership->currency,
        ];
    }

    /**
     * @return Builder<Membership>
     */
    protected function currentMembershipQuery(User $user): Builder
    {
        return Membership::query()->where('user_id', $user->id)->with('plan');
    }
}
