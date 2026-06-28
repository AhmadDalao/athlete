<?php

namespace App\Http\Controllers\Roster;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\AthleteInvitation;
use App\Models\User;
use App\Support\AthleteAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvitationIndexController extends Controller
{
    public function __invoke(Request $request): Response|StreamedResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');
        $adminMode = str_starts_with((string) $request->route()?->getName(), 'admin.');

        if ($adminMode) {
            abort_unless($viewer->hasPermission('admin.invitations.view'), 403);
        } else {
            abort_unless(AthleteAccess::canInviteAthletes($viewer), 403);
        }

        $filters = $this->filters($request);
        $query = $this->filteredQuery($viewer, $filters, $adminMode);

        if ($request->boolean('export')) {
            return $this->export((clone $query)->latest()->limit(5000)->get(), 'throughline-athlete-invitations.csv');
        }

        $invitations = (clone $query)
            ->with(['coach', 'invitedBy', 'acceptedUser'])
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (AthleteInvitation $invitation): array => $this->row($invitation));

        return Inertia::render('roster/invitations', [
            'adminMode' => $adminMode,
            'filters' => $filters,
            'summary' => [
                'total' => (clone $query)->count(),
                'pending' => (clone $query)->where('status', AthleteInvitation::STATUS_PENDING)->count(),
                'accepted' => (clone $query)->where('status', AthleteInvitation::STATUS_ACCEPTED)->count(),
                'cancelled' => (clone $query)->where('status', AthleteInvitation::STATUS_CANCELLED)->count(),
                'expired' => (clone $query)
                    ->where('status', AthleteInvitation::STATUS_PENDING)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<', now())
                    ->count(),
            ],
            'invitations' => $invitations,
            'coachOptions' => $viewer->hasRole(RoleName::Admin) || $viewer->hasRole(RoleName::Owner)
                ? $this->coachOptions()
                : [],
            'statusOptions' => [
                AthleteInvitation::STATUS_PENDING,
                AthleteInvitation::STATUS_ACCEPTED,
                AthleteInvitation::STATUS_EXPIRED,
                AthleteInvitation::STATUS_CANCELLED,
            ],
        ]);
    }

    /**
     * @return array{q:string|null,status:string|null,coach_id:string|null}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => $request->string('q')->trim()->value() ?: null,
            'status' => $request->string('status')->trim()->value() ?: null,
            'coach_id' => $request->string('coach_id')->trim()->value() ?: null,
        ];
    }

    /**
     * @param  array{q:string|null,status:string|null,coach_id:string|null}  $filters
     */
    private function filteredQuery(User $viewer, array $filters, bool $adminMode): Builder
    {
        return AthleteInvitation::query()
            ->with(['coach', 'invitedBy', 'acceptedUser'])
            ->when(! $adminMode, fn (Builder $query) => $query->where('coach_id', $viewer->id))
            ->when($filters['q'], function (Builder $query, string $value): void {
                $query->where(function (Builder $nested) use ($value): void {
                    $nested
                        ->where('name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%")
                        ->orWhere('goal', 'like', "%{$value}%");
                });
            })
            ->when($filters['status'], function (Builder $query, string $status): void {
                if ($status === AthleteInvitation::STATUS_EXPIRED) {
                    $query->where('status', AthleteInvitation::STATUS_PENDING)
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<', now());

                    return;
                }

                $query->where('status', $status);
            })
            ->when($adminMode && $filters['coach_id'], fn (Builder $query, string $coachId) => $query->where('coach_id', (int) $coachId));
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    private function coachOptions(): array
    {
        return User::query()
            ->role(RoleName::Coach)
            ->orderBy('name')
            ->get()
            ->map(fn (User $coach): array => [
                'value' => (string) $coach->id,
                'label' => $coach->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(AthleteInvitation $invitation): array
    {
        $status = $invitation->status === AthleteInvitation::STATUS_PENDING
            && $invitation->expires_at
            && $invitation->expires_at->isPast()
                ? AthleteInvitation::STATUS_EXPIRED
                : $invitation->status;

        return [
            'id' => $invitation->id,
            'name' => $invitation->name,
            'email' => $invitation->email,
            'phone' => $invitation->phone,
            'goal' => $invitation->goal,
            'notes' => $invitation->notes,
            'status' => $status,
            'expiresAt' => $invitation->expires_at?->toDateTimeString(),
            'acceptedAt' => $invitation->accepted_at?->toDateTimeString(),
            'cancelledAt' => $invitation->cancelled_at?->toDateTimeString(),
            'createdAt' => $invitation->created_at?->toDateTimeString(),
            'coach' => [
                'id' => $invitation->coach?->id,
                'name' => $invitation->coach?->name,
                'email' => $invitation->coach?->email,
            ],
            'invitedBy' => [
                'id' => $invitation->invitedBy?->id,
                'name' => $invitation->invitedBy?->name,
            ],
            'acceptedUser' => $invitation->acceptedUser ? [
                'id' => $invitation->acceptedUser->id,
                'name' => $invitation->acceptedUser->name,
                'email' => $invitation->acceptedUser->email,
            ] : null,
        ];
    }

    private function export($invitations, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($invitations): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Created', 'Status', 'Name', 'Email', 'Phone', 'Coach', 'Goal', 'Expires', 'Accepted']);

            foreach ($invitations as $invitation) {
                $row = $this->row($invitation);
                fputcsv($handle, [
                    $row['createdAt'],
                    $row['status'],
                    $row['name'],
                    $row['email'],
                    $row['phone'],
                    $row['coach']['name'],
                    $row['goal'],
                    $row['expiresAt'],
                    $row['acceptedAt'],
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
