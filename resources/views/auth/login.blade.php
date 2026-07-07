@extends('layouts.guest')

@section('content')
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="tl-panel">
                <div class="tl-eyebrow">Login</div>
                <h1 class="h2 mb-3">Enter Throughline</h1>
                <form method="POST" action="{{ route('login.store') }}" class="vstack gap-3">
                    @csrf
                    <div>
                        <label class="form-label">Email</label>
                        <input class="form-control form-control-lg" name="email" type="email" value="{{ old('email') }}" required autofocus>
                        @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="form-label">Password</label>
                        <input class="form-control form-control-lg" name="password" type="password" required>
                    </div>
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" value="1">
                        <span class="form-check-label">Remember me</span>
                    </label>
                    <button class="btn btn-tl btn-lg" type="submit">Login</button>
                </form>
                <div class="tl-panel mt-4 mb-0">
                    <div class="tl-eyebrow">Seed accounts</div>
                    <p class="mb-1">Owner: <code>owner@throughline.test</code></p>
                    <p class="mb-1">Coach: <code>coach@throughline.test</code></p>
                    <p class="mb-0">Athlete: <code>athlete@throughline.test</code></p>
                    <small class="tl-muted">Password for seeded accounts: <code>password</code></small>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
