@props([
    'href' => route('home'),
    'compact' => false,
    'inverse' => false,
    'showTagline' => true,
])

<a {{ $attributes->class(['tl-brand', 'is-compact' => $compact, 'is-inverse' => $inverse]) }} href="{{ $href }}" aria-label="Throughline home">
    @if($platformSettings['logo_path'] ?? null)
        <span class="tl-brand-mark is-uploaded" aria-hidden="true"><img src="{{ asset('storage/'.$platformSettings['logo_path']) }}" alt=""></span>
    @else
        <span class="tl-brand-mark" aria-hidden="true">
            <svg viewBox="0 0 48 48" role="img">
                <path d="M12 8v25l12 7 12-7V15l-8-5v21l-4 2.4L20 31V16l-8-5" />
                <path d="M12 21l8 5m8-7 8 5M20 16l8-5" />
            </svg>
        </span>
    @endif
    <span class="tl-brand-copy">
        <strong>{{ $platformSettings['app_name'] ?? 'Throughline' }}</strong>
        @if($showTagline)
            <small>{{ $platformSettings['tagline'] ?? 'Coaching, connected.' }}</small>
        @endif
    </span>
</a>
