<?php

namespace App\Http\Controllers\Athlete;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AthleteAccess;
use App\Support\AthleteProfileTables;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileExportController extends Controller
{
    public function __invoke(Request $request, User $user, string $section, AthleteProfileTables $profileTables): StreamedResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless(AthleteAccess::canViewAthlete($viewer, $user), 404);
        abort_unless(in_array($section, AthleteProfileTables::EXPORT_SECTIONS, true), 404);

        $payload = $profileTables->export($viewer, $user, $section, $profileTables->filters($request));

        return response()->streamDownload(function () use ($payload): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $payload['headers']);

            foreach ($payload['rows'] as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $payload['filename'], ['Content-Type' => 'text/csv']);
    }
}
