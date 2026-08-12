@php
    $planKeys = ['one', 'two', 'three'];
    $plans = collect($planKeys)->map(fn (string $key) => [
        'name' => $platformSettings["plan_{$key}_name"],
        'price' => $platformSettings["plan_{$key}_price"],
        'description' => $platformSettings["plan_{$key}_description"],
        'features' => collect(preg_split('/\r\n|\r|\n/', $platformSettings["plan_{$key}_features"] ?: ''))->filter()->values(),
    ]);
@endphp
<div class="row g-3">
    @foreach($plans as $index => $plan)
        <div class="col-lg-4">
            <article class="tl-price-card {{ $index === 1 ? 'featured' : '' }}">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div><div class="tl-eyebrow">Plan {{ $index + 1 }}</div><h3 class="h4 mt-2 mb-1">{{ $plan['name'] }}</h3></div>
                    @if($index === 1)<span class="tl-badge green">Popular</span>@endif
                </div>
                <p class="tl-muted mt-3">{{ $plan['description'] }}</p>
                <div class="tl-price mt-4">{{ $plan['price'] }}</div>
                @if($platformSettings['request_access_enabled'] && $platformSettings['public_contact_enabled'])
                    <a class="btn {{ $index === 1 ? 'btn-tl' : 'btn-outline-tl' }} w-100 mt-4" href="{{ route('contact') }}">{{ $platformSettings['homepage_primary_label'] }}</a>
                @endif
                <ul class="list-unstyled vstack gap-2 mt-4 mb-0">
                    @forelse($plan['features'] as $feature)
                        <li class="d-flex gap-2"><i class="fa-solid fa-check text-success mt-1"></i><span>{{ $feature }}</span></li>
                    @empty
                        <li class="d-flex gap-2"><i class="fa-solid fa-check text-success mt-1"></i><span>Coach and athlete workspace access</span></li>
                        <li class="d-flex gap-2"><i class="fa-solid fa-check text-success mt-1"></i><span>Programs, scheduling, and progress tracking</span></li>
                    @endforelse
                </ul>
            </article>
        </div>
    @endforeach
</div>
