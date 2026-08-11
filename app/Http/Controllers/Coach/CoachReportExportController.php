<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Queries\Coach\CoachReportQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CoachReportExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:160'],
        ]);
        $athletes = CoachReportQuery::athletes(
            (int) $request->user()->getKey(),
            $data['from'],
            $data['to'],
            trim((string) ($data['search'] ?? '')),
        )->get();

        return response()->streamDownload(function () use ($athletes): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['Athlete', 'Email', 'Scheduled', 'Completed', 'Partial', 'Missed', 'Completion rate', 'Average RPE', 'Duration minutes']);
            foreach ($athletes as $athlete) {
                $rate = $athlete->scheduled_count > 0 ? round(($athlete->completed_count / $athlete->scheduled_count) * 100, 1) : 0;
                fputcsv($output, [
                    $athlete->name,
                    $athlete->email,
                    $athlete->scheduled_count,
                    $athlete->completed_count,
                    $athlete->partial_count,
                    $athlete->missed_count,
                    $rate.'%',
                    round((float) ($athlete->average_rpe ?? 0), 1),
                    (int) ($athlete->duration_minutes ?? 0),
                ]);
            }
            fclose($output);
        }, 'coach-adherence-'.$data['from'].'-'.$data['to'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
