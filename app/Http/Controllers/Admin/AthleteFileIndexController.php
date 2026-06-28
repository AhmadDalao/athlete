<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AthleteFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AthleteFileIndexController extends Controller
{
    public function __invoke(Request $request): Response|StreamedResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless($viewer->hasPermission('athlete.files.view'), 403);

        $filters = $this->filters($request);
        $query = $this->filteredQuery($filters);

        if ($request->boolean('export')) {
            return $this->export((clone $query)->latest()->limit(5000)->get(), 'throughline-athlete-files.csv');
        }

        $files = (clone $query)
            ->with(['athlete', 'uploadedBy'])
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (AthleteFile $file): array => $this->row($file));

        return Inertia::render('admin/files', [
            'filters' => $filters,
            'summary' => [
                'total' => (clone $query)->count(),
                'active' => (clone $query)->where('status', AthleteFile::STATUS_ACTIVE)->count(),
                'archived' => (clone $query)->where('status', AthleteFile::STATUS_ARCHIVED)->count(),
                'athletesWithFiles' => (clone $query)->distinct('athlete_id')->count('athlete_id'),
            ],
            'files' => $files,
            'athleteOptions' => $this->athleteOptions(),
            'fileOptions' => [
                'categories' => [
                    ['value' => 'medical', 'label' => 'Medical'],
                    ['value' => 'progress', 'label' => 'Progress'],
                    ['value' => 'training', 'label' => 'Training'],
                    ['value' => 'media', 'label' => 'Media'],
                    ['value' => 'admin', 'label' => 'Admin'],
                ],
                'visibilities' => [
                    ['value' => AthleteFile::VISIBILITY_COACH_ADMIN, 'label' => 'Coach and admin'],
                    ['value' => AthleteFile::VISIBILITY_ATHLETE, 'label' => 'Athlete visible'],
                    ['value' => AthleteFile::VISIBILITY_ADMIN, 'label' => 'Admin only'],
                ],
                'statuses' => [
                    ['value' => AthleteFile::STATUS_ACTIVE, 'label' => 'Active'],
                    ['value' => AthleteFile::STATUS_ARCHIVED, 'label' => 'Archived'],
                ],
            ],
        ]);
    }

    /**
     * @return array{q:string|null,status:string|null,category:string|null,visibility:string|null,athlete_id:string|null}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => $request->string('q')->trim()->value() ?: null,
            'status' => $request->string('status')->trim()->value() ?: null,
            'category' => $request->string('category')->trim()->value() ?: null,
            'visibility' => $request->string('visibility')->trim()->value() ?: null,
            'athlete_id' => $request->string('athlete_id')->trim()->value() ?: null,
        ];
    }

    /**
     * @param  array{q:string|null,status:string|null,category:string|null,visibility:string|null,athlete_id:string|null}  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        return AthleteFile::query()
            ->with(['athlete', 'uploadedBy'])
            ->when($filters['q'], function (Builder $query, string $value): void {
                $query->where(function (Builder $nested) use ($value): void {
                    $nested
                        ->where('display_name', 'like', "%{$value}%")
                        ->orWhere('original_filename', 'like', "%{$value}%")
                        ->orWhere('notes', 'like', "%{$value}%")
                        ->orWhereHas('athlete', function (Builder $athleteQuery) use ($value): void {
                            $athleteQuery
                                ->where('name', 'like', "%{$value}%")
                                ->orWhere('email', 'like', "%{$value}%");
                        });
                });
            })
            ->when($filters['status'], fn (Builder $query, string $value) => $query->where('status', $value))
            ->when($filters['category'], fn (Builder $query, string $value) => $query->where('category', $value))
            ->when($filters['visibility'], fn (Builder $query, string $value) => $query->where('visibility', $value))
            ->when($filters['athlete_id'], fn (Builder $query, string $value) => $query->where('athlete_id', (int) $value));
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    private function athleteOptions(): array
    {
        return User::query()
            ->role('athlete')
            ->orderBy('name')
            ->get()
            ->map(fn (User $athlete): array => [
                'value' => (string) $athlete->id,
                'label' => "{$athlete->name} ({$athlete->email})",
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(AthleteFile $file): array
    {
        return [
            'id' => $file->id,
            'athlete' => [
                'id' => $file->athlete?->id,
                'name' => $file->athlete?->name,
                'email' => $file->athlete?->email,
            ],
            'displayName' => $file->display_name,
            'originalFilename' => $file->original_filename,
            'category' => $file->category,
            'visibility' => $file->visibility,
            'status' => $file->status,
            'mimeType' => $file->mime_type,
            'sizeBytes' => $file->size_bytes,
            'notes' => $file->notes,
            'uploadedBy' => $file->uploadedBy?->name,
            'createdAt' => $file->created_at?->toDateTimeString(),
            'archivedAt' => $file->archived_at?->toDateTimeString(),
            'previewable' => in_array($file->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'], true),
        ];
    }

    private function export($files, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($files): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Created', 'Status', 'Athlete', 'Email', 'Name', 'Original filename', 'Category', 'Visibility', 'Size bytes', 'Uploaded by', 'Notes']);

            foreach ($files as $file) {
                $row = $this->row($file);
                fputcsv($handle, [
                    $row['createdAt'],
                    $row['status'],
                    $row['athlete']['name'],
                    $row['athlete']['email'],
                    $row['displayName'],
                    $row['originalFilename'],
                    $row['category'],
                    $row['visibility'],
                    $row['sizeBytes'],
                    $row['uploadedBy'],
                    $row['notes'],
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
