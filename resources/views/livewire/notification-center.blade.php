<div wire:poll.30s>
    <x-tl.page-hero eyebrow="Inbox" title="Notifications" subtitle="Program changes, schedule updates, workout results, and messages for the active organization." />

    <x-tl.table-card
        eyebrow="Activity"
        title="Organization updates"
        subtitle="Open an update to jump to the related program, workout, athlete, or conversation."
        :count="$notifications->total()"
        icon="fa-solid fa-bell"
    >
        <div class="row g-3 align-items-end mb-3">
            <div class="col-sm-6 col-lg-3">
                <label class="form-label" for="notification-filter">Status</label>
                <select id="notification-filter" class="form-select" wire:model.live="filter">
                    <option value="all">All notifications</option>
                    <option value="unread">Unread only</option>
                </select>
            </div>
            <div class="col-sm-auto">
                <span class="tl-badge {{ $unreadCount > 0 ? 'green' : 'gray' }}">{{ $unreadCount }} unread</span>
            </div>
            <div class="col-sm-auto ms-sm-auto">
                <button class="btn btn-outline-tl" type="button" wire:click="markAllRead" @disabled($unreadCount === 0)>
                    <i class="fa-solid fa-check-double"></i> Mark all read
                </button>
            </div>
        </div>

        <div class="tl-table-wrap">
            <table class="table tl-table align-middle mb-0">
                <thead><tr><th>Status</th><th>Update</th><th>Received</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @forelse($notifications as $notification)
                        <tr wire:key="notification-{{ $notification->id }}" class="{{ $notification->read_at ? '' : 'fw-semibold' }}">
                            <td><span class="tl-badge {{ $notification->read_at ? 'gray' : 'green' }}">{{ $notification->read_at ? 'Read' : 'New' }}</span></td>
                            <td>
                                <strong>{{ data_get($notification->data, 'title', 'Throughline update') }}</strong>
                                <small class="d-block tl-muted">{{ data_get($notification->data, 'body') }}</small>
                            </td>
                            <td><time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->diffForHumans() }}</time></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-tl" type="button" wire:click="open('{{ $notification->id }}')">Open <i class="fa-solid fa-arrow-right"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="text-center py-5 tl-muted"><i class="fa-regular fa-bell-slash fa-2x d-block mb-3"></i>No notifications in this organization.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($notifications->hasPages())<div class="mt-3">{{ $notifications->links() }}</div>@endif
    </x-tl.table-card>
</div>
