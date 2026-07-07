<div>
    <div class="tl-hero"><div class="tl-eyebrow">Accountability</div><h2 class="h1 fw-bold">Logs</h2><p class="tl-muted mb-0">Audit and email delivery records.</p></div>
    <div class="tl-panel d-flex gap-2"><button class="btn {{ $tab === 'audit' ? 'btn-tl' : 'btn-outline-tl' }}" wire:click="$set('tab','audit')">Audit</button><button class="btn {{ $tab === 'email' ? 'btn-tl' : 'btn-outline-tl' }}" wire:click="$set('tab','email')">Email</button></div>
    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search logs'])
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
