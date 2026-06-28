<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\AthleteCheckInUpsertRequest;
use App\Models\AthleteCheckIn;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\RedirectResponse;

class AthleteCheckInUpdateController extends Controller
{
    public function __invoke(AthleteCheckInUpsertRequest $request, AthleteCheckIn $athleteCheckIn, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        $viewer = $this->manageableViewer($request->user(), $athleteCheckIn);

        $athleteCheckIn->fill($request->validated());
        $athleteCheckIn->save();

        $auditLogger->record(
            $request,
            'athlete_check_in.updated',
            $athleteCheckIn,
            "{$viewer->name} updated progress check-in for {$athleteCheckIn->logged_date?->toDateString()}.",
            [
                'logged_date' => $athleteCheckIn->logged_date?->toDateString(),
                'weight_kg' => $athleteCheckIn->weight_kg,
                'water_liters' => $athleteCheckIn->water_liters,
            ],
        );

        return to_route('progress.index');
    }

    private function manageableViewer(?User $viewer, AthleteCheckIn $athleteCheckIn): User
    {
        abort_unless($viewer, 403);

        $viewer->loadMissing('roles');

        abort_unless($viewer->hasRole(RoleName::Athlete), 403);
        abort_unless($athleteCheckIn->user_id === $viewer->id, 404);

        return $viewer;
    }
}
