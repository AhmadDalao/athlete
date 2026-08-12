<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProgressPhotoResource;
use App\Models\ProgressPhoto;
use App\Services\AuditLogger;
use App\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProgressPhotoController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $photos = ProgressPhoto::query()
            ->where('athlete_id', $request->user()->id)
            ->where(fn ($query) => $query
                ->where('uploaded_by', $request->user()->id)
                ->orWhere('visibility', '!=', 'private'))
            ->with('uploadedBy')
            ->latest('taken_on')
            ->paginate($this->pageSize($request->query('per_page')));

        return $this->paginated($photos, ProgressPhotoResource::class);
    }

    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate($this->rules());
        $athlete = $request->user();
        $path = $request->file('photo')->store("progress-photos/{$athlete->id}", 'public');
        $photo = ProgressPhoto::create([
            'organization_id' => $athlete->current_organization_id,
            'athlete_id' => $athlete->id,
            'uploaded_by' => $athlete->id,
            'path' => $path,
            'category' => $data['category'],
            'visibility' => 'coaches',
            'taken_on' => $data['taken_on'],
            'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
        ])->load('uploadedBy');
        $audit->record('progress_photo.uploaded', 'progress_photo', $photo->id, 'Uploaded an athlete progress photo.');

        return $this->success(new ProgressPhotoResource($photo), status: 201);
    }

    public function destroy(Request $request, ProgressPhoto $photo, AuditLogger $audit): JsonResponse
    {
        abort_unless($photo->organization_id === app(OrganizationContext::class)->id(), 403);
        abort_unless($photo->athlete_id === $request->user()->id && $photo->uploaded_by === $request->user()->id, 403);
        Storage::disk('public')->delete(array_filter([$photo->path, $photo->thumbnail_path]));
        $photoId = $photo->id;
        $photo->delete();
        $audit->record('progress_photo.deleted', 'progress_photo', $photoId, 'Deleted an athlete progress photo.');

        return $this->success(['id' => $photoId, 'deleted' => true]);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'taken_on' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', Rule::in(['progress', 'front', 'side', 'back', 'other'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
