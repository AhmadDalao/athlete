<div>
    <x-tl.page-hero
        eyebrow="Organization access"
        :title="$membership->user->name"
        :subtitle="$membership->user->email.' · '.str($membership->role)->headline().' at '.$organization->name"
    >
        <x-slot:actions>
            @if(auth()->user()->can('organizations.manage'))
                <a class="btn btn-outline-tl" href="{{ route('admin.organizations.show', $organization) }}"><i class="fa-solid fa-arrow-left"></i> Organization</a>
            @else
                <a class="btn btn-outline-tl" href="{{ route('admin.users') }}"><i class="fa-solid fa-arrow-left"></i> Users</a>
            @endif
            <a class="btn btn-outline-tl" href="{{ route('admin.users.show', $membership->user) }}"><i class="fa-solid fa-user"></i> User profile</a>
        </x-slot:actions>
        <x-slot:visual>
            <div class="row g-3">
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-shield-halved" label="Role defaults" :value="count($defaults)" tone="lime" /></div>
                <div class="col-6"><x-tl.metric-card icon="fa-solid fa-sliders" label="Overrides" :value="$overrideCount" tone="gold" /></div>
            </div>
        </x-slot:visual>
    </x-tl.page-hero>

    @if($membership->isOwner())
        <x-tl.section-card eyebrow="Owner protection" title="This access is locked" subtitle="Organization owners always retain every organization-level permission. Assign a new owner before changing this membership.">
            <span class="tl-badge gold"><i class="fa-solid fa-lock"></i> Full organization access</span>
        </x-tl.section-card>
    @else
        <form wire:submit.prevent="save">
            <x-tl.section-card eyebrow="Access policy" title="Role defaults with precise overrides" subtitle="Default follows the current organization role. Allow or deny only the exceptions; platform controls are never available here.">
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <button class="btn btn-outline-tl" type="button" wire:click="useRoleDefaults"><i class="fa-solid fa-rotate-left"></i> Reset to role defaults</button>
                    <button class="btn btn-tl" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save permissions</button>
                </div>

                <div class="row g-3">
                    @foreach($groups as $group => $permissions)
                        <div class="col-12 col-xl-6">
                            <div class="tl-access-group h-100">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                                    <h2 class="h5 mb-0">{{ $group }}</h2>
                                    <span class="tl-badge gray">{{ count($permissions) }} controls</span>
                                </div>
                                <div class="d-grid gap-3">
                                    @foreach($permissions as $permission => $description)
                                        <div class="tl-access-row">
                                            <div class="min-w-0">
                                                <strong class="d-block">{{ $description }}</strong>
                                                <code>{{ $permission }}</code>
                                                <span class="d-block tl-muted small mt-1">Role default: {{ in_array($permission, $defaults, true) ? 'Allowed' : 'Denied' }}</span>
                                            </div>
                                            @php($accessKey = str_replace('.', '__', $permission))
                                            <select class="form-select form-select-sm tl-access-select" wire:model="access.{{ $accessKey }}" aria-label="{{ $description }}">
                                                <option value="default">Use default</option>
                                                <option value="allow">Allow</option>
                                                <option value="deny">Deny</option>
                                            </select>
                                        </div>
                                        @error("access.{$accessKey}")<div class="text-danger small">{{ $message }}</div>@enderror
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-tl" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save permissions</button>
                </div>
            </x-tl.section-card>
        </form>
    @endif
</div>
