<div class="tl-section-card">
    <div class="row g-3 align-items-end">
        <div class="col-md-7">
            <label class="form-label">Search</label>
            <input class="form-control" type="search" placeholder="{{ $placeholder ?? 'Search records...' }}" wire:model.live.debounce.300ms="search">
        </div>
        <div class="col-md-2">
            <label class="form-label">Show</label>
            <select class="form-select" wire:model.live="perPage">
                @foreach($pageSizeOptions as $option)
                    <option value="{{ $option }}">{{ $option === 'all' ? 'All' : $option }}</option>
                @endforeach
            </select>
        </div>
        {{ $slot ?? '' }}
    </div>
</div>
