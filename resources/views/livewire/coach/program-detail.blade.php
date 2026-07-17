<div>
    <x-tl.page-hero
        eyebrow="Program builder"
        :title="$program->title"
        :subtitle="$program->athlete->name.' · '.$program->goal"
    />

    <x-tl.section-card title="Program control" subtitle="Edit the program header without rebuilding every session.">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
            <div></div>
            <button class="btn btn-outline-danger" type="button" wire:click="archiveProgram" wire:confirm="Archive this program? Existing athlete logs stay available.">Archive program</button>
        </div>
        <form wire:submit.prevent="updateProgram" class="row g-3 align-items-end">
            <div class="col-md-4"><label class="form-label">Title</label><input class="form-control" wire:model="programTitle"></div>
            <div class="col-md-3"><label class="form-label">Goal</label><input class="form-control" wire:model="programGoal"></div>
            <div class="col-md-2"><label class="form-label">Status</label><select class="form-select" wire:model="programStatus"><option value="draft">Draft</option><option value="active">Active</option><option value="archived">Archived</option></select></div>
            <div class="col-md-3"><label class="form-label">Dates</label><div class="d-flex gap-2"><input class="form-control" type="date" wire:model="programStartsOn"><input class="form-control" type="date" wire:model="programEndsOn"></div></div>
            <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" rows="2" wire:model="programNotes"></textarea></div>
            <div class="col-12"><button class="btn btn-tl" type="submit">Save program</button></div>
        </form>
    </x-tl.section-card>

    <x-tl.section-card title="Add session" subtitle="Build the assigned workout with clear exercise rows, targets, rest, load, notes, and media.">
        <form wire:submit.prevent="createSession" class="vstack gap-3">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Session title</label><input class="form-control" wire:model="title"></div>
                <div class="col-md-3"><label class="form-label">Focus</label><input class="form-control" wire:model="focus"></div>
                <div class="col-md-2"><label class="form-label">Date</label><input class="form-control" type="date" wire:model="scheduledOn"></div>
                <div class="col-md-3"><label class="form-label">Video/image URL</label><input class="form-control" wire:model="mediaUrl"></div>
            </div>
            <textarea class="form-control" rows="2" placeholder="Coach notes" wire:model="coachNotes"></textarea>
            <div class="d-md-none vstack gap-2">
                @foreach($exercises as $index => $exercise)
                    <div class="tl-mobile-record">
                        <div class="d-flex justify-content-between gap-2 align-items-center mb-3">
                            <div class="fw-bold">Exercise {{ $index + 1 }}</div>
                            <button class="btn btn-outline-danger btn-sm" type="button" wire:click="removeExercise({{ $index }})">Remove</button>
                        </div>
                        <div class="row g-2">
                            <div class="col-12"><label class="form-label small">Exercise</label><input class="form-control" wire:model="exercises.{{ $index }}.name"></div>
                            <div class="col-6"><label class="form-label small">Sets</label><input class="form-control" wire:model="exercises.{{ $index }}.sets"></div>
                            <div class="col-6"><label class="form-label small">Reps/time</label><input class="form-control" wire:model="exercises.{{ $index }}.reps"></div>
                            <div class="col-6"><label class="form-label small">Rest</label><input class="form-control" wire:model="exercises.{{ $index }}.rest"></div>
                            <div class="col-6"><label class="form-label small">Load</label><input class="form-control" wire:model="exercises.{{ $index }}.load"></div>
                            <div class="col-12"><label class="form-label small">Note</label><input class="form-control" wire:model="exercises.{{ $index }}.note"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="tl-table-wrap d-none d-md-block"><table class="table tl-table align-middle">
                <thead><tr><th>Exercise</th><th>Sets</th><th>Reps/time</th><th>Rest</th><th>Load</th><th>Note</th><th></th></tr></thead>
                <tbody>
                @foreach($exercises as $index => $exercise)
                    <tr>
                        <td><input class="form-control" wire:model="exercises.{{ $index }}.name"></td>
                        <td><input class="form-control" wire:model="exercises.{{ $index }}.sets"></td>
                        <td><input class="form-control" wire:model="exercises.{{ $index }}.reps"></td>
                        <td><input class="form-control" wire:model="exercises.{{ $index }}.rest"></td>
                        <td><input class="form-control" wire:model="exercises.{{ $index }}.load"></td>
                        <td><input class="form-control" wire:model="exercises.{{ $index }}.note"></td>
                        <td><button class="btn btn-outline-danger btn-sm" type="button" wire:click="removeExercise({{ $index }})">Remove</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="d-flex gap-2 flex-wrap"><button class="btn btn-outline-tl" type="button" wire:click="addExercise">Add exercise</button><button class="btn btn-tl" type="submit">Save session</button></div>
        </form>
    </x-tl.section-card>

    @if($editingSessionId)
        <x-tl.section-card title="Editing session" :subtitle="$editTitle" class="border border-warning">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <div class="tl-eyebrow">Editing session</div>
                    <h3 class="h5 mb-0">{{ $editTitle }}</h3>
                </div>
                <button class="btn btn-outline-tl" type="button" wire:click="cancelEditSession">Cancel edit</button>
            </div>
            <form wire:submit.prevent="updateSession" class="vstack gap-3">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Session title</label><input class="form-control" wire:model="editTitle"></div>
                    <div class="col-md-3"><label class="form-label">Focus</label><input class="form-control" wire:model="editFocus"></div>
                    <div class="col-md-2"><label class="form-label">Date</label><input class="form-control" type="date" wire:model="editScheduledOn"></div>
                    <div class="col-md-3"><label class="form-label">Video/image URL</label><input class="form-control" wire:model="editMediaUrl"></div>
                </div>
                <textarea class="form-control" rows="2" placeholder="Coach notes" wire:model="editCoachNotes"></textarea>
                <div class="d-md-none vstack gap-2">
                    @foreach($editExercises as $index => $exercise)
                        <div class="tl-mobile-record">
                            <div class="d-flex justify-content-between gap-2 align-items-center mb-3">
                                <div class="fw-bold">Exercise {{ $index + 1 }}</div>
                                <button class="btn btn-outline-danger btn-sm" type="button" wire:click="removeEditExercise({{ $index }})">Remove</button>
                            </div>
                            <div class="row g-2">
                                <div class="col-12"><label class="form-label small">Exercise</label><input class="form-control" wire:model="editExercises.{{ $index }}.name"></div>
                                <div class="col-6"><label class="form-label small">Sets</label><input class="form-control" wire:model="editExercises.{{ $index }}.sets"></div>
                                <div class="col-6"><label class="form-label small">Reps/time</label><input class="form-control" wire:model="editExercises.{{ $index }}.reps"></div>
                                <div class="col-6"><label class="form-label small">Rest</label><input class="form-control" wire:model="editExercises.{{ $index }}.rest"></div>
                                <div class="col-6"><label class="form-label small">Load</label><input class="form-control" wire:model="editExercises.{{ $index }}.load"></div>
                                <div class="col-12"><label class="form-label small">Note</label><input class="form-control" wire:model="editExercises.{{ $index }}.note"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="tl-table-wrap d-none d-md-block"><table class="table tl-table align-middle">
                    <thead><tr><th>Exercise</th><th>Sets</th><th>Reps/time</th><th>Rest</th><th>Load</th><th>Note</th><th></th></tr></thead>
                    <tbody>
                    @foreach($editExercises as $index => $exercise)
                        <tr>
                            <td><input class="form-control" wire:model="editExercises.{{ $index }}.name"></td>
                            <td><input class="form-control" wire:model="editExercises.{{ $index }}.sets"></td>
                            <td><input class="form-control" wire:model="editExercises.{{ $index }}.reps"></td>
                            <td><input class="form-control" wire:model="editExercises.{{ $index }}.rest"></td>
                            <td><input class="form-control" wire:model="editExercises.{{ $index }}.load"></td>
                            <td><input class="form-control" wire:model="editExercises.{{ $index }}.note"></td>
                            <td><button class="btn btn-outline-danger btn-sm" type="button" wire:click="removeEditExercise({{ $index }})">Remove</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
                <div class="d-flex gap-2 flex-wrap"><button class="btn btn-outline-tl" type="button" wire:click="addEditExercise">Add exercise</button><button class="btn btn-tl" type="submit">Update session</button></div>
            </form>
        </x-tl.section-card>
    @endif

    <x-tl.table-card title="Sessions" subtitle="All scheduled sessions inside this program." :count="$sessions->total()" icon="fa-solid fa-calendar-check">
        <div class="d-md-none vstack gap-2">
            @forelse($sessions as $session)
                <div class="tl-mobile-record">
                    <div class="d-flex justify-content-between gap-2 align-items-start">
                        <div>
                            <div class="tl-muted small">{{ $session->scheduled_on->format('M j, Y') }}</div>
                            <div class="fw-bold">{{ $session->title }}</div>
                        </div>
                        <span class="tl-badge {{ $session->status === 'cancelled' ? 'gray' : 'green' }}">{{ $session->status }}</span>
                    </div>
                    <div class="tl-mobile-record-grid mt-3">
                        <div><span class="tl-muted small d-block">Focus</span><span>{{ $session->focus ?: '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Exercises</span><span>{{ $session->exerciseSummary() }}</span></div>
                        <div><span class="tl-muted small d-block">Media</span><span>@if($session->media_url)<a href="{{ $session->media_url }}" target="_blank">Open</a>@else None @endif</span></div>
                        <div><span class="tl-muted small d-block">Logs</span><span>{{ $session->logs->count() }}</span></div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap mt-3">
                        <button class="btn btn-outline-tl btn-sm" type="button" wire:click="startEditSession({{ $session->id }})">Edit</button>
                        <button class="btn btn-outline-danger btn-sm" type="button" wire:click="deleteSession({{ $session->id }})" wire:confirm="Delete this empty session? If it has athlete logs it will be cancelled instead.">Delete</button>
                    </div>
                </div>
            @empty
                <div class="tl-mobile-record tl-muted">No sessions yet.</div>
            @endforelse
        </div>
        <div class="tl-table-wrap d-none d-md-block"><table class="table tl-table align-middle">
            <thead><tr><th>Date</th><th>Session</th><th>Status</th><th>Focus</th><th>Exercises</th><th>Media</th><th>Logs</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($sessions as $session)
                <tr>
                    <td>{{ $session->scheduled_on->format('Y-m-d') }}</td>
                    <td><strong>{{ $session->title }}</strong><br><span class="tl-muted">{{ $session->coach_notes ?: 'No notes' }}</span></td>
                    <td><span class="tl-badge {{ $session->status === 'cancelled' ? 'gray' : 'green' }}">{{ $session->status }}</span></td>
                    <td>{{ $session->focus }}</td>
                    <td>{{ $session->exerciseSummary() }}</td>
                    <td>@if($session->media_url)<a href="{{ $session->media_url }}" target="_blank">Open</a>@else - @endif</td>
                    <td>{{ $session->logs->count() }}</td>
                    <td>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-tl btn-sm" type="button" wire:click="startEditSession({{ $session->id }})">Edit</button>
                            <button class="btn btn-outline-danger btn-sm" type="button" wire:click="deleteSession({{ $session->id }})" wire:confirm="Delete this empty session? If it has athlete logs it will be cancelled instead.">Delete</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="tl-muted">No sessions yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="mt-3">{{ $sessions->links() }}</div>
    </x-tl.table-card>
</div>
