<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Queries\Admin\OperationsReportQuery;
use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __invoke(Request $request, OrganizationContext $context): StreamedResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:160'],
        ]);
        $organization = $context->organization();
        abort_unless($organization, 409, 'Select an organization before exporting reports.');

        $coaches = OperationsReportQuery::coaches(
            $organization->id,
            $data['from'],
            $data['to'],
            trim((string) ($data['search'] ?? '')),
        )->get();

        return response()->streamDownload(function () use ($coaches): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['Coach', 'Email', 'Athletes', 'Active programs', 'Scheduled', 'Completed', 'Partial', 'Missed', 'Overdue', 'Completion rate']);
            foreach ($coaches as $coach) {
                $rate = $coach->scheduled_count > 0 ? round(($coach->completed_count / $coach->scheduled_count) * 100, 1) : 0;
                fputcsv($output, [
                    $coach->name,
                    $coach->email,
                    $coach->athlete_count,
                    $coach->active_program_count,
                    $coach->scheduled_count,
                    $coach->completed_count,
                    $coach->partial_count,
                    $coach->missed_count,
                    $coach->overdue_count,
                    $rate.'%',
                ]);
            }
            fclose($output);
        }, 'throughline-operations-'.$data['from'].'-'.$data['to'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
