<div>
    <x-tl.page-hero eyebrow="People control" title="Roster assignments" subtitle="Connect multiple coaches and athletes inside the active organization, then keep the relationship history auditable." />

    <x-tl.section-card title="Assign coach" subtitle="An athlete may work with multiple active coaches. Existing inactive links are reactivated instead of duplicated.">
        <form class="row g-3 align-items-end" wire:submit.prevent="assign">
            <div class="col-md-5"><label class="form-label">Coach</label><select class="form-select" wire:model="coachId"><option value="">Choose coach</option>@foreach($coaches as $coach)<option value="{{ $coach->id }}">{{ $coach->name }} · {{ $coach->email }}</option>@endforeach</select>@error('coachId')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-5"><label class="form-label">Athlete</label><select class="form-select" wire:model="athleteId"><option value="">Choose athlete</option>@foreach($athletes as $athlete)<option value="{{ $athlete->id }}">{{ $athlete->name }} · {{ $athlete->email }}</option>@endforeach</select>@error('athleteId')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-2"><button class="btn btn-tl w-100" type="submit">Assign</button></div>
        </form>
    </x-tl.section-card>

    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search coach or athlete'])
    <div class="row g-3 mb-3"><div class="col-md-3"><label class="form-label">Status</label><select class="form-select" wire:model.live="status"><option value="all">All</option><option value="active">Active</option><option value="inactive">Ended</option></select></div></div>

    <x-tl.table-card title="Coach-athlete links" subtitle="Ending a link removes roster access without deleting historical programs or progress." :count="$assignments->total()" icon="fa-solid fa-people-arrows">
        <div class="tl-table-wrap"><table class="table tl-table align-middle"><thead><tr><th>Coach</th><th>Athlete</th><th>Status</th><th>Started</th><th>Ended</th><th>Action</th></tr></thead><tbody>
        @forelse($assignments as $assignment)<tr><td><strong>{{ $assignment->coach->name }}</strong><br><span class="tl-muted">{{ $assignment->coach->email }}</span></td><td><strong>{{ $assignment->athlete->name }}</strong><br><span class="tl-muted">{{ $assignment->athlete->email }}</span></td><td><span class="tl-badge {{ $assignment->status === 'active' ? 'green' : 'gray' }}">{{ $assignment->status }}</span></td><td>{{ $assignment->started_at?->format('Y-m-d') ?: '-' }}</td><td>{{ $assignment->ended_at?->format('Y-m-d') ?: '-' }}</td><td>@if($assignment->status === 'active')<button class="btn btn-outline-danger btn-sm" wire:click="end({{ $assignment->id }})" wire:confirm="End this coach-athlete relationship?">End</button>@else<button class="btn btn-outline-tl btn-sm" wire:click="reactivate({{ $assignment->id }})">Reactivate</button>@endif</td></tr>
        @empty<tr><td colspan="6" class="tl-muted">No roster assignments match the current filters.</td></tr>@endforelse
        </tbody></table></div><div class="mt-3">{{ $assignments->links() }}</div>
    </x-tl.table-card>
</div>
