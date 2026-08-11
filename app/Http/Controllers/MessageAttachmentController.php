<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageAttachmentController extends Controller
{
    public function __invoke(Request $request, MediaAsset $media): StreamedResponse
    {
        abort_unless($media->attachable_type === Message::class, 404);
        $message = Message::withTrashed()->findOrFail($media->attachable_id);
        abort_unless($message->conversation->participants()->whereKey($request->user()->id)->exists(), 403);
        abort_unless($media->path && Storage::disk($media->disk)->exists($media->path), 404);

        return Storage::disk($media->disk)->download($media->path, $media->original_name ?: basename($media->path));
    }
}
