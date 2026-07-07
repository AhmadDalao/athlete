<div>
    <div class="tl-hero"><div class="tl-eyebrow">Invitations</div><h2 class="h1 fw-bold">Athlete invites</h2><p class="tl-muted mb-0">Track every invite across every coach.</p></div>
    <div class="tl-panel">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input class="form-control" type="search" placeholder="Search invite name, email, or coach" wire:model.live.debounce.300ms="search">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" wire:model.live="status">
                    <option value="all">All</option>
                    <option value="pending">Pending</option>
                    <option value="accepted">Accepted</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Show</label>
                <select class="form-select" wire:model.live="perPage">
                    @foreach($pageSizeOptions as $option)
                        <option value="{{ $option }}">{{ $option === 'all' ? 'All' : $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <a class="btn btn-outline-tl w-100" href="{{ route('admin.invitations.export', ['search' => $search, 'status' => $status]) }}"><i class="fa-solid fa-download me-2"></i>Export CSV</a>
            </div>
        </div>
    </div>
    <div class="tl-panel">
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Athlete</th><th>Coach</th><th>Status</th><th>Expires</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($invitations as $invite)
                <tr>
                    <td><strong>{{ $invite->name ?: 'No name' }}</strong><br><span class="tl-muted">{{ $invite->email }}</span></td>
                    <td>{{ $invite->coach?->name }}</td>
                    <td><span class="tl-badge {{ $invite->status === 'pending' ? 'gold' : 'gray' }}">{{ $invite->status }}</span></td>
                    <td>{{ $invite->expires_at->format('Y-m-d') }}</td>
                    <td>{{ $invite->created_at->format('Y-m-d') }}</td>
                    <td>
                        @if($invite->status === 'pending')
                            <button class="btn btn-outline-tl btn-sm" wire:click="resend({{ $invite->id }})">Resend</button>
                            <button class="btn btn-outline-danger btn-sm" wire:click="cancel({{ $invite->id }})">Cancel</button>
                        @else
                            <span class="tl-muted">No action</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="tl-muted">No invitations found.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="mt-3">{{ $invitations->links() }}</div>
    </div>
</div>
