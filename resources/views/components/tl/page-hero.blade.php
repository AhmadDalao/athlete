@props([
    'eyebrow' => null,
    'title' => null,
    'subtitle' => null,
    'heading' => 'h2',
    'tone' => 'default',
])

<section {{ $attributes->class(['tl-page-hero', 'tl-page-hero-'.$tone]) }}>
    <div class="tl-page-hero-copy">
        @if($eyebrow)
            <div class="tl-eyebrow">{{ $eyebrow }}</div>
        @endif

        @if($title && $heading === 'h1')
            <h1>{{ $title }}</h1>
        @elseif($title)
            <h2>{{ $title }}</h2>
        @endif

        @if($subtitle)
            <p>{{ $subtitle }}</p>
        @endif

        @isset($actions)
            <div class="tl-hero-actions">
                {{ $actions }}
            </div>
        @endisset
    </div>

    @isset($visual)
        <div class="tl-page-hero-visual">
            {{ $visual }}
        </div>
    @endisset
</section>
