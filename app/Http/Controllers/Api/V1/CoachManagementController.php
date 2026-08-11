<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Models\AthleteInvitation;
use App\Models\Exercise;
use App\Models\PlatformSetting;
use App\Models\ProgramAssignment;
use App\Models\ScheduledWorkout;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\InvitationDeliveryService;
use App\Services\ProgramScheduleService;
use App\Services\TrainingProgramManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CoachManagementController extends Controller
{
    use RespondsWithApi;

    public function storeProgram(Request $request, AuditLogger $audit): JsonResponse
    {
        $coach = $request->user();
        $data = $request->validate($this->programRules());

        $program = DB::transaction(function () use ($coach, $data, $audit): TrainingProgram {
            $program = TrainingProgram::create($this->programPayload($data) + [
                'organization_id' => $coach->current_organization_id,
                'coach_id' => $coach->id,
                'athlete_id' => null,
                'is_template' => true,
            ]);
            $program->phases()->create([
                'organization_id' => $program->organization_id,
                'title' => 'Foundation',
                'sort_order' => 1,
                'duration_weeks' => $program->estimated_weeks,
            ]);
            $audit->record('program.created', 'training_program', $program->id, "Created reusable program {$program->title}.");

            return $program;
        });

        return $this->success($this->programData($program), status: 201);
    }

    public function program(Request $request, TrainingProgram $program): JsonResponse
    {
        $this->authorizeProgram($request, $program);

        return $this->success($this->programData($program));
    }

    public function updateProgram(
        Request $request,
        TrainingProgram $program,
        TrainingProgramManager $manager,
    ): JsonResponse {
        $this->authorizeProgram($request, $program);
        $program = $manager->updateProgram($program, $this->programPayload($request->validate($this->programRules())));

        return $this->success($this->programData($program));
    }

    public function storePhase(Request $request, TrainingProgram $program, AuditLogger $audit): JsonResponse
    {
        $this->authorizeProgram($request, $program);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_weeks' => ['nullable', 'integer', 'min:1', 'max:52'],
        ]);
        $phase = $program->phases()->create($data + [
            'organization_id' => $program->organization_id,
            'sort_order' => ((int) $program->phases()->max('sort_order')) + 1,
        ]);
        $audit->record('program_phase.created', 'program_phase', $phase->id, "Created phase {$phase->title}.");

        return $this->success($phase->only(['id', 'title', 'description', 'duration_weeks', 'sort_order']), status: 201);
    }

    public function storeSession(
        Request $request,
        TrainingProgram $program,
        TrainingProgramManager $manager,
    ): JsonResponse {
        $this->authorizeProgram($request, $program);
        $payload = $this->validatedSession($request, $program);
        $session = $manager->createSession($program, $payload);

        return $this->success($this->sessionData($session), status: 201);
    }

    public function updateSession(
        Request $request,
        TrainingProgram $program,
        TrainingSession $session,
        TrainingProgramManager $manager,
    ): JsonResponse {
        $this->authorizeProgram($request, $program);
        abort_unless($session->training_program_id === $program->id, 404);
        $session = $manager->updateSession($program, $session->id, $this->validatedSession($request, $program));

        return $this->success($this->sessionData($session));
    }

    public function assignProgram(
        Request $request,
        TrainingProgram $program,
        ProgramScheduleService $schedule,
    ): JsonResponse {
        $this->authorizeProgram($request, $program);
        $data = $request->validate([
            'athlete_id' => ['required', 'integer', 'exists:users,id'],
            'starts_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $athlete = $this->athleteQuery($request)
            ->whereKey($data['athlete_id'])
            ->firstOrFail();
        $assignment = $schedule->assign($program, $athlete, $request->user(), $data['starts_on'], $data['notes'] ?? null);

        return $this->success($this->assignmentData($assignment), status: 201);
    }

    public function assignmentStatus(
        Request $request,
        ProgramAssignment $assignment,
        ProgramScheduleService $schedule,
    ): JsonResponse {
        $assignment->loadMissing('program');
        $this->authorizeProgram($request, $assignment->program);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'paused', 'completed', 'cancelled'])]]);
        $schedule->setAssignmentStatus($assignment, $data['status']);

        return $this->success($this->assignmentData($assignment->refresh()));
    }

    public function reschedule(
        Request $request,
        ScheduledWorkout $workout,
        ProgramScheduleService $schedule,
    ): JsonResponse {
        abort_unless($workout->coach_id === $request->user()->id, 403);
        $data = $request->validate(['scheduled_for' => ['required', 'date']]);
        $workout = $schedule->reschedule($workout, $data['scheduled_for']);

        return $this->success(['id' => $workout->id, 'scheduled_for' => $workout->scheduled_for?->toIso8601String()]);
    }

    public function exercises(Request $request): JsonResponse
    {
        $exercises = Exercise::query()
            ->where('status', 'active')
            ->where(fn (Builder $query) => $query->where('owner_id', $request->user()->id)->orWhere('is_shared', true))
            ->when($request->string('search')->isNotEmpty(), fn (Builder $query) => $query->where('name', 'like', '%'.trim((string) $request->string('search')).'%'))
            ->orderBy('name')
            ->paginate($this->pageSize($request->query('per_page')));

        return $this->success(collect($exercises->items())->map(fn (Exercise $exercise): array => $this->exerciseData($exercise)), [
            'current_page' => $exercises->currentPage(),
            'last_page' => $exercises->lastPage(),
            'per_page' => $exercises->perPage(),
            'total' => $exercises->total(),
        ], [
            'previous' => $exercises->previousPageUrl(),
            'next' => $exercises->nextPageUrl(),
        ]);
    }

    public function invitations(Request $request): JsonResponse
    {
        $invitations = AthleteInvitation::query()
            ->where('coach_id', $request->user()->id)
            ->when($request->string('status')->isNotEmpty(), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate($this->pageSize($request->query('per_page')));

        return $this->success(collect($invitations->items())->map(fn (AthleteInvitation $invite): array => $this->invitationData($invite)), [
            'current_page' => $invitations->currentPage(),
            'last_page' => $invitations->lastPage(),
            'per_page' => $invitations->perPage(),
            'total' => $invitations->total(),
        ], [
            'previous' => $invitations->previousPageUrl(),
            'next' => $invitations->nextPageUrl(),
        ]);
    }

    public function storeInvitation(
        Request $request,
        InvitationDeliveryService $delivery,
        AuditLogger $audit,
    ): JsonResponse {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
        ]);
        $invite = AthleteInvitation::create([
            'organization_id' => $request->user()->current_organization_id,
            'coach_id' => $request->user()->id,
            'name' => $data['name'] ?? null,
            'email' => Str::lower($data['email']),
            'token' => Str::random(48),
            'status' => 'pending',
            'expires_at' => now()->addDays((int) PlatformSetting::get('invite_expiry_days', '7')),
        ]);
        $sent = $delivery->send($invite);
        $audit->record('invite.created', 'athlete_invitation', $invite->id, "Invited {$invite->email}. Email ".($sent ? 'sent' : 'failed').'.');

        return $this->success($this->invitationData($invite) + ['email_sent' => $sent], status: 201);
    }

    public function resendInvitation(
        Request $request,
        AthleteInvitation $invitation,
        InvitationDeliveryService $delivery,
        AuditLogger $audit,
    ): JsonResponse {
        $this->authorizeInvitation($request, $invitation);
        abort_unless($invitation->status === 'pending', 422);
        $sent = $delivery->send($invitation);
        $audit->record('invite.resent', 'athlete_invitation', $invitation->id, "Resent invite for {$invitation->email}. Email ".($sent ? 'sent' : 'failed').'.');

        return $this->success($this->invitationData($invitation) + ['email_sent' => $sent]);
    }

    public function cancelInvitation(Request $request, AthleteInvitation $invitation, AuditLogger $audit): JsonResponse
    {
        $this->authorizeInvitation($request, $invitation);
        $invitation->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $audit->record('invite.cancelled', 'athlete_invitation', $invitation->id, "Cancelled invite for {$invitation->email}.");

        return $this->success($this->invitationData($invitation->refresh()));
    }

    /** @return array<string, array<int, mixed>> */
    private function programRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'goal' => ['nullable', 'string', 'max:160'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'visibility' => ['required', Rule::in(['private', 'organization'])],
            'estimated_weeks' => ['nullable', 'integer', 'min:1', 'max:104'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @param array<string, mixed> $data */
    private function programPayload(array $data): array
    {
        return [
            'title' => $data['title'],
            'goal' => $data['goal'] ?? null,
            'status' => $data['status'],
            'visibility' => $data['visibility'],
            'estimated_weeks' => $data['estimated_weeks'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    private function validatedSession(Request $request, TrainingProgram $program): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'focus' => ['nullable', 'string', 'max:120'],
            'day_offset' => ['required', 'integer', 'min:0', 'max:730'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'program_phase_id' => ['nullable', 'integer', 'exists:program_phases,id'],
            'coach_notes' => ['nullable', 'string', 'max:1000'],
            'media_url' => ['nullable', 'url', 'max:255'],
            'exercises' => ['required', 'array', 'min:1', 'max:100'],
            'exercises.*.exercise_id' => ['nullable', 'integer', 'exists:exercise_library,id'],
            'exercises.*.section' => ['nullable', 'string', 'max:80'],
            'exercises.*.superset_label' => ['nullable', 'string', 'max:40'],
            'exercises.*.name' => ['required', 'string', 'max:160'],
            'exercises.*.sets' => ['nullable', 'integer', 'min:1', 'max:50'],
            'exercises.*.reps' => ['nullable', 'string', 'max:40'],
            'exercises.*.rest_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'exercises.*.load' => ['nullable', 'string', 'max:80'],
            'exercises.*.unit' => ['nullable', 'string', 'max:30'],
            'exercises.*.note' => ['nullable', 'string', 'max:200'],
            'exercises.*.media_url' => ['nullable', 'url', 'max:255'],
            'exercises.*.movement_type' => ['nullable', 'string', 'max:80'],
        ]);
        if (isset($data['program_phase_id'])) {
            abort_unless($program->phases()->whereKey($data['program_phase_id'])->exists(), 422);
        }
        $data['sort_order'] ??= ((int) $program->sessions()->max('sort_order')) + 1;
        $data['exercises'] = collect($data['exercises'])->map(fn (array $exercise): array => [
            'exercise_id' => $exercise['exercise_id'] ?? null,
            'section' => trim((string) ($exercise['section'] ?? 'Main work')),
            'superset_label' => trim((string) ($exercise['superset_label'] ?? '')),
            'name' => trim($exercise['name']),
            'sets' => (int) ($exercise['sets'] ?? 1),
            'reps' => trim((string) ($exercise['reps'] ?? '')),
            'rest_seconds' => isset($exercise['rest_seconds']) ? (int) $exercise['rest_seconds'] : null,
            'load' => trim((string) ($exercise['load'] ?? '')),
            'unit' => trim((string) ($exercise['unit'] ?? '')),
            'note' => trim((string) ($exercise['note'] ?? '')),
            'media_url' => trim((string) ($exercise['media_url'] ?? '')),
            'movement_type' => trim((string) ($exercise['movement_type'] ?? '')),
        ])->values()->all();

        return $data;
    }

    private function authorizeProgram(Request $request, TrainingProgram $program): void
    {
        abort_unless($program->coach_id === $request->user()->id, 403);
    }

    private function authorizeInvitation(Request $request, AthleteInvitation $invitation): void
    {
        abort_unless($invitation->coach_id === $request->user()->id, 403);
    }

    private function athleteQuery(Request $request): Builder
    {
        return User::query()->whereHas('athleteAssignments', fn (Builder $query) => $query
            ->where('coach_id', $request->user()->id)
            ->where('status', 'active'));
    }

    /** @return array<string, mixed> */
    private function programData(TrainingProgram $program): array
    {
        $program->load(['phases', 'sessions.phase', 'sessions.prescribedExercises', 'assignments.athlete']);

        return [
            'id' => $program->id,
            'title' => $program->title,
            'goal' => $program->goal,
            'status' => $program->status,
            'visibility' => $program->visibility,
            'estimated_weeks' => $program->estimated_weeks,
            'notes' => $program->notes,
            'phases' => $program->phases->map(fn ($phase): array => $phase->only(['id', 'title', 'description', 'duration_weeks', 'sort_order']))->values(),
            'sessions' => $program->sessions->map(fn (TrainingSession $session): array => $this->sessionData($session))->values(),
            'assignments' => $program->assignments->map(fn (ProgramAssignment $assignment): array => $this->assignmentData($assignment))->values(),
            'updated_at' => $program->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function sessionData(TrainingSession $session): array
    {
        $session->loadMissing(['phase', 'prescribedExercises']);

        return [
            'id' => $session->id,
            'title' => $session->title,
            'focus' => $session->focus,
            'day_offset' => $session->day_offset,
            'sort_order' => $session->sort_order,
            'estimated_minutes' => $session->estimated_minutes,
            'program_phase_id' => $session->program_phase_id,
            'phase_title' => $session->phase?->title,
            'coach_notes' => $session->coach_notes,
            'media_url' => $session->media_url,
            'exercises' => $session->prescribedExercises->map(fn ($exercise): array => [
                'id' => $exercise->id,
                'exercise_id' => $exercise->exercise_id,
                'section' => $exercise->section,
                'superset_label' => $exercise->superset_label,
                'name' => $exercise->name,
                'sets' => $exercise->target_sets,
                'reps' => $exercise->target_reps,
                'load' => $exercise->target_load,
                'unit' => $exercise->unit,
                'rest_seconds' => $exercise->rest_seconds,
                'note' => $exercise->notes,
                'media_url' => $exercise->media_url,
                'movement_type' => $exercise->movement_type,
            ])->values(),
        ];
    }

    /** @return array<string, mixed> */
    private function assignmentData(ProgramAssignment $assignment): array
    {
        $assignment->loadMissing('athlete');

        return [
            'id' => $assignment->id,
            'athlete' => $assignment->athlete?->only(['id', 'name', 'email']),
            'status' => $assignment->status,
            'starts_on' => $assignment->starts_on?->toDateString(),
            'ends_on' => $assignment->ends_on?->toDateString(),
            'notes' => $assignment->notes,
            'completion' => $assignment->completionStats(),
        ];
    }

    /** @return array<string, mixed> */
    private function invitationData(AthleteInvitation $invitation): array
    {
        return [
            'id' => $invitation->id,
            'name' => $invitation->name,
            'email' => $invitation->email,
            'status' => $invitation->status,
            'expires_at' => $invitation->expires_at?->toIso8601String(),
            'accepted_at' => $invitation->accepted_at?->toIso8601String(),
            'created_at' => $invitation->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function exerciseData(Exercise $exercise): array
    {
        return [
            'id' => $exercise->id,
            'name' => $exercise->name,
            'section' => $exercise->section,
            'movement_type' => $exercise->movement_type,
            'instructions' => $exercise->instructions,
            'sets' => $exercise->default_sets,
            'reps' => $exercise->default_reps,
            'load' => $exercise->default_load,
            'unit' => $exercise->unit,
            'rest_seconds' => $exercise->default_rest_seconds,
            'media_url' => $exercise->media_url,
            'shared' => $exercise->is_shared,
        ];
    }
}
