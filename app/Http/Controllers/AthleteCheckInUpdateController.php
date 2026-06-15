<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\AthleteCheckInUpsertRequest;
use App\Models\AthleteCheckIn;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AthleteCheckInUpdateController extends Controller
{
    public function __invoke(AthleteCheckInUpsertRequest $request, AthleteCheckIn $athleteCheckIn): RedirectResponse
    {
        $viewer = $this->manageableViewer($request->user(), $athleteCheckIn);

        $athleteCheckIn->fill($request->validated());
        $athleteCheckIn->save();

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
