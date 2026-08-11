@extends('layouts.guest')

@section('content')
<section class="tl-public-section">
    <div class="container">
        <div class="tl-public-heading">
            <div class="tl-eyebrow">Platform features</div>
            <h1 class="tl-public-page-title mt-3">Everything needed to coach the work.</h1>
            <p class="mt-4">From roster setup to the final set log, each module follows the same scoped, permission-aware workflow.</p>
        </div>
        @include('public.partials.feature-grid')
    </div>
</section>
<section class="container pb-5">
    <div class="tl-final-cta"><div class="row align-items-end g-4"><div class="col-lg-8"><div class="tl-eyebrow">See it with your workflow</div><h2 class="mt-3 mb-0">Tell us how your coaches work today.</h2></div><div class="col-lg-4 text-lg-end"><a class="btn btn-tl btn-lg" href="{{ route('contact') }}">Request access</a></div></div></div>
</section>
@endsection
