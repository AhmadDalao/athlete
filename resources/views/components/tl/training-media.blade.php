@props([
    'media',
    'title' => 'Training media',
    'compact' => false,
])

@if(($media['type'] ?? 'none') !== 'none')
    <div {{ $attributes->class(['tl-media-panel', 'is-compact' => $compact]) }}>
        @if($media['type'] === 'image')
            <img src="{{ $media['url'] }}" alt="{{ $title }}" loading="lazy">
        @elseif($media['type'] === 'video')
            <video src="{{ $media['url'] }}" controls playsinline preload="metadata"></video>
        @elseif($media['type'] === 'embed' && $media['embedUrl'])
            <iframe
                src="{{ $media['embedUrl'] }}"
                title="{{ $title }}"
                loading="lazy"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
            ></iframe>
        @else
            <div class="tl-media-link">
                <i class="fa-solid fa-up-right-from-square"></i>
                <div>
                    <strong>{{ $title }}</strong>
                    <p class="tl-muted mb-0">Open this coach resource in a new tab.</p>
                </div>
                <a class="btn btn-tl ms-auto" href="{{ $media['url'] }}" target="_blank" rel="noopener">Open</a>
            </div>
        @endif
    </div>
@endif
