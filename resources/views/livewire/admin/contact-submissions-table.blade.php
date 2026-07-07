<div>
    <div class="tl-hero">
        <div class="tl-eyebrow">Website inbox</div>
        <h2 class="h1 fw-bold">Contact submissions</h2>
        <p class="tl-muted mb-0">Public website leads and support requests land here.</p>
    </div>

    <div class="tl-panel">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input class="form-control" type="search" placeholder="Search name, email, phone, or message" wire:model.live.debounce.300ms="search">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" wire:model.live="status">
                    <option value="all">All</option>
                    <option value="new">New</option>
                    <option value="reviewed">Reviewed</option>
                    <option value="closed">Closed</option>
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
                <a class="btn btn-outline-tl w-100" href="{{ route('admin.contact-submissions.export', ['search' => $search, 'status' => $status]) }}"><i class="fa-solid fa-download me-2"></i>Export CSV</a>
            </div>
        </div>
    </div>

    <div class="tl-panel">
        <div class="tl-table-wrap">
            <table class="table tl-table align-middle">
                <thead><tr><th>Contact</th><th>Message</th><th>Status</th><th>Received</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($submissions as $submission)
                    <tr>
                        <td>
                            <strong>{{ $submission->name }}</strong><br>
                            <span class="tl-muted">{{ $submission->email }}</span><br>
                            <span class="tl-muted">{{ $submission->phone ?: 'No phone' }}</span>
                        </td>
                        <td class="text-break">{{ $submission->message }}</td>
                        <td><span class="tl-badge {{ $submission->status === 'new' ? 'gold' : ($submission->status === 'closed' ? 'gray' : 'green') }}">{{ $submission->status }}</span></td>
                        <td>{{ $submission->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-outline-tl btn-sm" type="button" wire:click="markStatus({{ $submission->id }}, 'reviewed')">Reviewed</button>
                                <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="markStatus({{ $submission->id }}, 'closed')">Close</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="tl-muted">No contact submissions found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $submissions->links() }}</div>
    </div>
</div>
