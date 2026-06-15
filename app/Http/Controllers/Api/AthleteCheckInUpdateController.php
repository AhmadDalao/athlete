<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleName;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Http\Requests\AthleteCheckInUpsertRequest;
use App\Models\AthleteCheckIn;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AthleteCheckInUpdateController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(AthleteCheckInUpsertRequest $request, AthleteCheckIn $athleteCheckIn): JsonResponse
    {
        $this->manageableViewer($request->user(), $athleteCheckIn);

        $athleteCheckIn->fill($request->validated());
        $athleteCheckIn->save();

        return response()->json([
            'data' => $this->checkInPayload($athleteCheckIn->fresh()),
            'meta' => $this->metaPayload(),
        ]);
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
