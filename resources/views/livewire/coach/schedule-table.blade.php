<div>
    <x-tl.page-hero eyebrow="Delivery" title="Athlete schedule" subtitle="Review generated workouts, filter the roster, and reschedule unfinished work without changing the template.">
        <x-slot:actions><a class="btn btn-outline-tl" href="{{ route('coach.programs') }}"><i class="fa-solid fa-dumbbell"></i> Program library</a></x-slot:actions>
    </x-tl.page-hero>

    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search athlete, workout, or program'])

    <x-tl.table-card title="Scheduled workouts" subtitle="Dates update instantly through Livewire. Execution records remain locked to protect athlete history." :count="$workouts->total()" icon="fa-solid fa-calendar-days">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-6 col-md-2"><label class="form-label">From</label><input class="form-control" type="date" wire:model.live="from"></div>
            <div class="col-6 col-md-2"><label class="form-label">To</label><input class="form-control" type="date" wire:model.live="to"></div>
            <div class="col-md-3"><label class="form-label">Athlete</label><select class="form-select" wire:model.live="athleteId"><option value="">All athletes</option>@foreach($athletes as $athlete)<option value="{{ $athlete->id }}">{{ $athlete->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" wire:model.live="status"><option value="all">All statuses</option><option value="scheduled">Scheduled</option><option value="completed">Completed</option><option value="partial">Partial</option><option value="missed">Missed</option><option value="skipped">Skipped</option><option value="cancelled">Cancelled</option></select></div>
            <div class="col-md-auto ms-md-auto"><a class="btn btn-outline-tl" href="{{ route('coach.schedule.export', ['search' => $search, 'from' => $from, 'to' => $to, 'status' => $status, 'athlete_id' => $athleteId]) }}"><i class="fa-solid fa-download"></i> Export CSV</a></div>
        </div>

        @if($rescheduleId)
            <form class="tl-inline-editor mb-3" wire:submit.prevent="saveReschedule">
                <div><strong>Reschedule workout</strong><span class="d-block tl-muted">Execution data cannot be moved.</span></div>
                <input class="form-control" type="date" wire:model="rescheduleDate">
                <button class="btn btn-tl" type="submit">Save date</button>
                <button class="btn btn-outline-tl" type="button" wire:click="cancelReschedule">Cancel</button>
                @error('rescheduleDate')<span class="text-danger small">{{ $message }}</span>@enderror
            </form>
        @endif

        <div class="tl-table-wrap"><table class="table tl-table align-middle"><thead><tr><th><button class="tl-sort-button" wire:click="sortBy('scheduled_for')">Date <i class="fa-solid fa-sort"></i></button></th><th>Athlete</th><th>Workout</th><th>Program</th><th>Status</th><th>Logs</th><th>Actions</th></tr></thead><tbody>
            @forelse($workouts as $workout)<tr wire:key="workout-{{ $workout->id }}"><td><strong>{{ $workout->scheduled_for->timezone($workout->assignment->timezone)->format('Y-m-d') }}</strong><span class="d-block tl-muted">{{ $workout->scheduled_for->timezone($workout->assignment->timezone)->format('H:i') }}</span></td><td><a class="tl-table-primary-link" href="{{ route('coach.athletes.show', $workout->athlete) }}">{{ $workout->athlete->name }}</a></td><td><strong>{{ $workout->session->title }}</strong><span class="d-block tl-muted">{{ $workout->session->exerciseSummary() }}</span></td><td>{{ $workout->session->program->title }}</td><td><span class="tl-badge {{ $workout->status === 'completed' ? 'green' : ($workout->status === 'scheduled' ? 'gold' : 'gray') }}">{{ $workout->status }}</span></td><td>{{ $workout->logs_count }}</td><td>@if($workout->logs_count === 0 && $workout->status === 'scheduled')<div class="d-flex gap-2"><button class="btn btn-outline-tl btn-sm" wire:click="beginReschedule({{ $workout->id }})">Reschedule</button><button class="btn btn-outline-danger btn-sm" wire:click="skip({{ $workout->id }})" wire:confirm="Skip this athlete workout?">Skip</button></div>@else<span class="tl-muted">Locked</span>@endif</td></tr>
            @empty<tr><td colspan="7" class="tl-muted">No workouts match this date range.</td></tr>@endforelse
        </tbody></table></div><div class="mt-3">{{ $workouts->links() }}</div>
    </x-tl.table-card>
</div>
