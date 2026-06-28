<?php

namespace App\Http\Controllers\Athlete;

use App\Http\Controllers\Controller;
use App\Models\AthleteFile;
use App\Models\User;
use App\Support\AthleteAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDownloadController extends Controller
{
    public function __invoke(Request $request, AthleteFile $athleteFile): StreamedResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless(AthleteAccess::canViewAthleteFile($viewer, $athleteFile), 403);
        abort_unless(Storage::exists($athleteFile->stored_path), 404);

        return Storage::download($athleteFile->stored_path, $athleteFile->original_filename);
    }
}
