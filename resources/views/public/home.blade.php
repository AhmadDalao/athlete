@extends('layouts.guest')

@section('content')
@php
    $planKeys = ['one', 'two', 'three'];
    $plans = collect($planKeys)->map(fn (string $key) => [
        'name' => \App\Models\PlatformSetting::get("plan_{$key}_name", str($key)->headline()),
        'price' => \App\Models\PlatformSetting::get("plan_{$key}_price", 'Contact for pricing'),
        'description' => \App\Models\PlatformSetting::get("plan_{$key}_description", ''),
        'features' => collect(preg_split('/\r\n|\r|\n/', \App\Models\PlatformSetting::get("plan_{$key}_features", '') ?: ''))->filter()->values(),
    ]);
@endphp
<section class="container py-5">
    <div class="tl-hero">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <div class="tl-eyebrow">Website-first coaching platform</div>
                <h1 class="display-4 fw-bold mt-3">{{ \App\Models\PlatformSetting::get('homepage_headline', 'Training, coaching, and progress tracking without the mess.') }}</h1>
                <p class="lead tl-muted mt-3">{{ \App\Models\PlatformSetting::get('homepage_subheadline', 'A direct platform for coaches to manage athletes, assign programs, and track real execution.') }}</p>
                <div class="d-flex gap-2 flex-wrap mt-4">
                    <a class="btn btn-tl btn-lg" href="{{ route('login') }}">Login</a>
                    <a class="btn btn-outline-tl btn-lg" href="{{ route('contact') }}">Contact us</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="tl-panel mb-0">
                    <div class="tl-eyebrow">MVP focus</div>
                    <div class="row g-3 mt-1">
                        <div class="col-6"><div class="tl-stat"><i class="fa-solid fa-user-tie text-warning"></i><strong>Coach</strong><span class="tl-muted">Invites, roster, programs</span></div></div>
                        <div class="col-6"><div class="tl-stat"><i class="fa-solid fa-person-running text-success"></i><strong>Athlete</strong><span class="tl-muted">Calendar, workouts, logs</span></div></div>
                        <div class="col-12"><div class="tl-stat"><i class="fa-solid fa-table text-info"></i><strong>Admin tables</strong><span class="tl-muted">Permissions, settings, tracking, and exports</span></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container pb-5">
    <div class="row align-items-end g-3 mb-3">
        <div class="col-lg-7">
            <div class="tl-eyebrow">Memberships</div>
            <h2 class="display-6 fw-bold mt-2">{{ \App\Models\PlatformSetting::get('pricing_headline', 'Simple plans for real coaching.') }}</h2>
            <p class="tl-muted mb-0">{{ \App\Models\PlatformSetting::get('pricing_subheadline', 'Start with the workflow you need now.') }}</p>
        </div>
        <div class="col-lg-5 text-lg-end">
            <a class="btn btn-tl btn-lg" href="{{ route('contact') }}">Request access</a>
        </div>
    </div>
    <div class="row g-3">
        @foreach($plans as $index => $plan)
            <div class="col-lg-4">
                <div class="tl-price-card h-100 {{ $index === 1 ? 'featured' : '' }}">
                    <div class="d-flex justify-content-between gap-3 align-items-start mb-4">
                        <div>
                            <div class="tl-eyebrow">Plan {{ $index + 1 }}</div>
                            <h3 class="h4 fw-bold mt-2 mb-1">{{ $plan['name'] }}</h3>
                            <p class="tl-muted mb-0">{{ $plan['description'] }}</p>
                        </div>
                        @if($index === 1)<span class="tl-badge gold">Popular</span>@endif
                    </div>
                    <div class="tl-price">{{ $plan['price'] }}</div>
                    <ul class="list-unstyled vstack gap-2 mt-4 mb-0">
                        @forelse($plan['features'] as $feature)
                            <li class="d-flex gap-2"><i class="fa-solid fa-check text-success mt-1"></i><span>{{ $feature }}</span></li>
                        @empty
                            <li class="tl-muted">No public features configured yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection
