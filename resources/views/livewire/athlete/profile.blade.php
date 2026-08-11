<div>
    <x-tl.page-hero eyebrow="Account" title="Profile and preferences" subtitle="Keep your contact, sport, and emergency details current.">
        <x-slot:actions><x-tl.theme-switch /></x-slot:actions>
    </x-tl.page-hero>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-tl.section-card title="Personal profile" subtitle="Information your authorized coaches can use to support training.">
                @if($errors->any())<div class="alert alert-danger tl-alert"><i class="fa-solid fa-triangle-exclamation"></i><div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>@endif
                <form class="row g-3" wire:submit="save">
                    <div class="col-md-6"><label class="form-label">Full name</label><input class="form-control" wire:model="name" autocomplete="name"></div>
                    <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" wire:model="phone" autocomplete="tel"></div>
                    <div class="col-md-6"><label class="form-label">Sport</label><input class="form-control" wire:model="sport"></div>
                    <div class="col-md-6"><label class="form-label">Position / discipline</label><input class="form-control" wire:model="position"></div>
                    <div class="col-md-6"><label class="form-label">Height cm</label><input class="form-control" type="number" step="0.1" wire:model="heightCm"></div>
                    <div class="col-md-6"><label class="form-label">Timezone</label><input class="form-control" wire:model="timezone" placeholder="Asia/Riyadh"></div>
                    <div class="col-12"><label class="form-label">Primary goal</label><input class="form-control" wire:model="primaryGoal"></div>
                    <div class="col-12"><label class="form-label">Bio</label><textarea class="form-control" rows="4" wire:model="bio"></textarea></div>
                    <div class="col-md-6"><label class="form-label">Emergency contact</label><input class="form-control" wire:model="emergencyContactName"></div>
                    <div class="col-md-6"><label class="form-label">Emergency phone</label><input class="form-control" wire:model="emergencyContactPhone"></div>
                    <div class="col-12"><button class="btn btn-tl" type="submit" wire:loading.attr="disabled">Save profile</button></div>
                </form>
            </x-tl.section-card>
        </div>
        <div class="col-xl-4">
            <x-tl.section-card title="Organizations" subtitle="The teams currently connected to your account.">
                <div class="vstack gap-2">@forelse($organizations as $organization)<div class="tl-mobile-record"><div class="d-flex align-items-center gap-3"><span class="tl-organization-avatar">{{ str($organization->name)->substr(0, 2)->upper() }}</span><div><strong class="d-block">{{ $organization->name }}</strong><span class="tl-muted small">{{ str($organization->pivot->role)->headline() }}</span></div></div></div>@empty<div class="tl-empty-state compact"><span>No active organization membership.</span></div>@endforelse</div>
            </x-tl.section-card>
            <x-tl.section-card title="Sign in" subtitle="Your email is managed securely by the platform."><div class="tl-mobile-record"><span class="tl-muted small d-block">Email</span><strong>{{ auth()->user()->email }}</strong></div><form class="mt-3" method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-danger w-100" type="submit"><i class="fa-solid fa-arrow-right-from-bracket"></i> Log out</button></form></x-tl.section-card>
        </div>
    </div>
</div>
