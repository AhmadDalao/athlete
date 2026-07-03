<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BuildsMobileAppPayloads;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Support\TrainingAppPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppProgramShowController extends Controller
{
    use BuildsMobileAppPayloads;
    use FormatsApiPayloads;

    public function __invoke(Request $request, TrainingProgram $trainingProgram, TrainingAppPresenter $presenter): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('roles');
        $this->abortUnlessMobileUser($user);

        $trainingProgram->loadMissing([
            'coach',
            'athlete',
            'sessions.workoutLog',
        ]);

        abort_unless($this->canOpenMobileProgram($user, $trainingProgram), 403);

        return response()->json([
            'data' => [
                'program' => $presenter->program($trainingProgram, includeSessions: true),
            ],
            'meta' => $this->metaPayload(),
        ]);
    }
}
