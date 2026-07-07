<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="tl-panel">
                <div class="tl-eyebrow">Contact</div>
                <h1 class="h2">Tell us what you need.</h1>
                <p class="tl-muted">Questions, coaching setup, or platform access. This saves directly into the admin database.</p>

                @if (session('status'))
                    <div class="alert alert-success border-0">{{ session('status') }}</div>
                @endif

                <form class="row g-3" wire:submit.prevent="submit">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input class="form-control form-control-lg" wire:model="name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input class="form-control form-control-lg" type="email" wire:model="email">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Phone</label>
                        <input class="form-control form-control-lg" wire:model="phone">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Message</label>
                        <textarea class="form-control form-control-lg" rows="5" wire:model="message"></textarea>
                    </div>
                    @if($errors->any())
                        <div class="col-12 text-danger small">{{ $errors->first() }}</div>
                    @endif
                    <div class="col-12">
                        <button class="btn btn-tl btn-lg" type="submit">Send message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
