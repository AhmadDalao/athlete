@extends('layouts.guest')

@section('content')
<section class="tl-auth-shell">
    <div class="tl-auth-story">
        <div class="tl-eyebrow">Account recovery</div>
        <h1 class="mt-3">Get back to the work.</h1>
        <p class="mt-4">Enter your account email. If it matches an active Throughline account, we will send a secure reset link.</p>
    </div>
    <div class="tl-auth-form-wrap">
        <div class="tl-auth-card">
            <x-tl.brand :href="route('home')" class="mb-5" />
            <div class="tl-eyebrow">Password reset</div>
            <h2 class="display-6 fw-bold mt-2">Request a reset link</h2>
            <p class="tl-muted mb-4">The link expires automatically and can only be used once.</p>
            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif
            <form method="POST" action="{{ route('password.email') }}" class="vstack gap-3">
                @csrf
                <div>
                    <label class="form-label" for="email">Email address</label>
                    <input class="form-control form-control-lg" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                    @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-tl btn-lg w-100" type="submit">Send reset link <i class="fa-solid fa-paper-plane"></i></button>
            </form>
            <p class="tl-muted small text-center mt-4 mb-0"><a class="text-decoration-underline" href="{{ route('login') }}">Back to login</a></p>
        </div>
    </div>
</section>
@endsection
