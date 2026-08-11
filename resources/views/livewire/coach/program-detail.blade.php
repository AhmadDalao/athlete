<div>
    <x-tl.page-hero eyebrow="Program builder" :title="$program->title" :subtitle="($program->goal ?: 'No goal set').' · reusable template'">
        <x-slot:actions>
            <a class="btn btn-outline-tl" href="{{ route('coach.programs') }}"><i class="fa-solid fa-arrow-left"></i> Program library</a>
            <button class="btn btn-outline-danger" type="button" wire:click="archiveProgram" wire:confirm="Archive this template? Existing athlete history stays intact."><i class="fa-solid fa-box-archive"></i> Archive</button>
        </x-slot:actions>
        <x-slot:visual>
            <div class="row g-3">
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-layer-group" label="Phases" :value="$phases->count()" tone="lime" /></div>
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-calendar-check" label="Sessions" :value="$sessions->total()" tone="blue" /></div>
                <div class="col-12"><x-tl.metric-card icon="fa-solid fa-user-check" label="Assignments" :value="$assignments->total()" tone="emerald" /></div>
            </div>
        </x-slot:visual>
    </x-tl.page-hero>

    <x-tl.section-card eyebrow="Template settings" title="Program identity" subtitle="Changes here affect the reusable template. Athlete dates live on each assignment.">
        <form class="row g-3 align-items-end" wire:submit.prevent="updateProgram">
            <div class="col-md-4"><label class="form-label">Title</label><input class="form-control" wire:model="programForm.title"></div>
            <div class="col-md-4"><label class="form-label">Goal</label><input class="form-control" wire:model="programForm.goal"></div>
            <div class="col-6 col-md-2"><label class="form-label">Status</label><select class="form-select" wire:model="programForm.status"><option value="draft">Draft</option><option value="active">Active</option><option value="archived">Archived</option></select></div>
            <div class="col-6 col-md-2"><label class="form-label">Weeks</label><input class="form-control" type="number" wire:model="programForm.estimatedWeeks"></div>
            <div class="col-md-3"><label class="form-label">Visibility</label><select class="form-select" wire:model="programForm.visibility"><option value="private">Only me</option><option value="organization">Organization coaches</option></select></div>
            <div class="col-md-7"><label class="form-label">Notes</label><input class="form-control" wire:model="programForm.notes"></div>
            <div class="col-md-2"><button class="btn btn-tl w-100" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button></div>
        </form>
    </x-tl.section-card>

    <div class="row g-3">
        <div class="col-xl-5">
            <x-tl.section-card eyebrow="Structure" title="Program phases" subtitle="Group sessions into clear blocks without changing their day offsets.">
                <form class="row g-2 align-items-end mb-3" wire:submit.prevent="createPhase">
                    <div class="col-md-6"><label class="form-label">Phase name</label><input class="form-control" wire:model="phaseTitle" placeholder="Foundation"></div>
                    <div class="col-5 col-md-3"><label class="form-label">Weeks</label><input class="form-control" type="number" min="1" wire:model="phaseDurationWeeks"></div>
                    <div class="col-7 col-md-3"><button class="btn btn-tl w-100" type="submit">Add phase</button></div>
                    <div class="col-12"><label class="form-label">Description</label><input class="form-control" wire:model="phaseDescription"></div>
                </form>
                <div class="tl-table-wrap"><table class="table tl-table align-middle mb-0"><thead><tr><th>Order</th><th>Phase</th><th>Weeks</th><th>Sessions</th><th></th></tr></thead><tbody>
                    @forelse($phases as $phase)<tr><td>{{ $phase->sort_order }}</td><td><strong>{{ $phase->title }}</strong><span class="d-block tl-muted">{{ $phase->description }}</span></td><td>{{ $phase->duration_weeks ?: '-' }}</td><td>{{ $phase->sessions_count }}</td><td><button class="btn btn-outline-danger btn-sm" wire:click="deletePhase({{ $phase->id }})" wire:confirm="Remove this phase? Sessions become ungrouped."><i class="fa-solid fa-trash"></i></button></td></tr>@empty<tr><td colspan="5" class="tl-muted">No phases yet.</td></tr>@endforelse
                </tbody></table></div>
            </x-tl.section-card>
        </div>
        <div class="col-xl-7">
            <x-tl.section-card eyebrow="Delivery" title="Assign to athlete" subtitle="Each assignment gets its own dated schedule. One template can serve many athletes.">
                <form class="row g-3 align-items-end" wire:submit.prevent="assignProgram">
                    <div class="col-md-5"><label class="form-label">Athlete</label><select class="form-select" wire:model="assignmentAthleteId"><option value="">Choose athlete</option>@foreach($athletes as $athlete)<option value="{{ $athlete->id }}">{{ $athlete->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Start date</label><input class="form-control" type="date" wire:model="assignmentStartsOn"></div>
                    <div class="col-md-4"><label class="form-label">Assignment note</label><input class="form-control" wire:model="assignmentNotes"></div>
                    <div class="col-12"><button class="btn btn-tl" type="submit"><i class="fa-solid fa-user-plus"></i> Assign and generate schedule</button></div>
                </form>
                @error('assignmentAthleteId')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </x-tl.section-card>
        </div>
    </div>

    <x-tl.table-card title="Athlete assignments" subtitle="Independent schedule state for every athlete using this template." :count="$assignments->total()" icon="fa-solid fa-user-check">
        <div class="tl-table-wrap"><table class="table tl-table align-middle"><thead><tr><th>Athlete</th><th>Starts</th><th>Ends</th><th>Status</th><th>Scheduled</th><th>Logs</th><th>Actions</th></tr></thead><tbody>
            @forelse($assignments as $assignment)
                <tr wire:key="assignment-{{ $assignment->id }}"><td><a class="tl-table-primary-link" href="{{ route('coach.athletes.show', $assignment->athlete) }}">{{ $assignment->athlete->name }}</a><span class="d-block tl-muted">{{ $assignment->athlete->email }}</span></td><td>{{ $assignment->starts_on->format('Y-m-d') }}</td><td>{{ $assignment->ends_on?->format('Y-m-d') ?: '-' }}</td><td><span class="tl-badge {{ $assignment->status === 'active' ? 'green' : ($assignment->status === 'paused' ? 'gold' : 'gray') }}">{{ $assignment->status }}</span></td><td>{{ $assignment->scheduled_workouts_count }}</td><td>{{ $assignment->workout_logs_count }}</td><td><div class="d-flex gap-2 flex-wrap">@if($assignment->status === 'active')<button class="btn btn-outline-tl btn-sm" wire:click="setAssignmentStatus({{ $assignment->id }}, 'paused')">Pause</button>@elseif($assignment->status === 'paused')<button class="btn btn-outline-tl btn-sm" wire:click="setAssignmentStatus({{ $assignment->id }}, 'active')">Resume</button>@endif @if(!in_array($assignment->status, ['completed','cancelled']))<button class="btn btn-outline-tl btn-sm" wire:click="setAssignmentStatus({{ $assignment->id }}, 'completed')">Complete</button><button class="btn btn-outline-danger btn-sm" wire:click="setAssignmentStatus({{ $assignment->id }}, 'cancelled')" wire:confirm="Cancel this athlete assignment?">Cancel</button>@endif</div></td></tr>
            @empty<tr><td colspan="7" class="tl-muted">No athlete assignments yet.</td></tr>@endforelse
        </tbody></table></div><div class="mt-3">{{ $assignments->links() }}</div>
    </x-tl.table-card>

    <x-tl.section-card eyebrow="Session builder" title="Add template session" subtitle="Day offset converts to a real calendar date when the program is assigned.">
        <x-tl.session-form :form="$sessionForm" model="sessionForm" submit-action="createSession" submit-label="Add session" add-action="addSessionExercise" remove-action="removeSessionExercise" :phases="$phases" :library-exercises="$libraryExercises" />
    </x-tl.section-card>

    @if($editingSessionId)
        <x-tl.section-card eyebrow="Editing session" title="Update session" subtitle="Open athlete schedules without execution logs will follow the new day offset.">
            <x-tl.session-form :form="$editSessionForm" model="editSessionForm" submit-action="updateSession" submit-label="Save session" add-action="addEditSessionExercise" remove-action="removeEditSessionExercise" :phases="$phases" :library-exercises="$libraryExercises" />
            <button class="btn btn-link tl-muted px-0 mt-2" type="button" wire:click="cancelEditSession">Cancel edit</button>
        </x-tl.section-card>
    @endif

    <x-tl.table-card title="Template sessions" subtitle="Ordered prescription source. Athlete execution is stored separately." :count="$sessions->total()" icon="fa-solid fa-calendar-check">
        <div class="tl-table-wrap"><table class="table tl-table align-middle"><thead><tr><th>Day</th><th>Phase</th><th>Session</th><th>Focus</th><th>Exercises</th><th>Media</th><th>Status</th><th>Actions</th></tr></thead><tbody>
            @forelse($sessions as $session)<tr wire:key="session-{{ $session->id }}"><td><strong>+{{ $session->day_offset }}</strong><span class="d-block tl-muted">{{ $session->estimated_minutes ? $session->estimated_minutes.' min' : 'No duration' }}</span></td><td>{{ $session->phase?->title ?: 'Ungrouped' }}</td><td><strong>{{ $session->title }}</strong><span class="d-block tl-muted">Order {{ $session->sort_order }}</span></td><td>{{ $session->focus ?: '-' }}</td><td>{{ $session->exerciseSummary() }}<span class="d-block tl-muted">{{ $session->prescribedExercises->count() }} movements</span></td><td>{{ $session->mediaCount() }}</td><td><span class="tl-badge {{ $session->status === 'cancelled' ? 'gray' : 'green' }}">{{ $session->status }}</span></td><td><div class="d-flex gap-2"><button class="btn btn-outline-tl btn-sm" wire:click="startEditSession({{ $session->id }})">Edit</button><button class="btn btn-outline-danger btn-sm" wire:click="deleteSession({{ $session->id }})" wire:confirm="Delete this session if no execution data exists?">Delete</button></div></td></tr>
            @empty<tr><td colspan="8" class="tl-muted">No sessions yet.</td></tr>@endforelse
        </tbody></table></div><div class="mt-3">{{ $sessions->links() }}</div>
    </x-tl.table-card>
</div>
