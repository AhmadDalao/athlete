<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Queries\Coach\AthleteProfileQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AthleteProfileExportController extends Controller
{
    public function __invoke(Request $request, User $athlete, string $section): StreamedResponse
    {
        $coachId = (int) $request->user()->getKey();
        abort_unless(AthleteProfileQuery::isAssignedTo($athlete, $coachId), 403);
        abort_unless(in_array($section, ['programs', 'schedule', 'workouts', 'progress', 'photos', 'records', 'notes'], true), 404);
        abort_unless($request->user()->can(match ($section) {
            'notes' => 'athletes.notes',
            'progress', 'photos', 'records' => 'progress.review',
            default => 'athletes.view',
        }), 403);

        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status', 'all');
        $from = $request->filled('from') ? (string) $request->string('from') : null;
        $to = $request->filled('to') ? (string) $request->string('to') : null;
        $category = (string) $request->string('category', 'all');
        $recordType = (string) $request->string('record_type', 'all');

        [$headings, $rows] = match ($section) {
            'programs' => [
                ['Program', 'Goal', 'Status', 'Starts', 'Ends', 'Scheduled workouts', 'Workout logs'],
                AthleteProfileQuery::assignments($coachId, $athlete->id, $search, $status)->get()->map(fn ($assignment) => [
                    $assignment->program->title,
                    $assignment->program->goal,
                    $assignment->status,
                    $assignment->starts_on?->toDateString(),
                    $assignment->ends_on?->toDateString(),
                    $assignment->scheduled_workouts_count,
                    $assignment->workout_logs_count,
                ]),
            ],
            'schedule' => [
                ['Date', 'Session', 'Program', 'Focus', 'Status', 'Workout log status'],
                AthleteProfileQuery::schedule($coachId, $athlete->id, $search, $status, $from, $to)->get()->map(fn ($workout) => [
                    $workout->scheduled_for?->toDateTimeString(),
                    $workout->session->title,
                    $workout->session->program->title,
                    $workout->session->focus,
                    $workout->status,
                    $workout->logs->first()?->status,
                ]),
            ],
            'workouts' => [
                ['Logged', 'Session', 'Program', 'Status', 'RPE', 'Duration minutes', 'Completed sets', 'Notes'],
                AthleteProfileQuery::workoutLogs($coachId, $athlete->id, $search, $status, $from, $to)->get()->map(fn ($log) => [
                    $log->created_at?->toDateTimeString(),
                    $log->session->title,
                    $log->session->program->title,
                    $log->status,
                    $log->rpe,
                    $log->duration_minutes,
                    $log->setLogs->whereNotNull('completed_at')->count(),
                    $log->notes,
                ]),
            ],
            'progress' => [
                ['Date', 'Weight', 'Calories', 'Protein', 'Hydration', 'Sleep quality', 'Soreness', 'Energy', 'Notes'],
                AthleteProfileQuery::progress($athlete->id, $search, $from, $to)->get()->map(fn ($entry) => [
                    $entry->logged_on?->toDateString(), $entry->weight, $entry->calories, $entry->protein,
                    $entry->hydration, $entry->sleep_quality, $entry->soreness, $entry->energy, $entry->notes,
                ]),
            ],
            'photos' => [
                ['Taken', 'Category', 'Visibility', 'Uploaded by', 'Notes', 'File'],
                AthleteProfileQuery::photos($athlete->id, $search, $category, $from, $to)->get()->map(fn ($photo) => [
                    $photo->taken_on?->toDateString(), $photo->category, $photo->visibility,
                    $photo->uploadedBy?->name, $photo->notes, $photo->path,
                ]),
            ],
            'records' => [
                ['Date', 'Exercise', 'Record type', 'Value', 'Unit'],
                AthleteProfileQuery::records($athlete->id, $search, $recordType)->get()->map(fn ($record) => [
                    $record->achieved_on?->toDateString(), $record->exercise_name, $record->record_type,
                    $record->value, $record->unit,
                ]),
            ],
            'notes' => [
                ['Created', 'Coach', 'Visibility', 'Pinned', 'Note'],
                AthleteProfileQuery::notes($coachId, $athlete->id, $search)->get()->map(fn ($note) => [
                    $note->created_at?->toDateTimeString(), $note->coach?->name, $note->visibility,
                    $note->is_pinned ? 'Yes' : 'No', $note->body,
                ]),
            ],
        };

        $filename = str($athlete->name)->slug().'-'.$section.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headings, $rows): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, $headings);
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
