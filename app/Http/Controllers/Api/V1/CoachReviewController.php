<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProgressPhotoResource;
use App\Models\CoachNote;
use App\Models\ProgressPhoto;
use App\Models\User;
use App\Queries\Coach\AthleteProfileQuery;
use App\Services\AuditLogger;
use App\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CoachReviewController extends Controller
{
    use RespondsWithApi;

    public function storeNote(Request $request, User $athlete, AuditLogger $audit): JsonResponse
    {
        $this->authorizeAthlete($request, $athlete);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'visibility' => ['required', Rule::in(['private', 'organization'])],
            'is_pinned' => ['sometimes', 'boolean'],
        ]);
        $note = CoachNote::create([
            'organization_id' => $request->user()->current_organization_id,
            'coach_id' => $request->user()->id,
            'athlete_id' => $athlete->id,
            'body' => trim($data['body']),
            'visibility' => $data['visibility'],
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
        ])->load('coach');
        $audit->record('coach_note.created', 'coach_note', $note->id, "Added a coach note for {$athlete->name}.");

        return $this->success($this->noteData($note, $request), status: 201);
    }

    public function updateNote(Request $request, User $athlete, CoachNote $note, AuditLogger $audit): JsonResponse
    {
        $this->authorizeAthlete($request, $athlete);
        abort_unless($note->organization_id === app(OrganizationContext::class)->id(), 403);
        abort_unless($note->athlete_id === $athlete->id && $note->coach_id === $request->user()->id, 403);
        $data = $request->validate([
            'body' => ['sometimes', 'required', 'string', 'max:5000'],
            'visibility' => ['sometimes', Rule::in(['private', 'organization'])],
            'is_pinned' => ['sometimes', 'boolean'],
        ]);
        if (array_key_exists('body', $data)) {
            $data['body'] = trim($data['body']);
        }
        $note->update($data);
        $audit->record('coach_note.updated', 'coach_note', $note->id, "Updated a coach note for {$athlete->name}.");

        return $this->success($this->noteData($note->refresh()->load('coach'), $request));
    }

    public function destroyNote(Request $request, User $athlete, CoachNote $note, AuditLogger $audit): JsonResponse
    {
        $this->authorizeAthlete($request, $athlete);
        abort_unless($note->organization_id === app(OrganizationContext::class)->id(), 403);
        abort_unless($note->athlete_id === $athlete->id && $note->coach_id === $request->user()->id, 403);
        $noteId = $note->id;
        $note->delete();
        $audit->record('coach_note.deleted', 'coach_note', $noteId, "Deleted a coach note for {$athlete->name}.");

        return $this->success(['id' => $noteId, 'deleted' => true]);
    }

    public function storePhoto(Request $request, User $athlete, AuditLogger $audit): JsonResponse
    {
        $this->authorizeAthlete($request, $athlete);
        $data = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'taken_on' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', Rule::in(['progress', 'front', 'side', 'back', 'other'])],
            'visibility' => ['required', Rule::in(['private', 'coaches', 'athlete'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $path = $request->file('photo')->store("progress-photos/{$athlete->id}", 'public');
        $photo = ProgressPhoto::create([
            'organization_id' => $request->user()->current_organization_id,
            'athlete_id' => $athlete->id,
            'uploaded_by' => $request->user()->id,
            'path' => $path,
            'category' => $data['category'],
            'visibility' => $data['visibility'],
            'taken_on' => $data['taken_on'],
            'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
        ])->load('uploadedBy');
        $audit->record('progress_photo.uploaded', 'progress_photo', $photo->id, "Uploaded a progress photo for {$athlete->name}.");

        return $this->success(new ProgressPhotoResource($photo), status: 201);
    }

    public function destroyPhoto(Request $request, User $athlete, ProgressPhoto $photo, AuditLogger $audit): JsonResponse
    {
        $this->authorizeAthlete($request, $athlete);
        abort_unless($photo->organization_id === app(OrganizationContext::class)->id(), 403);
        abort_unless($photo->athlete_id === $athlete->id && $photo->uploaded_by === $request->user()->id, 403);
        Storage::disk('public')->delete(array_filter([$photo->path, $photo->thumbnail_path]));
        $photoId = $photo->id;
        $photo->delete();
        $audit->record('progress_photo.deleted', 'progress_photo', $photoId, "Deleted a coach-uploaded photo for {$athlete->name}.");

        return $this->success(['id' => $photoId, 'deleted' => true]);
    }

    private function authorizeAthlete(Request $request, User $athlete): void
    {
        abort_unless(AthleteProfileQuery::isAssignedTo($athlete, (int) $request->user()->id), 403);
    }

    /** @return array<string, mixed> */
    private function noteData(CoachNote $note, Request $request): array
    {
        return [
            'id' => $note->id,
            'body' => $note->body,
            'visibility' => $note->visibility,
            'is_pinned' => $note->is_pinned,
            'coach' => $note->coach?->only(['id', 'name', 'email']),
            'can_edit' => $note->coach_id === $request->user()->id,
            'created_at' => $note->created_at?->toIso8601String(),
            'updated_at' => $note->updated_at?->toIso8601String(),
        ];
    }
}
