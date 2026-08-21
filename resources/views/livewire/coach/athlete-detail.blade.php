<div>
    <x-tl.page-hero
        eyebrow="Athlete profile"
        :title="$athlete->name"
        :subtitle="$athlete->email.' · '.($athlete->primary_goal ?: 'No goal set')"
    >
        <x-slot:actions>
            <a class="btn btn-tl" href="{{ route('coach.programs', ['athlete' => $athlete->id]) }}"><i class="fa-solid fa-plus"></i> Assign program</a>
            @can('messages.send')
                <a class="btn btn-outline-tl" href="{{ route('coach.messages', ['user' => $athlete->id]) }}"><i class="fa-regular fa-message"></i> Message</a>
            @endcan
            <a class="btn btn-outline-tl" href="{{ route('coach.athletes') }}"><i class="fa-solid fa-arrow-left"></i> Roster</a>
        </x-slot:actions>
    </x-tl.page-hero>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3"><x-tl.metric-card icon="fa-solid fa-dumbbell" label="Programs" :value="$counts['programs']" tone="lime" /></div>
        <div class="col-6 col-xl-3"><x-tl.metric-card icon="fa-solid fa-calendar-check" label="Scheduled workouts" :value="$counts['schedule']" tone="emerald" /></div>
        <div class="col-6 col-xl-3"><x-tl.metric-card icon="fa-solid fa-clipboard-check" label="Workout logs" :value="$counts['workouts']" tone="gold" /></div>
        <div class="col-6 col-xl-3"><x-tl.metric-card icon="fa-solid fa-chart-line" label="Progress logs" :value="$counts['progress']" tone="blue" /></div>
    </div>

    <x-tl.section-card eyebrow="Performance window" :title="$progressSummary['period']['from'].' to '.$progressSummary['period']['to']" subtitle="Use the date filters below to recalculate adherence, workload, recovery, records, and photos.">
        <div class="row g-3">
            <div class="col-6 col-xl-2"><x-tl.metric-card icon="fa-solid fa-bullseye" label="Adherence" :value="$progressSummary['adherence']['percent'].'%'" tone="lime" /></div>
            <div class="col-6 col-xl-2"><x-tl.metric-card icon="fa-solid fa-list-check" label="Sets complete" :value="$progressSummary['sets']['percent'].'%'" tone="emerald" /></div>
            <div class="col-6 col-xl-2"><x-tl.metric-card icon="fa-solid fa-weight-hanging" label="Training load" :value="number_format($progressSummary['sets']['volume'])" tone="gold" /></div>
            <div class="col-6 col-xl-2"><x-tl.metric-card icon="fa-solid fa-gauge-high" label="Average RPE" :value="$progressSummary['averages']['rpe'] ?? '-'" tone="blue" /></div>
            <div class="col-6 col-xl-2"><x-tl.metric-card icon="fa-solid fa-clock" label="Avg duration" :value="$progressSummary['averages']['duration_minutes'] ? $progressSummary['averages']['duration_minutes'].' min' : '-'" tone="lime" /></div>
            <div class="col-6 col-xl-2"><x-tl.metric-card icon="fa-solid fa-heart-pulse" label="Check-ins" :value="$progressSummary['counts']['check_ins']" tone="emerald" /></div>
        </div>
        <div class="d-flex flex-wrap gap-3 mt-3 tl-muted">
            <span>{{ $progressSummary['adherence']['completed'] }}/{{ $progressSummary['adherence']['total'] }} workouts completed</span>
            <span>{{ $progressSummary['adherence']['partial'] }} partial</span>
            <span>{{ $progressSummary['adherence']['missed'] }} missed or skipped</span>
            <span>{{ $progressSummary['counts']['records'] }} records</span>
            <span>{{ $progressSummary['counts']['photos'] }} progress photos</span>
        </div>
    </x-tl.section-card>

    <nav class="tl-record-tabs mb-3" aria-label="Athlete record sections">
        @foreach([
            'programs' => ['fa-dumbbell', 'Programs'],
            'schedule' => ['fa-calendar-days', 'Schedule'],
            'workouts' => ['fa-clipboard-check', 'Workouts'],
            'progress' => ['fa-chart-line', 'Progress'],
            'photos' => ['fa-images', 'Photos'],
            'records' => ['fa-trophy', 'Records'],
            'notes' => ['fa-note-sticky', 'Coach notes'],
        ] as $key => [$icon, $label])
            <button class="{{ $tab === $key ? 'active' : '' }}" type="button" wire:click="selectTab('{{ $key }}')">
                <i class="fa-solid {{ $icon }}"></i><span>{{ $label }}</span><small>{{ $counts[$key] }}</small>
            </button>
        @endforeach
    </nav>

    @if($tab === 'photos' && auth()->user()->can('progress.review'))
        <x-tl.section-card eyebrow="Progress media" title="Upload progress photo" subtitle="Images are stored through Laravel Storage and remain scoped to this athlete and organization.">
            <form class="row g-3 align-items-end" wire:submit="uploadPhoto">
                <div class="col-md-4">
                    <label class="form-label">Image</label>
                    <input class="form-control" type="file" accept="image/jpeg,image/png,image/webp" wire:model="photo">
                    @error('photo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Taken on</label>
                    <input class="form-control" type="date" wire:model="photoTakenOn">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Category</label>
                    <select class="form-select" wire:model="photoCategory">
                        <option value="progress">Progress</option><option value="front">Front</option><option value="side">Side</option><option value="back">Back</option><option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Visible to</label>
                    <select class="form-select" wire:model="photoVisibility">
                        <option value="coaches">Assigned coaches</option><option value="athlete">Athlete and coaches</option><option value="private">Only me</option>
                    </select>
                </div>
                <div class="col-md-10">
                    <label class="form-label">Note</label>
                    <input class="form-control" wire:model="photoNotes" placeholder="Pose, measurement context, or coaching note">
                </div>
                <div class="col-md-2"><button class="btn btn-tl w-100" type="submit"><i class="fa-solid fa-upload"></i> Upload</button></div>
            </form>
        </x-tl.section-card>
    @endif

    @if($tab === 'notes' && auth()->user()->can('athletes.notes'))
        <x-tl.section-card eyebrow="Coach-only context" title="Add private note" subtitle="Private notes are visible only to you. Organization notes can be reviewed by authorized coaches in this organization.">
            <form class="row g-3 align-items-end" wire:submit="addNote">
                <div class="col-lg-7">
                    <label class="form-label">Note</label>
                    <textarea class="form-control" rows="3" wire:model="noteBody" placeholder="What should the coaching team remember?"></textarea>
                    @error('noteBody')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label">Visibility</label>
                    <select class="form-select" wire:model="noteVisibility"><option value="private">Only me</option><option value="organization">Organization coaches</option></select>
                </div>
                <div class="col-sm-6 col-lg-1">
                    <label class="form-label d-block">Priority</label>
                    <label class="tl-switch"><input class="form-check-input" type="checkbox" wire:model="notePinned"><span>Pin</span></label>
                </div>
                <div class="col-lg-2"><button class="btn btn-tl w-100" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save note</button></div>
            </form>
        </x-tl.section-card>
    @endif

    <div class="tl-section-card">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input class="form-control" type="search" wire:model.live.debounce.300ms="search" placeholder="Search the current section">
            </div>
            <div class="col-6 col-md-2 col-xl-1">
                <label class="form-label">Show</label>
                <select class="form-select" wire:model.live="perPage">
                    @foreach($pageSizeOptions as $option)<option value="{{ $option }}">{{ $option === 'all' ? 'All' : $option }}</option>@endforeach
                </select>
            </div>
            @if(in_array($tab, ['programs', 'schedule', 'workouts']))
                <div class="col-6 col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model.live="status">
                        <option value="all">All statuses</option>
                        @foreach($tab === 'programs' ? ['active', 'paused', 'completed', 'cancelled'] : ['scheduled', 'in_progress', 'completed', 'partial', 'missed', 'skipped', 'cancelled'] as $option)<option value="{{ $option }}">{{ str($option)->replace('_', ' ')->headline() }}</option>@endforeach
                    </select>
                </div>
            @elseif($tab === 'photos')
                <div class="col-6 col-md-2"><label class="form-label">Category</label><select class="form-select" wire:model.live="category"><option value="all">All categories</option>@foreach(['progress', 'front', 'side', 'back', 'other'] as $option)<option value="{{ $option }}">{{ str($option)->headline() }}</option>@endforeach</select></div>
            @elseif($tab === 'records')
                <div class="col-6 col-md-2"><label class="form-label">Record type</label><select class="form-select" wire:model.live="recordType"><option value="all">All types</option>@foreach(['load', 'reps', 'volume', 'time', 'distance'] as $option)<option value="{{ $option }}">{{ str($option)->headline() }}</option>@endforeach</select></div>
            @endif
            @if(in_array($tab, ['schedule', 'workouts', 'progress', 'photos']))
                <div class="col-6 col-md-2"><label class="form-label">From</label><input class="form-control" type="date" wire:model.live="from"></div>
                <div class="col-6 col-md-2"><label class="form-label">To</label><input class="form-control" type="date" wire:model.live="to"></div>
            @endif
            <div class="col-md-auto ms-md-auto">
                <a class="btn btn-outline-tl" href="{{ route('coach.athletes.export', ['athlete' => $athlete, 'section' => $tab, 'search' => $search, 'status' => $status, 'category' => $category, 'record_type' => $recordType, 'from' => $from, 'to' => $to]) }}"><i class="fa-solid fa-download"></i> Export CSV</a>
            </div>
        </div>
    </div>

    <x-tl.table-card :title="str($tab)->headline()" subtitle="Filtered records for this athlete. Open linked records for deeper review." :count="$records->total()" icon="fa-solid fa-table-list">
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            @if($tab === 'programs')
                <thead><tr><th>Program</th><th>Goal</th><th>Status</th><th>Dates</th><th>Scheduled</th><th>Logs</th><th>Progress</th><th>Action</th></tr></thead>
                <tbody>@forelse($records as $programAssignment)
                    @php($completion = $programAssignment->completionStats())
                    <tr><td>@if($programAssignment->program->coach_id === auth()->id())<a class="tl-table-primary-link" href="{{ route('coach.programs.show', $programAssignment->program) }}">{{ $programAssignment->program->title }}</a>@else<strong>{{ $programAssignment->program->title }}</strong>@endif<span class="d-block tl-muted">Coach {{ $programAssignment->program->coach?->name ?: 'Unknown' }}</span></td><td>{{ $programAssignment->program->goal ?: '-' }}</td><td><span class="tl-badge {{ $programAssignment->status === 'active' ? 'green' : 'gray' }}">{{ $programAssignment->status }}</span></td><td>{{ $programAssignment->starts_on?->format('Y-m-d') }} to {{ $programAssignment->ends_on?->format('Y-m-d') ?: 'open' }}</td><td>{{ $programAssignment->scheduled_workouts_count }}</td><td>{{ $programAssignment->workout_logs_count }}</td><td><strong>{{ $completion['percent'] }}%</strong><br><span class="tl-muted">{{ $completion['completed'] }}/{{ $completion['total'] }}</span></td><td>@if($programAssignment->program->coach_id === auth()->id())<a class="btn btn-outline-tl btn-sm" href="{{ route('coach.programs.show', $programAssignment->program) }}">Edit plan</a>@else<span class="tl-muted">Read only</span>@endif</td></tr>
                @empty<tr><td colspan="8" class="tl-muted">No program assignments match these filters.</td></tr>@endforelse</tbody>
            @elseif($tab === 'schedule')
                <thead><tr><th>Date</th><th>Session</th><th>Program</th><th>Focus</th><th>Status</th><th>Execution</th><th>Action</th></tr></thead>
                <tbody>@forelse($records as $workout)
                    @php($execution = $workout->logs->first()?->status)
                    <tr><td>{{ $workout->scheduled_for?->timezone($workout->assignment->timezone)->format('Y-m-d H:i') }}</td><td><strong>{{ $workout->session->title }}</strong></td><td>{{ $workout->session->program->title }}</td><td>{{ $workout->session->focus ?: '-' }}</td><td><span class="tl-badge gray">{{ $workout->status }}</span></td><td>{{ $execution ?: 'Not logged' }}</td><td><a class="btn btn-outline-tl btn-sm" href="{{ route('coach.schedule', ['search' => $workout->session->title]) }}">Schedule</a></td></tr>
                @empty<tr><td colspan="7" class="tl-muted">No scheduled workouts match these filters.</td></tr>@endforelse</tbody>
            @elseif($tab === 'workouts')
                <thead><tr><th>Logged</th><th>Session</th><th>Status</th><th>RPE</th><th>Duration</th><th>Sets</th><th>Notes</th></tr></thead>
                <tbody>@forelse($records as $log)
                    <tr><td>{{ $log->created_at->format('Y-m-d H:i') }}</td><td><strong>{{ $log->session->title }}</strong><br><span class="tl-muted">{{ $log->session->program->title }}</span></td><td><span class="tl-badge {{ $log->status === 'completed' ? 'green' : 'gray' }}">{{ $log->status }}</span></td><td>{{ $log->rpe ?: '-' }}</td><td>{{ $log->duration_minutes ? $log->duration_minutes.' min' : '-' }}</td><td>{{ $log->setLogs->whereNotNull('completed_at')->count() }}/{{ $log->setLogs->count() }}</td><td>{{ $log->notes ?: '-' }}</td></tr>
                @empty<tr><td colspan="7" class="tl-muted">No workout logs match these filters.</td></tr>@endforelse</tbody>
            @elseif($tab === 'progress')
                <thead><tr><th>Date</th><th>Weight</th><th>Calories</th><th>Protein</th><th>Hydration</th><th>Sleep</th><th>Soreness</th><th>Energy</th><th>Notes</th></tr></thead>
                <tbody>@forelse($records as $entry)
                    <tr><td><strong>{{ $entry->logged_on->format('Y-m-d') }}</strong></td><td>{{ $entry->weight ? $entry->weight.' kg' : '-' }}</td><td>{{ $entry->calories ?: '-' }}</td><td>{{ $entry->protein ? $entry->protein.' g' : '-' }}</td><td>{{ $entry->hydration ? $entry->hydration.' ml' : '-' }}</td><td>{{ $entry->sleep_quality ?: '-' }}/10</td><td>{{ $entry->soreness ?: '-' }}/10</td><td>{{ $entry->energy ?: '-' }}/10</td><td>{{ $entry->notes ?: '-' }}</td></tr>
                @empty<tr><td colspan="9" class="tl-muted">No progress entries match these filters.</td></tr>@endforelse</tbody>
            @elseif($tab === 'photos')
                <thead><tr><th>Photo</th><th>Taken</th><th>Category</th><th>Visibility</th><th>Uploaded by</th><th>Notes</th><th>Actions</th></tr></thead>
                <tbody>@forelse($records as $progressPhoto)
                    <tr><td><a href="{{ route('coach.athletes.photos.view', [$athlete, $progressPhoto]) }}" target="_blank"><img class="tl-table-thumbnail" src="{{ route('coach.athletes.photos.view', [$athlete, $progressPhoto]) }}" alt="{{ $progressPhoto->category }} progress photo" loading="lazy"></a></td><td>{{ $progressPhoto->taken_on->format('Y-m-d') }}</td><td>{{ str($progressPhoto->category)->headline() }}</td><td><span class="tl-badge gray">{{ $progressPhoto->visibility }}</span></td><td>{{ $progressPhoto->uploadedBy?->name ?: 'Unknown' }}</td><td>{{ $progressPhoto->notes ?: '-' }}</td><td class="text-nowrap"><a class="btn btn-outline-tl btn-sm" href="{{ route('coach.athletes.photos.view', [$athlete, $progressPhoto]) }}" target="_blank">Open</a>@if($progressPhoto->uploaded_by === auth()->id()) <button class="btn btn-outline-danger btn-sm" type="button" wire:click="deletePhoto({{ $progressPhoto->id }})" wire:confirm="Delete this photo?">Delete</button>@endif</td></tr>
                @empty<tr><td colspan="7" class="tl-muted">No progress photos match these filters.</td></tr>@endforelse</tbody>
            @elseif($tab === 'records')
                <thead><tr><th>Date</th><th>Exercise</th><th>Record type</th><th>Value</th><th>Source</th></tr></thead>
                <tbody>@forelse($records as $record)
                    <tr><td>{{ $record->achieved_on->format('Y-m-d') }}</td><td><strong>{{ $record->exercise_name }}</strong></td><td><span class="tl-badge gold">{{ $record->record_type }}</span></td><td>{{ $record->value }} {{ $record->unit }}</td><td>{{ $record->workout_set_log_id ? 'Workout set' : 'Manual' }}</td></tr>
                @empty<tr><td colspan="5" class="tl-muted">No personal records match these filters.</td></tr>@endforelse</tbody>
            @else
                <thead><tr><th>Created</th><th>Coach</th><th>Visibility</th><th>Priority</th><th>Note</th><th>Actions</th></tr></thead>
                <tbody>@forelse($records as $note)
                    <tr><td>{{ $note->created_at->format('Y-m-d H:i') }}</td><td>{{ $note->coach?->name ?: 'Unknown' }}</td><td><span class="tl-badge gray">{{ $note->visibility }}</span></td><td>{{ $note->is_pinned ? 'Pinned' : '-' }}</td><td>{{ $note->body }}</td><td>@if($note->coach_id === auth()->id())<div class="d-flex gap-2"><button class="btn btn-outline-tl btn-sm" type="button" wire:click="toggleNotePinned({{ $note->id }})">{{ $note->is_pinned ? 'Unpin' : 'Pin' }}</button><button class="btn btn-outline-danger btn-sm" type="button" wire:click="deleteNote({{ $note->id }})" wire:confirm="Delete this note?">Delete</button></div>@else<span class="tl-muted">Read only</span>@endif</td></tr>
                @empty<tr><td colspan="6" class="tl-muted">No coach notes match this search.</td></tr>@endforelse</tbody>
            @endif
        </table></div>
        <div class="mt-3">{{ $records->links() }}</div>
    </x-tl.table-card>
</div>
