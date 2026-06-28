<?php

namespace App\Http\Controllers\Athlete;

use App\Http\Controllers\Controller;
use App\Models\AthleteFile;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use App\Support\AthleteAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FileArchiveController extends Controller
{
    public function __invoke(Request $request, AthleteFile $athleteFile, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');
        $athleteFile->loadMissing('athlete');

        abort_unless(AthleteAccess::canManageAthleteFiles($viewer, $athleteFile->athlete), 403);

        $athleteFile->forceFill([
            'status' => AthleteFile::STATUS_ARCHIVED,
            'archived_at' => now(),
            'archived_by_user_id' => $viewer->id,
        ])->save();

        $auditLogger->record(
            $request,
            'athlete_file.archived',
            $athleteFile,
            "{$viewer->name} archived athlete file {$athleteFile->display_name}.",
            ['athlete_id' => $athleteFile->athlete_id],
        );

        return back()->with('success', 'Athlete file archived.');
    }
}
