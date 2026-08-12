@extends('layouts.guest')

@section('content')
<section class="tl-auth-shell">
    <div class="tl-auth-story">
        <div class="tl-eyebrow">Welcome back</div>
        <h1 class="mt-3">Your next decision starts here.</h1>
        <p class="mt-4">Coaches open their roster. Athletes open today’s training. Owners open operations. No one lands in the wrong product.</p>
        <div class="tl-public-proof">
            <span><i class="fa-solid fa-shield-halved"></i> Scoped organization access</span>
            <span><i class="fa-solid fa-mobile-screen"></i> Mobile-first training</span>
        </div>
    </div>
    <div class="tl-auth-form-wrap">
        <div class="tl-auth-card">
            <x-tl.brand :href="route('home')" class="mb-5" />
            <div class="tl-eyebrow">Secure access</div>
            <h2 class="display-6 fw-bold mt-2">Log in to Throughline</h2>
            <p class="tl-muted mb-4">Use the account provided by your organization.</p>
            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif
            <form method="POST" action="{{ route('login.store') }}" class="vstack gap-3">
                @csrf
                <div>
                    <label class="form-label" for="email">Email address</label>
                    <input class="form-control form-control-lg" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                    @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control form-control-lg" id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" value="1">
                    <span class="form-check-label">Keep me signed in on this device</span>
                </label>
                <div class="text-end">
                    <a class="small text-decoration-underline" href="{{ route('password.request') }}">Forgot your password?</a>
                </div>
                <button class="btn btn-tl btn-lg w-100" type="submit">Log in <i class="fa-solid fa-arrow-right"></i></button>
            </form>
            @if($platformSettings['request_access_enabled'] && $platformSettings['public_contact_enabled'])
                <p class="tl-muted small text-center mt-4 mb-0">Need an account? <a class="text-decoration-underline" href="{{ route('contact') }}">Request access</a>.</p>
            @endif
        </div>
    </div>
</section>
@endsection
