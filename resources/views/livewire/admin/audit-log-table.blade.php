<div>
    <div class="tl-hero"><div class="tl-eyebrow">Accountability</div><h2 class="h1 fw-bold">Logs</h2><p class="tl-muted mb-0">Audit and email delivery records.</p></div>
    <div class="tl-panel">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn {{ $tab === 'audit' ? 'btn-tl' : 'btn-outline-tl' }}" type="button" wire:click="setTab('audit')">Audit</button>
                <button class="btn {{ $tab === 'email' ? 'btn-tl' : 'btn-outline-tl' }}" type="button" wire:click="setTab('email')">Email</button>
            </div>
            <a class="btn btn-outline-tl" href="{{ route('admin.audit.export', ['tab' => $tab, 'search' => $search, 'audit_action' => $auditAction, 'audit_entity' => $auditEntity, 'email_status' => $emailStatus, 'email_type' => $emailType, 'from' => $from, 'to' => $to]) }}"><i class="fa-solid fa-download me-2"></i>Export CSV</a>
        </div>
    </div>
    <div class="tl-panel">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4">
                <label class="form-label">Search</label>
                <input class="form-control" type="search" placeholder="Search logs" wire:model.live.debounce.300ms="search">
            </div>
            @if($tab === 'audit')
                <div class="col-md-2">
                    <label class="form-label">Action</label>
                    <select class="form-select" wire:model.live="auditAction">
                        <option value="all">All actions</option>
                        @foreach($auditActions as $action)
                            <option value="{{ $action }}">{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Entity</label>
                    <select class="form-select" wire:model.live="auditEntity">
                        <option value="all">All entities</option>
                        @foreach($auditEntities as $entity)
                            <option value="{{ $entity }}">{{ $entity }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model.live="emailStatus">
                        <option value="all">All statuses</option>
                        @foreach($emailStatuses as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select class="form-select" wire:model.live="emailType">
                        <option value="all">All types</option>
                        @foreach($emailTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
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
    </div>
    <div class="tl-panel">
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            @if($tab === 'audit')
                <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Summary</th><th>IP</th></tr></thead>
                <tbody>@forelse($auditLogs as $log)<tr><td>{{ $log->created_at->format('Y-m-d H:i') }}</td><td>{{ $log->user?->name ?? 'System' }}</td><td>{{ $log->action }}</td><td>{{ $log->entity }}</td><td>{{ $log->summary }}</td><td>{{ $log->ip_address }}</td></tr>@empty<tr><td colspan="6" class="tl-muted">No audit logs.</td></tr>@endforelse</tbody>
            @else
                <thead><tr><th>Time</th><th>Recipient</th><th>Subject</th><th>Type</th><th>Status</th><th>Error</th></tr></thead>
                <tbody>@forelse($emailLogs as $log)<tr><td>{{ $log->created_at->format('Y-m-d H:i') }}</td><td>{{ $log->recipient }}</td><td>{{ $log->subject }}</td><td>{{ $log->type }}</td><td>{{ $log->status }}</td><td>{{ $log->error }}</td></tr>@empty<tr><td colspan="6" class="tl-muted">No email logs.</td></tr>@endforelse</tbody>
            @endif
        </table></div>
        <div class="mt-3">{{ $tab === 'audit' ? $auditLogs->links() : $emailLogs->links() }}</div>
    </div>
</div>
