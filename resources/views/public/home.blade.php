@extends('layouts.guest')

@section('content')
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
                        <div class="col-12"><div class="tl-stat"><i class="fa-solid fa-table text-info"></i><strong>Admin tables</strong><span class="tl-muted">Permissions, settings, tracking, exports later</span></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
