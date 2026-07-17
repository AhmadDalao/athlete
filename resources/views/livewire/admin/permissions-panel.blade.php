<div>
    <x-tl.page-hero
        eyebrow="Access control"
        title="Permissions"
        subtitle="Owner access is locked. Everyone else gets explicit grouped permissions so control stays boring and safe."
    />

    <x-tl.section-card title="User access editor" subtitle="Pick a user, apply defaults if needed, then save only the permissions that should be active.">
        <label class="form-label">User</label>
        <select class="form-select mb-4" wire:model.live="selectedUserId">
            @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} · {{ $user->role }}</option>@endforeach
        </select>
        <div class="d-flex gap-2 flex-wrap mb-3">
            <button class="btn btn-outline-tl btn-sm" type="button" wire:click="applyRoleDefaults">Use role defaults</button>
            <button class="btn btn-outline-danger btn-sm" type="button" wire:click="clearSelection">Clear selection</button>
        </div>
        <form wire:submit.prevent="save">
            <div class="row g-3">
                @foreach($groups as $group => $permissions)
                    <div class="col-lg-6">
                        <div class="tl-stat">
                            <h3 class="h5">{{ $group }}</h3>
                            @foreach($permissions as $permission => $description)
                                <label class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" wire:model="selectedPermissions" value="{{ $permission }}">
                                    <span class="form-check-label"><strong>{{ $permission }}</strong><br><small class="tl-muted">{{ $description }}</small></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <button class="btn btn-tl mt-3" type="submit">Save permissions</button>
        </form>
    </x-tl.section-card>
</div>
