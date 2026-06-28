<?php

namespace App\Http\Controllers\Roster;

use App\Http\Controllers\Controller;
use App\Models\AthleteInvitation;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use App\Support\AthleteAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvitationCancelController extends Controller
{
    public function __invoke(Request $request, AthleteInvitation $invitation, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless(AthleteAccess::canInviteAthletes($viewer), 403);
        abort_unless($viewer->hasPermission('admin.invitations.manage') || $invitation->coach_id === $viewer->id, 404);
        abort_unless($invitation->status === AthleteInvitation::STATUS_PENDING, 422);

        $invitation->forceFill([
            'status' => AthleteInvitation::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ])->save();

        $auditLogger->record(
            $request,
            'athlete_invitation.cancelled',
            $invitation,
            "{$viewer->name} cancelled invitation to {$invitation->email}.",
            ['invitation_id' => $invitation->id],
        );

        return back()->with('success', 'Invitation cancelled.');
    }
}
