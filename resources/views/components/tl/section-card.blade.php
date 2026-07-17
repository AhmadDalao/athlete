@props([
    'eyebrow' => null,
    'title' => null,
    'subtitle' => null,
    'actionHref' => null,
    'actionLabel' => null,
    'icon' => null,
])

<section {{ $attributes->class('tl-section-card') }}>
    <div class="tl-section-head">
        <div>
            @if($eyebrow)
                <div class="tl-eyebrow">{{ $eyebrow }}</div>
            @endif
            @if($title)
                <h3>{{ $title }}</h3>
            @endif
            @if($subtitle)
                <p>{{ $subtitle }}</p>
            @endif
        </div>

        @if($actionHref && $actionLabel)
            <a class="btn btn-outline-tl btn-sm" href="{{ $actionHref }}">
                @if($icon)<i class="{{ $icon }}"></i>@endif
                {{ $actionLabel }}
            </a>
        @endif
    </div>

    {{ $slot }}
</section>
