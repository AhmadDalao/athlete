<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Models\TrainingProgram;
use App\Support\TrainingExerciseParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainingSessionStoreController extends Controller
{
    public function __invoke(Request $request, TrainingProgram $trainingProgram, TrainingExerciseParser $exerciseParser): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->hasRole(RoleName::Coach) && $trainingProgram->coach_id === $user->id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'scheduled_date' => ['required', 'date'],
            'focus' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'exercises' => ['nullable', 'string'],
        ]);

        $trainingProgram->sessions()->create([
            'title' => $validated['title'],
            'scheduled_date' => $validated['scheduled_date'],
            'focus' => $validated['focus'],
            'instructions' => $validated['instructions'],
            'exercises' => $exerciseParser->parse($validated['exercises'] ?? null),
            'sort_order' => ((int) $trainingProgram->sessions()->max('sort_order')) + 1,
        ]);

        return redirect()->route('training.index');
    }
}
