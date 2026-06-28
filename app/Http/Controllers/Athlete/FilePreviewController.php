<?php

namespace App\Http\Controllers\Athlete;

use App\Http\Controllers\Controller;
use App\Models\AthleteFile;
use App\Models\User;
use App\Support\AthleteAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FilePreviewController extends Controller
{
    private const PREVIEWABLE_MIME_TYPES = [
        'application/pdf',
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __invoke(Request $request, AthleteFile $athleteFile): StreamedResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless(AthleteAccess::canViewAthleteFile($viewer, $athleteFile), 403);
        abort_unless(Storage::exists($athleteFile->stored_path), 404);

        $mimeType = $athleteFile->mime_type ?: Storage::mimeType($athleteFile->stored_path);

        abort_unless(in_array($mimeType, self::PREVIEWABLE_MIME_TYPES, true), 415);

        return Storage::response(
            $athleteFile->stored_path,
            $athleteFile->original_filename,
            ['Content-Type' => $mimeType],
            'inline',
        );
    }
}
