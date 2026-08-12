@extends('layouts.guest')

@section('content')
<section class="tl-public-section">
    <div class="container">
        <div class="tl-public-heading">
            <div class="tl-eyebrow">Pricing</div>
            <h1 class="tl-public-page-title mt-3">{{ $platformSettings['pricing_headline'] }}</h1>
            <p class="mt-4">{{ $platformSettings['pricing_subheadline'] }}</p>
        </div>
        @include('public.partials.pricing-grid')
    </div>
</section>
@endsection
