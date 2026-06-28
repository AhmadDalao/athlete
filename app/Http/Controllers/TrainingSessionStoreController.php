<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Models\TrainingProgram;
use App\Services\PlatformAuditLogger;
use App\Support\TrainingExerciseParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainingSessionStoreController extends Controller
{
    public function __invoke(Request $request, TrainingProgram $trainingProgram, TrainingExerciseParser $exerciseParser, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->hasRole(RoleName::Coach) && $trainingProgram->coach_id === $user->id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'scheduled_date' => ['required', 'date'],
            'focus' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'exercises' => ['nullable', 'string'],
        ]);

        $session = $trainingProgram->sessions()->create([
            'title' => $validated['title'],
            'scheduled_date' => $validated['scheduled_date'],
            'focus' => $validated['focus'],
            'instructions' => $validated['instructions'],
            'video_url' => $validated['video_url'] ?? null,
            'exercises' => $exerciseParser->parse($validated['exercises'] ?? null),
            'sort_order' => ((int) $trainingProgram->sessions()->max('sort_order')) + 1,
        ]);

        $trainingProgram->loadMissing(['coach', 'athlete']);

        $auditLogger->record(
            $request,
            'training_session.created',
            $session,
            "{$user->name} added {$session->title} to {$trainingProgram->title} for {$trainingProgram->athlete->name}.",
            [
                'training_program_id' => $trainingProgram->id,
                'scheduled_date' => $session->scheduled_date?->toDateString(),
            ],
        );

        return redirect()->route('training.index');
    }
}
