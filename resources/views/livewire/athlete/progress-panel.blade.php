<div>
    <x-tl.page-hero
        eyebrow="Progress"
        title="Daily check-ins"
        subtitle="Track food, body, hydration, sleep quality, soreness, energy, and notes without turning the app into a maze."
    />

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-list-check" label="Entries" :value="$stats['entries']" tone="lime" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-weight-scale" label="Avg weight" :value="$stats['avgWeight']" detail="kg" tone="emerald" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-bowl-food" label="Avg protein" :value="$stats['avgProtein']" detail="g" tone="gold" /></div>
        <div class="col-6 col-lg-3"><x-tl.metric-card icon="fa-solid fa-bolt" label="Avg energy" :value="$stats['avgEnergy']" detail="/10" tone="blue" /></div>
    </div>

    <x-tl.section-card title="Add or update today" subtitle="One form, one daily record. Saving the same date updates that date.">
        <form class="row g-3 align-items-end" wire:submit.prevent="save">
            <div class="col-md-2"><label class="form-label">Date</label><input class="form-control" type="date" wire:model="loggedOn"></div>
            <div class="col-md-2"><label class="form-label">Weight</label><input class="form-control" type="number" step="0.1" wire:model="weight"></div>
            <div class="col-md-2"><label class="form-label">Calories</label><input class="form-control" type="number" wire:model="calories"></div>
            <div class="col-md-2"><label class="form-label">Protein</label><input class="form-control" type="number" wire:model="protein"></div>
            <div class="col-md-2"><label class="form-label">Hydration ml</label><input class="form-control" type="number" wire:model="hydration"></div>
            <div class="col-md-2"><label class="form-label">Sleep 1-10</label><input class="form-control" type="number" wire:model="sleepQuality"></div>
            <div class="col-md-2"><label class="form-label">Soreness</label><input class="form-control" type="number" wire:model="soreness"></div>
            <div class="col-md-2"><label class="form-label">Energy</label><input class="form-control" type="number" wire:model="energy"></div>
            <div class="col-md-8"><label class="form-label">Notes</label><input class="form-control" wire:model="notes"></div>
            <div class="col-md-2"><button class="btn btn-tl w-100" type="submit">Save</button></div>
        </form>
    </x-tl.section-card>

    <x-tl.section-card title="Trends" subtitle="Charts summarize the latest entries. The table below remains the source of truth.">
        <div class="row g-3">
            @foreach(['Weight' => $charts['weight'], 'Protein' => $charts['protein'], 'Energy' => $charts['energy']] as $label => $series)
                <div class="col-lg-4">
                    <div class="tl-mini-chart">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h3 class="h6 mb-1">{{ $label }} trend</h3>
                                <span class="tl-muted">Latest 7 logged entries</span>
                            </div>
                            <span class="tl-badge gray">trend</span>
                        </div>
                        <div class="tl-bars">
                            @forelse($series as $point)
                                <div class="tl-bar-item">
                                    <span class="tl-bar-value">{{ $point['value'] }}</span>
                                    <span class="tl-bar" style="height: {{ $point['height'] }}%"></span>
                                    <span class="tl-bar-date">{{ $point['date'] }}</span>
                                </div>
                            @empty
                                <div class="tl-muted">No data yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-tl.section-card>

    <x-tl.section-card title="Filter progress" subtitle="Find entries by date range or note text.">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4"><label class="form-label">Search</label><input class="form-control" type="search" placeholder="Search notes" wire:model.live.debounce.300ms="search"></div>
            <div class="col-md-2"><label class="form-label">From</label><input class="form-control" type="date" wire:model.live="from"></div>
            <div class="col-md-2"><label class="form-label">To</label><input class="form-control" type="date" wire:model.live="to"></div>
            <div class="col-md-2"><label class="form-label">Show</label><select class="form-select" wire:model.live="perPage">@foreach($pageSizeOptions as $option)<option value="{{ $option }}">{{ $option === 'all' ? 'All' : $option }}</option>@endforeach</select></div>
        </div>
    </x-tl.section-card>

    <x-tl.table-card
        title="Progress table"
        subtitle="The table is the source of truth. Charts only summarize the newest entries."
        :count="$entries->total()"
        icon="fa-solid fa-chart-line"
    >
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Date</th><th>Weight</th><th>Calories</th><th>Protein</th><th>Hydration</th><th>Sleep</th><th>Soreness</th><th>Energy</th><th>Notes</th></tr></thead>
            <tbody>@forelse($entries as $entry)<tr><td>{{ $entry->logged_on->format('Y-m-d') }}</td><td>{{ $entry->weight }}</td><td>{{ $entry->calories }}</td><td>{{ $entry->protein }}g</td><td>{{ $entry->hydration }}ml</td><td>{{ $entry->sleep_quality }}/10</td><td>{{ $entry->soreness }}/10</td><td>{{ $entry->energy }}/10</td><td>{{ $entry->notes }}</td></tr>@empty<tr><td colspan="9" class="tl-muted">No progress entries.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="mt-3">{{ $entries->links() }}</div>
    </x-tl.table-card>
</div>
