<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Queries\Coach\ProgramQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgramExportController extends Controller
{
    public function __invoke(Request $request, ProgramQuery $programs): StreamedResponse
    {
        $rows = $programs->build($request->user(), $request->only(['search', 'status', 'kind']))
            ->orderBy('title')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['id', 'kind', 'athlete', 'title', 'goal', 'status', 'visibility', 'estimated_weeks', 'sessions', 'assignments', 'completed_workouts', 'total_workouts', 'completion_percent', 'updated_at']);

            foreach ($rows as $program) {
                $completion = $program->completionStats();
                fputcsv($handle, [
                    $program->id, $program->is_template ? 'preset' : 'athlete_plan', $program->athlete?->name,
                    $program->title, $program->goal, $program->status,
                    $program->visibility, $program->estimated_weeks, $program->sessions_count,
                    $program->is_template ? $program->athlete_plans_count : $program->assignments_count,
                    $completion['completed'], $completion['total'],
                    $completion['percent'], $program->updated_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'throughline-programs-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
