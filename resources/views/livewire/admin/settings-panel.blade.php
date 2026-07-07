<div>
    <div class="tl-hero"><div class="tl-eyebrow">System control</div><h2 class="h1 fw-bold">Settings</h2><p class="tl-muted mb-0">Control website copy, invite expiry, support email, and mail labels.</p></div>
    <form class="tl-panel" wire:submit.prevent="save">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">App name</label><input class="form-control" wire:model="settings.app_name"></div>
            <div class="col-md-6"><label class="form-label">Tagline</label><input class="form-control" wire:model="settings.tagline"></div>
            <div class="col-md-6"><label class="form-label">Support email</label><input class="form-control" type="email" wire:model="settings.support_email"></div>
            <div class="col-md-6"><label class="form-label">Invite expiry days</label><input class="form-control" type="number" wire:model="settings.invite_expiry_days"></div>
            <div class="col-12"><label class="form-label">Homepage headline</label><input class="form-control" wire:model="settings.homepage_headline"></div>
            <div class="col-12"><label class="form-label">Homepage subheadline</label><textarea class="form-control" rows="3" wire:model="settings.homepage_subheadline"></textarea></div>
            <div class="col-md-6"><label class="form-label">Mail from name</label><input class="form-control" wire:model="settings.mail_from_name"></div>
            <div class="col-md-6"><label class="form-label">Mail from address</label><input class="form-control" type="email" wire:model="settings.mail_from_address"></div>
        </div>
        @if($errors->any())<div class="text-danger small mt-3">{{ $errors->first() }}</div>@endif
        <button class="btn btn-tl mt-3" type="submit">Save settings</button>
    </form>
</div>
