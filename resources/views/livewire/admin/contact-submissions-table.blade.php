<div>
    <x-tl.page-hero
        eyebrow="Website inbox"
        title="Contact submissions"
        subtitle="Public website leads and support requests land here. Review them quickly, close noise, and keep real opportunities visible."
    />

    <x-tl.section-card title="Filter inbox" subtitle="Search public leads by contact details or message text.">
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
    </x-tl.section-card>

    <x-tl.table-card
        title="Submission table"
        subtitle="Keep this table clean: new, reviewed, or closed."
        :count="$submissions->total()"
        icon="fa-solid fa-inbox"
    >
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
    </x-tl.table-card>
</div>
