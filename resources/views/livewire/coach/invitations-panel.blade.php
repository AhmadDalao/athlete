<div>
    <x-tl.page-hero
        eyebrow="Invite athletes"
        title="Invitations"
        subtitle="Invite athletes by email, resend pending links, and cancel stale invitations before they clutter the roster."
    />

    <x-tl.section-card title="Create invite" subtitle="The athlete receives a setup link and is automatically assigned to you after acceptance.">
        <form class="row g-3 align-items-end" wire:submit.prevent="invite">
            <div class="col-md-4"><label class="form-label">Name</label><input class="form-control" wire:model="name"></div>
            <div class="col-md-5"><label class="form-label">Email</label><input class="form-control" type="email" wire:model="email"></div>
            <div class="col-md-3"><button class="btn btn-tl w-100" type="submit">Create invite</button></div>
        </form>
    </x-tl.section-card>

    @include('livewire.partials.table-toolbar', ['placeholder' => 'Search invite'])

    <x-tl.table-card
        title="Invite table"
        subtitle="Pending invites are actionable. Accepted and cancelled records stay visible for audit."
        :count="$invitations->total()"
        icon="fa-solid fa-paper-plane"
    >
        <div class="tl-table-wrap"><table class="table tl-table align-middle">
            <thead><tr><th>Athlete</th><th>Status</th><th>Expires</th><th>Accept link</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($invitations as $invite)
                <tr>
                    <td><strong>{{ $invite->name ?: 'No name' }}</strong><br><span class="tl-muted">{{ $invite->email }}</span></td>
                    <td><span class="tl-badge {{ $invite->status === 'pending' ? 'gold' : 'gray' }}">{{ $invite->status }}</span></td>
                    <td>{{ $invite->expires_at->format('Y-m-d') }}</td>
                    <td><code>{{ route('invites.accept', $invite->token) }}</code></td>
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
                <tr><td colspan="5" class="tl-muted">No invitations yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="mt-3">{{ $invitations->links() }}</div>
    </x-tl.table-card>
</div>
