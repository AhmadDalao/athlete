<div>
    <div class="tl-hero"><div class="tl-eyebrow">Progress</div><h2 class="h1 fw-bold">Daily check-ins</h2><p class="tl-muted mb-0">Food, body, sleep quality, soreness, energy, and notes.</p></div>
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="tl-stat"><span class="tl-muted">Entries</span><strong>{{ $stats['entries'] }}</strong></div></div>
        <div class="col-6 col-lg-3"><div class="tl-stat"><span class="tl-muted">Avg weight</span><strong>{{ $stats['avgWeight'] }}</strong><small class="tl-muted">kg</small></div></div>
        <div class="col-6 col-lg-3"><div class="tl-stat"><span class="tl-muted">Avg protein</span><strong>{{ $stats['avgProtein'] }}</strong><small class="tl-muted">g</small></div></div>
        <div class="col-6 col-lg-3"><div class="tl-stat"><span class="tl-muted">Avg energy</span><strong>{{ $stats['avgEnergy'] }}</strong><small class="tl-muted">/10</small></div></div>
    </div>
    <div class="tl-panel">
        <h3 class="h5 mb-3">Add or update today</h3>
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
    </div>
    <div class="tl-panel">
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
    </div>
    <div class="tl-panel">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4"><label class="form-label">Search</label><input class="form-control" type="search" placeholder="Search notes" wire:model.live.debounce.300ms="search"></div>
            <div class="col-md-2"><label class="form-label">From</label><input class="form-control" type="date" wire:model.live="from"></div>
            <div class="col-md-2"><label class="form-label">To</label><input class="form-control" type="date" wire:model.live="to"></div>
            <div class="col-md-2"><label class="form-label">Show</label><select class="form-select" wire:model.live="perPage">@foreach($pageSizeOptions as $option)<option value="{{ $option }}">{{ $option === 'all' ? 'All' : $option }}</option>@endforeach</select></div>
        </div>
    </div>
    <div class="tl-panel">
        <div class="d-flex justify-content-between gap-3 flex-wrap mb-3">
            <div>
                <h3 class="h5 mb-1">Progress table</h3>
                <p class="tl-muted mb-0">The table is the source of truth. Charts only summarize the newest entries.</p>
            </div>
        </div>
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Date</th><th>Weight</th><th>Calories</th><th>Protein</th><th>Hydration</th><th>Sleep</th><th>Soreness</th><th>Energy</th><th>Notes</th></tr></thead>
            <tbody>@forelse($entries as $entry)<tr><td>{{ $entry->logged_on->format('Y-m-d') }}</td><td>{{ $entry->weight }}</td><td>{{ $entry->calories }}</td><td>{{ $entry->protein }}g</td><td>{{ $entry->hydration }}ml</td><td>{{ $entry->sleep_quality }}/10</td><td>{{ $entry->soreness }}/10</td><td>{{ $entry->energy }}/10</td><td>{{ $entry->notes }}</td></tr>@empty<tr><td colspan="9" class="tl-muted">No progress entries.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="mt-3">{{ $entries->links() }}</div>
    </div>
</div>
