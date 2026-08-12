<div>
    <x-tl.page-hero
        eyebrow="Accountability"
        title="Audit log"
        subtitle="System changes and access-sensitive actions stay searchable, exportable, and separate from mail delivery."
    >
        <x-slot:actions><a class="btn btn-outline-tl" href="{{ route('admin.audit.export', ['search' => $search, 'audit_action' => $auditAction, 'audit_entity' => $auditEntity, 'from' => $from, 'to' => $to]) }}"><i class="fa-solid fa-download me-2"></i>Export CSV</a></x-slot:actions>
    </x-tl.page-hero>

    <x-tl.section-card title="Filter logs" subtitle="Search, date range, and log-specific filters update in place.">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4">
                <label class="form-label">Search</label>
                <input class="form-control" type="search" placeholder="Search logs" wire:model.live.debounce.300ms="search">
            </div>
            <div class="col-md-2">
                <label class="form-label">Action</label>
                <select class="form-select" wire:model.live="auditAction">
                    <option value="all">All actions</option>
                    @foreach($auditActions as $action)<option value="{{ $action }}">{{ $action }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Entity</label>
                <select class="form-select" wire:model.live="auditEntity">
                    <option value="all">All entities</option>
                    @foreach($auditEntities as $entity)<option value="{{ $entity }}">{{ $entity }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">From</label><input class="form-control" type="date" wire:model.live="from"></div>
            <div class="col-md-2"><label class="form-label">To</label><input class="form-control" type="date" wire:model.live="to"></div>
            <div class="col-md-2">
                <label class="form-label">Show</label>
                <select class="form-select" wire:model.live="perPage">
                    @foreach($pageSizeOptions as $option)
                        <option value="{{ $option }}">{{ $option === 'all' ? 'All' : $option }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-tl.section-card>

    <x-tl.table-card
        title="Audit table"
        subtitle="This is the operational trail. Export the current filtered result when you need a handover."
        :count="$auditLogs->total()"
        icon="fa-solid fa-clipboard-list"
    >
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Summary</th><th>IP</th></tr></thead>
            <tbody>@forelse($auditLogs as $log)<tr><td>{{ $log->created_at->format('Y-m-d H:i') }}</td><td>{{ $log->user?->name ?? 'System' }}</td><td><span class="tl-badge gray">{{ $log->action }}</span></td><td>{{ $log->entity }}</td><td>{{ $log->summary }}</td><td>{{ $log->ip_address ?: '—' }}</td></tr>@empty<tr><td colspan="6" class="tl-muted">No audit logs match the filters.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="mt-3">{{ $auditLogs->links() }}</div>
    </x-tl.table-card>
</div>
