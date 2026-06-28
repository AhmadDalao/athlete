<?php

namespace App\Http\Controllers;

use App\Models\TrainingSession;
use App\Models\User;
use App\Services\AthleteWorkoutExecutionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AthleteWorkoutShowController extends Controller
{
    public function __invoke(Request $request, TrainingSession $trainingSession, AthleteWorkoutExecutionService $workouts): Response
    {
        /** @var User $athlete */
        $athlete = $request->user();

        return Inertia::render('athlete-app/workout', [
            'execution' => $workouts->payload($athlete, $trainingSession),
        ]);
    }
}
