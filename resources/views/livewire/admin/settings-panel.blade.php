<div>
    <x-tl.page-hero
        eyebrow="Platform control"
        title="Website control"
        subtitle="Branding, public content, visibility, invitations, and safe sender identity live here. Infrastructure secrets stay in the environment."
    >
        <x-slot:actions>
            <a class="btn btn-outline-tl" href="{{ route('home') }}" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Preview website</a>
            <button class="btn btn-tl" type="button" wire:click="save" wire:loading.attr="disabled"><i class="fa-solid fa-floppy-disk"></i> Save all</button>
        </x-slot:actions>
    </x-tl.page-hero>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-tl.metric-card icon="fa-solid fa-palette" label="Default theme" :value="str($settings['default_theme'])->headline()" tone="lime" /></div>
        <div class="col-6 col-xl-3"><x-tl.metric-card icon="fa-solid fa-tags" label="Pricing page" :value="$settings['public_pricing_enabled'] ? 'Visible' : 'Hidden'" tone="gold" /></div>
        <div class="col-6 col-xl-3"><x-tl.metric-card icon="fa-solid fa-paper-plane" label="Invitations" :value="$settings['invitations_enabled'] ? 'Enabled' : 'Paused'" tone="cyan" /></div>
        <div class="col-6 col-xl-3"><x-tl.metric-card icon="fa-solid fa-envelope" label="Sender" :value="$settings['mail_from_address'] ?: 'Environment default'" tone="emerald" /></div>
    </div>

    <form wire:submit.prevent="save">
        <div class="accordion tl-settings-accordion" id="websiteControlSections">
            <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#brandingControl"><i class="fa-solid fa-fingerprint me-3"></i><span><strong>Branding and identity</strong><small>Logo, product name, contact details, links, and default appearance.</small></span></button></h2>
                <div class="accordion-collapse collapse show" id="brandingControl" data-bs-parent="#websiteControlSections">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-lg-8">
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="form-label">Product name</label><input class="form-control" wire:model="settings.app_name"></div>
                                    <div class="col-md-6"><label class="form-label">Tagline</label><input class="form-control" wire:model="settings.tagline"></div>
                                    <div class="col-md-6"><label class="form-label">Support email</label><input class="form-control" type="email" wire:model="settings.support_email"></div>
                                    <div class="col-md-6"><label class="form-label">Support phone</label><input class="form-control" wire:model="settings.support_phone" placeholder="Optional"></div>
                                    <div class="col-12"><label class="form-label">Contact address</label><input class="form-control" wire:model="settings.contact_address" placeholder="Optional public address"></div>
                                    <div class="col-12"><label class="form-label">Footer description</label><textarea class="form-control" rows="2" wire:model="settings.footer_copy"></textarea></div>
                                    <div class="col-md-6"><label class="form-label">Terms URL</label><input class="form-control" type="url" wire:model="settings.terms_url" placeholder="https://..."></div>
                                    <div class="col-md-6"><label class="form-label">Privacy URL</label><input class="form-control" type="url" wire:model="settings.privacy_url" placeholder="https://..."></div>
                                    <div class="col-md-5"><label class="form-label">Guest default theme</label><select class="form-select" wire:model="settings.default_theme"><option value="system">Follow device</option><option value="dark">Dark</option><option value="light">Light</option></select></div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="tl-settings-upload">
                                    <span class="tl-eyebrow">Website logo</span>
                                    <div class="tl-settings-logo-preview">
                                        @if($logo)
                                            <img src="{{ $logo->temporaryUrl() }}" alt="New logo preview">
                                        @elseif($settings['logo_path'])
                                            <img src="{{ Storage::disk('public')->url($settings['logo_path']) }}" alt="Current logo">
                                        @else
                                            <i class="fa-solid fa-bezier-curve"></i><span>Throughline mark</span>
                                        @endif
                                    </div>
                                    <input class="form-control" type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp">
                                    <small class="tl-muted">PNG, JPG, or WEBP. Maximum 2 MB.</small>
                                    @if($settings['logo_path'])<button class="btn btn-sm btn-outline-danger mt-2" type="button" wire:click="removeLogo">Remove logo</button>@endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#homepageControl"><i class="fa-solid fa-browser me-3"></i><span><strong>Homepage content</strong><small>Hero copy, role messaging, feature introduction, and CTA labels.</small></span></button></h2>
                <div class="accordion-collapse collapse" id="homepageControl" data-bs-parent="#websiteControlSections">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Hero eyebrow</label><input class="form-control" wire:model="settings.homepage_eyebrow"></div>
                            <div class="col-md-4"><label class="form-label">Hero headline</label><input class="form-control" wire:model="settings.homepage_headline"></div>
                            <div class="col-md-4"><label class="form-label">Headline accent</label><input class="form-control" wire:model="settings.homepage_headline_accent"></div>
                            <div class="col-12"><label class="form-label">Hero description</label><textarea class="form-control" rows="3" wire:model="settings.homepage_subheadline"></textarea></div>
                            <div class="col-md-6"><label class="form-label">Primary CTA</label><input class="form-control" wire:model="settings.homepage_primary_label"></div>
                            <div class="col-md-6"><label class="form-label">Secondary CTA</label><input class="form-control" wire:model="settings.homepage_secondary_label"></div>
                            <div class="col-md-6"><label class="form-label">Features headline</label><input class="form-control" wire:model="settings.features_headline"></div>
                            <div class="col-md-6"><label class="form-label">Features description</label><input class="form-control" wire:model="settings.features_subheadline"></div>
                            <div class="col-md-6"><label class="form-label">Coach headline</label><input class="form-control" wire:model="settings.coach_headline"></div>
                            <div class="col-md-6"><label class="form-label">Athlete headline</label><input class="form-control" wire:model="settings.athlete_headline"></div>
                            <div class="col-md-6"><label class="form-label">Coach description</label><textarea class="form-control" rows="3" wire:model="settings.coach_description"></textarea></div>
                            <div class="col-md-6"><label class="form-label">Athlete description</label><textarea class="form-control" rows="3" wire:model="settings.athlete_description"></textarea></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#visibilityControl"><i class="fa-solid fa-eye me-3"></i><span><strong>Feature visibility</strong><small>Hide unfinished public surfaces and pause new invitations without deleting data.</small></span></button></h2>
                <div class="accordion-collapse collapse" id="visibilityControl" data-bs-parent="#websiteControlSections">
                    <div class="accordion-body">
                        <div class="row g-3">
                            @foreach([
                                'public_features_enabled' => ['Features page', 'Show product features in public navigation and homepage.'],
                                'public_pricing_enabled' => ['Pricing page', 'Show editable plans and pricing content.'],
                                'public_contact_enabled' => ['Contact form', 'Accept and store new public contact submissions.'],
                                'request_access_enabled' => ['Request access CTA', 'Show access buttons that lead to the contact form.'],
                                'invitations_enabled' => ['Athlete invitations', 'Allow coaches to create and resend athlete invites.'],
                            ] as $key => [$label, $help])
                                <div class="col-md-6">
                                    <label class="tl-setting-toggle">
                                        <span><strong>{{ $label }}</strong><small>{{ $help }}</small></span>
                                        <span class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" wire:model="settings.{{ $key }}"></span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pricingControl"><i class="fa-solid fa-tags me-3"></i><span><strong>Pricing content</strong><small>Public display only. Billing automation is intentionally outside this release.</small></span></button></h2>
                <div class="accordion-collapse collapse" id="pricingControl" data-bs-parent="#websiteControlSections">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-md-5"><label class="form-label">Pricing headline</label><input class="form-control" wire:model="settings.pricing_headline"></div>
                            <div class="col-md-7"><label class="form-label">Pricing description</label><input class="form-control" wire:model="settings.pricing_subheadline"></div>
                            @foreach(['one' => 'Plan 1', 'two' => 'Plan 2', 'three' => 'Plan 3'] as $key => $label)
                                <div class="col-12"><div class="tl-settings-divider"><span>{{ $label }}</span></div></div>
                                <div class="col-md-3"><label class="form-label">Name</label><input class="form-control" wire:model="settings.plan_{{ $key }}_name"></div>
                                <div class="col-md-3"><label class="form-label">Price</label><input class="form-control" wire:model="settings.plan_{{ $key }}_price"></div>
                                <div class="col-md-6"><label class="form-label">Description</label><input class="form-control" wire:model="settings.plan_{{ $key }}_description"></div>
                                <div class="col-12"><label class="form-label">Features, one per line</label><textarea class="form-control" rows="3" wire:model="settings.plan_{{ $key }}_features"></textarea></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#communicationControl"><i class="fa-solid fa-envelope-open-text me-3"></i><span><strong>Contact, invitations, and email</strong><small>Editable message content and safe sender labels. SMTP credentials remain protected in .env.</small></span></button></h2>
                <div class="accordion-collapse collapse" id="communicationControl" data-bs-parent="#websiteControlSections">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Contact headline</label><input class="form-control" wire:model="settings.contact_headline"></div>
                            <div class="col-md-5"><label class="form-label">Contact description</label><input class="form-control" wire:model="settings.contact_subheadline"></div>
                            <div class="col-md-3"><label class="form-label">Submit label</label><input class="form-control" wire:model="settings.contact_button_label"></div>
                            <div class="col-md-4"><label class="form-label">Invite expiry days</label><input class="form-control" type="number" min="1" max="60" wire:model="settings.invite_expiry_days"></div>
                            <div class="col-md-8"><label class="form-label">Invitation subject</label><input class="form-control" wire:model="settings.invite_email_subject"></div>
                            <div class="col-12"><label class="form-label">Invitation body</label><textarea class="form-control" rows="6" wire:model="settings.invite_email_body"></textarea><small class="tl-muted">Tokens: {app_name}, {coach_name}, {athlete_name}, {invite_link}, {expires_at}</small></div>
                            <div class="col-md-6"><label class="form-label">Sender name</label><input class="form-control" wire:model="settings.mail_from_name"></div>
                            <div class="col-md-6"><label class="form-label">Sender address</label><input class="form-control" type="email" wire:model="settings.mail_from_address"></div>
                            <div class="col-12"><div class="alert alert-info mb-0"><i class="fa-solid fa-lock me-2"></i>Mail host, port, username, password, and encryption are server secrets. Configure them in Hostinger environment variables, never in this database.</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($errors->any())<div class="alert alert-danger mt-3"><i class="fa-solid fa-triangle-exclamation me-2"></i>{{ $errors->first() }}</div>@endif
        <div class="tl-settings-savebar"><span><i class="fa-solid fa-circle-info"></i> Changes apply to the public website and future invitations after saving.</span><button class="btn btn-tl" type="submit" wire:loading.attr="disabled"><i class="fa-solid fa-floppy-disk"></i> Save all settings</button></div>
    </form>
</div>
