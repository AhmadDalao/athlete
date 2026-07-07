<div>
    <div class="tl-hero"><div class="tl-eyebrow">System control</div><h2 class="h1 fw-bold">Settings</h2><p class="tl-muted mb-0">Control website copy, invite expiry, support email, and mail labels.</p></div>
    <form wire:submit.prevent="save">
        <div class="tl-panel">
            <div class="tl-eyebrow">Website identity</div>
            <div class="row g-3 mt-1">
                <div class="col-md-6"><label class="form-label">App name</label><input class="form-control" wire:model="settings.app_name"></div>
                <div class="col-md-6"><label class="form-label">Tagline</label><input class="form-control" wire:model="settings.tagline"></div>
                <div class="col-md-6"><label class="form-label">Support email</label><input class="form-control" type="email" wire:model="settings.support_email"></div>
                <div class="col-12"><label class="form-label">Homepage headline</label><input class="form-control" wire:model="settings.homepage_headline"></div>
                <div class="col-12"><label class="form-label">Homepage subheadline</label><textarea class="form-control" rows="3" wire:model="settings.homepage_subheadline"></textarea></div>
                <div class="col-md-5"><label class="form-label">Contact headline</label><input class="form-control" wire:model="settings.contact_headline"></div>
                <div class="col-md-5"><label class="form-label">Contact subheadline</label><input class="form-control" wire:model="settings.contact_subheadline"></div>
                <div class="col-md-2"><label class="form-label">Contact button</label><input class="form-control" wire:model="settings.contact_button_label"></div>
            </div>
        </div>

        <div class="tl-panel">
            <div class="tl-eyebrow">Invitation control</div>
            <div class="row g-3 mt-1">
                <div class="col-md-4"><label class="form-label">Invite expiry days</label><input class="form-control" type="number" wire:model="settings.invite_expiry_days"></div>
                <div class="col-md-8"><label class="form-label">Invite email subject</label><input class="form-control" wire:model="settings.invite_email_subject"></div>
                <div class="col-12">
                    <label class="form-label">Invite email body</label>
                    <textarea class="form-control" rows="6" wire:model="settings.invite_email_body"></textarea>
                    <div class="tl-muted small mt-2">Available tokens: {app_name}, {coach_name}, {athlete_name}, {invite_link}, {expires_at}</div>
                </div>
            </div>
        </div>

        <div class="tl-panel">
            <div class="tl-eyebrow">Public pricing and memberships</div>
            <div class="row g-3 mt-1">
                <div class="col-md-5"><label class="form-label">Pricing headline</label><input class="form-control" wire:model="settings.pricing_headline"></div>
                <div class="col-md-7"><label class="form-label">Pricing subheadline</label><input class="form-control" wire:model="settings.pricing_subheadline"></div>
                @foreach(['one' => 'Plan 1', 'two' => 'Plan 2', 'three' => 'Plan 3'] as $key => $label)
                    <div class="col-12"><hr class="border-secondary opacity-25"></div>
                    <div class="col-md-3"><label class="form-label">{{ $label }} name</label><input class="form-control" wire:model="settings.plan_{{ $key }}_name"></div>
                    <div class="col-md-3"><label class="form-label">{{ $label }} price</label><input class="form-control" wire:model="settings.plan_{{ $key }}_price"></div>
                    <div class="col-md-6"><label class="form-label">{{ $label }} description</label><input class="form-control" wire:model="settings.plan_{{ $key }}_description"></div>
                    <div class="col-12">
                        <label class="form-label">{{ $label }} features</label>
                        <textarea class="form-control" rows="3" wire:model="settings.plan_{{ $key }}_features"></textarea>
                        <div class="tl-muted small mt-2">One feature per line. Keep this public-facing and simple.</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="tl-panel">
            <div class="tl-eyebrow">Mail labels</div>
            <div class="row g-3 mt-1">
                <div class="col-md-6"><label class="form-label">Mail from name</label><input class="form-control" wire:model="settings.mail_from_name"></div>
                <div class="col-md-6"><label class="form-label">Mail from address</label><input class="form-control" type="email" wire:model="settings.mail_from_address"></div>
            </div>
        </div>

        @if($errors->any())<div class="text-danger small mt-3">{{ $errors->first() }}</div>@endif
        <button class="btn btn-tl mt-3" type="submit">Save settings</button>
    </form>
</div>
