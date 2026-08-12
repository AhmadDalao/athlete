<div>
    <x-tl.page-hero eyebrow="Delivery control" title="Email logs" subtitle="Invitation, password, and system mail attempts are tracked in one operational table.">
        <x-slot:actions><a class="btn btn-outline-tl" href="{{ route('admin.email-logs.export', ['search' => $search, 'email_status' => $status, 'email_type' => $type, 'from' => $from, 'to' => $to]) }}"><i class="fa-solid fa-download me-2"></i>Export CSV</a></x-slot:actions>
    </x-tl.page-hero>

    <div class="row g-3 mb-3">
        <div class="col-4"><x-tl.metric-card label="Attempts" :value="$summary['total']" icon="fa-solid fa-envelopes-bulk" tone="blue" /></div>
        <div class="col-4"><x-tl.metric-card label="Sent" :value="$summary['sent']" icon="fa-solid fa-circle-check" tone="emerald" /></div>
        <div class="col-4"><x-tl.metric-card label="Failed" :value="$summary['failed']" icon="fa-solid fa-triangle-exclamation" tone="gold" /></div>
    </div>

    <x-tl.section-card title="Filter delivery attempts" subtitle="Every change refreshes this table in place through Livewire.">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4"><label class="form-label">Search</label><input class="form-control" type="search" placeholder="Recipient, subject, type, or error" wire:model.live.debounce.300ms="search"></div>
            <div class="col-md-2"><label class="form-label">Status</label><select class="form-select" wire:model.live="status"><option value="all">All statuses</option>@foreach($statuses as $option)<option value="{{ $option }}">{{ str($option)->headline() }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Type</label><select class="form-select" wire:model.live="type"><option value="all">All types</option>@foreach($types as $option)<option value="{{ $option }}">{{ str($option)->headline() }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label class="form-label">From</label><input class="form-control" type="date" wire:model.live="from"></div>
            <div class="col-6 col-md-2"><label class="form-label">To</label><input class="form-control" type="date" wire:model.live="to"></div>
            <div class="col-md-2"><label class="form-label">Show</label><select class="form-select" wire:model.live="perPage">@foreach($pageSizeOptions as $option)<option value="{{ $option }}">{{ $option === 'all' ? 'All' : $option }}</option>@endforeach</select></div>
        </div>
    </x-tl.section-card>

    <x-tl.table-card title="Delivery attempts" subtitle="Failures keep the provider error visible so mail configuration can be fixed without guessing." :count="$emailLogs->total()" icon="fa-solid fa-envelope-circle-check">
        <div class="tl-table-wrap"><table class="table tl-table align-middle"><thead><tr><th>Time</th><th>Recipient</th><th>Subject</th><th>Type</th><th>Status</th><th>Provider result</th></tr></thead><tbody>
            @forelse($emailLogs as $log)
                <tr wire:key="email-log-{{ $log->id }}"><td>{{ $log->created_at->format('Y-m-d H:i') }}</td><td><strong>{{ $log->recipient }}</strong></td><td>{{ $log->subject }}</td><td>{{ str($log->type)->headline() }}</td><td><span class="tl-badge {{ $log->status === 'sent' ? 'green' : ($log->status === 'failed' ? 'gold' : 'gray') }}">{{ $log->status }}</span></td><td>{{ $log->error ?: 'Accepted by configured mailer' }}</td></tr>
            @empty
                <tr><td colspan="6" class="tl-muted">No email attempts match the filters.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="mt-3">{{ $emailLogs->links() }}</div>
    </x-tl.table-card>
</div>
