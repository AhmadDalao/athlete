@extends('layouts.guest')

@section('content')
<section class="tl-public-section">
    <div class="container">
        <div class="tl-public-heading">
            <div class="tl-eyebrow">Pricing</div>
            <h1 class="tl-public-page-title mt-3">Simple plans. No fake complexity.</h1>
            <p class="mt-4">Pricing is managed by the owner and reflects the coaching workflow, not a maze of technical add-ons.</p>
        </div>
        @include('public.partials.pricing-grid')
    </div>
</section>
@endsection
