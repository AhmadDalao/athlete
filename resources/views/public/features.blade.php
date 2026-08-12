@extends('layouts.guest')

@section('content')
<section class="tl-public-section">
    <div class="container">
        <div class="tl-public-heading">
            <div class="tl-eyebrow">Platform features</div>
            <h1 class="tl-public-page-title mt-3">{{ $platformSettings['features_headline'] }}</h1>
            <p class="mt-4">{{ $platformSettings['features_subheadline'] }}</p>
        </div>
        @include('public.partials.feature-grid')
    </div>
</section>
<section class="container pb-5">
    <div class="tl-final-cta"><div class="row align-items-end g-4"><div class="col-lg-8"><div class="tl-eyebrow">See it with your workflow</div><h2 class="mt-3 mb-0">Tell us how your coaches work today.</h2></div><div class="col-lg-4 text-lg-end">@if($platformSettings['request_access_enabled'] && $platformSettings['public_contact_enabled'])<a class="btn btn-tl btn-lg" href="{{ route('contact') }}">{{ $platformSettings['homepage_primary_label'] }}</a>@endif</div></div></div>
</section>
@endsection
