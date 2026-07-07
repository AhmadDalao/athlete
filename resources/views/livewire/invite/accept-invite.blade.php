<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="tl-panel">
                <div class="tl-eyebrow">Coach invite</div>
                <h1 class="h2">Join Throughline</h1>
                <p class="tl-muted">Coach {{ $invitation->coach->name }} invited you to join their roster.</p>
                <form wire:submit.prevent="accept" class="vstack gap-3">
                    <div><label class="form-label">Name</label><input class="form-control form-control-lg" wire:model="name"></div>
                    <div><label class="form-label">Email</label><input class="form-control form-control-lg" type="email" wire:model="email"></div>
                    <div><label class="form-label">Password</label><input class="form-control form-control-lg" type="password" wire:model="password"></div>
                    @if($errors->any())<div class="text-danger small">{{ $errors->first() }}</div>@endif
                    <button class="btn btn-tl btn-lg" type="submit">Accept invite</button>
                </form>
            </div>
        </div>
    </div>
</section>
