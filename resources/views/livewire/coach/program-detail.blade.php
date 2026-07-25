<div>
    <x-tl.page-hero
        eyebrow="Program builder"
        :title="$program->title"
        :subtitle="$program->athlete->name.' · '.$program->goal"
    />

    <x-tl.section-card title="Program control" subtitle="Edit the program header without rebuilding every session.">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
            <div></div>
            <button
                class="btn btn-outline-danger"
                type="button"
                wire:click="archiveProgram"
                wire:confirm="Archive this program? Existing athlete logs stay available."
            >
                Archive program
            </button>
        </div>
        <form wire:submit.prevent="updateProgram" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Title</label>
                <input class="form-control" wire:model="programForm.title">
                @error('programForm.title') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Goal</label>
                <input class="form-control" wire:model="programForm.goal">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" wire:model="programForm.status">
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Dates</label>
                <div class="d-flex gap-2">
                    <input class="form-control" type="date" wire:model="programForm.startsOn">
                    <input class="form-control" type="date" wire:model="programForm.endsOn">
                </div>
                @error('programForm.endsOn') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea class="form-control" rows="2" wire:model="programForm.notes"></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-tl" type="submit">Save program</button>
            </div>
        </form>
    </x-tl.section-card>

    <x-tl.section-card title="Add session" subtitle="Build the assigned workout with clear exercise rows, targets, rest, load, notes, and media.">
        <x-tl.session-form
            :form="$sessionForm"
            model="sessionForm"
            submit-action="createSession"
            submit-label="Save session"
            add-action="addSessionExercise"
            remove-action="removeSessionExercise"
        />
    </x-tl.section-card>

    @if($editingSessionId)
        <x-tl.section-card title="Editing session" :subtitle="$editSessionForm->title" class="border border-warning">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <div class="tl-eyebrow">Editing session</div>
                    <h3 class="h5 mb-0">{{ $editSessionForm->title }}</h3>
                </div>
                <button class="btn btn-outline-tl" type="button" wire:click="cancelEditSession">Cancel edit</button>
            </div>
            <x-tl.session-form
                :form="$editSessionForm"
                model="editSessionForm"
                submit-action="updateSession"
                submit-label="Update session"
                add-action="addEditSessionExercise"
                remove-action="removeEditSessionExercise"
            />
        </x-tl.section-card>
    @endif

    <x-tl.table-card
        title="Sessions"
        subtitle="All scheduled sessions inside this program."
        :count="$sessions->total()"
        icon="fa-solid fa-calendar-check"
    >
        <div class="d-md-none vstack gap-2">
            @forelse($sessions as $session)
                <div class="tl-mobile-record">
                    <div class="d-flex justify-content-between gap-2 align-items-start">
                        <div>
                            <div class="tl-muted small">{{ $session->scheduled_on->format('M j, Y') }}</div>
                            <div class="fw-bold">{{ $session->title }}</div>
                        </div>
                        <span class="tl-badge {{ $session->status === 'cancelled' ? 'gray' : 'green' }}">
                            {{ $session->status }}
                        </span>
                    </div>
                    <div class="tl-mobile-record-grid mt-3">
                        <div><span class="tl-muted small d-block">Focus</span><span>{{ $session->focus ?: '-' }}</span></div>
                        <div><span class="tl-muted small d-block">Exercises</span><span>{{ $session->exerciseSummary() }}</span></div>
                        <div><span class="tl-muted small d-block">Media</span><span>{{ $session->mediaCount() }} item(s)</span></div>
                        <div><span class="tl-muted small d-block">Logs</span><span>{{ $session->logs->count() }}</span></div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap mt-3">
                        <button class="btn btn-outline-tl btn-sm" type="button" wire:click="startEditSession({{ $session->id }})">
                            Edit
                        </button>
                        <button
                            class="btn btn-outline-danger btn-sm"
                            type="button"
                            wire:click="deleteSession({{ $session->id }})"
                            wire:confirm="Delete this empty session? If it has athlete logs it will be cancelled instead."
                        >
                            Delete
                        </button>
                    </div>
                </div>
            @empty
                <div class="tl-mobile-record tl-muted">No sessions yet.</div>
            @endforelse
        </div>

        <div class="tl-table-wrap d-none d-md-block">
            <table class="table tl-table align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Session</th>
                        <th>Status</th>
                        <th>Focus</th>
                        <th>Exercises</th>
                        <th>Media</th>
                        <th>Logs</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td>{{ $session->scheduled_on->format('Y-m-d') }}</td>
                            <td>
                                <strong>{{ $session->title }}</strong><br>
                                <span class="tl-muted">{{ $session->coach_notes ?: 'No notes' }}</span>
                            </td>
                            <td>
                                <span class="tl-badge {{ $session->status === 'cancelled' ? 'gray' : 'green' }}">
                                    {{ $session->status }}
                                </span>
                            </td>
                            <td>{{ $session->focus }}</td>
                            <td>{{ $session->exerciseSummary() }}</td>
                            <td>{{ $session->mediaCount() }} item(s)</td>
                            <td>{{ $session->logs->count() }}</td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="btn btn-outline-tl btn-sm" type="button" wire:click="startEditSession({{ $session->id }})">
                                        Edit
                                    </button>
                                    <button
                                        class="btn btn-outline-danger btn-sm"
                                        type="button"
                                        wire:click="deleteSession({{ $session->id }})"
                                        wire:confirm="Delete this empty session? If it has athlete logs it will be cancelled instead."
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="tl-muted">No sessions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $sessions->links() }}</div>
    </x-tl.table-card>
</div>
