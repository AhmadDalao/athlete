<?php

namespace App\Http\Controllers\Athlete;

use App\Http\Controllers\Controller;
use App\Models\AthleteFile;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use App\Support\AthleteAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FileStoreController extends Controller
{
    public function __invoke(Request $request, User $user, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless(AthleteAccess::canManageAthleteFiles($viewer, $user), 403);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(['medical', 'progress', 'training', 'media', 'admin'])],
            'visibility' => ['required', 'string', Rule::in([AthleteFile::VISIBILITY_COACH_ADMIN, AthleteFile::VISIBILITY_ATHLETE, AthleteFile::VISIBILITY_ADMIN])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $uploaded = $request->file('file');
        $extension = $uploaded->getClientOriginalExtension();
        $filename = Str::uuid()->toString().($extension ? ".{$extension}" : '');
        $storedPath = $uploaded->storeAs("athlete-files/{$user->id}", $filename);

        $file = AthleteFile::query()->create([
            'athlete_id' => $user->id,
            'uploaded_by_user_id' => $viewer->id,
            'category' => $validated['category'],
            'visibility' => $validated['visibility'],
            'status' => AthleteFile::STATUS_ACTIVE,
            'display_name' => $validated['display_name'] ?: pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME),
            'original_filename' => $uploaded->getClientOriginalName(),
            'stored_path' => $storedPath,
            'mime_type' => $uploaded->getMimeType(),
            'size_bytes' => $uploaded->getSize() ?: 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        $auditLogger->record(
            $request,
            'athlete_file.uploaded',
            $file,
            "{$viewer->name} uploaded {$file->display_name} for {$user->name}.",
            [
                'athlete_id' => $user->id,
                'category' => $file->category,
                'visibility' => $file->visibility,
            ],
        );

        return back()->with('success', 'Athlete file uploaded.');
    }
}
