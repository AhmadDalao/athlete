@props([
    'eyebrow' => null,
    'title' => null,
    'subtitle' => null,
    'count' => null,
    'actionHref' => null,
    'actionLabel' => null,
    'icon' => 'fa-solid fa-table-list',
])

<section {{ $attributes->class('tl-table-card') }}>
    <div class="tl-table-card-head">
        <div class="d-flex align-items-start gap-3">
            <span class="tl-table-card-icon"><i class="{{ $icon }}"></i></span>
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
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if(! is_null($count))
                <span class="tl-badge gray">{{ $count }} records</span>
            @endif
            @if($actionHref && $actionLabel)
                <a class="btn btn-outline-tl btn-sm" href="{{ $actionHref }}">
                    <i class="fa-solid fa-download me-1"></i>{{ $actionLabel }}
                </a>
            @endif
        </div>
    </div>

    {{ $slot }}
</section>
