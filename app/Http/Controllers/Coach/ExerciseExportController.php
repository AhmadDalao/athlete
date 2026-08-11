<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Queries\Coach\ExerciseQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExerciseExportController extends Controller
{
    public function __invoke(Request $request, ExerciseQuery $exercises): StreamedResponse
    {
        $rows = $exercises->build($request->user(), $request->only(['search', 'status', 'ownership']))
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['id', 'name', 'section', 'movement_type', 'sets', 'reps', 'load', 'unit', 'rest_seconds', 'owner', 'shared', 'media_url', 'status']);

            foreach ($rows as $exercise) {
                fputcsv($handle, [
                    $exercise->id, $exercise->name, $exercise->section, $exercise->movement_type,
                    $exercise->default_sets, $exercise->default_reps, $exercise->default_load,
                    $exercise->unit, $exercise->default_rest_seconds, $exercise->owner?->name,
                    $exercise->is_shared ? 'yes' : 'no', $exercise->media_url, $exercise->status,
                ]);
            }

            fclose($handle);
        }, 'throughline-exercises-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
