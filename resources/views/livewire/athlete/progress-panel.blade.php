<div>
    <div class="tl-hero"><div class="tl-eyebrow">Progress</div><h2 class="h1 fw-bold">Daily check-ins</h2><p class="tl-muted mb-0">Food, body, sleep quality, soreness, energy, and notes.</p></div>
    <div class="tl-panel">
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
    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search notes'])
    <div class="tl-panel">
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Date</th><th>Weight</th><th>Calories</th><th>Protein</th><th>Hydration</th><th>Sleep</th><th>Soreness</th><th>Energy</th><th>Notes</th></tr></thead>
            <tbody>@forelse($entries as $entry)<tr><td>{{ $entry->logged_on->format('Y-m-d') }}</td><td>{{ $entry->weight }}</td><td>{{ $entry->calories }}</td><td>{{ $entry->protein }}g</td><td>{{ $entry->hydration }}ml</td><td>{{ $entry->sleep_quality }}/10</td><td>{{ $entry->soreness }}/10</td><td>{{ $entry->energy }}/10</td><td>{{ $entry->notes }}</td></tr>@empty<tr><td colspan="9" class="tl-muted">No progress entries.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="mt-3">{{ $entries->links() }}</div>
    </div>
</div>
