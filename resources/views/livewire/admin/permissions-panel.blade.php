<div>
    <div class="tl-hero"><div class="tl-eyebrow">Access control</div><h2 class="h1 fw-bold">Permissions</h2><p class="tl-muted mb-0">Owner is locked. Everyone else gets explicit access.</p></div>
    <div class="tl-panel">
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
    </div>
</div>
