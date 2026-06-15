<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleName;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Http\Requests\AthleteCheckInUpsertRequest;
use App\Models\AthleteCheckIn;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AthleteCheckInStoreController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(AthleteCheckInUpsertRequest $request): JsonResponse
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

        return response()->json([
            'data' => $this->checkInPayload($checkIn->fresh()),
            'meta' => $this->metaPayload(),
        ], 201);
    }
}
