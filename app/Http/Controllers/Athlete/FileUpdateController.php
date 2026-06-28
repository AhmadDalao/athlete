<?php

namespace App\Http\Controllers\Athlete;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\AthleteFile;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use App\Support\AthleteAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FileUpdateController extends Controller
{
    public function __invoke(Request $request, AthleteFile $athleteFile, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');
        $athleteFile->loadMissing('athlete');

        abort_unless(AthleteAccess::canManageAthleteFiles($viewer, $athleteFile->athlete), 403);

        $validated = $request->validate([
            'athlete_id' => ['nullable', 'integer', 'exists:users,id'],
            'display_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(['medical', 'progress', 'training', 'media', 'admin'])],
            'visibility' => ['required', 'string', Rule::in([AthleteFile::VISIBILITY_COACH_ADMIN, AthleteFile::VISIBILITY_ATHLETE, AthleteFile::VISIBILITY_ADMIN])],
            'status' => ['required', 'string', Rule::in([AthleteFile::STATUS_ACTIVE, AthleteFile::STATUS_ARCHIVED])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $targetAthleteId = (int) ($validated['athlete_id'] ?? $athleteFile->athlete_id);

        if ($targetAthleteId !== $athleteFile->athlete_id) {
            abort_unless($viewer->hasRole(RoleName::Admin) || $viewer->hasRole(RoleName::Owner), 403);
            abort_unless(User::query()->role(RoleName::Athlete)->whereKey($targetAthleteId)->exists(), 422);
        }

        $athleteFile->fill([
            'athlete_id' => $targetAthleteId,
            'display_name' => $validated['display_name'],
            'category' => $validated['category'],
            'visibility' => $validated['visibility'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'archived_at' => $validated['status'] === AthleteFile::STATUS_ARCHIVED ? ($athleteFile->archived_at ?? now()) : null,
            'archived_by_user_id' => $validated['status'] === AthleteFile::STATUS_ARCHIVED ? ($athleteFile->archived_by_user_id ?? $viewer->id) : null,
        ])->save();

        $auditLogger->record(
            $request,
            'athlete_file.updated',
            $athleteFile,
            "{$viewer->name} updated athlete file {$athleteFile->display_name}.",
            [
                'athlete_id' => $athleteFile->athlete_id,
                'category' => $athleteFile->category,
                'visibility' => $athleteFile->visibility,
                'status' => $athleteFile->status,
            ],
        );

        return back()->with('success', 'Athlete file updated.');
    }
}
