<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Support\TrainingAppPresenter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AthleteProgramShowController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(Request $request, TrainingProgram $trainingProgram, TrainingAppPresenter $presenter): Response|RedirectResponse
    {
        /** @var User $athlete */
        $athlete = $request->user()->loadMissing('roles');

        if (! $athlete->hasRole(RoleName::Athlete)) {
            return redirect()->to($athlete->landingPath());
        }

        $trainingProgram->load(['coach', 'athlete', 'sessions.workoutLog.setLogs']);

        if ($trainingProgram->athlete_id !== $athlete->id) {
            throw new AuthorizationException('This program is not assigned to this athlete.');
        }

        return Inertia::render('athlete-app/program', [
            'viewer' => [
                'id' => $athlete->id,
                'name' => $athlete->name,
                'email' => $athlete->email,
            ],
            'program' => $presenter->program($trainingProgram, includeSessions: true),
        ]);
    }
}
