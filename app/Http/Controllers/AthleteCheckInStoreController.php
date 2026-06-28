<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\AthleteCheckInUpsertRequest;
use App\Models\AthleteCheckIn;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\RedirectResponse;

class AthleteCheckInStoreController extends Controller
{
    public function __invoke(AthleteCheckInUpsertRequest $request, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()?->loadMissing('roles');

        abort_unless($viewer && $viewer->hasRole(RoleName::Athlete), 403);

        $checkIn = AthleteCheckIn::query()->updateOrCreate(
            [
                'user_id' => $viewer->id,
                'logged_date' => $request->validated('logged_date'),
            ],
            $request->safe()->except('logged_date') + [
                'user_id' => $viewer->id,
                'logged_date' => $request->validated('logged_date'),
            ],
        );

        $auditLogger->record(
            $request,
            'athlete_check_in.saved',
            $checkIn,
            "{$viewer->name} saved progress check-in for {$checkIn->logged_date?->toDateString()}.",
            [
                'logged_date' => $checkIn->logged_date?->toDateString(),
                'weight_kg' => $checkIn->weight_kg,
                'water_liters' => $checkIn->water_liters,
            ],
        );

        return to_route('progress.index');
    }
}
