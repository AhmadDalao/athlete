<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\ProgressPhoto;
use App\Models\User;
use App\Queries\Coach\AthleteProfileQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgressPhotoController extends Controller
{
    public function __invoke(Request $request, User $athlete, ProgressPhoto $photo): StreamedResponse
    {
        abort_unless(AthleteProfileQuery::isAssignedTo($athlete, (int) $request->user()->getKey()), 403);
        abort_unless($photo->athlete_id === $athlete->id, 404);
        abort_unless(Storage::disk('public')->exists($photo->path), 404);

        return Storage::disk('public')->response($photo->path, basename($photo->path), [
            'Cache-Control' => 'private, max-age=300',
            'Content-Disposition' => 'inline',
        ]);
    }
}
