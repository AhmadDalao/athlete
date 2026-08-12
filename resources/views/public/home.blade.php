@extends('layouts.guest')

@section('content')
<section class="tl-public-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 tl-public-hero-copy">
                <div class="tl-eyebrow">{{ $platformSettings['homepage_eyebrow'] }}</div>
                <h1 class="mt-3">{{ $platformSettings['homepage_headline'] }} <span>{{ $platformSettings['homepage_headline_accent'] }}</span></h1>
                <p class="lead mt-4">{{ $platformSettings['homepage_subheadline'] }}</p>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    @if($platformSettings['request_access_enabled'] && $platformSettings['public_contact_enabled'])<a class="btn btn-tl btn-lg" href="{{ route('contact') }}">{{ $platformSettings['homepage_primary_label'] }} <i class="fa-solid fa-arrow-right"></i></a>@endif
                    @if($platformSettings['public_features_enabled'])<a class="btn btn-outline-tl btn-lg" href="{{ route('features') }}"><i class="fa-regular fa-circle-play"></i> {{ $platformSettings['homepage_secondary_label'] }}</a>@endif
                </div>
                <div class="tl-public-proof">
                    <span><i class="fa-solid fa-check"></i> No spreadsheet chaos</span>
                    <span><i class="fa-solid fa-check"></i> Mobile-first athlete flow</span>
                    <span><i class="fa-solid fa-check"></i> Real execution data</span>
                </div>
            </div>
            <div class="col-lg-6 tl-public-preview">
                <x-tl.product-preview />
            </div>
        </div>
    </div>
</section>

<section class="tl-logo-strip">
    <div class="container tl-logo-strip-inner">
        <span>Designed for</span>
        <strong><i class="fa-solid fa-person-running me-2"></i>Performance coaches</strong>
        <strong><i class="fa-solid fa-people-group me-2"></i>Training teams</strong>
        <strong><i class="fa-solid fa-heart-pulse me-2"></i>Independent athletes</strong>
        <strong><i class="fa-solid fa-apple-whole me-2"></i>Nutrition professionals</strong>
    </div>
</section>

@if($platformSettings['public_features_enabled'])
<section class="tl-public-section" id="features">
    <div class="container">
        <div class="tl-public-heading">
            <div class="tl-eyebrow">One operating system</div>
            <h2 class="mt-3">{{ $platformSettings['features_headline'] }}</h2>
            <p class="mt-3">{{ $platformSettings['features_subheadline'] }}</p>
        </div>
        @include('public.partials.feature-grid')
    </div>
</section>
@endif

<section class="tl-public-section is-subtle">
    <div class="container">
        <div class="row g-3">
            <div class="col-lg-6">
                <article class="tl-role-panel is-coach">
                    <div class="tl-eyebrow">For coaches</div>
                    <h2 class="mt-3">{{ $platformSettings['coach_headline'] }}</h2>
                    <p class="mt-3">{{ $platformSettings['coach_description'] }}</p>
                    <div class="tl-role-list">
                        <span><i class="fa-solid fa-check"></i> Reusable phases, sessions, and exercises</span>
                        <span><i class="fa-solid fa-check"></i> Daily schedule and adherence review</span>
                        <span><i class="fa-solid fa-check"></i> Athlete progress, photos, and private notes</span>
                        <span><i class="fa-solid fa-check"></i> Scoped access for every coach</span>
                    </div>
                </article>
            </div>
            <div class="col-lg-6">
                <article class="tl-role-panel is-athlete">
                    <div class="tl-eyebrow">For athletes</div>
                    <h2 class="mt-3">{{ $platformSettings['athlete_headline'] }}</h2>
                    <p class="mt-3">{{ $platformSettings['athlete_description'] }}</p>
                    <div class="tl-role-list">
                        <span><i class="fa-solid fa-check"></i> Calendar and assigned programs</span>
                        <span><i class="fa-solid fa-check"></i> Sets, reps, load, rest, and RPE</span>
                        <span><i class="fa-solid fa-check"></i> Videos, images, and coach cues</span>
                        <span><i class="fa-solid fa-check"></i> Progress entries and photos</span>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="tl-public-section">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5">
                <div class="tl-public-heading mb-0">
                    <div class="tl-eyebrow">How it works</div>
                    <h2 class="mt-3">A clean line from plan to proof.</h2>
                    <p class="mt-3">Throughline is opinionated on purpose. Every step should answer the next coaching question.</p>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="tl-workflow-step"><span>01</span><div><h3>Invite and connect</h3><p>Add the athlete, choose their coaches, and keep access inside the right organization.</p></div></div>
                <div class="tl-workflow-step"><span>02</span><div><h3>Build and assign</h3><p>Create a reusable program, schedule it, and adapt the dates without damaging the template.</p></div></div>
                <div class="tl-workflow-step"><span>03</span><div><h3>Execute and record</h3><p>The athlete follows the workout and records actual sets, reps, load, RPE, notes, and completion.</p></div></div>
                <div class="tl-workflow-step"><span>04</span><div><h3>Review and coach</h3><p>The coach sees adherence and progress, then makes the next decision from evidence.</p></div></div>
            </div>
        </div>
    </div>
</section>

@if($platformSettings['public_pricing_enabled'])
<section class="tl-public-section is-subtle" id="pricing">
    <div class="container">
        <div class="tl-public-heading">
            <div class="tl-eyebrow">Pricing</div>
            <h2 class="mt-3">{{ \App\Models\PlatformSetting::get('pricing_headline', 'Start with the coaching workflow you need.') }}</h2>
            <p class="mt-3">{{ \App\Models\PlatformSetting::get('pricing_subheadline', 'Pricing content is controlled by the owner from the website settings. Checkout comes after the coaching product is stable.') }}</p>
        </div>
        @include('public.partials.pricing-grid')
    </div>
</section>
@endif

<section class="tl-public-section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5">
                <div class="tl-public-heading mb-0">
                    <div class="tl-eyebrow">Questions</div>
                    <h2 class="mt-3">Clear answers before you start.</h2>
                </div>
            </div>
            <div class="col-lg-7 tl-faq">
                <details open><summary>Is Throughline for individual coaches or teams?</summary><p>Both. Organizations separate teams and permissions, while coaches can manage only the athletes and programs assigned to them.</p></details>
                <details><summary>Can one athlete work with multiple coaches?</summary><p>Yes. Athlete assignments and program assignments are separate, so strength, sport, and nutrition specialists can collaborate without sharing unnecessary access.</p></details>
                <details><summary>Does the athlete get a mobile app?</summary><p>Yes. The website experience is available now, and the Flutter app uses the same Laravel API for coach and athlete workflows.</p></details>
                <details><summary>Are wearables and payments included?</summary><p>Not in this core release. Coaching execution comes first; those integrations return after the workflow is stable and tested.</p></details>
            </div>
        </div>
    </div>
</section>

<section class="container pb-5">
    <div class="tl-final-cta">
        <div class="row align-items-end g-4">
            <div class="col-lg-8"><div class="tl-eyebrow">Ready when you are</div><h2 class="mt-3 mb-0">Make coaching simpler without making it shallow.</h2></div>
            <div class="col-lg-4 text-lg-end">@if($platformSettings['request_access_enabled'] && $platformSettings['public_contact_enabled'])<a class="btn btn-tl btn-lg" href="{{ route('contact') }}">{{ $platformSettings['homepage_primary_label'] }} <i class="fa-solid fa-arrow-right"></i></a>@endif</div>
        </div>
    </div>
</section>
@endsection
