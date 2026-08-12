<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\Message;
use App\Models\ProgressPhoto;
use App\Queries\Coach\AthleteProfileQuery;
use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function progressPhoto(Request $request, ProgressPhoto $photo): StreamedResponse
    {
        $user = $request->user();
        abort_unless($photo->organization_id === app(OrganizationContext::class)->id(), 403);
        $isAthlete = $photo->athlete_id === $user->id
            && ($photo->uploaded_by === $user->id || $photo->visibility !== 'private');
        $isCoach = AthleteProfileQuery::isAssignedTo($photo->athlete, (int) $user->id)
            && ($photo->visibility !== 'private' || $photo->uploaded_by === $user->id);

        abort_unless($isAthlete || $isCoach, 403);
        abort_unless(Storage::disk('public')->exists($photo->path), 404);

        return Storage::disk('public')->response($photo->path, basename($photo->path), [
            'Cache-Control' => 'private, max-age=300',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function messageAttachment(Request $request, MediaAsset $media): StreamedResponse
    {
        abort_unless($media->organization_id === app(OrganizationContext::class)->id(), 403);
        abort_unless($media->attachable_type === Message::class, 404);
        $message = Message::withTrashed()->findOrFail($media->attachable_id);
        abort_unless($message->conversation->participants()->whereKey($request->user()->id)->exists(), 403);
        abort_unless($media->path && Storage::disk($media->disk)->exists($media->path), 404);

        $disposition = str_starts_with((string) $media->mime_type, 'image/') ? 'inline' : 'attachment';

        return Storage::disk($media->disk)->response($media->path, $media->original_name ?: basename($media->path), [
            'Cache-Control' => 'private, max-age=300',
            'Content-Disposition' => $disposition,
        ]);
    }
}
