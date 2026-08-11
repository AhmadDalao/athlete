<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ThemePreferenceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['required', Rule::in(['system', 'dark', 'light'])],
        ]);

        $request->user()->forceFill(['theme_preference' => $validated['theme']])->save();

        return response()->json([
            'data' => ['theme_preference' => $validated['theme']],
        ]);
    }
}
