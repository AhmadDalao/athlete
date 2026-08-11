<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Queries\Coach\ScheduleQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScheduleExportController extends Controller
{
    public function __invoke(Request $request, ScheduleQuery $schedule): StreamedResponse
    {
        $rows = $schedule->build($request->user(), $request->only(['search', 'from', 'to', 'status', 'athlete_id']))
            ->orderBy('scheduled_for')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['id', 'date', 'time', 'timezone', 'athlete', 'athlete_email', 'program', 'workout', 'focus', 'status', 'execution_logs']);

            foreach ($rows as $workout) {
                $local = $workout->scheduled_for->timezone($workout->assignment->timezone);
                fputcsv($handle, [
                    $workout->id, $local->toDateString(), $local->format('H:i'),
                    $workout->assignment->timezone, $workout->athlete->name, $workout->athlete->email,
                    $workout->session->program->title, $workout->session->title,
                    $workout->session->focus, $workout->status, $workout->logs_count,
                ]);
            }

            fclose($handle);
        }, 'throughline-schedule-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
