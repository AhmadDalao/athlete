@extends('layouts.guest')

@section('content')
<section class="tl-auth-shell">
    <div class="tl-auth-story">
        <div class="tl-eyebrow">Secure account</div>
        <h1 class="mt-3">Choose a new password.</h1>
        <p class="mt-4">After the reset, every existing mobile session is signed out. That is deliberate, not a bug.</p>
    </div>
    <div class="tl-auth-form-wrap">
        <div class="tl-auth-card">
            <x-tl.brand :href="route('home')" class="mb-5" />
            <div class="tl-eyebrow">Password reset</div>
            <h2 class="display-6 fw-bold mt-2">Set your new password</h2>
            <form method="POST" action="{{ route('password.update') }}" class="vstack gap-3 mt-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label class="form-label" for="email">Email address</label>
                    <input class="form-control form-control-lg" id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required>
                    @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="form-label" for="password">New password</label>
                    <input class="form-control form-control-lg" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                    @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="form-label" for="password_confirmation">Confirm new password</label>
                    <input class="form-control form-control-lg" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                </div>
                <button class="btn btn-tl btn-lg w-100" type="submit">Update password <i class="fa-solid fa-shield-halved"></i></button>
            </form>
        </div>
    </div>
</section>
@endsection
