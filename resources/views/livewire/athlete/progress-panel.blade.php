<div>
    <x-tl.page-hero eyebrow="Performance" title="Progress that means something" subtitle="Check-ins, training output, progress photos, and personal records in one place." />

    <div class="tl-range-tabs mb-3" role="group" aria-label="Performance range">
        @foreach(['7' => 'Week', '30' => 'Month', '90' => '3 months', '365' => 'Year'] as $value => $label)
            <button class="{{ $range === $value ? 'active' : '' }}" type="button" wire:click="$set('range', '{{ $value }}')">{{ $label }}</button>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3"><x-tl.metric-card icon="fa-solid fa-dumbbell" label="Workouts" :value="$performance['workouts']" :detail="$range.' day range'" tone="lime" /></div>
        <div class="col-6 col-xl-3"><x-tl.metric-card icon="fa-solid fa-list-check" label="Sets completed" :value="$performance['sets']" detail="Logged execution" tone="emerald" /></div>
        <div class="col-6 col-xl-3"><x-tl.metric-card icon="fa-solid fa-weight-hanging" label="Volume" :value="number_format($performance['volume'])" detail="Load × reps" tone="gold" /></div>
        <div class="col-6 col-xl-3"><x-tl.metric-card icon="fa-regular fa-clock" label="Duration" :value="$performance['duration'].' min'" detail="Completed sessions" tone="blue" /></div>
    </div>

    <x-tl.section-card title="Daily check-in" subtitle="Saving an existing date updates that record instead of creating a duplicate.">
        @if($errors->any())<div class="alert alert-danger tl-alert"><i class="fa-solid fa-triangle-exclamation"></i><div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>@endif
        <form class="row g-3 align-items-end" wire:submit="save">
            <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Date</label><input class="form-control" type="date" wire:model="loggedOn"></div>
            <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Weight kg</label><input class="form-control" type="number" step="0.1" wire:model="weight"></div>
            <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Calories</label><input class="form-control" type="number" wire:model="calories"></div>
            <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Protein g</label><input class="form-control" type="number" wire:model="protein"></div>
            <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Hydration ml</label><input class="form-control" type="number" wire:model="hydration"></div>
            <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Sleep 1–10</label><input class="form-control" type="number" min="1" max="10" wire:model="sleepQuality"></div>
            <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Soreness 1–10</label><input class="form-control" type="number" min="1" max="10" wire:model="soreness"></div>
            <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Energy 1–10</label><input class="form-control" type="number" min="1" max="10" wire:model="energy"></div>
            <div class="col-md-6"><label class="form-label">Note for coach</label><input class="form-control" wire:model="notes" placeholder="Recovery, pain, appetite, or context"></div>
            <div class="col-md-2"><button class="btn btn-tl w-100" type="submit" wire:loading.attr="disabled">Save check-in</button></div>
        </form>
    </x-tl.section-card>

    <x-tl.section-card title="Health trends" subtitle="Range-aware summaries from your own check-ins.">
        <div class="row g-3">
            @foreach(['Weight' => $charts['weight'], 'Protein' => $charts['protein'], 'Energy' => $charts['energy']] as $label => $series)
                <div class="col-xl-4"><div class="tl-mini-chart"><div class="d-flex justify-content-between align-items-start gap-2 mb-3"><div><h3 class="h6 mb-1">{{ $label }}</h3><span class="tl-muted">Last {{ $range }} days</span></div><span class="tl-badge gray">trend</span></div><div class="tl-bars">
                    @forelse($series as $point)<div class="tl-bar-item"><span class="tl-bar-value">{{ $point['value'] }}</span><span class="tl-bar" style="height: {{ $point['height'] }}%"></span><span class="tl-bar-date">{{ $point['date'] }}</span></div>@empty<div class="tl-empty-state compact"><span>No data yet.</span></div>@endforelse
                </div></div></div>
            @endforeach
        </div>
    </x-tl.section-card>

    <div class="row g-3 mb-3">
        <div class="col-xl-5">
            <section class="tl-section-card h-100">
                <div class="tl-section-head"><div><h3>Progress photos</h3><p>Private to you and authorized coaches.</p></div></div>
                <form class="row g-3 mb-4" wire:submit="uploadPhoto">
                    <div class="col-12"><label class="form-label">Photo</label><input class="form-control" type="file" accept="image/*" wire:model="photo"></div>
                    <div class="col-6"><label class="form-label">Taken on</label><input class="form-control" type="date" wire:model="photoTakenOn"></div>
                    <div class="col-6"><label class="form-label">View</label><select class="form-select" wire:model="photoCategory"><option value="progress">Progress</option><option value="front">Front</option><option value="side">Side</option><option value="back">Back</option><option value="other">Other</option></select></div>
                    <div class="col-12"><label class="form-label">Note</label><input class="form-control" wire:model="photoNotes"></div>
                    <div class="col-12"><button class="btn btn-tl w-100" type="submit" wire:loading.attr="disabled" wire:target="photo,uploadPhoto">Upload photo</button></div>
                </form>
                <div class="tl-photo-grid">
                    @forelse($photos as $item)
                        <article class="tl-photo-card"><img src="{{ route('app.progress.photos.view', $item) }}" alt="{{ $item->category }} progress photo from {{ $item->taken_on->format('M j, Y') }}"><div><strong>{{ str($item->category)->headline() }}</strong><span>{{ $item->taken_on->format('M j, Y') }}</span></div><button class="btn btn-sm btn-outline-danger" type="button" wire:click="deletePhoto({{ $item->id }})" wire:confirm="Delete this progress photo?"><i class="fa-solid fa-trash"></i></button></article>
                    @empty<div class="tl-empty-state compact"><span>No progress photos uploaded.</span></div>@endforelse
                </div>
            </section>
        </div>
        <div class="col-xl-7">
            <section class="tl-section-card h-100">
                <div class="tl-section-head"><div><h3>Personal records</h3><p>New best completed loads are captured automatically.</p></div></div>
                <div class="tl-table-wrap"><table class="table tl-table align-middle"><thead><tr><th>Exercise</th><th>Record</th><th>Type</th><th>Date</th></tr></thead><tbody>
                    @forelse($records as $record)<tr><td><strong>{{ $record->exercise_name }}</strong></td><td>{{ rtrim(rtrim(number_format((float) $record->value, 2), '0'), '.') }} {{ $record->unit }}</td><td>{{ str($record->record_type)->headline() }}</td><td>{{ $record->achieved_on->format('Y-m-d') }}</td></tr>@empty<tr><td colspan="4" class="tl-muted">Complete loaded sets to establish personal records.</td></tr>@endforelse
                </tbody></table></div>
            </section>
        </div>
    </div>

    <x-tl.section-card title="Filter check-ins" subtitle="Search notes or restrict the table to a date range.">
        <div class="row g-3 align-items-end"><div class="col-lg-4"><label class="form-label">Search</label><input class="form-control" type="search" placeholder="Search notes" wire:model.live.debounce.300ms="search"></div><div class="col-md-2"><label class="form-label">From</label><input class="form-control" type="date" wire:model.live="from"></div><div class="col-md-2"><label class="form-label">To</label><input class="form-control" type="date" wire:model.live="to"></div><div class="col-md-2"><label class="form-label">Show</label><select class="form-select" wire:model.live="perPage">@foreach($pageSizeOptions as $option)<option value="{{ $option }}">{{ $option === 'all' ? 'All' : $option }}</option>@endforeach</select></div></div>
    </x-tl.section-card>

    <x-tl.table-card title="Check-in history" subtitle="The operational source of truth for your manual progress data." :count="$entries->total()" icon="fa-solid fa-chart-line">
        <div class="tl-table-wrap"><table class="table tl-table align-middle"><thead><tr><th>Date</th><th>Weight</th><th>Calories</th><th>Protein</th><th>Hydration</th><th>Sleep</th><th>Soreness</th><th>Energy</th><th>Notes</th></tr></thead><tbody>
            @forelse($entries as $entry)<tr><td>{{ $entry->logged_on->format('Y-m-d') }}</td><td>{{ $entry->weight !== null ? $entry->weight.' kg' : '-' }}</td><td>{{ $entry->calories ?? '-' }}</td><td>{{ $entry->protein !== null ? $entry->protein.' g' : '-' }}</td><td>{{ $entry->hydration !== null ? $entry->hydration.' ml' : '-' }}</td><td>{{ $entry->sleep_quality !== null ? $entry->sleep_quality.'/10' : '-' }}</td><td>{{ $entry->soreness !== null ? $entry->soreness.'/10' : '-' }}</td><td>{{ $entry->energy !== null ? $entry->energy.'/10' : '-' }}</td><td>{{ $entry->notes ?: '-' }}</td></tr>@empty<tr><td colspan="9" class="tl-muted">No progress entries.</td></tr>@endforelse
        </tbody></table></div><div class="mt-3">{{ $entries->links() }}</div>
    </x-tl.table-card>
</div>
