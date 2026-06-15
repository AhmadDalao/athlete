<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\AthleteCheckInUpsertRequest;
use App\Models\AthleteCheckIn;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AthleteCheckInStoreController extends Controller
{
    public function __invoke(AthleteCheckInUpsertRequest $request): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()?->loadMissing('roles');

        abort_unless($viewer && $viewer->hasRole(RoleName::Athlete), 403);

        AthleteCheckIn::query()->updateOrCreate(
            [
                'user_id' => $viewer->id,
                'logged_date' => $request->validated('logged_date'),
            ],
            $request->safe()->except('logged_date') + [
                'user_id' => $viewer->id,
                'logged_date' => $request->validated('logged_date'),
            ],
        );

        return to_route('progress.index');
    }
}
